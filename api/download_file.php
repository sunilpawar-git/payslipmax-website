<?php
/**
 * File Download Endpoint
 * 
 * This endpoint allows the app to download files that have been uploaded via the website.
 * It requires authentication via a secure token specific to each file.
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
    // Extract required parameters
    $uploadId = isset($_GET['id']) ? $_GET['id'] : '';
    $secureToken = isset($_GET['token']) ? $_GET['token'] : '';
    
    // Validate parameters
    if (empty($uploadId)) {
        throw new Exception('Missing upload ID');
    }
    
    if (empty($secureToken)) {
        throw new Exception('Missing secure token');
    }
    
    // Connect to database
    $db = get_database_connection();
    
    // Find the upload
    $stmt = $db->prepare('SELECT id, filename, file_path, secure_token, is_password_protected 
                          FROM uploads 
                          WHERE (id = ? OR string_id = ?) AND status = ?');
    
    $status = 'pending';
    $stmt->bind_param('sss', $uploadId, $uploadId, $status);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('File not found or already processed');
    }
    
    $upload = $result->fetch_assoc();
    
    // Verify secure token
    if ($upload['secure_token'] !== $secureToken) {
        throw new Exception('Invalid secure token');
    }
    
    // Get file path
    $filePath = $upload['file_path'];
    
    // Verify file exists
    if (!file_exists($filePath)) {
        throw new Exception('File not found on server');
    }
    
    // Log download
    log_activity('file_downloaded', [
        'upload_id' => $upload['id'],
        'filename' => $upload['filename']
    ]);
    
    // Update status to 'downloading'
    $stmt = $db->prepare('UPDATE uploads SET status = ? WHERE id = ?');
    $status = 'downloading';
    $stmt->bind_param('ss', $status, $upload['id']);
    $stmt->execute();
    
    // Serve the file
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $upload['filename'] . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    
    // Output file content
    readfile($filePath);
    exit();
    
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    
    // Log error
    log_error('file_download_failed', $e->getMessage());
} 