<?php

namespace WP_Easy\WP_Reporter\Data;

defined('ABSPATH') || exit;

/**
 * Handle error log data retrieval and processing
 */
final class ErrorsHandler {
    
    /**
     * Get errors from log files
     */
    public static function get_errors(array $filters = []): array {
        // DIRECT DEBUG: Try parsing the specific file we know exists
        $log_path = 'C:\\Users\\Alan.Blair\\Local Sites\\bricks-playground\\logs\\php\\error.log';
        
        if (!file_exists($log_path)) {
            return [['error' => 'Log file not found at: ' . $log_path]];
        }
        
        if (!is_readable($log_path)) {
            return [['error' => 'Log file not readable at: ' . $log_path]];
        }
        
        // Read the last few lines directly
        $content = file_get_contents($log_path);
        if ($content === false) {
            return [['error' => 'Could not read log file']];
        }
        
        $all_lines = explode("\n", $content);
        $total_lines = count($all_lines);
        
        // Look for PHP error lines in the entire file, working backwards
        $found_errors = [];
        for ($i = $total_lines - 1; $i >= 0 && count($found_errors) < 10; $i--) {
            $line = trim($all_lines[$i]);
            if (empty($line)) continue;
            
            // Look for PHP error lines specifically
            if (preg_match('/^\[([^\]]+)\]\s+PHP\s+([^:]+):\s+(.+)/', $line)) {
                $found_errors[] = $line;
            }
        }
        
        $last_lines = array_reverse($found_errors); // Most recent first
        
        $errors = [];
        foreach ($last_lines as $line) {
            if (preg_match('/^\[([^\]]+)\]\s+PHP\s+([^:]+):\s+(.+)/', $line, $matches)) {
                $message = trim($matches[3]);
                
                // Show the log file path (shortened)
                $log_file = str_replace('C:\\Users\\Alan.Blair\\Local Sites\\bricks-playground\\', '', $log_path);
                
                $errors[] = [
                    'id' => md5($line),
                    'datetime' => date('Y-m-d H:i:s', strtotime($matches[1])),
                    'log_type' => 'Server',
                    'level' => trim($matches[2]),
                    'message' => $message,
                    'file' => $log_file,
                ];
            }
        }
        
        return array_slice($errors, 0, 10);
    }
    
    /**
     * Get WordPress errors from debug.log and error_log
     */
    private static function get_wordpress_errors(): array {
        $errors = [];
        
        // Common WordPress log locations
        $log_paths = [
            WP_CONTENT_DIR . '/debug.log',
            ABSPATH . 'wp-content/debug.log',
            ABSPATH . 'error_log',
            ABSPATH . 'wp-content/error_log',
        ];
        
        // Check for custom log file path
        if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
            $log_paths[] = WP_DEBUG_LOG;
        }
        
        foreach ($log_paths as $log_path) {
            if (file_exists($log_path) && is_readable($log_path)) {
                $log_errors = self::parse_wordpress_log($log_path);
                $errors = array_merge($errors, $log_errors);
            }
        }
        
        return $errors;
    }
    
    /**
     * Get server errors from various server log locations
     */
    private static function get_server_errors(): array {
        $errors = [];
        
        // Common server log locations (adjust based on server setup)
        $log_paths = [
            // Apache logs
            '/var/log/apache2/error.log',
            '/var/log/apache2/error_log',
            '/var/log/httpd/error_log',
            '/usr/local/apache/logs/error_log',
            
            // Nginx logs
            '/var/log/nginx/error.log',
            
            // PHP logs
            '/var/log/php_errors.log',
            '/var/log/php-fpm.log',
            ini_get('error_log'), // PHP configured error log
            
            // cPanel/shared hosting locations
            dirname(ABSPATH) . '/logs/error_log',
            dirname(ABSPATH) . '/error_logs/error_log',
            
            // WP Engine Local - specific path
            'C:\Users\Alan.Blair\Local Sites\bricks-playground\logs\php\error.log',
            
            // WP Engine Local - calculated path  
            dirname(dirname(dirname(ABSPATH))) . '/logs/php/error.log',
            
            // Local development (XAMPP, MAMP, etc.)
            'C:/xampp/apache/logs/error.log',
            '/Applications/MAMP/logs/apache_error.log',
        ];
        
        // Remove empty values and duplicates
        $log_paths = array_unique(array_filter($log_paths));
        
        foreach ($log_paths as $log_path) {
            if (file_exists($log_path) && is_readable($log_path)) {
                $log_errors = self::parse_server_log($log_path);
                $errors = array_merge($errors, $log_errors);
            }
        }
        
        return $errors;
    }
    
    /**
     * Parse WordPress debug.log format
     */
    private static function parse_wordpress_log(string $log_path): array {
        $errors = [];
        
        try {
            // Read last 50 lines to get recent errors
            $lines = self::tail_file($log_path, 50);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // WordPress log format: [DD-Mon-YYYY HH:MM:SS UTC] PHP Fatal error: message in file on line X
                if (preg_match('/^\[([^\]]+)\]\s+PHP\s+([^:]+):\s+(.+?)\s+in\s+([^\s]+)\s+on\s+line\s+(\d+)/', $line, $matches)) {
                    $errors[] = [
                        'id' => md5($line),
                        'datetime' => self::format_datetime($matches[1]),
                        'log_type' => 'WordPress',
                        'level' => trim($matches[2]),
                        'message' => trim($matches[3]),
                        'file' => self::sanitize_file_path($matches[4]) . ':' . $matches[5],
                    ];
                }
                // General WordPress error format
                elseif (preg_match('/^\[([^\]]+)\]\s+(.+)/', $line, $matches)) {
                    $errors[] = [
                        'id' => md5($line),
                        'datetime' => self::format_datetime($matches[1]),
                        'log_type' => 'WordPress',
                        'level' => 'Error',
                        'message' => trim($matches[2]),
                        'file' => 'N/A',
                    ];
                }
            }
        } catch (Exception $e) {
            // Log parsing failed, continue silently
        }
        
        return $errors;
    }
    
    /**
     * Parse server log formats (Apache, Nginx, etc.)
     */
    private static function parse_server_log(string $log_path): array {
        $errors = [];
        
        try {
            // Read last 30 lines to get recent errors
            $lines = self::tail_file($log_path, 30);
            
            // Fallback: Just read the file directly if tail_file returns nothing
            if (empty($lines)) {
                $content = file_get_contents($log_path);
                $all_lines = explode("\n", $content);
                $lines = array_slice($all_lines, -30); // Last 30 lines
            }
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Skip our own debug messages to avoid recursion
                if (strpos($line, 'WP Reporter:') !== false) {
                    continue;
                }
                
                
                // PHP error log format: [DD-Mon-YYYY HH:MM:SS UTC] PHP Fatal error: message in file on line X
                if (preg_match('/^\[([^\]]+)\]\s+PHP\s+([^:]+):\s+(.+)/', $line, $matches)) {
                    $message = trim($matches[3]);
                    
                    // Extract the main error message (first line of multi-line error)
                    $first_line = explode("\n", $message)[0];
                    
                    $errors[] = [
                        'id' => md5($line),
                        'datetime' => self::format_datetime($matches[1]),
                        'log_type' => 'Server',
                        'level' => trim($matches[2]),
                        'message' => $first_line,
                        'file' => self::extract_file_from_message($first_line),
                    ];
                }
                // Apache error log format: [Day Mon DD HH:MM:SS.uuu YYYY] [level] [pid] message
                elseif (preg_match('/^\[([^\]]+)\]\s+\[([^\]]+)\]\s+(?:\[[^\]]+\]\s+)?(.+)/', $line, $matches)) {
                    $datetime = self::parse_apache_datetime($matches[1]);
                    if ($datetime) {
                        $errors[] = [
                            'id' => md5($line),
                            'datetime' => $datetime,
                            'log_type' => 'Server',
                            'level' => ucfirst(trim($matches[2])),
                            'message' => trim($matches[3]),
                            'file' => self::extract_file_from_message($matches[3]),
                        ];
                    }
                }
                // Nginx error log format: YYYY/MM/DD HH:MM:SS [level] pid#tid: message
                elseif (preg_match('/^(\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2})\s+\[([^\]]+)\]\s+[^:]+:\s*(.+)/', $line, $matches)) {
                    $errors[] = [
                        'id' => md5($line),
                        'datetime' => date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $matches[1]))),
                        'log_type' => 'Server',
                        'level' => ucfirst(trim($matches[2])),
                        'message' => trim($matches[3]),
                        'file' => self::extract_file_from_message($matches[3]),
                    ];
                }
                // Generic fallback - any line with [timestamp] format
                elseif (preg_match('/^\[([^\]]+)\]\s+(.+)/', $line, $matches)) {
                    $errors[] = [
                        'id' => md5($line),
                        'datetime' => self::format_datetime($matches[1]),
                        'log_type' => 'Server',
                        'level' => 'Error',
                        'message' => trim($matches[2]),
                        'file' => self::extract_file_from_message($matches[2]),
                    ];
                }
            }
        } catch (Exception $e) {
            // Log parsing failed, continue silently
        }
        
        return $errors;
    }
    
    /**
     * Read the last N lines of a file efficiently
     */
    private static function tail_file(string $file_path, int $lines = 50): array {
        $handle = @fopen($file_path, 'r');
        if (!$handle) {
            return [];
        }
        
        // Get file size
        fseek($handle, 0, SEEK_END);
        $file_size = ftell($handle);
        
        // Start from end and work backwards
        $pos = $file_size;
        $result = [];
        $line_count = 0;
        
        // Read in chunks from the end
        $chunk_size = 4096;
        $buffer = '';
        
        while ($pos > 0 && $line_count < $lines) {
            $read_size = min($chunk_size, $pos);
            $pos -= $read_size;
            
            fseek($handle, $pos);
            $chunk = fread($handle, $read_size);
            $buffer = $chunk . $buffer;
            
            // Split into lines and count
            $lines_in_chunk = explode("\n", $buffer);
            
            // Keep the partial line at the beginning for next iteration
            if ($pos > 0) {
                $buffer = array_shift($lines_in_chunk);
            } else {
                $buffer = '';
            }
            
            // Add lines to result (in reverse order)
            for ($i = count($lines_in_chunk) - 1; $i >= 0; $i--) {
                if ($line_count >= $lines) break;
                if (trim($lines_in_chunk[$i]) !== '') {
                    array_unshift($result, $lines_in_chunk[$i]);
                    $line_count++;
                }
            }
        }
        
        fclose($handle);
        return array_reverse($result); // Return in chronological order
    }
    
    /**
     * Format datetime string consistently
     */
    private static function format_datetime(string $datetime_str): string {
        try {
            $timestamp = strtotime($datetime_str);
            if ($timestamp) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        } catch (Exception $e) {
            // Ignore
        }
        
        return $datetime_str;
    }
    
    /**
     * Parse Apache datetime format
     */
    private static function parse_apache_datetime(string $datetime_str): ?string {
        try {
            // Remove microseconds if present: Mon Jan 01 12:00:00.123456 2024
            $datetime_str = preg_replace('/\.\d+/', '', $datetime_str);
            $timestamp = strtotime($datetime_str);
            if ($timestamp) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        } catch (Exception $e) {
            // Ignore
        }
        
        return null;
    }
    
    /**
     * Extract file path from error message
     */
    private static function extract_file_from_message(string $message): string {
        // Look for file paths in various formats
        if (preg_match('/(?:in|at|from)\s+([^\s,]+\.php)/', $message, $matches)) {
            return self::sanitize_file_path($matches[1]);
        }
        
        if (preg_match('/([^\s]+\.php)/', $message, $matches)) {
            return self::sanitize_file_path($matches[1]);
        }
        
        return 'N/A';
    }
    
    /**
     * Sanitize file path for display
     */
    private static function sanitize_file_path(string $file_path): string {
        // Remove sensitive path information
        $file_path = str_replace([ABSPATH, WP_CONTENT_DIR, WPMU_PLUGIN_DIR, WP_PLUGIN_DIR], 
                                ['', '/wp-content', '/wp-content/mu-plugins', '/wp-content/plugins'], 
                                $file_path);
        
        // Remove any remaining absolute paths
        $file_path = preg_replace('/^\/[^\/]+\/[^\/]+\/[^\/]+/', '...', $file_path);
        
        return $file_path;
    }
}