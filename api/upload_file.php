<?php
/**
 * File Upload Endpoint
 * 
 * This endpoint handles file uploads from the website and creates
 * records that can be accessed by registered devices.
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Require database connection and helpers
require_once 'db_config.php';
require_once 'helpers.php';

// Function to log detailed debug information
function log_debug($data, $label = 'DEBUG') {
    $logFile = __DIR__ . '/upload_debug.log';
    $logMessage = "[" . date('Y-m-d H:i:s') . "] $label: " . print_r($data, true) . "\n\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Log incoming request data
log_debug([
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'post_data' => $_POST,
    'files' => array_map(function($file) {
        return [
            'name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type'],
            'error' => $file['error']
        ];
    }, $_FILES),
    'headers' => getallheaders()
], 'New Request');

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
        $errorMsg = 'No file was uploaded or file upload failed. ';
        $errorMsg .= '$_FILES content: ' . print_r($_FILES, true);
        log_debug($errorMsg, 'File Upload Error');
        throw new Exception('No file was uploaded or file upload failed.');
    }
    
    $file = $_FILES['file'];
    log_debug($file, 'File upload data');
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        
        $errorCode = $file['error'];
        $errorMessage = $errorMessages[$errorCode] ?? 'Unknown upload error';
        $errorDetails = 'File upload error: ' . $errorMessage . ' (code: ' . $errorCode . ')';
        log_debug($errorDetails, 'Upload Error');
        throw new Exception($errorDetails);
    }
    
    // Verify the uploaded file is a PDF
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if ($mimeType !== 'application/pdf') {
        $errorMsg = 'Invalid file type. Only PDF files are allowed. Detected type: ' . $mimeType;
        log_debug($errorMsg, 'File Validation Error');
        throw new Exception($errorMsg);
    }
    
    // Check file size (2MB limit)
    $maxFileSize = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxFileSize) {
        $errorMsg = 'File is too large. Maximum allowed size is 2MB.';
        log_debug($errorMsg, 'File Size Error');
        throw new Exception($errorMsg);
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
    $isPasswordProtected = !empty($password) ? 1 : 0; // Convert to integer for database
    
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
    
    $source = 'website';
    $status = 'pending';
    
    log_debug([
        'string_id' => $stringId,
        'file_name' => $fileName,
        'file_path' => $filePath,
        'file_size' => $fileSize,
        'is_password_protected' => $isPasswordProtected,
        'secure_token' => $secureToken,
        'source' => $source,
        'status' => $status
    ], 'Preparing to save upload');
    
    // Insert upload into database
    $query = 'INSERT INTO uploads 
              (string_id, filename, file_path, file_size, is_password_protected, 
               secure_token, source, status, uploaded_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())';
    
    log_debug($query, 'Database Query');
    
    $stmt = $db->prepare($query);
    
    if ($stmt === false) {
        throw new Exception('Failed to prepare statement: ' . $db->error);
    }
    
    // Bind parameters - fixed type string to match parameters
    $bound = $stmt->bind_param('sssiisss', 
        $stringId, 
        $fileName, 
        $filePath, 
        $fileSize, 
        $isPasswordProtected, 
        $secureToken,
        $source,
        $status
    );
    
    if ($bound === false) {
        throw new Exception('Failed to bind parameters: ' . $stmt->error);
    }
    
    $executed = $stmt->execute();
    
    if ($executed === false) {
        throw new Exception('Failed to execute statement: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows <= 0) {
        throw new Exception('No rows affected. Upload record not saved.');
    }
    
    $uploadId = $stmt->insert_id;
    log_debug(['upload_id' => $uploadId], 'Upload record created successfully');
    
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
        'success' => true,
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