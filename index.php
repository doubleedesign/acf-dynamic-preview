<?php
/**
 * Plugin Name:         ACF Dynamic Preview
 * Description:         Dynamic render and preview functionality for ACF flexible content modules, forked from the ACF Extended plugin.
 * Version:             0.1.0
 * Requires PHP:        8.3
 * Requires plugins:    advanced-custom-fields-pro
 * Author:              Double-E Design
 * Plugin URI:          https://www.github.com/doubleedesign/acf-dynamic-rendering
 * Author URI:          https://www.doubleedesign.com.au
 * Text Domain:         acf-dynamic-preview
 */
require_once __DIR__ . '/vendor/autoload.php';
use Doubleedesign\ACFDynamicPreview\PluginEntryPoint;

new PluginEntrypoint();

function activate_acf_dynamic_preview(): void {
    PluginEntrypoint::activate();
}
function deactivate_acf_dynamic_preview(): void {
    PluginEntrypoint::deactivate();
}
function uninstall_acf_dynamic_preview(): void {
    PluginEntrypoint::uninstall();
}
register_activation_hook(__FILE__, 'activate_acf_dynamic_preview');
register_deactivation_hook(__FILE__, 'deactivate_acf_dynamic_preview');
register_uninstall_hook(__FILE__, 'uninstall_acf_dynamic_preview');
