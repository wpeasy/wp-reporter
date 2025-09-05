<?php

namespace WP_Easy\WP_Reporter\Data;

defined('ABSPATH') || exit;

/**
 * Themes data handler
 */
final class ThemesHandler {
    
    /**
     * Get themes data with optional filtering
     */
    public static function get_themes(array $filters = []): array {
        // Get all themes
        $all_themes = wp_get_themes();
        $current_theme = get_stylesheet();
        
        // Get update information
        $update_themes = get_site_transient('update_themes');
        
        $themes_data = [];
        
        foreach ($all_themes as $theme_slug => $theme) {
            $is_active = ($theme_slug === $current_theme);
            $has_update = isset($update_themes->response[$theme_slug]);
            $latest_version = $has_update ? $update_themes->response[$theme_slug]['new_version'] : $theme->get('Version');
            
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
            
            $themes_data[] = [
                'name' => sanitize_text_field($theme->get('Name')),
                'slug' => sanitize_text_field($theme_slug),
                'active' => $is_active,
                'version' => sanitize_text_field($theme->get('Version')),
                'latest_version' => sanitize_text_field($latest_version),
                'update_available' => $has_update,
                'description' => sanitize_textarea_field($theme->get('Description')),
                'author' => sanitize_text_field($theme->get('Author')),
                'vendor_link' => esc_url_raw($theme->get('ThemeURI')),
                'author_uri' => esc_url_raw($theme->get('AuthorURI')),
                'template' => sanitize_text_field($theme->get('Template')),
                'stylesheet' => sanitize_text_field($theme->get('Stylesheet')),
                'screenshot' => $theme->get_screenshot() ? esc_url_raw($theme->get_screenshot()) : '',
            ];
        }
        
        // Sort by name, with active theme first
        usort($themes_data, function($a, $b) {
            if ($a['active'] && !$b['active']) return -1;
            if (!$a['active'] && $b['active']) return 1;
            return strcasecmp($a['name'], $b['name']);
        });
        
        return $themes_data;
    }
    
    /**
     * Get theme update count
     */
    public static function get_update_count(): int {
        $update_themes = get_site_transient('update_themes');
        return is_object($update_themes) && isset($update_themes->response) 
            ? count($update_themes->response) 
            : 0;
    }
    
    /**
     * Get current theme info
     */
    public static function get_current_theme_info(): array {
        $theme = wp_get_theme();
        
        return [
            'name' => sanitize_text_field($theme->get('Name')),
            'version' => sanitize_text_field($theme->get('Version')),
            'description' => sanitize_textarea_field($theme->get('Description')),
            'author' => sanitize_text_field($theme->get('Author')),
            'theme_uri' => esc_url_raw($theme->get('ThemeURI')),
            'author_uri' => esc_url_raw($theme->get('AuthorURI')),
            'template' => sanitize_text_field($theme->get('Template')),
            'stylesheet' => sanitize_text_field($theme->get('Stylesheet')),
            'screenshot' => $theme->get_screenshot() ? esc_url_raw($theme->get_screenshot()) : '',
            'tags' => $theme->get('Tags') ? array_map('sanitize_text_field', $theme->get('Tags')) : [],
        ];
    }
    
    /**
     * Get total theme count
     */
    public static function get_total_count(): int {
        $all_themes = wp_get_themes();
        return count($all_themes);
    }
    
    /**
     * Check if theme is a child theme
     */
    public static function is_child_theme(string $theme_slug): bool {
        $theme = wp_get_theme($theme_slug);
        return $theme->parent() !== false;
    }
    
    /**
     * Get parent theme info for child themes
     */
    public static function get_parent_theme_info(string $child_theme_slug): ?array {
        $child_theme = wp_get_theme($child_theme_slug);
        $parent_theme = $child_theme->parent();
        
        if (!$parent_theme) {
            return null;
        }
        
        return [
            'name' => sanitize_text_field($parent_theme->get('Name')),
            'version' => sanitize_text_field($parent_theme->get('Version')),
            'slug' => sanitize_text_field($parent_theme->get_stylesheet()),
        ];
    }
    
    /**
     * Detect theme framework/builder
     */
    public static function detect_theme_framework(string $theme_slug): string {
        $theme = wp_get_theme($theme_slug);
        $theme_name = strtolower($theme->get('Name'));
        $theme_description = strtolower($theme->get('Description'));
        $theme_tags = array_map('strtolower', $theme->get('Tags') ?: []);
        
        // Popular frameworks and builders
        $frameworks = [
            'genesis' => 'Genesis Framework',
            'divi' => 'Divi',
            'avada' => 'Avada',
            'enfold' => 'Enfold',
            'x' => 'X Theme',
            'pro' => 'Pro Theme',
            'astra' => 'Astra',
            'generatepress' => 'GeneratePress',
            'oceanwp' => 'OceanWP',
            'neve' => 'Neve',
            'kadence' => 'Kadence',
            'blocksy' => 'Blocksy',
        ];
        
        // Check theme name and description
        foreach ($frameworks as $key => $name) {
            if (str_contains($theme_name, $key) || str_contains($theme_description, $key)) {
                return $name;
            }
        }
        
        // Check tags
        foreach ($theme_tags as $tag) {
            if (isset($frameworks[$tag])) {
                return $frameworks[$tag];
            }
        }
        
        // Check for block theme
        if (wp_is_block_theme()) {
            return 'Block Theme (FSE)';
        }
        
        return 'Standard';
    }
}