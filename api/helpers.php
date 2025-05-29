<?php
/**
 * Helper Functions
 * 
 * This file contains common utility functions used throughout the API.
 */
 
// Include the database configuration
require_once __DIR__ . '/db_config.php';

// Generate a secure random token
function generate_secure_token($length = 32) {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length / 2));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes($length / 2));
    } else {
        // Fallback to less secure method if neither is available
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $index = mt_rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
        
        return $randomString;
    }
}

// Generate a readable ID string (for user-friendly IDs)
function generate_string_id($length = 8) {
    // Use only alphanumeric characters that are unambiguous
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $result = '';
    
    for ($i = 0; $i < $length; $i++) {
        $result .= $characters[random_int(0, strlen($characters) - 1)];
    }
    
    return $result;
}

// Log application activity
function log_activity($activity_type, $activity_data = null) {
    try {
        // Skip logging if database connection fails
        try {
            $db = get_database_connection();
        } catch (Exception $e) {
            error_log('Failed to get database connection in log_activity: ' . $e->getMessage());
            return false;
        }
        
        // Prepare the activity data as JSON
        $activity_json = null;
        if ($activity_data !== null) {
            if (is_array($activity_data) || is_object($activity_data)) {
                $activity_json = json_encode($activity_data);
                if ($activity_json === false) {
                    $activity_json = json_encode(['error' => 'Failed to encode activity data']);
                }
            } else {
                $activity_json = (string)$activity_data;
            }
        }
        
        // Get IP address and user agent
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $db->prepare('INSERT INTO activity_log 
                             (activity_type, activity_data, ip_address, user_agent, created_at) 
                             VALUES (?, ?, ?, ?, NOW())');
        
        $stmt->bind_param('ssss', 
            $activity_type,
            $activity_json,
            $ip_address,
            $user_agent
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute activity log query: ' . $stmt->error);
        }
        
        $db->close();
    } catch (Exception $e) {
        // If database logging fails, log to file
        error_log('Activity log failed: ' . $e->getMessage());
    }
}

// Log application errors
function log_error($error_type, $error_message, $stack_trace = null) {
    try {
        $db = get_database_connection();
        
        $stmt = $db->prepare('INSERT INTO error_log 
                             (error_type, error_message, stack_trace, ip_address, user_agent, created_at) 
                             VALUES (?, ?, ?, ?, ?, NOW())');
        
        $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        
        $stmt->bind_param('sssss', $error_type, $error_message, $stack_trace, $ipAddress, $userAgent);
        $stmt->execute();
        
        $db->close();
    } catch (Exception $e) {
        // If database logging fails, log to file
        error_log('Error log failed: ' . $e->getMessage());
        error_log('Original error: ' . $error_type . ' - ' . $error_message);
    }
}

// Clean expired uploads (files older than a certain time)
function clean_expired_uploads($days_to_keep = 7) {
    try {
        $db = get_database_connection();
        
        // Get list of expired uploads
        $stmt = $db->prepare('SELECT id, file_path FROM uploads 
                              WHERE status = ? AND uploaded_at < DATE_SUB(NOW(), INTERVAL ? DAY)');
        
        $status = 'processed';
        $stmt->bind_param('si', $status, $days_to_keep);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $deleted_count = 0;
        
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
            
            $deleted_count++;
        }
        
        $db->close();
        
        return $deleted_count;
    } catch (Exception $e) {
        error_log('Clean expired uploads failed: ' . $e->getMessage());
        return 0;
    }
}

// Validate password for a password-protected file
function validate_file_password($upload_id, $password) {
    try {
        $db = get_database_connection();
        
        // Get file info
        $stmt = $db->prepare('SELECT file_path, is_password_protected FROM uploads WHERE id = ? OR string_id = ?');
        $stmt->bind_param('ss', $upload_id, $upload_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $upload = $result->fetch_assoc();
        
        // If not password protected, always return true
        if (!$upload['is_password_protected']) {
            return true;
        }
        
        // Check for password file
        $passwordFile = dirname($upload['file_path']) . '/' . pathinfo($upload['file_path'], PATHINFO_FILENAME) . '.pwd';
        
        if (!file_exists($passwordFile)) {
            return false;
        }
        
        // Get stored password hash
        $passwordHash = file_get_contents($passwordFile);
        
        // Verify password
        return password_verify($password, $passwordHash);
        
    } catch (Exception $e) {
        error_log('Password validation failed: ' . $e->getMessage());
        return false;
    }
} 
