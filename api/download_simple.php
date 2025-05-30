<?php
/**
 * Simple File Download Endpoint for Testing
 * 
 * This endpoint works with the file-based upload system for local testing.
 */

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
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
    
    // For file-based system, look for the file in uploads directory
    $uploadsDir = __DIR__ . '/uploads';
    
    // Read the upload log to find the file
    $logFile = $uploadsDir . '/upload.log';
    
    if (!file_exists($logFile)) {
        throw new Exception('Upload log not found');
    }
    
    $logContents = file_get_contents($logFile);
    $logLines = explode("\n", $logContents);
    
    $foundFile = null;
    $foundFilename = null;
    
    // Search through log entries
    foreach ($logLines as $line) {
        if (empty(trim($line))) continue;
        
        $logEntry = json_decode($line, true);
        if ($logEntry && isset($logEntry['upload_id']) && $logEntry['upload_id'] === $uploadId) {
            // Found the upload entry, now find the actual file
            $loggedFilename = $logEntry['filename'];
            
            // Get all PDF files in the uploads directory
            $allFiles = glob($uploadsDir . '/*.pdf');
            
            // Find the file that ends with our logged filename
            foreach ($allFiles as $file) {
                $basename = basename($file);
                // Use substr instead of str_ends_with for PHP compatibility
                if (substr($basename, -strlen($loggedFilename)) === $loggedFilename) {
                    $foundFile = $file;
                    $foundFilename = $loggedFilename; // Use original filename for download
                    break;
                }
            }
            
            if ($foundFile) {
                break;
            }
        }
    }
    
    if (!$foundFile || !file_exists($foundFile)) {
        throw new Exception('File not found on server');
    }
    
    // Clear any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Serve the file
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $foundFilename . '"');
    header('Content-Length: ' . filesize($foundFile));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    
    // Output file content
    readfile($foundFile);
    exit();
    
} catch (Exception $e) {
    // Clear any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Return error response
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 