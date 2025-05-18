<?php
/**
 * Cleanup Script
 * 
 * This script should be run periodically via cron job to clean up old files
 * and perform other maintenance tasks.
 * 
 * Recommended cron schedule: 0 2 * * * /usr/bin/php /path/to/cleanup.php
 * (This runs the script every day at 2:00 AM)
 */

// Require database connection and helpers
require_once 'db_config.php';
require_once 'helpers.php';

// Start time for performance tracking
$start_time = microtime(true);

// Initialize log
$log = [];
$log[] = 'Starting cleanup at ' . date('Y-m-d H:i:s');

try {
    // Clean up files older than 7 days that have been processed
    $deleted_count = clean_expired_uploads(7);
    $log[] = "Deleted $deleted_count processed uploads";
    
    // Clean up failed uploads older than 1 day
    try {
        $db = get_database_connection();
        
        // First, get list of failed uploads
        $stmt = $db->prepare('SELECT id, file_path FROM uploads 
                              WHERE status = ? AND uploaded_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
        
        $status = 'failed';
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $failed_deleted = 0;
        
        while ($row = $result->fetch_assoc()) {
            $filePath = $row['file_path'];
            
            // Delete the file if it exists
            if (file_exists($filePath)) {
                unlink($filePath);
                
                // Check for password file
                $passwordFile = dirname($filePath) . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.pwd';
                if (file_exists($passwordFile)) {
                    unlink($passwordFile);
                }
            }
            
            // Delete from database
            $deleteStmt = $db->prepare('DELETE FROM uploads WHERE id = ?');
            $deleteStmt->bind_param('i', $row['id']);
            $deleteStmt->execute();
            
            $failed_deleted++;
        }
        
        $log[] = "Deleted $failed_deleted failed uploads";
        
        // Clean up orphaned files in the uploads directory
        $uploadsDir = __DIR__ . '/uploads';
        $files = scandir($uploadsDir);
        $orphaned_deleted = 0;
        
        foreach ($files as $file) {
            // Skip . and .. directories
            if ($file === '.' || $file === '..' || $file === '.htaccess') {
                continue;
            }
            
            // Skip password files for now
            if (pathinfo($file, PATHINFO_EXTENSION) === 'pwd') {
                continue;
            }
            
            $filePath = $uploadsDir . '/' . $file;
            
            // Check if file exists in database
            $stmt = $db->prepare('SELECT id FROM uploads WHERE file_path = ?');
            $stmt->bind_param('s', $filePath);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // If not in database, delete it
            if ($result->num_rows === 0) {
                if (unlink($filePath)) {
                    $orphaned_deleted++;
                    
                    // Also delete any password file
                    $passwordFile = $uploadsDir . '/' . pathinfo($file, PATHINFO_FILENAME) . '.pwd';
                    if (file_exists($passwordFile)) {
                        unlink($passwordFile);
                    }
                }
            }
        }
        
        $log[] = "Deleted $orphaned_deleted orphaned files";
        
        // Clean up old log entries (older than 30 days)
        $stmt = $db->prepare('DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
        $stmt->execute();
        $activity_deleted = $stmt->affected_rows;
        $log[] = "Deleted $activity_deleted old activity log entries";
        
        $stmt = $db->prepare('DELETE FROM error_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
        $stmt->execute();
        $error_deleted = $stmt->affected_rows;
        $log[] = "Deleted $error_deleted old error log entries";
        
        $db->close();
        
    } catch (Exception $e) {
        $log[] = 'Error cleaning up files: ' . $e->getMessage();
    }
    
    // Calculate execution time
    $execution_time = microtime(true) - $start_time;
    $log[] = 'Cleanup completed in ' . round($execution_time, 2) . ' seconds';
    
    // Write log to file
    $log_message = implode("\n", $log);
    file_put_contents(__DIR__ . '/logs/cleanup_' . date('Y-m-d') . '.log', $log_message);
    
    // Output log if running from command line
    if (php_sapi_name() === 'cli') {
        echo $log_message . "\n";
    }
    
} catch (Exception $e) {
    $error_message = 'Critical error during cleanup: ' . $e->getMessage();
    error_log($error_message);
    
    if (php_sapi_name() === 'cli') {
        echo $error_message . "\n";
    }
} 
