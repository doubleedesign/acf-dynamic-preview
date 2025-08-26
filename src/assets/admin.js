/**
 * global acf
 */

jQuery(document).ready(function($) {
	// Initialise the custom buttons' event listeners on page load
	initButtons($);

	// When a new module is added to the page
	acf.addAction('append', function(maybeModule) {
		// 'append' could also hit repeaters and such, so this is a proxy for "is this a flexible module?"
		// Reinitialise button listeners - this probably isn't the most efficient, but it's fine for now
		if(maybeModule[0].classList.contains('layout')) {
			initButtons($);
		}
		// Default to edit mode
		maybeModule[0].dataset.mode = 'edit';
	});

	// If WYSIWYG field (e.g., my "copy" module) is already on the page but is empty,
	// default it to edit mode
	acf.addAction('load_field/type=wysiwyg', function(field) {
		const moduleArea = field.$el.closest('.layout')[0];
		if(moduleArea && !field.val()) {
			moduleArea.dataset.mode = 'edit';
		}
	});

});

function initButtons($) {
	$('[data-action="toggle-render-mode"]').on('click', function(e) {
		e.preventDefault();

		const isPreviewButton = e.currentTarget.dataset.toggleValue === 'preview';
		const isEditButton = e.currentTarget.dataset.toggleValue === 'edit';
		const moduleArea = e.currentTarget.closest('.layout');
		const previewArea = moduleArea.querySelector('.acf-dynamic-fc-preview__content');

		if(isPreviewButton) {
			// Get all the field values within the current module area without submitting the form
			const inputs = moduleArea.querySelectorAll('input, select, textarea');
			const data = merge_sub_field_data(inputs);

			$.ajax({
				url: ajaxurl, // WordPress AJAX URL
				type: 'POST',
				data: {
					// This action name must match the PHP action hook, without the wp_ajax_ prefix
					headers: {
						'Content-Type': 'application/json',
						'X-Requested-With': 'XMLHttpRequest'
					},
					action: 'fetch_module_preview',
					nonce: dynamic_rendering.nonce,
					body: JSON.stringify({
						module_name: e.currentTarget.dataset.moduleName,
						fields: data
					})
				},
				success: function(response) {
					if(response.success && response.data.html) {
						// Replace the preview area's content with the returned HTML
						previewArea.innerHTML = response.data.html;
						// Remove error messages if any
						const existingErrors = previewArea.querySelectorAll('.acf-notice');
						existingErrors.forEach(error => error.remove());
					}
					else if(response.data.error) {
						console.error(response.data.error);
						// Clear previous errors
						const existingErrors = previewArea.querySelectorAll('.acf-notice');
						existingErrors.forEach(error => error.remove());
						// Insert an error message above the preview area, so the preview of saved data stays there
						const message = previewArea.appendChild(document.createElement('div'));
						message.classList.add('acf-notice');
						message.innerText = response.data.error;
					}
				},
				error: function(error) {
					console.error(error);
				}
			});

			// Then switch to preview mode
			moduleArea.dataset.mode = 'preview';
		}
		if(isEditButton) {
			moduleArea.dataset.mode = 'edit';
			moduleArea.classList.remove('-collapsed');
		}
	});
}

function merge_sub_field_data(inputs) {
	const initial = [];

	// Note: inputs are a NodeList, not an array, so .map and such don't work directly
	inputs.forEach(input => {
		if(input.name) { // make sure we only process actual inputs
			const obj = process_input_key_and_value(input.name, input.value);
			// Check if object is actually an object before proceeding
			if(obj && typeof obj === 'object' && !Array.isArray(obj)) {
				initial.push(obj);
			}
		}
	});

	return window.lodash.mergeWith({}, ...initial, (objValue, srcValue) => {
		// If both values are objects, merge them
		// If one is a primitive and one is an object, keep the object
		if (window.lodash.isObject(objValue) && window.lodash.isObject(srcValue)) {
			return window.lodash.merge(objValue, srcValue);
		}
		// Otherwise, use the source value (last one wins for primitives)
		return srcValue;
	});
}

function process_input_key_and_value(key, value = null) {
	// The key needs to be turned into the object structure, so split by brackets
	const keys = key.split(/[\[\]]+/).filter(key => key !== '');

	// Filter some out to drill down to a useful level
	const filteredKeys = keys.filter(k => {
		return k !== 'acf'
			&& !k.startsWith('row')
			&& !['acf_fc_layout_custom_label', 'acf_fc_layout_disabled', 'acf_fc_layout'].includes(k);
	})
		// Slice off the first one, which is expected to be the overall flexible content field key after the initial filter
	.slice(1, 10);

	if(filteredKeys.length === 1) {
		return { [filteredKeys[0]]: value };
	}

	return build_nested_object(filteredKeys, value);
}

function build_nested_object(keys, value) {
	if (keys.length === 0) {
		return value;
	}

	const key = keys[0];
	const isArray = !isNaN(keys[1]); // Check if the next key is a number, indicating an array

	if (isArray) {
		const arr = [];
		const index = parseInt(keys[1], 10);
		arr[index] = build_nested_object(keys.slice(2), value);
		return { [key]: arr };
	} else {
		return { [key]: build_nested_object(keys.slice(1), value) };
	}
}
