<?php

namespace WP_Easy\WP_Reporter\Data;

defined('ABSPATH') || exit;

/**
 * System info data handler
 */
final class InfoHandler {
    
    /**
     * Get WordPress info (similar to Tools > Site Health)
     */
    public static function get_wordpress_info(): array {
        global $wp_version;
        
        return [
            'WordPress Version' => $wp_version,
            'Site URL' => get_option('siteurl'),
            'Home URL' => get_option('home'),
            'Admin Email' => get_option('admin_email'),
            'Timezone' => get_option('timezone_string') ?: 'UTC' . get_option('gmt_offset'),
            'Date Format' => get_option('date_format'),
            'Time Format' => get_option('time_format'),
            'Language' => get_locale(),
            'Multisite' => is_multisite() ? 'Yes' : 'No',
            'Debug Mode' => defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled',
            'Memory Limit' => self::get_memory_limit(),
            'Max Execution Time' => ini_get('max_execution_time') . ' seconds',
            'Max Upload Size' => size_format(wp_max_upload_size()),
            'Post Max Size' => ini_get('post_max_size'),
            'Active Plugins' => count(get_option('active_plugins', [])),
            'Active Theme' => wp_get_theme()->get('Name'),
        ];
    }
    
    /**
     * Get WordPress constants info
     */
    public static function get_constants_info(): array {
        $constants = [
            'ABSPATH' => ABSPATH,
            'WP_CONTENT_DIR' => WP_CONTENT_DIR,
            'WP_CONTENT_URL' => WP_CONTENT_URL,
            'WP_PLUGIN_DIR' => WP_PLUGIN_DIR,
            'WP_PLUGIN_URL' => WP_PLUGIN_URL,
            'WPMU_PLUGIN_DIR' => WPMU_PLUGIN_DIR,
            'WPMU_PLUGIN_URL' => WPMU_PLUGIN_URL,
        ];
        
        // Add conditional constants
        if (defined('WP_DEBUG')) {
            $constants['WP_DEBUG'] = WP_DEBUG ? 'true' : 'false';
        }
        
        if (defined('WP_DEBUG_LOG')) {
            $constants['WP_DEBUG_LOG'] = WP_DEBUG_LOG ? 'true' : 'false';
        }
        
        if (defined('WP_DEBUG_DISPLAY')) {
            $constants['WP_DEBUG_DISPLAY'] = WP_DEBUG_DISPLAY ? 'true' : 'false';
        }
        
        if (defined('WP_CACHE')) {
            $constants['WP_CACHE'] = WP_CACHE ? 'true' : 'false';
        }
        
        if (defined('WP_CRON_DISABLED')) {
            $constants['WP_CRON_DISABLED'] = WP_CRON_DISABLED ? 'true' : 'false';
        }
        
        if (defined('AUTOMATIC_UPDATER_DISABLED')) {
            $constants['AUTOMATIC_UPDATER_DISABLED'] = AUTOMATIC_UPDATER_DISABLED ? 'true' : 'false';
        }
        
        if (defined('WP_POST_REVISIONS')) {
            $constants['WP_POST_REVISIONS'] = is_bool(WP_POST_REVISIONS) 
                ? (WP_POST_REVISIONS ? 'true' : 'false') 
                : WP_POST_REVISIONS;
        }
        
        if (defined('EMPTY_TRASH_DAYS')) {
            $constants['EMPTY_TRASH_DAYS'] = EMPTY_TRASH_DAYS;
        }
        
        if (defined('WP_MEMORY_LIMIT')) {
            $constants['WP_MEMORY_LIMIT'] = WP_MEMORY_LIMIT;
        }
        
        return $constants;
    }
    
    /**
     * Get directories and sizes info
     */
    public static function get_directories_info(): array {
        $info = [];
        
        // WordPress installation directory
        $info['WordPress Directory'] = ABSPATH;
        $info['WordPress Directory Size'] = self::get_directory_size(ABSPATH);
        
        // Content directory
        $info['Content Directory'] = WP_CONTENT_DIR;
        $info['Content Directory Size'] = self::get_directory_size(WP_CONTENT_DIR);
        
        // Uploads directory
        $uploads = wp_upload_dir();
        $info['Uploads Directory'] = $uploads['basedir'];
        $info['Uploads Directory Size'] = self::get_directory_size($uploads['basedir']);
        $info['Uploads URL'] = $uploads['baseurl'];
        
        // Plugins directory
        $info['Plugins Directory'] = WP_PLUGIN_DIR;
        $info['Plugins Directory Size'] = self::get_directory_size(WP_PLUGIN_DIR);
        
        // Themes directory
        $themes_dir = get_theme_root();
        $info['Themes Directory'] = $themes_dir;
        $info['Themes Directory Size'] = self::get_directory_size($themes_dir);
        
        // Check for common cache directories
        $cache_dirs = [
            WP_CONTENT_DIR . '/cache',
            WP_CONTENT_DIR . '/w3tc-cache',
            WP_CONTENT_DIR . '/wp-rocket-cache',
            WP_CONTENT_DIR . '/litespeed-cache',
        ];
        
        foreach ($cache_dirs as $cache_dir) {
            if (is_dir($cache_dir)) {
                $cache_name = basename($cache_dir);
                $info[ucfirst($cache_name) . ' Directory'] = $cache_dir;
                $info[ucfirst($cache_name) . ' Directory Size'] = self::get_directory_size($cache_dir);
            }
        }
        
        return $info;
    }
    
    /**
     * Get server info
     */
    public static function get_server_info(): array {
        $server_info = [
            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'PHP Version' => phpversion(),
            'PHP SAPI' => php_sapi_name(),
            'MySQL Version' => self::get_mysql_version(),
            'Server OS' => php_uname('s') . ' ' . php_uname('r'),
            'Server Architecture' => php_uname('m'),
            'Server IP' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
            'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        ];
        
        // PHP Extensions
        $important_extensions = [
            'curl', 'gd', 'mbstring', 'xml', 'zip', 'json', 'mysqli', 'openssl'
        ];
        
        foreach ($important_extensions as $ext) {
            $server_info['PHP Extension: ' . $ext] = extension_loaded($ext) ? 'Loaded' : 'Not Loaded';
        }
        
        // PHP Settings
        $php_settings = [
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_vars' => ini_get('max_input_vars'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'allow_url_fopen' => ini_get('allow_url_fopen') ? 'On' : 'Off',
            'display_errors' => ini_get('display_errors') ? 'On' : 'Off',
        ];
        
        foreach ($php_settings as $setting => $value) {
            $server_info['PHP Setting: ' . $setting] = $value;
        }
        
        return $server_info;
    }
    
    /**
     * Get memory limit
     */
    private static function get_memory_limit(): string {
        $memory_limit = ini_get('memory_limit');
        
        if (defined('WP_MEMORY_LIMIT')) {
            $wp_memory_limit = WP_MEMORY_LIMIT;
            return "WordPress: {$wp_memory_limit}, PHP: {$memory_limit}";
        }
        
        return $memory_limit;
    }
    
    /**
     * Get MySQL version
     */
    private static function get_mysql_version(): string {
        global $wpdb;
        
        if (method_exists($wpdb, 'db_version')) {
            return $wpdb->db_version();
        }
        
        $version = $wpdb->get_var('SELECT VERSION()');
        return $version ?: 'Unknown';
    }
    
    /**
     * Get directory size (with caching to avoid timeouts)
     */
    private static function get_directory_size(string $directory): string {
        if (!is_dir($directory)) {
            return 'Directory not found';
        }
        
        // Use cache to avoid repeated expensive operations
        $cache_key = 'wpe_wpr_dir_size_' . md5($directory);
        $cached_size = get_transient($cache_key);
        
        if ($cached_size !== false) {
            return $cached_size;
        }
        
        // Calculate size with timeout protection
        $size = self::calculate_directory_size($directory, 5); // 5 second timeout
        $formatted_size = $size ? size_format($size) : 'Unable to calculate';
        
        // Cache for 1 hour
        set_transient($cache_key, $formatted_size, HOUR_IN_SECONDS);
        
        return $formatted_size;
    }
    
    /**
     * Calculate directory size with timeout protection
     */
    private static function calculate_directory_size(string $directory, int $max_seconds = 5): int {
        $start_time = time();
        $total_size = 0;
        
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($iterator as $file) {
                // Check timeout
                if (time() - $start_time > $max_seconds) {
                    break;
                }
                
                if ($file->isFile()) {
                    $total_size += $file->getSize();
                }
            }
        } catch (\Exception $e) {
            return 0;
        }
        
        return $total_size;
    }
}