<?php
namespace Doubleedesign\ACFDynamicPreview;

class PluginEntryPoint {
    private static string $version = '0.1.0';

    public function __construct() {
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
