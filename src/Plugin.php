<?php

namespace WP_Easy\WP_Reporter;

defined('ABSPATH') || exit;

use WP_Easy\WP_Reporter\Admin\AdminPage;
use WP_Easy\WP_Reporter\API\RestController;

/**
 * Main Plugin class
 */
final class Plugin {
    
    /**
     * Initialize the plugin
     */
    public static function init(): void {
        add_action('init', [self::class, 'load_textdomain']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_frontend_assets']);
        
        // Initialize components
        AdminPage::init();
        RestController::init();
    }
    
    /**
     * Load plugin text domain for translations
     */
    public static function load_textdomain(): void {
        load_plugin_textdomain(
            'wpe-wpr',
            false,
            dirname(WPE_WPR_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets(string $hook_suffix): void {
        // Only load on our admin page
        if (!str_contains($hook_suffix, 'wp-reporter')) {
            return;
        }
        
        wp_enqueue_style(
            'wpe-wpr-admin',
            WPE_WPR_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WPE_WPR_VERSION
        );
        
        wp_enqueue_script(
            'wpe-wpr-admin',
            WPE_WPR_PLUGIN_URL . 'assets/js/admin-alpine.js',
            [],
            WPE_WPR_VERSION,
            true
        );
        
        wp_enqueue_script(
            'wpe-wpr-alpine',
            WPE_WPR_PLUGIN_URL . 'assets/js/alpine.min.js',
            ['wpe-wpr-admin'],
            WPE_WPR_VERSION,
            true
        );
        
        // Localize script with REST API data
        wp_localize_script('wpe-wpr-admin', 'wpeWprAdmin', [
            'restUrl' => rest_url('wpe-wpr/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
    
    /**
     * Enqueue frontend assets (if needed)
     */
    public static function enqueue_frontend_assets(): void {
        // Frontend assets can be added here if needed
    }
}