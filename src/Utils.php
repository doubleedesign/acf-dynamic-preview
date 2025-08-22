<?php

namespace Doubleedesign\ACFDynamicPreview;

class Utils {

	public static function kebab_case($string): string {
		// Use WordPress's sanitize_title function for initial conversion
		$initial = sanitize_title($string);

		// Replace underscores with hyphens
		return str_replace('_', '-', $initial);
	}
}
