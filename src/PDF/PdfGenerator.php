<?php

namespace WP_Easy\WP_Reporter\PDF;

defined('ABSPATH') || exit;

use TCPDF;
use WP_Easy\WP_Reporter\Data\PluginsHandler;
use WP_Easy\WP_Reporter\Data\PagesHandler;
use WP_Easy\WP_Reporter\Data\ThemesHandler;
use WP_Easy\WP_Reporter\Data\InfoHandler;
use WP_Easy\WP_Reporter\Data\ErrorsHandler;

/**
 * PDF Report Generator using TCPDF
 */
final class PdfGenerator {
    
    /**
     * PDF instance
     */
    private TCPDF $pdf;
    
    /**
     * Report data
     */
    private array $data;
    
    /**
     * Filters applied
     */
    private array $filters;
    
    /**
     * Constructor
     */
    public function __construct(array $filters = []) {
        // Ensure TCPDF is loaded
        if (!class_exists('TCPDF')) {
            $tcpdf_path = WPE_WPR_PLUGIN_PATH . 'vendor/tecnickcom/tcpdf/tcpdf.php';
            if (file_exists($tcpdf_path)) {
                require_once $tcpdf_path;
            }
        }
        
        $this->filters = $filters;
        $this->init_pdf();
    }
    
    /**
     * Initialize TCPDF instance
     */
    private function init_pdf(): void {
        $this->pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $this->pdf->SetCreator('WP Reporter Plugin');
        $this->pdf->SetAuthor(get_bloginfo('name'));
        $this->pdf->SetTitle('WordPress Site Report - ' . get_bloginfo('name'));
        $this->pdf->SetSubject('WordPress Installation Report');
        
        // Set header and footer fonts
        $this->pdf->setHeaderFont(['helvetica', '', 12]);
        $this->pdf->setFooterFont(['helvetica', '', 8]);
        
        // Set margins
        $this->pdf->SetMargins(15, 27, 15);
        $this->pdf->SetHeaderMargin(5);
        $this->pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $this->pdf->SetAutoPageBreak(true, 25);
        
        // Set image scale factor
        $this->pdf->setImageScale(1.25);
        
        // Set header and footer
        $this->set_header_footer();
    }
    
    /**
     * Set PDF header and footer
     */
    private function set_header_footer(): void {
        // Set header data
        $this->pdf->SetHeaderData('', 0, 'WordPress Site Report', 
            get_bloginfo('name') . ' - ' . home_url() . "\n" . 
            'Generated on ' . current_time('F j, Y \a\t g:i A'), 
            array(0,64,255), array(0,64,128)
        );
        
        // Set header and footer fonts
        $this->pdf->setHeaderFont(Array('helvetica', '', 10));
        $this->pdf->setFooterFont(Array('helvetica', '', 8));
        
        // Set margins
        $this->pdf->SetMargins(15, 27, 15);
        $this->pdf->SetHeaderMargin(5);
        $this->pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $this->pdf->SetAutoPageBreak(TRUE, 25);
    }
    
    /**
     * Generate complete PDF report
     */
    public function generate(): string {
        $this->collect_data();
        
        // Add pages
        $this->add_cover_page();
        $this->add_plugins_section();
        $this->add_pages_section();
        $this->add_themes_section();
        $this->add_errors_section();
        $this->add_info_section();
        
        return $this->pdf->Output('wp-report-' . sanitize_title(get_bloginfo('name')) . '-' . date('Y-m-d-H-i-s') . '.pdf', 'S');
    }
    
    /**
     * Collect all data for the report
     */
    private function collect_data(): void {
        $this->data = [
            'plugins' => PluginsHandler::get_plugins($this->filters['plugins'] ?? []),
            'pages' => PagesHandler::get_pages($this->filters['pages'] ?? []),
            'themes' => ThemesHandler::get_themes($this->filters['themes'] ?? []),
            'wordpress_info' => InfoHandler::get_wordpress_info(),
            'constants_info' => InfoHandler::get_constants_info(),
            'directories_info' => InfoHandler::get_directories_info(),
            'server_info' => InfoHandler::get_server_info(),
            'errors' => ErrorsHandler::get_errors($this->filters['errors'] ?? []),
        ];
    }
    
    /**
     * Add cover page
     */
    private function add_cover_page(): void {
        $this->pdf->AddPage();
        
        // Site logo/icon if available
        $site_icon = get_site_icon_url(120);
        if ($site_icon) {
            $this->pdf->Image($site_icon, 85, 50, 40, 40, '', '', '', false, 300, '', false, false, 1);
        }
        
        $this->pdf->Ln(60);
        
        // Title
        $this->pdf->SetFont('helvetica', 'B', 24);
        $this->pdf->Cell(0, 15, 'WordPress Site Report', 0, 1, 'C');
        
        $this->pdf->Ln(10);
        
        // Site information
        $this->pdf->SetFont('helvetica', '', 14);
        $this->pdf->Cell(0, 10, get_bloginfo('name'), 0, 1, 'C');
        $this->pdf->SetFont('helvetica', '', 12);
        $this->pdf->Cell(0, 8, home_url(), 0, 1, 'C');
        
        $this->pdf->Ln(20);
        
        // Report summary
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'Report Summary', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        
        $summary_data = [
            'WordPress Version' => $this->data['wordpress_info']['WordPress Version'],
            'Active Theme' => $this->data['wordpress_info']['Active Theme'],
            'Total Plugins' => count($this->data['plugins']),
            'Active Plugins' => count(array_filter($this->data['plugins'], fn($p) => $p['active'])),
            'Total Pages' => count($this->data['pages']),
            'Total Themes' => count($this->data['themes']),
            'Recent Errors' => count($this->data['errors']),
        ];
        
        foreach ($summary_data as $label => $value) {
            $this->pdf->Cell(70, 6, $label . ':', 0, 0, 'L');
            $this->pdf->Cell(0, 6, (string) $value, 0, 1, 'L');
        }
        
        $this->pdf->Ln(10);
        
        // Generation info
        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->Cell(0, 5, 'Generated on: ' . current_time('F j, Y \a\t g:i A T'), 0, 1, 'L');
        $this->pdf->Cell(0, 5, 'Generated by: WP Reporter Plugin v' . WPE_WPR_VERSION, 0, 1, 'L');
    }
    
    /**
     * Add plugins section
     */
    private function add_plugins_section(): void {
        $this->pdf->AddPage();
        $this->add_section_header('Plugins Report');
        
        if (empty($this->data['plugins'])) {
            $this->pdf->SetFont('helvetica', 'I', 10);
            $this->pdf->Cell(0, 8, 'No plugins found matching the current filters.', 0, 1, 'L');
            return;
        }
        
        // Add applied filters info
        $this->add_filters_info($this->filters['plugins'] ?? []);
        
        // Create table
        $header = ['Plugin Name', 'Status', 'Version', 'Latest Version', 'Update Status', 'Vendor Link'];
        $this->create_table($header, $this->data['plugins'], [
            'name' => 50,
            'active' => 20,
            'version' => 25,
            'latest_version' => 25,
            'update_available' => 25,
            'vendor_link' => 35,
        ], function($row) {
            return [
                $row['name'],
                $row['active'] ? 'Active' : 'Inactive',
                $row['version'],
                $row['latest_version'] ?? 'N/A',
                $row['update_available'] ? 'Available' : 'Current',
                !empty($row['vendor_link']) ? 'Available' : 'N/A',
            ];
        });
    }
    
    /**
     * Add pages section
     */
    private function add_pages_section(): void {
        $this->pdf->AddPage();
        $this->add_section_header('Pages Report');
        
        if (empty($this->data['pages'])) {
            $this->pdf->SetFont('helvetica', 'I', 10);
            $this->pdf->Cell(0, 8, 'No pages found matching the current filters.', 0, 1, 'L');
            return;
        }
        
        // Add applied filters info
        $this->add_filters_info($this->filters['pages'] ?? []);
        
        // Create table
        $header = ['Page Title', 'Status', 'ID', 'Parent ID', 'Builder'];
        $this->create_table($header, $this->data['pages'], [
            'title' => 70,
            'status' => 25,
            'id' => 15,
            'parent_id' => 20,
            'builder' => 35,
        ], function($row) {
            $title = $row['title'];
            if ($row['parent_id']) {
                $title = '   └ ' . $title; // Indent child pages
            }
            return [
                $title,
                ucfirst($row['status']),
                (string) $row['id'],
                $row['parent_id'] ? (string) $row['parent_id'] : '-',
                $row['builder'] ?? 'Unknown',
            ];
        });
    }
    
    /**
     * Add themes section
     */
    private function add_themes_section(): void {
        $this->pdf->AddPage();
        $this->add_section_header('Themes Report');
        
        if (empty($this->data['themes'])) {
            $this->pdf->SetFont('helvetica', 'I', 10);
            $this->pdf->Cell(0, 8, 'No themes found matching the current filters.', 0, 1, 'L');
            return;
        }
        
        // Add applied filters info
        $this->add_filters_info($this->filters['themes'] ?? []);
        
        // Create table
        $header = ['Theme Name', 'Status', 'Version', 'Updates'];
        $this->create_table($header, $this->data['themes'], [
            'name' => 70,
            'active' => 20,
            'version' => 25,
            'update_available' => 25,
        ], function($row) {
            return [
                $row['name'],
                $row['active'] ? 'Active' : 'Inactive',
                $row['version'],
                $row['update_available'] ? 'Available' : 'Current',
            ];
        });
    }
    
    /**
     * Add errors section
     */
    private function add_errors_section(): void {
        $this->pdf->AddPage();
        $this->add_section_header('Recent Errors Report');
        
        if (empty($this->data['errors'])) {
            $this->pdf->SetFont('helvetica', 'I', 10);
            $this->pdf->Cell(0, 8, 'No recent errors found in log files.', 0, 1, 'L');
            return;
        }
        
        // Add applied filters info
        $this->add_filters_info($this->filters['errors'] ?? []);
        
        // Add note about error sources
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->Cell(0, 6, 'Showing the most recent errors from WordPress debug logs and server error logs.', 0, 1, 'L');
        $this->pdf->Ln(5);
        
        // Create custom errors table with wrapping messages
        $this->create_errors_table($this->data['errors']);
    }
    
    /**
     * Add system info section
     */
    private function add_info_section(): void {
        $this->pdf->AddPage();
        $this->add_section_header('System Information');
        
        // WordPress Info
        $this->add_subsection_header('WordPress Information');
        $this->add_info_table($this->data['wordpress_info']);
        
        // Constants Info
        $this->add_subsection_header('WordPress Constants');
        $this->add_info_table($this->data['constants_info']);
        
        // Directories Info
        $this->add_subsection_header('Directories and Sizes');
        $this->add_info_table($this->data['directories_info']);
        
        // Server Info
        $this->add_subsection_header('Server Information');
        $this->add_info_table($this->data['server_info']);
    }
    
    /**
     * Add section header
     */
    private function add_section_header(string $title): void {
        $this->pdf->SetFont('helvetica', 'B', 16);
        $this->pdf->Cell(0, 12, $title, 0, 1, 'L');
        $this->pdf->Ln(5);
    }
    
    /**
     * Add subsection header
     */
    private function add_subsection_header(string $title): void {
        $this->pdf->Ln(8);
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, $title, 0, 1, 'L');
        $this->pdf->Ln(2);
    }
    
    /**
     * Add filters info
     */
    private function add_filters_info(array $filters): void {
        if (empty($filters)) {
            return;
        }
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(0, 6, 'Applied Filters:', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 9);
        
        foreach ($filters as $key => $value) {
            if ($value) {
                $this->pdf->Cell(0, 5, '• ' . ucfirst(str_replace('_', ' ', $key)) . ': ' . ucfirst($value), 0, 1, 'L');
            }
        }
        
        $this->pdf->Ln(5);
    }
    
    /**
     * Create errors table with wrapping message column
     */
    private function create_errors_table(array $data): void {
        if (empty($data)) {
            return;
        }
        
        // Table header
        $this->pdf->SetFont('helvetica', 'B', 8);
        $this->pdf->SetFillColor(240, 240, 240);
        
        // Column widths
        $col_widths = [30, 20, 20, 85, 45]; // datetime, log_type, level, message, file
        $headers = ['Date/Time', 'Log Type', 'Level', 'Message', 'File/Location'];
        
        foreach ($headers as $i => $header) {
            $this->pdf->Cell($col_widths[$i], 8, $header, 1, 0, 'C', true);
        }
        $this->pdf->Ln();
        
        // Disable auto page breaks for manual control
        $this->pdf->SetAutoPageBreak(false);
        
        // Table data
        $this->pdf->SetFont('helvetica', '', 7);
        $this->pdf->SetFillColor(255, 255, 255);
        
        foreach ($data as $row) {
            $message = $row['message'];
            
            // Limit message to approximately 4 lines (roughly 280 characters for 85mm column)
            if (strlen($message) > 280) {
                $message = substr($message, 0, 277) . '...';
            }
            
            // Calculate how many lines the message will need
            $message_lines = ceil(strlen($message) / 70); // Roughly 70 chars per line in 85mm column
            $message_lines = min($message_lines, 4); // Max 4 lines
            
            // Calculate row height (4mm per line + padding)
            $row_height = max(8, $message_lines * 4 + 2);
            
            // Check if we need a new page (row height + margin)
            if (($this->pdf->GetY() + $row_height) > ($this->pdf->getPageHeight() - $this->pdf->getBreakMargin())) {
                $this->pdf->AddPage();
                
                // Redraw table header on new page
                $this->pdf->SetFont('helvetica', 'B', 8);
                $this->pdf->SetFillColor(240, 240, 240);
                
                foreach ($headers as $i => $header) {
                    $this->pdf->Cell($col_widths[$i], 8, $header, 1, 0, 'C', true);
                }
                $this->pdf->Ln();
                
                // Reset font for data
                $this->pdf->SetFont('helvetica', '', 7);
                $this->pdf->SetFillColor(255, 255, 255);
            }
            
            // Store current position
            $start_y = $this->pdf->GetY();
            $start_x = $this->pdf->GetX();
            
            // Date/Time column with border
            $this->pdf->Cell($col_widths[0], $row_height, $row['datetime'], 1, 0, 'L', true);
            
            // Log Type column with border
            $this->pdf->Cell($col_widths[1], $row_height, $row['log_type'], 1, 0, 'C', true);
            
            // Level column with border
            $this->pdf->Cell($col_widths[2], $row_height, $row['level'], 1, 0, 'C', true);
            
            // Message column - useRect for border and write text manually
            $msg_x = $this->pdf->GetX();
            $msg_y = $this->pdf->GetY();
            
            // Draw border for message cell
            $this->pdf->Rect($msg_x, $msg_y, $col_widths[3], $row_height, 'D');
            
            // Add text with wrapping inside the cell
            $this->pdf->SetXY($msg_x + 1, $msg_y + 1); // Small padding
            
            // Split message into lines that fit
            $words = explode(' ', $message);
            $lines = [];
            $current_line = '';
            
            foreach ($words as $word) {
                $test_line = $current_line . ($current_line ? ' ' : '') . $word;
                if ($this->pdf->GetStringWidth($test_line) <= ($col_widths[3] - 2)) {
                    $current_line = $test_line;
                } else {
                    if ($current_line) $lines[] = $current_line;
                    $current_line = $word;
                    if (count($lines) >= 3) break; // Max 4 lines (3 + current)
                }
            }
            if ($current_line) $lines[] = $current_line;
            
            // Write each line
            foreach ($lines as $i => $line) {
                if ($i < 4) { // Max 4 lines
                    $this->pdf->SetXY($msg_x + 1, $msg_y + 1 + ($i * 3));
                    $this->pdf->Cell($col_widths[3] - 2, 3, $line, 0, 0, 'L', false);
                }
            }
            
            // File column with border
            $file_x = $msg_x + $col_widths[3];
            $file_path = $row['file'];
            if (strlen($file_path) > 25) {
                $file_path = substr($file_path, 0, 22) . '...';
            }
            
            $this->pdf->SetXY($file_x, $msg_y);
            $this->pdf->Cell($col_widths[4], $row_height, $file_path, 1, 0, 'L', true);
            
            // Move to next row
            $this->pdf->SetXY($start_x, $start_y + $row_height);
        }
        
        // Re-enable auto page breaks
        $this->pdf->SetAutoPageBreak(true, 25);
        
        $this->pdf->Ln(5);
    }
    
    /**
     * Create data table
     */
    private function create_table(array $headers, array $data, array $widths, callable $formatter): void {
        // Table header
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->SetFillColor(240, 240, 240);
        
        foreach ($headers as $i => $header) {
            $width = array_values($widths)[$i];
            $this->pdf->Cell($width, 8, $header, 1, 0, 'C', true);
        }
        $this->pdf->Ln();
        
        // Table data
        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->SetFillColor(255, 255, 255);
        
        foreach ($data as $row) {
            $formatted_row = $formatter($row);
            
            foreach ($formatted_row as $i => $cell) {
                $width = array_values($widths)[$i];
                $this->pdf->Cell($width, 6, (string) $cell, 1, 0, 'L', true);
            }
            $this->pdf->Ln();
        }
        
        $this->pdf->Ln(5);
    }
    
    /**
     * Add info table for key-value pairs
     */
    private function add_info_table(array $data): void {
        $this->pdf->SetFont('helvetica', '', 9);
        
        foreach ($data as $key => $value) {
            // Key column
            $this->pdf->SetFont('helvetica', 'B', 9);
            $this->pdf->Cell(70, 5, (string) $key, 1, 0, 'L', true);
            
            // Value column
            $this->pdf->SetFont('helvetica', '', 9);
            $this->pdf->Cell(0, 5, (string) $value, 1, 1, 'L', true);
        }
        
        $this->pdf->Ln(3);
    }
}