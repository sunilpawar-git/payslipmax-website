<?php
/**
 * Device Registration Endpoint
 * 
 * This endpoint handles registering mobile devices for receiving web uploads.
 * It generates a unique device token that can be used to associate uploads with specific devices.
 */

// Require database connection and helpers
require_once 'db_config.php';
require_once 'helpers.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit();
}

try {
    // Get request body as JSON
    $json_str = file_get_contents('php://input');
    $data = json_decode($json_str, true);
    
    // Validate required fields
    if (empty($data['deviceName']) || empty($data['deviceType']) || empty($data['osVersion'])) {
        throw new Exception('Missing required device information');
    }
    
    // Extract device info
    $deviceName = $data['deviceName'];
    $deviceType = $data['deviceType'];
    $osVersion = $data['osVersion'];
    $appVersion = isset($data['appVersion']) ? $data['appVersion'] : 'Unknown';
    
    // Generate a unique device token (secure random string)
    $deviceToken = generate_secure_token(32);
    
    // Connect to database
    $db = get_database_connection();
    
    // Store device information in database
    $stmt = $db->prepare('INSERT INTO devices (device_token, device_name, device_type, os_version, app_version, created_at) 
                          VALUES (?, ?, ?, ?, ?, NOW())');
    $stmt->bind_param('sssss', $deviceToken, $deviceName, $deviceType, $osVersion, $appVersion);
    $stmt->execute();
    
    if ($stmt->affected_rows <= 0) {
        throw new Exception('Failed to register device');
    }
    
    // Log device registration
    log_activity('device_registered', [
        'device_token' => $deviceToken,
        'device_name' => $deviceName,
        'device_type' => $deviceType
    ]);
    
    // Return success response with device token
    echo json_encode([
        'status' => 'success',
        'deviceToken' => $deviceToken
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    
    // Log error
    log_error('device_registration_failed', $e->getMessage());
} 
