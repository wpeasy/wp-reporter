<?php

namespace WP_Easy\WP_Reporter\Data;

defined('ABSPATH') || exit;

/**
 * Plugins data handler
 */
final class PluginsHandler {
    
    /**
     * Get plugins data with optional filtering
     */
    public static function get_plugins(array $filters = []): array {
        // Get all plugins
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        
        // Get update information
        $update_plugins = get_site_transient('update_plugins');
        
        $plugins_data = [];
        
        foreach ($all_plugins as $plugin_file => $plugin_data) {
            $is_active = in_array($plugin_file, $active_plugins, true);
            $has_update = isset($update_plugins->response[$plugin_file]);
            $latest_version = $has_update ? $update_plugins->response[$plugin_file]->new_version : $plugin_data['Version'];
            
            // Apply filters
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'active' && !$is_active) {
                    continue;
                }
                if ($filters['status'] === 'inactive' && $is_active) {
                    continue;
                }
            }
            
            if (!empty($filters['update_status'])) {
                if ($filters['update_status'] === 'available' && !$has_update) {
                    continue;
                }
                if ($filters['update_status'] === 'current' && $has_update) {
                    continue;
                }
            }
            
            $plugins_data[] = [
                'name' => sanitize_text_field($plugin_data['Name']),
                'active' => $is_active,
                'version' => sanitize_text_field($plugin_data['Version']),
                'latest_version' => sanitize_text_field($latest_version),
                'update_available' => $has_update,
                'description' => sanitize_textarea_field($plugin_data['Description']),
                'author' => sanitize_text_field($plugin_data['Author']),
                'vendor_link' => esc_url_raw($plugin_data['PluginURI'] ?? ''),
                'file' => sanitize_text_field($plugin_file),
            ];
        }
        
        // Sort by name
        usort($plugins_data, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
        
        return $plugins_data;
    }
    
    /**
     * Get plugin update count
     */
    public static function get_update_count(): int {
        $update_plugins = get_site_transient('update_plugins');
        return is_object($update_plugins) && isset($update_plugins->response) 
            ? count($update_plugins->response) 
            : 0;
    }
    
    /**
     * Get active plugin count
     */
    public static function get_active_count(): int {
        $active_plugins = get_option('active_plugins', []);
        return count($active_plugins);
    }
    
    /**
     * Get total plugin count
     */
    public static function get_total_count(): int {
        $all_plugins = get_plugins();
        return count($all_plugins);
    }
    
    /**
     * Detect page builder for a specific plugin
     */
    public static function detect_page_builder(string $plugin_file): string {
        $builders = [
            'beaver-builder-lite-version/fl-builder.php' => 'Beaver Builder',
            'beaver-builder/fl-builder.php' => 'Beaver Builder',
            'elementor/elementor.php' => 'Elementor',
            'bricks/bricks.php' => 'Bricks Builder',
            'oxygen/functions.php' => 'Oxygen Builder',
            'divi-builder/divi-builder.php' => 'Divi Builder',
            'visual-composer/plugin-wp.php' => 'Visual Composer',
            'js_composer/js_composer.php' => 'WPBakery Page Builder',
        ];
        
        return $builders[$plugin_file] ?? 'Unknown';
    }
}