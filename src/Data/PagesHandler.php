<?php

namespace WP_Easy\WP_Reporter\Data;

defined('ABSPATH') || exit;

/**
 * Pages data handler
 */
final class PagesHandler {
    
    /**
     * Get pages data with optional filtering
     */
    public static function get_pages(array $filters = []): array {
        $args = [
            'post_type' => 'page',
            'post_status' => ['publish', 'draft', 'private'],
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ];
        
        // Apply status filter
        if (!empty($filters['status'])) {
            $args['post_status'] = [$filters['status']];
        }
        
        $pages = get_posts($args);
        $pages_data = [];
        
        foreach ($pages as $page) {
            $builder = self::detect_page_builder($page->ID);
            
            // Apply builder filter
            if (!empty($filters['builder']) && $filters['builder'] !== strtolower($builder)) {
                continue;
            }
            
            $pages_data[] = [
                'id' => $page->ID,
                'title' => sanitize_text_field($page->post_title),
                'status' => sanitize_text_field($page->post_status),
                'parent_id' => $page->post_parent ?: null,
                'builder' => sanitize_text_field($builder),
                'url' => esc_url_raw(get_permalink($page->ID)),
                'modified' => sanitize_text_field($page->post_modified),
                'author' => sanitize_text_field(get_the_author_meta('display_name', $page->post_author)),
            ];
        }
        
        // Sort hierarchically
        $pages_data = self::sort_pages_hierarchically($pages_data);
        
        return $pages_data;
    }
    
    /**
     * Detect page builder used for a page
     */
    public static function detect_page_builder(int $page_id): string {
        // Check for various page builders
        
        // Elementor
        if (get_post_meta($page_id, '_elementor_edit_mode', true) === 'builder') {
            return 'Elementor';
        }
        
        // Beaver Builder
        if (get_post_meta($page_id, '_fl_builder_enabled', true)) {
            return 'Beaver Builder';
        }
        
        // Bricks Builder
        if (get_post_meta($page_id, '_bricks_editor_mode', true) === 'bricks') {
            return 'Bricks Builder';
        }
        
        // Oxygen Builder
        if (get_post_meta($page_id, 'ct_builder_shortcodes', true)) {
            return 'Oxygen Builder';
        }
        
        // Divi Builder
        if (get_post_meta($page_id, '_et_pb_use_builder', true) === 'on') {
            return 'Divi Builder';
        }
        
        // Visual Composer / WPBakery
        if (get_post_meta($page_id, '_wpb_vc_js_status', true)) {
            return 'WPBakery Page Builder';
        }
        
        // Gutenberg (check for blocks)
        $content = get_post_field('post_content', $page_id);
        if (has_blocks($content)) {
            return 'Gutenberg';
        }
        
        // Classic Editor
        if (!empty($content)) {
            return 'Classic Editor';
        }
        
        return 'Unknown';
    }
    
    /**
     * Sort pages hierarchically
     */
    private static function sort_pages_hierarchically(array $pages): array {
        $top_level_pages = [];
        $child_pages = [];
        
        // Separate top-level and child pages
        foreach ($pages as $page) {
            if (!$page['parent_id']) {
                $top_level_pages[] = $page;
            } else {
                $child_pages[$page['parent_id']][] = $page;
            }
        }
        
        // Sort top-level pages alphabetically
        usort($top_level_pages, function($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });
        
        // Sort child pages alphabetically
        foreach ($child_pages as &$children) {
            usort($children, function($a, $b) {
                return strcasecmp($a['title'], $b['title']);
            });
        }
        
        // Combine hierarchically
        $sorted_pages = [];
        foreach ($top_level_pages as $parent) {
            $sorted_pages[] = $parent;
            
            // Add children if they exist
            if (isset($child_pages[$parent['id']])) {
                foreach ($child_pages[$parent['id']] as $child) {
                    $sorted_pages[] = $child;
                    
                    // Add grandchildren if they exist
                    if (isset($child_pages[$child['id']])) {
                        foreach ($child_pages[$child['id']] as $grandchild) {
                            $sorted_pages[] = $grandchild;
                        }
                    }
                }
            }
        }
        
        return $sorted_pages;
    }
    
    /**
     * Get page count by status
     */
    public static function get_count_by_status(): array {
        $counts = wp_count_posts('page');
        
        return [
            'published' => (int) ($counts->publish ?? 0),
            'draft' => (int) ($counts->draft ?? 0),
            'private' => (int) ($counts->private ?? 0),
            'total' => (int) array_sum((array) $counts),
        ];
    }
    
    /**
     * Get page count by builder
     */
    public static function get_count_by_builder(): array {
        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);
        
        $builder_counts = [
            'gutenberg' => 0,
            'elementor' => 0,
            'beaver_builder' => 0,
            'bricks_builder' => 0,
            'divi_builder' => 0,
            'oxygen_builder' => 0,
            'wpbakery' => 0,
            'classic_editor' => 0,
            'unknown' => 0,
        ];
        
        foreach ($pages as $page) {
            $builder = strtolower(str_replace(' ', '_', self::detect_page_builder($page->ID)));
            if (isset($builder_counts[$builder])) {
                $builder_counts[$builder]++;
            } else {
                $builder_counts['unknown']++;
            }
        }
        
        return $builder_counts;
    }
}