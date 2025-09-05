# WP Reporter Plugin

## Purpose

This WordPress plugin provides an admin interface to create reports on teh current status of a WordPress installation. Features will include:

- Admin Page Tabs - Pages, Plugins, Themes, Info. Tabs switched with JS, not reload.

### Plugins
- List plugins with Name, Active/Inactive, Version, Current Version, Update Status, Vendor Link
- Filter by Active/Inactive, Update Status

### Pages
- List pages in Alphabetical order at all levels, with child pages under their parents
- List  Pages with Title, Status, ID, Parent ID, Builder (Gutenberg/Beaver Builder/Bricks Builder/Elementor) add any other known detectable page builders.
- Filter by Draft/Published, Builder

### Themes
- List themes with Name, Active/Inactive, Version, Current Version, Update Status, Vendor Link
- Filter by Active/Inactive, Update Status

### WordPress
- Show a table with all the common details found under Tools->Info->WordPress and Tools->Info->WordPress
- Include details found under Tools->Info->WordPress and Tools->Info->WordPress Constants

### Directories and Sizes
- Show a table with all the common details found under Tools->Info->Directories and Sizes 

### Server
- Show a table with all the common details found under Tools->Info->Server 

If any if these details can be pulled from the existing WordPress functions used to populate Tools->info , use these.

WordPress, WordPress Constants Directories and Sizes, Server, should all be under the Info Tab

With each Tab I want to be able to filter the data visually I want the data to update with a JS fetch call, not reload. I wan to be able to Export tables as CSV.

I also want a button at the page top to generate a PDF report. It will respect all the filter settings on each tab and generate a nicely formatted PDF report.

## Architecture

**Namespace**: `WP_Easy\WP_Reporter`


## Code Style Guidelines

### PHP Conventions

1. **Namespace**: All classes use `WP_Easy\WP_Reporter` namespace
2. **Class Structure**: Final classes with static methods for WordPress hooks
3. **Security**: Always use `defined('ABSPATH') || exit;` at top of files
4. **Sanitization**: Extensive use of WordPress sanitization functions
5. **Nonces**: WordPress nonces for security, custom nonce for REST API
6. **Constants**: Prefix WPE_WPR_. Plugin paths defined as constants (`WPE_WPR_PLUGIN_PATH`, `WPE_WPR_PLUGIN_URL`)

### Method Patterns

- `init()`: Static method to register WordPress hooks
- `render()`, `handle_*()`: Methods that output HTML or handle requests
- Private helper methods prefixed with underscore when appropriate
- Extensive parameter validation and type checking


### Frontend Assets

1. **JavaScript**: Vanilla JS with AlpineJS integration, no jQuery dependency except admin
2. **CSS**: BEM methodology, uses CSS cascade layers (`@layer`) to reduce our specificity
3. **Icons**: Inline SVG icons with `currentColor` for theme compatibility
4. **Responsive**: Mobile-first design approach

### Security Practices

- Rate limiting (30 requests per 60 seconds per IP)
- Same-origin enforcement in REST API
- Nonce validation on all endpoints
- File upload restrictions to documents directory
- Sanitization of all user inputs

### WordPress Integration

- Follows WordPress coding standards
- Uses WordPress APIs extensively (Settings API, REST API, Custom Post Types)
- Translation ready with `wpe-wpr` text domain
- Hooks into WordPress media library for file management
- Compatible with WordPress multisite

### Development Features

- CodeMirror 6 integration for CSS editing in admin
- Composer autoloading (PSR-4)
- Graceful fallbacks (Alpine.js optional, CSS editor fallback to textarea)
- Extensive error handling and validation

## Configuration



### Shortcode Usage


## Development Notes

- Plugin follows WordPress plugin standards
- No external JavaScript loading. Download all required JS and serve locally
- Uses modern PHP features while maintaining 7.4 compatibility
- Frontend uses modern JavaScript (ES6+) with graceful degradation
- CSS uses modern features (Grid, Flexbox, CSS Custom Properties)
- Extensive use of WordPress core functions and APIs
- No external dependencies beyond WordPress and optional AlpineJS CDN