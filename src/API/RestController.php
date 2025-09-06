<?php

namespace WP_Easy\WP_Reporter\API;

defined('ABSPATH') || exit;

use WP_Easy\WP_Reporter\Data\PluginsHandler;
use WP_Easy\WP_Reporter\Data\PagesHandler;
use WP_Easy\WP_Reporter\Data\ThemesHandler;
use WP_Easy\WP_Reporter\Data\InfoHandler;
use WP_Easy\WP_Reporter\Data\ErrorsHandler;
use WP_Easy\WP_Reporter\PDF\PdfGenerator;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * REST API Controller
 */
final class RestController {
    
    /**
     * API namespace
     */
    private const NAMESPACE = 'wpe-wpr/v1';
    
    /**
     * Rate limiting storage
     */
    private static array $rate_limits = [];
    
    /**
     * Initialize REST API endpoints
     */
    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }
    
    /**
     * Register all REST API routes
     */
    public static function register_routes(): void {
        // Plugins endpoint
        register_rest_route(self::NAMESPACE, '/plugins', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_plugins'],
            'permission_callback' => [self::class, 'check_permissions'],
            'args' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['active', 'inactive'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'update_status' => [
                    'type' => 'string',
                    'enum' => ['available', 'current'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
        
        // Pages endpoint
        register_rest_route(self::NAMESPACE, '/pages', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_pages'],
            'permission_callback' => [self::class, 'check_permissions'],
            'args' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['publish', 'draft'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'builder' => [
                    'type' => 'string',
                    'enum' => ['gutenberg', 'beaver', 'bricks', 'elementor'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
        
        // Themes endpoint
        register_rest_route(self::NAMESPACE, '/themes', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_themes'],
            'permission_callback' => [self::class, 'check_permissions'],
            'args' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['active', 'inactive'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'update_status' => [
                    'type' => 'string',
                    'enum' => ['available', 'current'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
        
        // Info endpoints
        register_rest_route(self::NAMESPACE, '/info/wordpress', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_wordpress_info'],
            'permission_callback' => [self::class, 'check_permissions'],
        ]);
        
        register_rest_route(self::NAMESPACE, '/info/constants', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_constants_info'],
            'permission_callback' => [self::class, 'check_permissions'],
        ]);
        
        register_rest_route(self::NAMESPACE, '/info/directories', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_directories_info'],
            'permission_callback' => [self::class, 'check_permissions'],
        ]);
        
        register_rest_route(self::NAMESPACE, '/info/server', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_server_info'],
            'permission_callback' => [self::class, 'check_permissions'],
        ]);
        
        // Errors endpoint
        register_rest_route(self::NAMESPACE, '/errors', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_errors'],
            'permission_callback' => [self::class, 'check_permissions'],
            'args' => [
                'log_type' => [
                    'type' => 'string',
                    'enum' => ['wordpress', 'server'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
        
        // Export endpoints
        register_rest_route(self::NAMESPACE, '/export/csv', [
            'methods' => 'POST',
            'callback' => [self::class, 'export_csv'],
            'permission_callback' => [self::class, 'check_permissions'],
            'args' => [
                'tab' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['plugins', 'pages', 'themes', 'info', 'errors'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'filters' => [
                    'type' => ['object', 'string'],
                    'default' => [],
                    'sanitize_callback' => function($value) {
                        if (is_string($value)) {
                            $decoded = json_decode($value, true);
                            return is_array($decoded) ? $decoded : [];
                        }
                        return is_array($value) ? $value : [];
                    },
                ],
            ],
        ]);
        
        register_rest_route(self::NAMESPACE, '/export/pdf', [
            'methods' => 'POST',
            'callback' => [self::class, 'export_pdf'],
            'permission_callback' => [self::class, 'check_permissions'],
            'args' => [
                'filters' => [
                    'type' => 'string',
                    'default' => '{}',
                    'sanitize_callback' => function($param) {
                        // Accept both string and array
                        if (is_string($param)) {
                            return $param;
                        } elseif (is_array($param)) {
                            return json_encode($param);
                        }
                        return '{}';
                    },
                ],
                'includes' => [
                    'type' => 'string',
                    'default' => '{"plugins":true,"pages":true,"themes":true,"info":true,"errors":true}',
                    'sanitize_callback' => function($param) {
                        // Accept both string and array
                        if (is_string($param)) {
                            return $param;
                        } elseif (is_array($param)) {
                            return json_encode($param);
                        }
                        return '{"plugins":true,"pages":true,"themes":true,"info":true,"errors":true}';
                    },
                ],
            ],
        ]);
    }
    
    /**
     * Check API permissions and rate limiting
     */
    public static function check_permissions(): bool {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $current_time = time();
        $rate_limit_window = 60; // 60 seconds
        $rate_limit_requests = 30; // 30 requests per minute
        
        if (!isset(self::$rate_limits[$ip])) {
            self::$rate_limits[$ip] = [];
        }
        
        // Clean old requests
        self::$rate_limits[$ip] = array_filter(
            self::$rate_limits[$ip],
            fn($timestamp) => $timestamp > ($current_time - $rate_limit_window)
        );
        
        // Check rate limit
        if (count(self::$rate_limits[$ip]) >= $rate_limit_requests) {
            return false;
        }
        
        // Add current request
        self::$rate_limits[$ip][] = $current_time;
        
        return true;
    }
    
    /**
     * Get plugins data
     */
    public static function get_plugins(WP_REST_Request $request): WP_REST_Response {
        $filters = [
            'status' => $request->get_param('status'),
            'update_status' => $request->get_param('update_status'),
        ];
        
        $data = PluginsHandler::get_plugins($filters);
        
        return new WP_REST_Response($data, 200);
    }
    
    /**
     * Get pages data
     */
    public static function get_pages(WP_REST_Request $request): WP_REST_Response {
        $filters = [
            'status' => $request->get_param('status'),
            'builder' => $request->get_param('builder'),
        ];
        
        $data = PagesHandler::get_pages($filters);
        
        return new WP_REST_Response($data, 200);
    }
    
    /**
     * Get themes data
     */
    public static function get_themes(WP_REST_Request $request): WP_REST_Response {
        $filters = [
            'status' => $request->get_param('status'),
            'update_status' => $request->get_param('update_status'),
        ];
        
        $data = ThemesHandler::get_themes($filters);
        
        return new WP_REST_Response($data, 200);
    }
    
    /**
     * Get WordPress info
     */
    public static function get_wordpress_info(): WP_REST_Response {
        $data = InfoHandler::get_wordpress_info();
        return new WP_REST_Response($data, 200);
    }
    
    /**
     * Get WordPress constants info
     */
    public static function get_constants_info(): WP_REST_Response {
        $data = InfoHandler::get_constants_info();
        return new WP_REST_Response($data, 200);
    }
    
    /**
     * Get directories info
     */
    public static function get_directories_info(): WP_REST_Response {
        $data = InfoHandler::get_directories_info();
        return new WP_REST_Response($data, 200);
    }
    
    /**
     * Get server info
     */
    public static function get_server_info(): WP_REST_Response {
        $data = InfoHandler::get_server_info();
        return new WP_REST_Response($data, 200);
    }
    
    /**
     * Get errors from log files
     */
    public static function get_errors(WP_REST_Request $request): WP_REST_Response {
        $filters = [
            'log_type' => $request->get_param('log_type'),
        ];
        
        $data = ErrorsHandler::get_errors(array_filter($filters));
        return new WP_REST_Response($data, 200);
    }
    
    /**
     * Export CSV
     */
    public static function export_csv(WP_REST_Request $request) {
        $tab = $request->get_param('tab');
        $filters = $request->get_param('filters') ?: [];
        
        $data = [];
        $filename = 'wp-reporter-' . $tab . '-' . date('Y-m-d-H-i-s') . '.csv';
        
        switch ($tab) {
            case 'plugins':
                $data = PluginsHandler::get_plugins($filters);
                break;
            case 'pages':
                $data = PagesHandler::get_pages($filters);
                break;
            case 'themes':
                $data = ThemesHandler::get_themes($filters);
                break;
            case 'info':
                $data = array_merge(
                    InfoHandler::get_wordpress_info(),
                    InfoHandler::get_constants_info(),
                    InfoHandler::get_directories_info(),
                    InfoHandler::get_server_info()
                );
                break;
            case 'errors':
                $data = ErrorsHandler::get_errors($filters ?: []);
                break;
        }
        
        if (empty($data)) {
            return new WP_REST_Response(['error' => 'No data available for export'], 404);
        }
        
        // Ensure data is properly structured for CSV
        if (!is_array($data) || !isset($data[0]) || !is_array($data[0])) {
            return new WP_REST_Response(['error' => 'Invalid data format for CSV export'], 400);
        }
        
        // Generate CSV content
        $csv_content = self::array_to_csv($data);
        
        return new WP_REST_Response([
            'filename' => $filename,
            'content' => $csv_content,
            'mime_type' => 'text/csv',
            'size' => strlen($csv_content),
        ], 200);
    }
    
    /**
     * Export PDF
     */
    public static function export_pdf(WP_REST_Request $request) {
        try {
            // Check if TCPDF is available - try to load it first
            if (!class_exists('TCPDF')) {
                // Try to include TCPDF directly
                $tcpdf_path = WPE_WPR_PLUGIN_PATH . 'vendor/tecnickcom/tcpdf/tcpdf.php';
                
                if (file_exists($tcpdf_path)) {
                    require_once $tcpdf_path;
                } else {
                    return new WP_REST_Response([
                        'error' => "TCPDF file not found at: {$tcpdf_path}",
                        'code' => 'tcpdf_file_missing',
                        'debug' => [
                            'plugin_path' => WPE_WPR_PLUGIN_PATH,
                            'tcpdf_path' => $tcpdf_path,
                            'file_exists' => file_exists($tcpdf_path)
                        ]
                    ], 400);
                }
                
                // Check again
                if (!class_exists('TCPDF')) {
                    return new WP_REST_Response([
                        'error' => 'TCPDF file found but class not loaded. There may be a syntax error or dependency issue.',
                        'code' => 'tcpdf_class_missing'
                    ], 400);
                }
            }
            
            $filters_param = $request->get_param('filters') ?: '{}';
            $filters = is_string($filters_param) ? json_decode($filters_param, true) : $filters_param;
            $filters = $filters ?: [];
            
            $includes_param = $request->get_param('includes') ?: '{"plugins":true,"pages":true,"themes":true,"info":true,"errors":true}';
            $includes = is_string($includes_param) ? json_decode($includes_param, true) : $includes_param;
            $includes = $includes ?: ['plugins' => true, 'pages' => true, 'themes' => true, 'info' => true, 'errors' => true];
            
            // Create PDF generator with filters and includes
            $pdf_generator = new PdfGenerator($filters, $includes);
            
            // Generate PDF content
            $pdf_content = $pdf_generator->generate();
            
            // Generate filename
            $filename = 'wp-report-' . sanitize_title(get_bloginfo('name')) . '-' . date('Y-m-d-H-i-s') . '.pdf';
            
            return new WP_REST_Response([
                'filename' => $filename,
                'content' => base64_encode($pdf_content),
                'mime_type' => 'application/pdf',
                'size' => strlen($pdf_content),
            ], 200);
            
        } catch (Exception $e) {
            error_log('PDF generation error: ' . $e->getMessage());
            error_log('PDF generation stack trace: ' . $e->getTraceAsString());
            
            return new WP_REST_Response([
                'error' => 'PDF generation failed: ' . $e->getMessage(),
                'code' => 'pdf_generation_failed',
                'debug' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }
    
    /**
     * Convert array to CSV format
     */
    private static function array_to_csv(array $data): string {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Write header row
        fputcsv($output, array_keys($data[0]));
        
        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }
        
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);
        
        return $csv_content;
    }
}