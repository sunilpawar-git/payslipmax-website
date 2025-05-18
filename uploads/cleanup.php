<?php
// File: cleanup.php
// Purpose: Remove uploaded files older than the retention period

// Configuration
$upload_dir = 'uploads/'; // Path to your uploads directory 
$retention_days = 7;      // Keep files for 7 days
$cutoff_time = time() - (86400 * $retention_days); // 86400 seconds = 1 day
$log_file = 'cleanup_log.txt'; // Log file to record cleanup activities

// Initialize counters
$deleted_count = 0;
$error_count = 0;
$scanned_count = 0;

// Open log file
$log = fopen($log_file, 'a');
fwrite($log, "=== Cleanup started at " . date('Y-m-d H:i:s') . " ===\n");

// Scan directory
if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    
    foreach ($files as $file) {
        // Skip directory entries
        if ($file === '.' || $file === '..') continue;
        
        $file_path = $upload_dir . $file;
        $scanned_count++;
        
        // Only process files (not directories)
        if (is_file($file_path)) {
            $file_time = filemtime($file_path);
            
            // If file is older than retention period, delete it
            if ($file_time < $cutoff_time) {
                $file_info = pathinfo($file_path);
                $base_name = $file_info['filename'];
                $extension = isset($file_info['extension']) ? $file_info['extension'] : '';
                
                // Get the file's upload ID (the base name without extension)
                $upload_id = $base_name;
                
                // For files like '12345abc.json', we need the base name
                if ($extension === 'json' || $extension === 'pdf' || $extension === 'pwd') {
                    // Find and delete all related files with this upload ID
                    $related_files = [
                        $upload_dir . $upload_id . '.pdf',
                        $upload_dir . $upload_id . '.json',
                        $upload_dir . $upload_id . '.pwd'
                    ];
                    
                    foreach ($related_files as $related_file) {
                        if (file_exists($related_file)) {
                            if (unlink($related_file)) {
                                fwrite($log, "Deleted: $related_file\n");
                                $deleted_count++;
                            } else {
                                fwrite($log, "ERROR: Failed to delete $related_file\n");
                                $error_count++;
                            }
                        }
                    }
                }
            }
        }
    }
} else {
    fwrite($log, "ERROR: Upload directory not found\n");
}

// Write summary
fwrite($log, "Summary: Scanned $scanned_count files, deleted $deleted_count files with $error_count errors\n");
fwrite($log, "=== Cleanup completed at " . date('Y-m-d H:i:s') . " ===\n\n");
fclose($log);

// If this is run from browser, display results
if (php_sapi_name() !== 'cli') {
    echo "<h2>Cleanup Completed</h2>";
    echo "<p>Scanned: $scanned_count files</p>";
    echo "<p>Deleted: $deleted_count files</p>";
    echo "<p>Errors: $error_count</p>";
}
?>