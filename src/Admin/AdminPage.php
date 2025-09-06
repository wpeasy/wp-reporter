<?php

namespace WP_Easy\WP_Reporter\Admin;

defined('ABSPATH') || exit;

/**
 * Admin Page handler
 */
final class AdminPage {
    
    /**
     * Initialize admin page
     */
    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_admin_menu']);
    }
    
    /**
     * Add admin menu page
     */
    public static function add_admin_menu(): void {
        add_menu_page(
            __('WP Reporter', 'wpe-wpr'),
            __('WP Reporter', 'wpe-wpr'),
            'manage_options',
            'wp-reporter',
            [self::class, 'render_admin_page'],
            'dashicons-clipboard',
            30
        );
    }
    
    /**
     * Render the main admin page
     */
    public static function render_admin_page(): void {
        ?>
        <div class="wrap wp-reporter" x-data="wpReporter()">
            
            <h1 class="wp-reporter__title">
                <?php esc_html_e('WP Reporter', 'wpe-wpr'); ?>
                <button type="button" 
                        class="button button-primary wp-reporter__export-pdf" 
                        x-on:click="exportPdf()"
                        x-bind:disabled="isExportingPdf"
                        x-text="isExportingPdf ? '<?php esc_attr_e('Generating PDF...', 'wpe-wpr'); ?>' : '<?php esc_attr_e('Generate PDF Report', 'wpe-wpr'); ?>'">
                </button>
            </h1>
            
            <div class="wp-reporter__tabs">
                <nav class="nav-tab-wrapper wp-reporter__nav">
                    <a href="#plugins" 
                       class="nav-tab" 
                       x-bind:class="currentTab === 'plugins' ? 'nav-tab-active' : ''"
                       x-on:click.prevent="switchTab('plugins')">
                        <?php esc_html_e('Plugins', 'wpe-wpr'); ?>
                    </a>
                    <a href="#pages" 
                       class="nav-tab" 
                       x-bind:class="currentTab === 'pages' ? 'nav-tab-active' : ''"
                       x-on:click.prevent="switchTab('pages')">
                        <?php esc_html_e('Pages', 'wpe-wpr'); ?>
                    </a>
                    <a href="#themes" 
                       class="nav-tab" 
                       x-bind:class="currentTab === 'themes' ? 'nav-tab-active' : ''"
                       x-on:click.prevent="switchTab('themes')">
                        <?php esc_html_e('Themes', 'wpe-wpr'); ?>
                    </a>
                    <a href="#info" 
                       class="nav-tab" 
                       x-bind:class="currentTab === 'info' ? 'nav-tab-active' : ''"
                       x-on:click.prevent="switchTab('info')">
                        <?php esc_html_e('Info', 'wpe-wpr'); ?>
                    </a>
                    <a href="#errors" 
                       class="nav-tab" 
                       x-bind:class="currentTab === 'errors' ? 'nav-tab-active' : ''"
                       x-on:click.prevent="switchTab('errors')">
                        <?php esc_html_e('Errors', 'wpe-wpr'); ?>
                    </a>
                </nav>
                
                <!-- Plugins Tab -->
                <div id="plugins-tab" 
                     class="wp-reporter__tab-content" 
                     :class="currentTab === 'plugins' ? '' : 'hidden'">
                    <div class="wp-reporter__filters">
                        <div class="wp-reporter__filter-group">
                            <label for="plugins-status-filter"><?php esc_html_e('Status:', 'wpe-wpr'); ?></label>
                            <select id="plugins-status-filter" 
                                    class="wp-reporter__filter"
                                    x-model="filters.plugins.status"
                                    x-on:change="filterChanged('plugins')">
                                <option value=""><?php esc_html_e('All', 'wpe-wpr'); ?></option>
                                <option value="active"><?php esc_html_e('Active', 'wpe-wpr'); ?></option>
                                <option value="inactive"><?php esc_html_e('Inactive', 'wpe-wpr'); ?></option>
                            </select>
                        </div>
                        <div class="wp-reporter__filter-group">
                            <label for="plugins-update-filter"><?php esc_html_e('Updates:', 'wpe-wpr'); ?></label>
                            <select id="plugins-update-filter" 
                                    class="wp-reporter__filter"
                                    x-model="filters.plugins.update_status"
                                    x-on:change="filterChanged('plugins')">
                                <option value=""><?php esc_html_e('All', 'wpe-wpr'); ?></option>
                                <option value="available"><?php esc_html_e('Update Available', 'wpe-wpr'); ?></option>
                                <option value="current"><?php esc_html_e('Up to Date', 'wpe-wpr'); ?></option>
                            </select>
                        </div>
                        <label class="wp-reporter__pdf-checkbox">
                            <input type="checkbox" x-model="pdfIncludes.plugins" />
                            <span class="wp-reporter__checkbox-label"><?php esc_html_e('Include in PDF', 'wpe-wpr'); ?></span>
                        </label>
                        <button type="button" 
                                class="button wp-reporter__export-csv" 
                                x-on:click="exportCsv('plugins')"
                                x-bind:disabled="isExportingCsv"
                                x-text="isExportingCsv ? '<?php esc_attr_e('Exporting...', 'wpe-wpr'); ?>' : '<?php esc_attr_e('Export CSV', 'wpe-wpr'); ?>'">
                        </button>
                    </div>
                    <div class="wp-reporter__table-container">
                        <div class="wp-reporter__loading" x-show="loading.plugins"><?php esc_html_e('Loading...', 'wpe-wpr'); ?></div>
                        <table class="wp-reporter__table" id="plugins-table" x-show="!loading.plugins">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Name', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Status', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Version', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Latest Version', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Update Status', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Vendor Link', 'wpe-wpr'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="plugin in data.plugins" x-key="plugin.name">
                                    <tr>
                                        <td x-text="plugin.name"></td>
                                        <td><span x-bind:class="'wp-reporter__status-badge wp-reporter__status-badge--' + (plugin.active ? 'active' : 'inactive')" x-text="plugin.active ? 'Active' : 'Inactive'"></span></td>
                                        <td x-text="plugin.version"></td>
                                        <td x-text="plugin.latest_version"></td>
                                        <td><span x-bind:class="'wp-reporter__status-badge wp-reporter__status-badge--' + (plugin.update_available ? 'update-available' : 'current')" x-text="plugin.update_available ? 'Available' : 'Current'"></span></td>
                                        <td><a x-show="plugin.vendor_link" x-bind:href="plugin.vendor_link" target="_blank">View</a><span x-show="!plugin.vendor_link">-</span></td>
                                    </tr>
                                </template>
                                <tr x-show="data.plugins && data.plugins.length === 0">
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #646970;">No data found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pages Tab -->
                <div id="pages-tab" 
                     class="wp-reporter__tab-content" 
                     x-show="currentTab === 'pages'">
                    <div class="wp-reporter__filters">
                        <div class="wp-reporter__filter-group">
                            <label for="pages-status-filter"><?php esc_html_e('Status:', 'wpe-wpr'); ?></label>
                            <select id="pages-status-filter" 
                                    class="wp-reporter__filter"
                                    x-model="filters.pages.status"
                                    x-on:change="filterChanged('pages')">
                                <option value=""><?php esc_html_e('All', 'wpe-wpr'); ?></option>
                                <option value="publish"><?php esc_html_e('Published', 'wpe-wpr'); ?></option>
                                <option value="draft"><?php esc_html_e('Draft', 'wpe-wpr'); ?></option>
                            </select>
                        </div>
                        <div class="wp-reporter__filter-group">
                            <label for="pages-builder-filter"><?php esc_html_e('Builder:', 'wpe-wpr'); ?></label>
                            <select id="pages-builder-filter" 
                                    class="wp-reporter__filter"
                                    x-model="filters.pages.builder"
                                    x-on:change="filterChanged('pages')">
                                <option value=""><?php esc_html_e('All', 'wpe-wpr'); ?></option>
                                <option value="gutenberg"><?php esc_html_e('Gutenberg', 'wpe-wpr'); ?></option>
                                <option value="beaver"><?php esc_html_e('Beaver Builder', 'wpe-wpr'); ?></option>
                                <option value="bricks"><?php esc_html_e('Bricks Builder', 'wpe-wpr'); ?></option>
                                <option value="elementor"><?php esc_html_e('Elementor', 'wpe-wpr'); ?></option>
                            </select>
                        </div>
                        <label class="wp-reporter__pdf-checkbox">
                            <input type="checkbox" x-model="pdfIncludes.pages" />
                            <span class="wp-reporter__checkbox-label"><?php esc_html_e('Include in PDF', 'wpe-wpr'); ?></span>
                        </label>
                        <button type="button" 
                                class="button wp-reporter__export-csv" 
                                x-on:click="exportCsv('pages')"
                                x-bind:disabled="isExportingCsv"
                                x-text="isExportingCsv ? '<?php esc_attr_e('Exporting...', 'wpe-wpr'); ?>' : '<?php esc_attr_e('Export CSV', 'wpe-wpr'); ?>'">
                        </button>
                    </div>
                    <div class="wp-reporter__table-container">
                        <div class="wp-reporter__loading" x-show="loading.pages"><?php esc_html_e('Loading...', 'wpe-wpr'); ?></div>
                        <table class="wp-reporter__table" id="pages-table" x-show="!loading.pages">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Title', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Status', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('ID', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Parent ID', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Builder', 'wpe-wpr'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="page in data.pages" x-key="page.id">
                                    <tr>
                                        <td>
                                            <span x-bind:class="page.parent_id ? 'wp-reporter__page-title--child' : ''" 
                                                  x-text="formatPageTitle(page)"></span>
                                        </td>
                                        <td>
                                            <span x-bind:class="getStatusBadgeClass(page.status)" 
                                                  x-text="page.status.charAt(0).toUpperCase() + page.status.slice(1)"></span>
                                        </td>
                                        <td x-text="page.id"></td>
                                        <td x-text="page.parent_id || '-'"></td>
                                        <td x-text="page.builder"></td>
                                    </tr>
                                </template>
                                <tr x-show="data.pages && data.pages.length === 0">
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #646970;">No data found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Themes Tab -->
                <div id="themes-tab" 
                     class="wp-reporter__tab-content" 
                     x-show="currentTab === 'themes'">
                    <div class="wp-reporter__filters">
                        <div class="wp-reporter__filter-group">
                            <label for="themes-status-filter"><?php esc_html_e('Status:', 'wpe-wpr'); ?></label>
                            <select id="themes-status-filter" 
                                    class="wp-reporter__filter"
                                    x-model="filters.themes.status"
                                    x-on:change="filterChanged('themes')">
                                <option value=""><?php esc_html_e('All', 'wpe-wpr'); ?></option>
                                <option value="active"><?php esc_html_e('Active', 'wpe-wpr'); ?></option>
                                <option value="inactive"><?php esc_html_e('Inactive', 'wpe-wpr'); ?></option>
                            </select>
                        </div>
                        <div class="wp-reporter__filter-group">
                            <label for="themes-update-filter"><?php esc_html_e('Updates:', 'wpe-wpr'); ?></label>
                            <select id="themes-update-filter" 
                                    class="wp-reporter__filter"
                                    x-model="filters.themes.update_status"
                                    x-on:change="filterChanged('themes')">
                                <option value=""><?php esc_html_e('All', 'wpe-wpr'); ?></option>
                                <option value="available"><?php esc_html_e('Update Available', 'wpe-wpr'); ?></option>
                                <option value="current"><?php esc_html_e('Up to Date', 'wpe-wpr'); ?></option>
                            </select>
                        </div>
                        <label class="wp-reporter__pdf-checkbox">
                            <input type="checkbox" x-model="pdfIncludes.themes" />
                            <span class="wp-reporter__checkbox-label"><?php esc_html_e('Include in PDF', 'wpe-wpr'); ?></span>
                        </label>
                        <button type="button" 
                                class="button wp-reporter__export-csv" 
                                x-on:click="exportCsv('themes')"
                                x-bind:disabled="isExportingCsv"
                                x-text="isExportingCsv ? '<?php esc_attr_e('Exporting...', 'wpe-wpr'); ?>' : '<?php esc_attr_e('Export CSV', 'wpe-wpr'); ?>'">
                        </button>
                    </div>
                    <div class="wp-reporter__table-container">
                        <div class="wp-reporter__loading" x-show="loading.themes"><?php esc_html_e('Loading...', 'wpe-wpr'); ?></div>
                        <table class="wp-reporter__table" id="themes-table" x-show="!loading.themes">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Name', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Status', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Version', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Latest Version', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Update Status', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Vendor Link', 'wpe-wpr'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="theme in data.themes" x-key="theme.slug">
                                    <tr>
                                        <td x-text="theme.name"></td>
                                        <td><span x-bind:class="'wp-reporter__status-badge wp-reporter__status-badge--' + (theme.active ? 'active' : 'inactive')" x-text="theme.active ? 'Active' : 'Inactive'"></span></td>
                                        <td x-text="theme.version"></td>
                                        <td x-text="theme.latest_version"></td>
                                        <td><span x-bind:class="'wp-reporter__status-badge wp-reporter__status-badge--' + (theme.update_available ? 'update-available' : 'current')" x-text="theme.update_available ? 'Available' : 'Current'"></span></td>
                                        <td><a x-show="theme.vendor_link" x-bind:href="theme.vendor_link" target="_blank">View</a><span x-show="!theme.vendor_link">-</span></td>
                                    </tr>
                                </template>
                                <tr x-show="data.themes && data.themes.length === 0">
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #646970;">No data found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Info Tab -->
                <div id="info-tab" 
                     class="wp-reporter__tab-content" 
                     x-show="currentTab === 'info'">
                    <div class="wp-reporter__filters">
                        <label class="wp-reporter__pdf-checkbox">
                            <input type="checkbox" x-model="pdfIncludes.info" />
                            <span class="wp-reporter__checkbox-label"><?php esc_html_e('Include in PDF', 'wpe-wpr'); ?></span>
                        </label>
                        <button type="button" 
                                class="button wp-reporter__export-csv" 
                                x-on:click="exportCsv('info')"
                                x-bind:disabled="isExportingCsv"
                                x-text="isExportingCsv ? '<?php esc_attr_e('Exporting...', 'wpe-wpr'); ?>' : '<?php esc_attr_e('Export CSV', 'wpe-wpr'); ?>'">
                        </button>
                    </div>
                    <div class="wp-reporter__info-sections">
                        <div class="wp-reporter__info-section">
                            <h3><?php esc_html_e('WordPress', 'wpe-wpr'); ?></h3>
                            <div class="wp-reporter__table-container">
                                <div class="wp-reporter__loading" x-show="loading.info"><?php esc_html_e('Loading...', 'wpe-wpr'); ?></div>
                                <table class="wp-reporter__table" id="wordpress-info-table" x-show="!loading.info">
                                    <tbody>
                                        <template x-for="[key, value] in Object.entries(data.wordpress_info)" x-key="key">
                                            <tr>
                                                <td style="font-weight: 600;" x-text="key"></td>
                                                <td x-text="value"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="wp-reporter__info-section">
                            <h3><?php esc_html_e('WordPress Constants', 'wpe-wpr'); ?></h3>
                            <div class="wp-reporter__table-container">
                                <table class="wp-reporter__table" id="constants-info-table" x-show="!loading.info">
                                    <tbody>
                                        <template x-for="[key, value] in Object.entries(data.constants_info)" x-key="key">
                                            <tr>
                                                <td style="font-weight: 600;" x-text="key"></td>
                                                <td x-text="value"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="wp-reporter__info-section">
                            <h3><?php esc_html_e('Directories and Sizes', 'wpe-wpr'); ?></h3>
                            <div class="wp-reporter__table-container">
                                <table class="wp-reporter__table" id="directories-info-table" x-show="!loading.info">
                                    <tbody>
                                        <template x-for="[key, value] in Object.entries(data.directories_info)" x-key="key">
                                            <tr>
                                                <td style="font-weight: 600;" x-text="key"></td>
                                                <td x-text="value"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="wp-reporter__info-section">
                            <h3><?php esc_html_e('Server', 'wpe-wpr'); ?></h3>
                            <div class="wp-reporter__table-container">
                                <table class="wp-reporter__table" id="server-info-table" x-show="!loading.info">
                                    <tbody>
                                        <template x-for="[key, value] in Object.entries(data.server_info)" x-key="key">
                                            <tr>
                                                <td style="font-weight: 600;" x-text="key"></td>
                                                <td x-text="value"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="wp-reporter__filters">
                        <button type="button" 
                                class="button wp-reporter__export-csv" 
                                x-on:click="exportCsv('info')"
                                x-bind:disabled="isExportingCsv"
                                x-text="isExportingCsv ? '<?php esc_attr_e('Exporting...', 'wpe-wpr'); ?>' : '<?php esc_attr_e('Export CSV', 'wpe-wpr'); ?>'">
                        </button>
                    </div>
                </div>
                
                <!-- Errors Tab -->
                <div id="errors-tab" 
                     class="wp-reporter__tab-content" 
                     :class="currentTab === 'errors' ? '' : 'hidden'">
                    <div class="wp-reporter__filters">
                        <div class="wp-reporter__filter-group">
                            <label for="errors-type-filter"><?php esc_html_e('Log Type:', 'wpe-wpr'); ?></label>
                            <select id="errors-type-filter" 
                                    class="wp-reporter__filter"
                                    x-model="filters.errors.log_type"
                                    x-on:change="filterChanged('errors')">
                                <option value=""><?php esc_html_e('All Logs', 'wpe-wpr'); ?></option>
                                <option value="wordpress"><?php esc_html_e('WordPress', 'wpe-wpr'); ?></option>
                                <option value="server"><?php esc_html_e('Server', 'wpe-wpr'); ?></option>
                            </select>
                        </div>
                        
                        <label class="wp-reporter__pdf-checkbox">
                            <input type="checkbox" x-model="pdfIncludes.errors" />
                            <span class="wp-reporter__checkbox-label"><?php esc_html_e('Include in PDF', 'wpe-wpr'); ?></span>
                        </label>
                        
                        <button type="button" 
                                class="button wp-reporter__export-csv" 
                                x-on:click="exportCsv('errors')"
                                x-bind:disabled="isExportingCsv"
                                x-text="isExportingCsv ? '<?php esc_attr_e('Exporting...', 'wpe-wpr'); ?>' : '<?php esc_attr_e('Export CSV', 'wpe-wpr'); ?>'">
                        </button>
                    </div>
                    <div class="wp-reporter__table-container">
                        <div class="wp-reporter__loading" x-show="loading.errors"><?php esc_html_e('Loading...', 'wpe-wpr'); ?></div>
                        <table class="wp-reporter__table" id="errors-table" x-show="!loading.errors">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Date/Time', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Log Type', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Level', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('Message', 'wpe-wpr'); ?></th>
                                    <th><?php esc_html_e('File', 'wpe-wpr'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="error in data.errors" x-key="error.id">
                                    <tr>
                                        <td x-text="error.datetime"></td>
                                        <td><span x-bind:class="'wp-reporter__status-badge wp-reporter__status-badge--' + error.log_type.toLowerCase()" x-text="error.log_type"></span></td>
                                        <td><span x-bind:class="'wp-reporter__status-badge wp-reporter__status-badge--' + error.level.toLowerCase()" x-text="error.level"></span></td>
                                        <td x-text="error.message" style="max-width: 300px; word-wrap: break-word;"></td>
                                        <td x-text="error.file" style="font-size: 0.9em; color: #666;"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}