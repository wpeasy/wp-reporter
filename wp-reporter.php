<?php
/**
 * Plugin Name: WP Reporter
 * Plugin URI: https://github.com/wpea/wp-reporter
 * Description: WordPress admin interface to create reports on the current status of a WordPress installation.
 * Version: 1.1.0
 * Author: WP Easy
 * Author URI: https://wpeasy.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpe-wpr
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('WPE_WPR_VERSION', '1.1.0');
define('WPE_WPR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPE_WPR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPE_WPR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if Composer autoloader exists
if (file_exists(WPE_WPR_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once WPE_WPR_PLUGIN_PATH . 'vendor/autoload.php';
}

// Include core classes if autoloader not available
if (!class_exists('WP_Easy\WP_Reporter\Plugin')) {
    require_once WPE_WPR_PLUGIN_PATH . 'src/Plugin.php';
    require_once WPE_WPR_PLUGIN_PATH . 'src/Admin/AdminPage.php';
    require_once WPE_WPR_PLUGIN_PATH . 'src/API/RestController.php';
    require_once WPE_WPR_PLUGIN_PATH . 'src/Data/PluginsHandler.php';
    require_once WPE_WPR_PLUGIN_PATH . 'src/Data/PagesHandler.php';
    require_once WPE_WPR_PLUGIN_PATH . 'src/Data/ThemesHandler.php';
    require_once WPE_WPR_PLUGIN_PATH . 'src/Data/InfoHandler.php';
    require_once WPE_WPR_PLUGIN_PATH . 'src/PDF/PdfGenerator.php';
}

// Always include ErrorsHandler (new class not in original autoloader)
if (!class_exists('WP_Easy\WP_Reporter\Data\ErrorsHandler')) {
    require_once WPE_WPR_PLUGIN_PATH . 'src/Data/ErrorsHandler.php';
}

use WP_Easy\WP_Reporter\Plugin;

/**
 * Initialize the plugin
 */
function wpe_wpr_init() {
    Plugin::init();
}

// Hook into WordPress initialization
add_action('plugins_loaded', 'wpe_wpr_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create any required database tables or options here if needed
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});