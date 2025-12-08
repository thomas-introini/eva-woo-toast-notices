<?php
/**
 * Plugin Name:       Eva Toast Notices
 * Description:       Replaces WordPress notices with toast notifications in admin and frontend.
 * Author:            Eva Caffè
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Tested up to:      6.6
 * Version:           1.0.0
 * Text Domain:       eva-toast-notices
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// Basic plugin constants.
define('EVA_TOAST_NOTICES_VERSION', '1.0.0');
define('EVA_TOAST_NOTICES_FILE', __FILE__);
define('EVA_TOAST_NOTICES_PATH', plugin_dir_path(__FILE__));
define('EVA_TOAST_NOTICES_URL', plugin_dir_url(__FILE__));

// Simple PSR-4 autoloader for EvaToastNotices namespace.
spl_autoload_register(
    static function (string $class): void {
        $prefix   = 'EvaToastNotices\\';
        $base_dir = EVA_TOAST_NOTICES_PATH . 'src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file           = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
);

use EvaToastNotices\Plugin;

/**
 * Bootstrap the plugin on plugins_loaded.
 */
add_action(
    'plugins_loaded',
    static function (): void {
        Plugin::instance()->init();
    }
);

/**
 * Activation hook to set up default options.
 */
register_activation_hook(
    __FILE__,
    static function (): void {
        Plugin::activate();
    }
);


