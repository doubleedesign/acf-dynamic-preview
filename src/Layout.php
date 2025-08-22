<?php
namespace Doubleedesign\ACFDynamicPreview;
use Exception;

/**
 * This is a copy of ACF\Pro\Fields\FlexibleContent\Layout with some extra stuff added.
 * This is for two reasons:
 * 	1. So we can use class_alias to swap out the original ACF class with this one,
 * 	   simplifying adding our stuff in the absence of suitable hooks to insert what we need
 *  2. Almost everything in the original class is private, so extending it would involve copying a lot of stuff anyway
 * 	   (class_alias doesn't allow us to extend the class we're aliasing...but that's ok in this case)
 */
class Layout {
    /**
     * The Flexible Content field the layout belongs to.
     *
     * @var array
     */
    private $field;

    /**
     * The layout being rendered.
     *
     * @var array
     */
    private $layout;

    /**
     * The order of the layout.
     *
     * @var int|string
     */
    private $order;

    /**
     * The value of the layout.
     *
     * @var mixed
     */
    private $value;

    /**
     * The input prefix.
     *
     * @var string
     */
    private $prefix;

    /**
     * If the layout is disabled.
     *
     * @var bool
     */
    private $disabled;

    /**
     * If the layout has been renamed, the new name of the layout.
     *
     * @var string
     */
    private $renamed;

    /**
     * Constructs the class.
     *
     * @since 6.5
     *
     * @param  array  $field  The Flexible Content field the layout belongs to.
     * @param  array  $layout  The layout to render.
     * @param  int|string  $order  The order of the layout.
     * @param  mixed  $value  The value of the layout.
     * @param  bool  $disabled  If the layout is disabled.
     * @param  string  $renamed  If the layout has been renamed, the new name of the layout.
     */
    public function __construct($field, $layout, $order, $value, $disabled = false, $renamed = '') {
        $this->field = $field;
        $this->layout = $layout;
        $this->order = $order;
        $this->value = $value;
        $this->disabled = $disabled;
        $this->renamed = $renamed;

        add_action('acf/render_field/type=flexible_content', [$this, 'enqueue_module_assets'], 8);
    }

    /**
     * Renders the layout.
     *
     * @since 6.5
     *
     * @return void
     */
    public function render(): void {
	    $id = 'row-' . $this->order;
	    $class = 'layout';

	    if ($this->order === 'acfcloneindex') {
		    $id = 'acfcloneindex';
		    $class .= ' acf-clone';
	    }

	    $this->prefix = $this->field['name'] . '[' . $id . ']';

	    $div_attrs = array(
		    'class'        => $class,
		    'data-id'      => $id,
		    'data-layout'  => $this->layout['name'],
		    'data-label'   => $this->layout['label'],
		    'data-min'     => $this->layout['min'],
		    'data-max'     => $this->layout['max'],
		    'data-enabled' => $this->disabled ? 0 : 1,
		    'data-renamed' => empty($this->renamed) ? 0 : 1,
	    );

	    echo '<div ' . acf_esc_attrs($div_attrs) . '>';
			// TODO: Can we avoid having all of these in the DOM at once and only render what's visible?
			// Note: Unsaved data is a challenge for that.
			$this->render_form();
	   		$this->render_preview($this->layout['name']);
        echo '</div>'; // End layout wrapper div.
    }

	private function render_form(): void {
		acf_hidden_input(
			array(
				'name'  => $this->prefix . '[acf_fc_layout]',
				'value' => $this->layout['name'],
			)
		);

		acf_hidden_input(
			array(
				'class' => 'acf-fc-layout-disabled',
				'name'  => $this->prefix . '[acf_fc_layout_disabled]',
				'value' => $this->disabled ? 1 : 0,
			)
		);

		acf_hidden_input(
			array(
				'class' => 'acf-fc-layout-custom-label',
				'name'  => $this->prefix . '[acf_fc_layout_custom_label]',
				'value' => $this->renamed,
			)
		);

		$this->action_buttons();

		if (!empty($this->layout['sub_fields'])) {
			if ($this->layout['display'] === 'table') {
				$this->render_as_table();
			}
			else {
				$this->render_as_div();
			}
		}
	}

    /**
     * Renders a layout as a table.
     *
     * @since 6.5
     *
     * @return void
     */
    private function render_as_table(): void {
        $sub_fields = $this->layout['sub_fields'];
        ?>
		<table class="acf-table">
			<thead>
			<tr>
				<?php
                foreach ($sub_fields as $sub_field) {
                    // Set prefix to generate correct "for" attribute on <label>.
                    $sub_field['prefix'] = $this->prefix;

                    // Prepare field (allow sub fields to be removed).
                    $sub_field = acf_prepare_field($sub_field);
                    if (!$sub_field) {
                        continue;
                    }

                    $th_attrs = array(
                        'class'     => 'acf-th',
                        'data-name' => $sub_field['_name'],
                        'data-type' => $sub_field['type'],
                        'data-key'  => $sub_field['key'],
                    );

                    if ($sub_field['wrapper']['width']) {
                        $th_attrs['data-width'] = $sub_field['wrapper']['width'];
                        $th_attrs['style'] = 'width: ' . $sub_field['wrapper']['width'] . '%;';
                    }

                    echo '<th ' . acf_esc_attrs($th_attrs) . '>';
                    acf_render_field_label($sub_field);
                    acf_render_field_instructions($sub_field);
                    echo '</th>';
                }
        ?>
			</tr>
			</thead>
			<tbody>
			<tr><?php $this->sub_fields(); ?></tr>
			</tbody>
		</table>
		<?php
    }

    /**
     * Renders a layout as a div.
     *
     * @since 6.5
     *
     * @return void
     */
    private function render_as_div(): void {
        $class = 'acf-fields';

        if ($this->layout['display'] === 'row') {
            $class .= ' -left';
        }

        echo '<div class="' . esc_attr($class) . '">';
        $this->sub_fields();
        echo '</div>';
    }

    /**
     * Renders the layout actions (Add, Duplicate, Rename).
     *
     * @since 6.5
     *
     * @return void
     */
    private function action_buttons(): void {
        $title = $this->get_title();
        $order = is_numeric($this->order) ? $this->order + 1 : 0;
        ?>
		<div class="acf-fc-layout-actions-wrap">
			<div class="acf-fc-layout-handle" title="<?php esc_attr_e('Drag to reorder', 'acf'); ?>" data-name="collapse-layout">
				<span class="acf-fc-layout-order"><?php echo (int)$order; ?></span>
				<span class="acf-fc-layout-draggable-icon"></span>
				<span class="acf-fc-layout-title">
					<?php echo !empty($this->renamed) ? esc_html($this->renamed) : $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped earlier in function.?>
				</span>
				<span class="acf-fc-layout-original-title">
					(<?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped earlier in function.?>)
				</span>
				<span class="acf-layout-disabled"><?php esc_html_e('Disabled', 'acf'); ?></span>
			</div>
			<div class="acf-fc-layout-controls">
				<a class="acf-js-tooltip" href="#" data-name="add-layout" data-context="layout" title="<?php esc_attr_e('Add layout', 'acf'); ?>"><span class="acf-icon -plus-alt "></span></a>
				<a class="acf-js-tooltip" href="#" data-name="duplicate-layout" title="<?php esc_attr_e('Duplicate layout', 'acf'); ?>"><span class="acf-icon -duplicate-alt"></span></a>
				<a class="acf-js-tooltip" aria-haspopup="menu" href="#" data-name="more-layout-actions" title="<?php esc_attr_e('More layout actions...', 'acf'); ?>"><span class="acf-icon -more-actions"></span></a>
				<?php $this->render_extra_actions($this->layout['name']); ?>
				<div class="acf-layout-collapse">
					<a class="acf-icon -collapse -clear" href="#" data-name="collapse-layout" aria-label="<?php esc_attr_e('Toggle layout', 'acf'); ?>"></a>
				</div>
			</div>
		</div>
		<?php
    }

	/**
	 * Renders the preview mode overlay and initial content (from saved data).
	 * Previewing unsaved data is handled elsewhere - see BackendPreview.php
	 * @param $module_name
	 * @return void
	 */
    protected function render_preview($module_name): void {
        ob_start();
        $this->layout_preview(array(
            'post_id'   => acf_get_valid_post_id(),
            'i'         => $this->order,
            'field_key' => $this->field['key'],
            'layout'    => $this->layout['name'],
            'value'     => $this->value,
        ));
        $html = ob_get_clean();

        echo <<<HTML
			<div class="acf-dynamic-fc-preview">
				<button class="acf-dynamic-fc-preview__overlay-button" 
						type="button"
						data-action="toggle-render-mode" 
						data-toggle-value="edit"
						data-module-name="{$module_name}" 
					>
					<span class="dashicons dashicons-edit"></span>
					Edit
				</button>
				<div class="acf-dynamic-fc-preview__content">
					$html
				</div>
			</div>
		HTML;
    }

    protected function render_extra_actions($module_name): void {

        echo <<<HTML
			<div class="acf-dynamic-fc-extra-actions">
				<a class="acf-js-tooltip" 
						href="javascript:void(0);"
						data-name="preview-layout"
						data-action="toggle-render-mode" 
						data-toggle-value="preview"
						data-module-name="{$module_name}" 
						aria-label="Preview this module"
						title="Preview layout"
					>
					<span class="dashicons dashicons-visibility"></span>
				</a>
			</div>
		HTML;
    }

    /**
     * Layout preview. Handles the initial rendering of a layout in preview mode with saved data.
	 * Previewing unsaved data is handled elsewhere - see BackendPreview.php
     *
     * @param  array  $options
     *
     * @return bool|null
     */
    public function layout_preview(array $options): ?bool {
        global $is_preview;
        $field = acf_get_field($options['field_key']);
        $instance = acf_get_field_type('flexible_content');
        $layout = $instance->get_layout($options['layout'], $field);
        $i = (int)$options['i'];
        $field_key = $options['field_key'];
        $value = wp_unslash($options['value']);
        $is_preview = true;
        $meta = array(
            $field_key => array()
        );

        // if preview index is higher than 0
        // add empty layouts to mimic get_row_index()
        if ($i > 0) {
            for ($j = 0; $j < $i; $j++) {
                $meta[$field_key][] = array(
                    'acf_fc_layout' => $layout['name']
                );
            }
        }

        // append current layout
        $meta[$field_key][] = $value;

        if (have_rows($field_key)) {
            while (have_rows($field_key)) {
                the_row();

                // continue to loop until the correct preview index
                if (acf_get_loop('active', 'i') !== $i) {

                    // remove previously created empty layouts
                    // so acf_get_loop('active', 'value') only return one row (current)
                    $loop = acf_get_loop('active');
                    unset($loop['value'][$loop['i']]);
                    acf_update_loop('active', 'value', $loop['value']);

                    continue;
                }

                $this->render_layout_template($layout, $field);
            }
        }

        return $this->return_or_die();
    }

    public function render_layout_template($layout, $field): void {
        $name = $field['_name'];
        $key = $field['key'];

        try {
            $file = TemplateHandler::get_template_path($layout['name']);
			if($file && !is_dir($file)) {
				include $file;
			}
        }
        catch (Exception $e) {
            error_log(sprintf(
                'Error rendering layout template: %s',
                $e->getMessage()
            ));
            echo '<div class="acf-notice">' . wpautop(esc_html($e->getMessage())) . '</div>';
        }
    }

    /**
     * Renders the subfields for a layout.
     *
     * @since 6.5
     *
     * @return void
     */
    private function sub_fields() {
        foreach ($this->layout['sub_fields'] as $sub_field) {

            // add value
            if (isset($this->value[$sub_field['key']])) {

                // this is a normal value
                $sub_field['value'] = $this->value[$sub_field['key']];
            }
            elseif (isset($sub_field['default_value'])) {

                // no value, but this sub field has a default value
                $sub_field['value'] = $sub_field['default_value'];
            }

            // update prefix to allow for nested values
            $sub_field['prefix'] = $this->prefix;

            // Render the input.
            $el = $this->layout['display'] === 'table' ? 'td' : 'div';
            acf_render_field_wrap($sub_field, $el);
        }
    }

    /**
     * Returns the filtered layout title.
     *
     * @since 6.5
     *
     * @return string
     */
    public function get_title() {
        $rows = array();
        $rows[$this->order] = $this->value;

        acf_add_loop(
            array(
                'selector' => $this->field['name'],
                'name'     => $this->field['name'],
                'value'    => $rows,
                'field'    => $this->field,
                'i'        => $this->order,
                'post_id'  => 0,
            )
        );

        // Make the title filterable.
        $title = esc_html($this->layout['label']);
        $title = apply_filters('acf/fields/flexible_content/layout_title', $title, $this->field, $this->layout, $this->order);
        $title = apply_filters('acf/fields/flexible_content/layout_title/name=' . $this->field['_name'], $title, $this->field, $this->layout, $this->order);
        $title = apply_filters('acf/fields/flexible_content/layout_title/key=' . $this->field['key'], $title, $this->field, $this->layout, $this->order);
        $title = acf_esc_html($title);

        acf_remove_loop();
        reset_rows(); // TODO: Make sure this is actually where this should go if needed at all.

        return $title;
    }

    /**
     * Enqueue the CSS and JS for each module in a flexible content field.
     *
     * @param  $field  - the top-level flexible content field
     *
     * @return void
     */
    public function enqueue_module_assets($field): void {
        foreach ($field['layouts'] as $module) {
			try {
				$name = Utils::kebab_case($module['name']);
				$dir_path = TemplateHandler::get_template_path($name);
				$dir_url = TemplateHandler::get_template_url($name);

				// Look for CSS and JS in theme/modules/$name, kebab-cased
				$style = "$dir_path/$name.css";
				$script = "$dir_path/modules/$name/$name.js";

				if(file_exists($style)) {
					wp_enqueue_style(
						"module-$name-style",
						"$dir_url/modules/$name/$name.css",
						[],
						filemtime($style)
					);
				}

				if(file_exists($script)) {
					wp_enqueue_script(
						"module-$name-script",
						"$dir_url/modules/$name/$name.js",
						[],
						filemtime($script),
						true
					);
				}
			}
			catch (Exception $e) {
				acf_add_admin_notice(sprintf('Error enqueuing module assets: %s', $e->getMessage()), 'error');
				error_log(sprintf('Error enqueuing module assets: %s', $e->getMessage()));
			}
        }
    }

    public function return_or_die() {
        if (wp_doing_ajax() && acf_maybe_get_POST('action') === 'acf-dynamic-preview') {
            exit;
        }

        return true;
    }
}
