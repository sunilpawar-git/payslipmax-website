<?php
/**
 * API Status Page
 * 
 * Provides information about the PayslipMax API and its status.
 */

// Disable direct output for security
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    // Only show limited information if directly accessed
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'online',
        'api' => 'PayslipMax Web Upload API',
        'version' => '1.0',
        'documentation' => 'Contact support for API documentation'
    ]);
    exit();
}

// For internal use
function get_api_status() {
    // Check if database is available
    $db_status = 'unknown';
    
    try {
        require_once 'db_config.php';
        $db = get_database_connection();
        if ($db->ping()) {
            $db_status = 'connected';
        } else {
            $db_status = 'disconnected';
        }
        $db->close();
    } catch (Exception $e) {
        $db_status = 'error: ' . $e->getMessage();
    }
    
    // Check uploads directory
    $uploads_dir = __DIR__ . '/uploads';
    $uploads_status = 'not found';
    
    if (file_exists($uploads_dir)) {
        if (is_writable($uploads_dir)) {
            $uploads_status = 'writable';
        } else {
            $uploads_status = 'not writable';
        }
    }
    
    // Check logs directory
    $logs_dir = __DIR__ . '/logs';
    $logs_status = 'not found';
    
    if (file_exists($logs_dir)) {
        if (is_writable($logs_dir)) {
            $logs_status = 'writable';
        } else {
            $logs_status = 'not writable';
        }
    }
    
    return [
        'status' => 'online',
        'api' => 'PayslipMax Web Upload API',
        'version' => '1.0',
        'time' => date('Y-m-d H:i:s'),
        'environment' => [
            'php_version' => phpversion(),
            'database' => $db_status,
            'uploads_directory' => $uploads_status,
            'logs_directory' => $logs_status
        ]
    ];
} 
