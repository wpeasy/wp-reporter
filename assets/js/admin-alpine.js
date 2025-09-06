/**
 * WP Reporter Admin JavaScript with AlpineJS
 */

// Create the component as a global function that Alpine can find
window.wpReporter = () => {
    const component = {
                // State management
                currentTab: 'plugins',
        loading: {
            plugins: false,
            pages: false,
            themes: false,
            info: false,
            errors: false
        },
        pdfIncludes: {
            plugins: true,
            pages: true,
            themes: true,
            info: true,
            errors: true
        },
        filters: {
            plugins: {
                status: '',
                update_status: ''
            },
            pages: {
                status: '',
                builder: ''
            },
            themes: {
                status: '',
                update_status: ''
            },
            info: {},
            errors: {
                log_type: ''
            }
        },
        data: {
            plugins: [],
            pages: [],
            themes: [],
            wordpress_info: {},
            constants_info: {},
            directories_info: {},
            server_info: {},
            errors: []
        },
        loadedTabs: [],
        isExportingCsv: false,
        isExportingPdf: false,

        // Configuration
        get config() {
            return window.wpeWprAdmin || {
                restUrl: '/wp-json/wpe-wpr/v1/',
                nonce: ''
            };
        },

        // Initialize component  
        init() {
            this.loadCurrentTab();
        },

        // Tab management
        switchTab(tabName) {
            if (tabName === this.currentTab) return;
            
            this.currentTab = tabName;
            this.loadCurrentTab();
        },

        // Load current tab data if not already loaded
        loadCurrentTab() {
            if (this.loadedTabs.includes(this.currentTab)) {
                return;
            }
            
            this.loadTabData(this.currentTab, this.filters[this.currentTab] || {});
        },

        // Filter change handler
        filterChanged(tab) {
            this.loadTabData(tab, this.filters[tab]);
        },

        // Load tab data from API
        async loadTabData(tab, filters = {}) {
            this.loading[tab] = true;
            
            try {
                let endpoint = '';
                switch (tab) {
                    case 'plugins':
                        endpoint = 'plugins';
                        break;
                    case 'pages':
                        endpoint = 'pages';
                        break;
                    case 'themes':
                        endpoint = 'themes';
                        break;
                    case 'info':
                        await this.loadInfoTab();
                        return;
                    case 'errors':
                        endpoint = 'errors';
                        break;
                    default:
                        return;
                }
                
                const response = await this.fetchApi(endpoint, filters);
                this.data[tab] = response;
                
                if (!this.loadedTabs.includes(tab)) {
                    this.loadedTabs.push(tab);
                }
            } catch (error) {
                console.error(`Error loading ${tab} data:`, error);
                this.data[tab] = [];
            } finally {
                this.loading[tab] = false;
            }
        },

        // Load info tab (multiple endpoints)
        async loadInfoTab() {
            const endpoints = ['wordpress', 'constants', 'directories', 'server'];
            this.loading.info = true;
            
            try {
                const promises = endpoints.map(endpoint => 
                    this.fetchApi(`info/${endpoint}`)
                );
                
                const responses = await Promise.all(promises);
                
                endpoints.forEach((endpoint, index) => {
                    this.data[endpoint + '_info'] = responses[index];
                });
                
                if (!this.loadedTabs.includes('info')) {
                    this.loadedTabs.push('info');
                }
            } catch (error) {
                console.error('Error loading info data:', error);
            } finally {
                this.loading.info = false;
            }
        },

        // API fetch helper
        async fetchApi(endpoint, params = {}) {
            const url = new URL(this.config.restUrl + endpoint, window.location.origin);
            
            // Add query parameters for GET requests
            Object.keys(params).forEach(key => {
                if (params[key]) {
                    url.searchParams.append(key, params[key]);
                }
            });
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': this.config.nonce,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }
            
            return await response.json();
        },

        // CSV export
        async exportCsv(tab) {
            this.isExportingCsv = true;
            
            try {
                const response = await fetch(this.config.restUrl + 'export/csv', {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': this.config.nonce,
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        tab: tab,
                        filters: JSON.stringify(this.filters[tab] || {})
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                this.downloadFile(result.filename, result.content, result.mime_type);
            } catch (error) {
                console.error('CSV export error:', error);
                alert('Export failed. Please try again.');
            } finally {
                this.isExportingCsv = false;
            }
        },

        // PDF export
        async exportPdf() {
            this.isExportingPdf = true;
            
            try {
                const response = await fetch(this.config.restUrl + 'export/pdf', {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': this.config.nonce,
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        filters: JSON.stringify(this.filters),
                        includes: JSON.stringify(this.pdfIncludes)
                    })
                });
                
                if (!response.ok) {
                    let errorMessage = `HTTP error! status: ${response.status}`;
                    let errorText = '';
                    
                    try {
                        // Try to get the response as text first
                        errorText = await response.text();
                        console.log('Raw error response:', errorText);
                        
                        // Try to parse as JSON
                        const errorData = JSON.parse(errorText);
                        errorMessage = errorData.error || errorMessage;
                        console.log('Parsed error data:', errorData);
                    } catch (e) {
                        // If JSON parsing fails, use the text response or default message
                        console.log('Error parsing JSON response:', e);
                        if (errorText) {
                            errorMessage = `${errorMessage} - Response: ${errorText.substring(0, 200)}`;
                        }
                    }
                    throw new Error(errorMessage);
                }
                
                const result = await response.json();
                
                // Convert base64 to blob and download
                const binaryString = atob(result.content);
                const bytes = new Uint8Array(binaryString.length);
                for (let i = 0; i < binaryString.length; i++) {
                    bytes[i] = binaryString.charCodeAt(i);
                }
                const blob = new Blob([bytes], { type: result.mime_type });
                this.downloadBlob(result.filename, blob);
            } catch (error) {
                console.error('PDF export error:', error);
                
                // Show the detailed error message
                let errorMessage = error.message;
                if (errorMessage.includes('PDF generation failed:')) {
                    errorMessage = errorMessage; // Already formatted
                } else {
                    errorMessage = 'PDF export failed: ' + errorMessage;
                }
                
                alert(errorMessage);
            } finally {
                this.isExportingPdf = false;
            }
        },

        // Download file helper
        downloadFile(filename, content, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            this.downloadBlob(filename, blob);
        },

        // Download blob helper
        downloadBlob(filename, blob) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            URL.revokeObjectURL(url);
            document.body.removeChild(a);
        },

        // Utility methods
        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        // Format page title with hierarchy
        formatPageTitle(page) {
            return page.parent_id ? '└ ' + page.title : page.title;
        },

        // Get status badge class
        getStatusBadgeClass(status, type = 'status') {
            const classes = {
                status: {
                    active: 'wp-reporter__status-badge--active',
                    inactive: 'wp-reporter__status-badge--inactive',
                    published: 'wp-reporter__status-badge--published',
                    publish: 'wp-reporter__status-badge--published',
                    draft: 'wp-reporter__status-badge--draft'
                },
                update: {
                    true: 'wp-reporter__status-badge--update-available',
                    false: 'wp-reporter__status-badge--current',
                    available: 'wp-reporter__status-badge--update-available',
                    current: 'wp-reporter__status-badge--current'
                }
            };
            
            const typeClasses = classes[type] || classes.status;
            return 'wp-reporter__status-badge ' + (typeClasses[status] || '');
        }
            };
            
    // Auto-initialize when component is created
    setTimeout(() => component.init(), 100);
    
    return component;
};