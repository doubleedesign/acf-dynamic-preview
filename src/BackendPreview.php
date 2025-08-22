<?php
namespace Doubleedesign\ACFDynamicPreview;
use Exception;

class BackendPreview {

    public function __construct() {
	    add_action('admin_init', [$this, 'enqueue_assets']);
	    add_action('wp_ajax_fetch_module_preview', [$this, 'fetch_module_preview']);
    }

    public function enqueue_assets(): void {
        $path = plugin_dir_url(dirname(__DIR__)) . 'acf-dynamic-preview/src/assets';
        $version = PluginEntryPoint::get_version();

        wp_enqueue_script('lodash', "{$path}/lodash.js", [], '', true);
        wp_enqueue_script('acf-dynamic-preview', "{$path}/admin.js", ['acf', 'lodash'], $version, true);
        wp_enqueue_style('acf-dynamic-preview', "{$path}/admin.css", [], $version);

		// Make variables available to JS via the ACF data object
		acf_localize_data([
			'dynamic_rendering' => [
			]
		]);

		// Nonce for AJAX interaction
		wp_localize_script('acf-dynamic-preview', 'dynamic_rendering', [
			'nonce' => wp_create_nonce('preview_toggle_nonce')
		]);
    }

	public function fetch_module_preview(): void {
		if (!wp_verify_nonce($_POST['nonce'], 'preview_toggle_nonce')) {
			wp_die('Security check failed');
		}

		$postData = json_decode(stripslashes($_POST['body']), true);
		try {
			$file =  TemplateHandler::get_template_path($postData['module_name']);
			if($file && !is_dir($file)) {
				$html = $this->get_preview_html($file, [
					'is_backend_preview' => true,
					'fields' => $postData['fields'] ?? []
				]);

				wp_send_json_success(array(
					'html' => $html
				));
			} else {
				throw new Exception(sprintf(
					"No module template file found for dynamic preview of module '%s'.",
					$postData['module_name'],
				));
			}
		}
		catch (Exception $e) {
			wp_send_json_error(array(
				'error' => $e->getMessage()
			));
		}
	}

	protected function get_preview_html($file, $vars = []): string {
		ob_start();
		extract($vars);
		include $file;

		return ob_get_clean();
	}
}
