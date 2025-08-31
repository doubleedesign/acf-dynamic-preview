<?php
namespace Doubleedesign\ACFDynamicPreview;

class PluginEntryPoint {
    private static string $version = '0.1.0';

    public function __construct() {
	    add_action('admin_init', [$this, 'handle_no_acf'], 1);

		new TemplateHandler();

        if (is_admin()) {
            new BackendPreview();

            /** @noinspection PhpIgnoredClassAliasDeclaration */
            class_alias(
                'Doubleedesign\\ACFDynamicPreview\\Layout',
                'ACF\Pro\Fields\FlexibleContent\\Layout'
            );
        }
    }

    public static function get_version(): string {
        return self::$version;
    }

	public function handle_no_acf(): void {
		if(!class_exists('ACF')) {
			deactivate_plugins('acf-dynamic-preview/index.php');
			add_action('admin_notices', function() {
				echo '<div class="error"><p><strong>ACF Dynamic Preview</strong> requires <a href="https://www.advancedcustomfields.com/" target="_blank">Advanced Custom Fields</a> to be installed and activated.</p></div>';
			});
		}
	}

    public static function activate() {
        // Activation logic here
    }

    public static function deactivate() {
        // Deactivation logic here
    }

    public static function uninstall() {
        // Uninstallation logic here
    }
}
