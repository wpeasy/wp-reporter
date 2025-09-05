# Changelog

All notable changes to WP Reporter will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-09-05

### Added

#### Errors Tab - New Error Reporting System
- **Complete Errors Tab**: New tab displaying the last 10 PHP errors from server error logs
- **Multi-source Error Detection**: 
  - WordPress debug.log parsing
  - Server error log parsing (Apache, Nginx, PHP)
  - WP Engine Local environment support with Windows path handling
- **Error Information Display**:
  - Error datetime with proper formatting
  - Error level (Fatal error, Warning, Notice, etc.)
  - Full error message with truncation for display
  - File location with path sanitization for security
  - Log type classification (WordPress/Server)
- **Filtering Capabilities**: Filter errors by log type (WordPress vs Server)
- **CSV Export Integration**: Errors tab data included in CSV export functionality
- **PDF Report Integration**: Errors section added to PDF reports with proper formatting

#### New Data Handler
- **ErrorsHandler Class**: Comprehensive error log parsing and management
  - Multi-format log file parsing (PHP, Apache, Nginx, WordPress)
  - Windows environment compatibility with proper path handling
  - Efficient file reading with backwards scanning algorithm
  - Duplicate error filtering and chronological sorting
  - Memory-efficient processing of large log files

### Fixed
- **Class Autoloading**: Resolved ErrorsHandler class not found issues
- **Windows Path Support**: Fixed file path handling for Windows development environments
- **Log Parsing**: Improved regex patterns for multi-line PHP error parsing
- **File Path Extraction**: Enhanced extraction and sanitization of file paths from error messages
- **Stack Trace Filtering**: Proper filtering of stack trace lines from error display

### Changed
- **Plugin Version**: Updated from 1.0.0 to 1.1.0
- **Error Display Count**: Increased from 5 to 10 errors for better debugging visibility
- **CSS Enhancements**: Added error-specific badge styling and colors
- **Log Search Algorithm**: Improved to scan entire log files backwards for better error discovery
- **Manual Class Includes**: Added ErrorsHandler to manual includes for compatibility

### Technical Improvements
- **REST API Extension**: Added `/errors` endpoint with filtering support
- **AlpineJS Integration**: Extended frontend reactivity to include errors tab
- **Security Enhancements**: Added proper path sanitization for Windows environments
- **Performance Optimization**: Efficient log file parsing with configurable line limits

## [1.0.0] - 2024-09-03

### Added

#### Core Plugin Structure
- Main plugin file with WordPress headers and initialization hooks
- PSR-4 autoloading support with fallback to manual includes
- Plugin constants for paths and URLs
- Activation and deactivation hooks with flush_rewrite_rules()

#### Admin Interface
- **Admin Page**: Clean tabbed interface accessible from WordPress admin menu
- **Tab Navigation**: JavaScript-powered tab switching without page reloads
- **Four Main Tabs**:
  - **Plugins Tab**: List all plugins with filtering capabilities
  - **Pages Tab**: Hierarchical page listing with builder detection
  - **Themes Tab**: Theme management with update status
  - **Info Tab**: Comprehensive system information display

#### REST API Endpoints
- Secure REST API with `wpe-wpr/v1` namespace
- **Security Features**:
  - Rate limiting (30 requests per 60 seconds per IP)
  - WordPress nonce verification
  - Same-origin policy enforcement
  - User capability checks (`manage_options`)
- **Data Endpoints**:
  - `/plugins` - Plugin data with status and update filtering
  - `/pages` - Page data with status and builder filtering
  - `/themes` - Theme data with status and update filtering
  - `/info/wordpress` - WordPress core information
  - `/info/constants` - WordPress constants
  - `/info/directories` - Directory sizes and paths
  - `/info/server` - Server and PHP information
- **Export Endpoints**:
  - `/export/csv` - CSV export for individual tabs
  - `/export/pdf` - PDF export placeholder (requires library)

#### Data Management Classes
- **PluginsHandler**: Complete plugin information management
  - Active/inactive status detection
  - Update availability checking
  - Plugin metadata extraction
  - Sorting and filtering capabilities
- **PagesHandler**: Intelligent page management
  - Hierarchical page organization (parent/child relationships)
  - Page builder detection for 7+ builders (Gutenberg, Elementor, Beaver Builder, Bricks, Divi, Oxygen, WPBakery)
  - Status filtering (published, draft, private)
  - Alphabetical sorting within hierarchy levels
- **ThemesHandler**: Comprehensive theme management
  - Active theme identification
  - Update status monitoring
  - Child theme detection
  - Theme framework identification
- **InfoHandler**: System information aggregation
  - WordPress core details matching Tools > Site Health
  - WordPress constants with conditional detection
  - Directory size calculation with caching and timeout protection
  - Server environment details (PHP, MySQL, extensions)

#### Frontend Assets
- **CSS Styling** (`assets/css/admin.css`):
  - CSS cascade layers for reduced specificity conflicts
  - BEM methodology for maintainable CSS
  - Responsive design with mobile-first approach
  - Dark mode support via `prefers-color-scheme`
  - Print-friendly styles for reports
  - Status badges with semantic colors
  - Loading animations and transitions
- **JavaScript Functionality** (`assets/js/admin.js`):
  - Vanilla JavaScript implementation (no jQuery dependency except admin)
  - Tab switching with browser history support
  - Real-time filtering with debounced API calls
  - CSV download functionality
  - Error handling and loading states
  - HTML escaping for security

#### Filtering & Export Features
- **Advanced Filtering**:
  - **Plugins**: Filter by active/inactive status and update availability
  - **Pages**: Filter by publish status and page builder type
  - **Themes**: Filter by active/inactive status and update availability
  - All filters update data via AJAX without page reload
- **CSV Export**: 
  - Individual tab export with current filter settings applied
  - Proper CSV formatting with headers
  - Secure download via blob URLs
- **PDF Export**: Framework ready (requires TCPDF/mPDF library installation)

#### Page Builder Detection
- **Comprehensive Detection** for popular page builders:
  - **Gutenberg**: Block editor detection via `has_blocks()`
  - **Elementor**: Meta key `_elementor_edit_mode`
  - **Beaver Builder**: Meta key `_fl_builder_enabled`
  - **Bricks Builder**: Meta key `_bricks_editor_mode`
  - **Divi Builder**: Meta key `_et_pb_use_builder`
  - **Oxygen Builder**: Meta key `ct_builder_shortcodes`
  - **WPBakery**: Meta key `_wpb_vc_js_status`
  - **Classic Editor**: Fallback detection
- **Hierarchical Display**: Parent/child page relationships with visual indentation

#### Security & Performance
- **Security Measures**:
  - All user inputs sanitized using WordPress functions
  - SQL injection prevention through WordPress APIs
  - XSS protection via proper escaping
  - CSRF protection through nonces
  - Rate limiting to prevent abuse
- **Performance Optimizations**:
  - Directory size caching with 1-hour expiration
  - Timeout protection for large directory calculations
  - Efficient database queries using WordPress core functions
  - Lazy loading of tab data

#### WordPress Integration
- **Standards Compliance**:
  - WordPress Coding Standards adherence
  - Translation ready with `wpe-wpr` text domain
  - Proper hook usage and WordPress APIs
  - Compatible with WordPress 5.0+ and PHP 7.4+
- **Multisite Support**: Detection and reporting
- **Theme Compatibility**: Works with any properly coded WordPress theme

### Technical Specifications
- **Namespace**: `WP_Easy\WP_Reporter`
- **Text Domain**: `wpe-wpr`
- **Minimum WordPress**: 5.0
- **Minimum PHP**: 7.4
- **Tested up to**: WordPress 6.3

### Known Limitations
- PDF export requires additional library installation (TCPDF, mPDF, or similar)
- Directory size calculations have 5-second timeout for large installations
- Some page builders may require additional meta key detection patterns

### Developer Notes
- Plugin follows WordPress plugin development best practices
- Extensive code documentation and inline comments
- Modular architecture for easy extension
- No external JavaScript dependencies (except jQuery for admin)
- CSS uses modern features with graceful fallbacks