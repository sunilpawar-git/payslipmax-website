<?php
/**
 * Pending Uploads Endpoint
 * 
 * This endpoint returns a list of pending uploads for a specific device.
 * It requires authentication via the device token.
 */

// Require database connection and helpers
require_once 'db_config.php';
require_once 'helpers.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit();
}

try {
    // Extract device token from Authorization header
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        throw new Exception('Missing or invalid Authorization header');
    }
    
    $deviceToken = trim($matches[1]);
    
    // Validate device token
    if (empty($deviceToken) || strlen($deviceToken) < 32) {
        throw new Exception('Invalid device token');
    }
    
    // Connect to database
    $db = get_database_connection();
    
    // Verify device exists
    $stmt = $db->prepare('SELECT id FROM devices WHERE device_token = ? AND is_active = 1');
    $stmt->bind_param('s', $deviceToken);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Device not found or inactive');
    }
    
    $deviceId = $result->fetch_assoc()['id'];
    
    // Get pending uploads for this device
    $stmt = $db->prepare('SELECT 
                            id, string_id, filename, uploaded_at, file_size, 
                            is_password_protected, source, secure_token
                          FROM uploads 
                          WHERE device_id = ? AND status = ?
                          ORDER BY uploaded_at DESC
                          LIMIT 50');
    
    $status = 'pending'; // Only get pending uploads
    $stmt->bind_param('is', $deviceId, $status);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $uploads = [];
    while ($row = $result->fetch_assoc()) {
        $uploads[] = [
            'id' => $row['id'],
            'stringID' => $row['string_id'],
            'filename' => $row['filename'],
            'uploadedAt' => $row['uploaded_at'],
            'fileSize' => (int) $row['file_size'],
            'isPasswordProtected' => (bool) $row['is_password_protected'],
            'source' => $row['source'],
            'secureToken' => $row['secure_token'],
            'status' => 'pending'
        ];
    }
    
    // Log activity
    log_activity('pending_uploads_fetched', [
        'device_id' => $deviceId,
        'count' => count($uploads)
    ]);
    
    // Return success response with uploads
    echo json_encode($uploads);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    
    // Log error
    log_error('fetch_pending_uploads_failed', $e->getMessage());
} 
