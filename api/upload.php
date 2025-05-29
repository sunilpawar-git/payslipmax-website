<?php
/**
 * File Upload Endpoint
 * 
 * This endpoint handles file uploads from the website and creates
 * records that can be accessed by registered devices.
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
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }
    
    // Validate file type (only accept PDFs)
    $file = $_FILES['file'];
    $fileName = basename($file['name']);
    $fileSize = $file['size'];
    $fileTmpPath = $file['tmp_name'];
    
    // Check file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($fileExt !== 'pdf') {
        throw new Exception('Only PDF files are allowed');
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $fileMimeType = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);
    
    if ($fileMimeType !== 'application/pdf') {
        throw new Exception('Invalid file type. Only PDF files are allowed.');
    }
    
    // Check if device token was provided
    $deviceToken = isset($_POST['device_token']) ? $_POST['device_token'] : '';
    
    // Check if password was provided
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $isPasswordProtected = !empty($password);
    
    // Connect to database
    $db = get_database_connection();
    
    // Get device ID from token
    $deviceId = null;
    
    if (!empty($deviceToken)) {
        $stmt = $db->prepare('SELECT id FROM devices WHERE device_token = ? AND is_active = 1');
        $stmt->bind_param('s', $deviceToken);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $deviceId = $result->fetch_assoc()['id'];
        }
    }
    
    // Generate unique string ID for this upload
    $stringId = generate_string_id(10);
    
    // Generate a secure token for this upload
    $secureToken = generate_secure_token(32);
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/uploads';
    if (!file_exists($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    // Generate a unique filename
    $uniqueFilename = uniqid() . '_' . $fileName;
    $filePath = $uploadsDir . '/' . $uniqueFilename;
    
    // Move uploaded file to storage directory
    if (!move_uploaded_file($fileTmpPath, $filePath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // If file is password protected, store the password securely
    if ($isPasswordProtected) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $passwordFilePath = $uploadsDir . '/' . pathinfo($uniqueFilename, PATHINFO_FILENAME) . '.pwd';
        file_put_contents($passwordFilePath, $passwordHash);
    }
    
    // Insert upload into database
    $stmt = $db->prepare('INSERT INTO uploads 
                          (string_id, filename, file_path, file_size, is_password_protected, 
                           secure_token, device_id, source, status, uploaded_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    
    $source = 'website';
    $status = 'pending';
    
    $stmt->bind_param('sssisissss', 
                      $stringId, 
                      $fileName, 
                      $filePath, 
                      $fileSize, 
                      $isPasswordProtected, 
                      $secureToken,
                      $deviceId,
                      $source,
                      $status);
    $stmt->execute();
    
    if ($stmt->affected_rows <= 0) {
        throw new Exception('Failed to store upload in database');
    }
    
    $uploadId = $stmt->insert_id;
    
    // Log activity
    log_activity('file_uploaded', [
        'upload_id' => $uploadId,
        'filename' => $fileName,
        'size' => $fileSize,
        'device_id' => $deviceId
    ]);
    
    // Generate deep link for the app
    $deepLink = 'payslipmax://upload' . 
                '?id=' . urlencode($stringId) . 
                '&filename=' . urlencode($fileName) . 
                '&size=' . $fileSize . 
                '&source=website' . 
                '&token=' . $secureToken . 
                '&protected=' . ($isPasswordProtected ? 'true' : 'false');
    
    // Generate QR code URL for the deep link
    $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($deepLink);
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'uploadId' => $stringId,
        'filename' => $fileName,
        'fileSize' => $fileSize,
        'isPasswordProtected' => $isPasswordProtected,
        'deepLink' => $deepLink,
        'qrCode' => $qrCodeUrl
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    
    // Log error
    log_error('file_upload_failed', $e->getMessage());
} 
