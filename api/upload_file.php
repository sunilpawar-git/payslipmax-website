<?php
/**
 * PayslipMax File Upload Endpoint - Production Version
 * Simplified to remove dependencies that might cause issues
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper headers
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Only POST is accepted.'
    ]);
    exit();
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
        throw new Exception('No file was uploaded or file upload failed.');
    }
    
    $file = $_FILES['file'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File too large (exceeds server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (exceeds form limit)',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $errorMessage = $errorMessages[$file['error']] ?? 'Unknown upload error';
        throw new Exception($errorMessage);
    }
    
    // Validate file type
    $allowedMimeTypes = ['application/pdf'];
    if (!in_array($file['type'], $allowedMimeTypes)) {
        throw new Exception('Invalid file type. Only PDF files are allowed. Got: ' . $file['type']);
    }
    
    // Check file extension
    $fileName = basename($file['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($fileExt !== 'pdf') {
        throw new Exception('Invalid file extension. Only .pdf files are allowed.');
    }
    
    // Check file size (15MB limit)
    $maxFileSize = 15 * 1024 * 1024; // 15MB
    if ($file['size'] > $maxFileSize) {
        throw new Exception('File is too large. Maximum allowed size is 15MB.');
    }
    
    // Validate PDF signature
    $handle = fopen($file['tmp_name'], 'rb');
    if ($handle) {
        $header = fread($handle, 8);
        fclose($handle);
        
        if (strpos($header, '%PDF') !== 0) {
            throw new Exception('File does not appear to be a valid PDF document.');
        }
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/uploads';
    if (!file_exists($uploadsDir)) {
        if (!mkdir($uploadsDir, 0755, true)) {
            throw new Exception('Failed to create uploads directory.');
        }
    }
    
    // Generate unique filename
    $uniqueFilename = uniqid() . '_' . time() . '_' . $fileName;
    $filePath = $uploadsDir . '/' . $uniqueFilename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to save uploaded file.');
    }
    
    // Generate response data
    $uploadId = uniqid('upload_', true);
    $uploadTime = date('Y-m-d H:i:s');
    
    // Get optional parameters
    $deviceToken = isset($_POST['device_token']) ? trim($_POST['device_token']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Handle password protection
    if (!empty($password)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $passwordFile = $uploadsDir . '/' . pathinfo($uniqueFilename, PATHINFO_FILENAME) . '.pwd';
        file_put_contents($passwordFile, $passwordHash);
    }
    
    // Log upload (simple file-based logging)
    $logData = [
        'upload_id' => $uploadId,
        'filename' => $fileName,
        'size' => $file['size'],
        'timestamp' => $uploadTime,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    $logFile = $uploadsDir . '/upload.log';
    @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    
    // Create deep link for mobile app with all necessary parameters
    $deepLink = "payslipmax://upload?" . http_build_query([
        'id' => $uploadId,
        'filename' => $fileName,
        'size' => $file['size'],
        'timestamp' => time(),
        'hash' => hash('sha256', $uploadId . $fileName . $file['size'])
    ]);
    
    // Generate QR code URL using a reliable QR code service
    $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
        'size' => '200x200',
        'data' => $deepLink,
        'format' => 'png',
        'bgcolor' => 'ffffff',
        'color' => '000000',
        'qzone' => '1',
        'margin' => '10'
    ]);
    
    // Success response
    echo json_encode([
        'status' => 'success',
        'message' => 'File uploaded successfully!',
        'uploadId' => $uploadId,
        'filename' => $fileName,
        'fileSize' => $file['size'],
        'uploadTime' => $uploadTime,
        'deepLink' => $deepLink,
        'qrCode' => $qrCodeUrl,
        'hasPassword' => !empty($password)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    
    // Log error
    $errorLog = __DIR__ . '/uploads/error.log';
    $errorData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage(),
        'file_data' => $_FILES['file'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    @file_put_contents($errorLog, json_encode($errorData) . "\n", FILE_APPEND | LOCK_EX);
}
?> 