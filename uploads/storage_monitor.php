<?php
// storage_monitor.php
$upload_dir = 'uploads/';
$alert_threshold_mb = 100; // Alert when uploads directory exceeds 100MB

// Calculate directory size
function get_directory_size($path) {
    $size = 0;
    $files = scandir($path);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        if (is_dir($path . '/' . $file)) {
            $size += get_directory_size($path . '/' . $file);
        } else {
            $size += filesize($path . '/' . $file);
        }
    }
    return $size;
}

$size_bytes = get_directory_size($upload_dir);
$size_mb = $size_bytes / (1024 * 1024); // Convert to MB

if ($size_mb > $alert_threshold_mb) {
    // Send an email alert
    mail('mail.sunilpawar@gmail.com', 'Storage Alert: PayslipMax Upload Directory', 
        "The uploads directory has reached {$size_mb}MB, exceeding the threshold of {$alert_threshold_mb}MB.");
    
    // Log the alert
    file_put_contents('storage_alerts.log', date('Y-m-d H:i:s') . " - SIZE: {$size_mb}MB\n", FILE_APPEND);
}

// Output for manual checking
echo "Current storage usage: " . round($size_mb, 2) . "MB";
?>