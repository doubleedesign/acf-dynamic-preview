<?php

namespace Doubleedesign\ACFDynamicPreview;
use Exception;

class TemplateHandler {
	protected static array $template_paths = [];

	public function __construct() {
		add_action('plugins_loaded', [$this, 'set_template_paths'], 20);
	}

	public function set_template_paths(): void {
		$theme_path = get_stylesheet_directory();
		$parent_theme_path = get_template_directory();

		$default_paths = [
			$theme_path . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR,
			$parent_theme_path . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR,
		];

		self::$template_paths = apply_filters('acf_dynamic_preview_template_paths', $default_paths);
	}

	/**
	 * Get the template file to use to render a module.
	 * @param $module_name
	 * @return string|null
	 * @throws Exception
	 */
	public static function get_template_path($module_name): string|null {
		$name = Utils::kebab_case($module_name);

		// Loop through each directory in the template paths and return the first match
		$path = null;
		foreach(self::$template_paths as $base_path) {
			// In a subfolder per module, e.g. modules/hero/hero.php
			$file = $base_path . $name . DIRECTORY_SEPARATOR . $name . '.php';
			if(file_exists($file)) {
				$path = $file;
				break;
			}
			// In the main modules folder, e.g. modules/hero.php
			// Or directly in the custom directory configured by a theme or plugin using the filter
			else if(file_exists($base_path . $name . '.php')) {
				$path = $base_path . $name . '.php';
				break;
			}
		}

		if($path && !is_dir($path)) {
			return $path;
		}
		else {
			throw new Exception(sprintf(
				"No module template file found for module '%s'. Searched in paths:\n %s",
				$name,
				join("\n", self::$template_paths)
			));
		}
	}

	/**
	 * @throws Exception
	 */
	public static function get_template_url($module_name): string|null {
		$name = Utils::kebab_case($module_name);
		$urls = array_map(function($path) {
			// TODO Make this account for parent themes and plugins
			return str_replace(get_stylesheet_directory(), get_stylesheet_directory_uri(), $path);
		}, self::$template_paths);

		// Loop through each directory in the template URLs and return the first match
		$path = array_filter($urls, function($base_url) use ($name) {
			$file = str_replace(get_stylesheet_directory_uri(), get_stylesheet_directory(), $base_url) . $name . DIRECTORY_SEPARATOR . $name . '.php';
			if(file_exists($file)) {
				return $file;
			}
			return null;
		})[0] ?? null;

		if($path) {
			return $path;
		}
		else {
			throw new Exception(sprintf(
				'No module template file found for module "%s". Searched in paths: %s',
				$name,
				join("\n", $urls)
			));
		}
	}
}
