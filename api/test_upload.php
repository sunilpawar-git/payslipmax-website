<?php
/**
 * Test Upload File
 * 
 * This script provides a simple form to test the file upload functionality.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayslipMax Test Upload</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f7fb;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #3B82F6;
            margin-top: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        input[type="text"], 
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        input[type="file"] {
            margin-top: 8px;
        }
        .dropzone {
            border: 2px dashed #3B82F6;
            padding: 40px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: #f8faff;
            cursor: pointer;
        }
        .dropzone p {
            color: #666;
            margin: 0;
        }
        button {
            background-color: #3B82F6;
            color: #fff;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        button:hover {
            background-color: #2563EB;
        }
        .success {
            background-color: #10B981;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .error {
            background-color: #EF4444;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .result {
            margin-top: 30px;
            padding: 20px;
            background-color: #f0f9ff;
            border-radius: 5px;
            border-left: 5px solid #3B82F6;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        .qr-code img {
            max-width: 200px;
        }
        .deep-link {
            background-color: #f0f0f0;
            padding: 12px;
            border-radius: 4px;
            font-family: monospace;
            word-break: break-all;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PayslipMax Test Upload</h1>
        <p>Use this form to test the file upload functionality. You can upload a PDF file and optionally protect it with a password.</p>
        
        <?php
        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'db_config.php';
            require_once 'helpers.php';
            
            try {
                // Check if file was uploaded
                if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('No file uploaded or upload error: ' . $_FILES['pdf_file']['error']);
                }
                
                $file = $_FILES['pdf_file'];
                
                // Validate file type
                if ($file['type'] !== 'application/pdf') {
                    throw new Exception('Only PDF files are allowed');
                }
                
                // Get device token from form
                $deviceToken = isset($_POST['device_token']) ? trim($_POST['device_token']) : '';
                
                // Get password if provided
                $password = isset($_POST['password']) ? trim($_POST['password']) : '';
                $isPasswordProtected = !empty($password);
                
                // Connect to database
                $db = get_database_connection();
                
                // Check if device exists
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
                
                // Generate unique IDs and tokens
                $stringId = generate_string_id(12);
                $secureToken = generate_secure_token(32);
                
                // Create uploads directory if it doesn't exist
                $uploadsDir = __DIR__ . '/uploads';
                if (!file_exists($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                
                // Create a unique filename
                $filename = $stringId . '.pdf';
                $filePath = $uploadsDir . '/' . $filename;
                
                // Move the uploaded file
                if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                    throw new Exception('Failed to save uploaded file');
                }
                
                // If password protected, save password
                if ($isPasswordProtected) {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $passwordFile = $uploadsDir . '/' . $stringId . '.pwd';
                    file_put_contents($passwordFile, $passwordHash);
                }
                
                // Insert into database
                $stmt = $db->prepare('INSERT INTO uploads 
                                     (string_id, filename, file_path, file_size, is_password_protected, 
                                      secure_token, device_id, source, status, uploaded_at) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                
                $originalFilename = $file['name'];
                $fileSize = $file['size'];
                $source = 'web_test';
                $status = 'pending';
                
                $stmt->bind_param('sssisssss', $stringId, $originalFilename, $filePath, $fileSize, 
                                 $isPasswordProtected, $secureToken, $deviceId, $source, $status);
                $stmt->execute();
                
                if ($stmt->affected_rows <= 0) {
                    throw new Exception('Failed to save upload information to database');
                }
                
                // Generate deep link for app
                $appLink = "payslipmax://upload?id=$stringId&filename=" . urlencode($originalFilename) . 
                          "&size=$fileSize&source=web&token=$secureToken&protected=" . 
                          ($isPasswordProtected ? 'true' : 'false');
                
                // Generate QR code
                $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($appLink);
                
                // Success message
                echo '<div class="success">File uploaded successfully!</div>';
                echo '<div class="result">';
                echo '<h2>Upload Details</h2>';
                echo '<p><strong>Filename:</strong> ' . htmlspecialchars($originalFilename) . '</p>';
                echo '<p><strong>File Size:</strong> ' . htmlspecialchars(number_format($fileSize / 1024, 2)) . ' KB</p>';
                echo '<p><strong>Upload ID:</strong> ' . htmlspecialchars($stringId) . '</p>';
                echo '<p><strong>Password Protected:</strong> ' . ($isPasswordProtected ? 'Yes' : 'No') . '</p>';
                
                echo '<h3>Deep Link</h3>';
                echo '<p>Use this deep link to open the file in the PayslipMax app:</p>';
                echo '<div class="deep-link">' . htmlspecialchars($appLink) . '</div>';
                echo '<p><a href="' . htmlspecialchars($appLink) . '">Open in PayslipMax App</a></p>';
                
                echo '<div class="qr-code">';
                echo '<p>Scan this QR code with your device:</p>';
                echo '<img src="' . htmlspecialchars($qrCodeUrl) . '" alt="QR Code">';
                echo '</div>';
                
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                
                // Log error
                if (function_exists('log_error')) {
                    log_error('test_upload_failed', $e->getMessage());
                }
            }
        }
        ?>
        
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="device_token">Device Token (Optional):</label>
                <input type="text" id="device_token" name="device_token" placeholder="Enter device token to associate with a specific device">
                <p>If you don't have a device token, the upload will be available for any device that provides the correct upload ID.</p>
            </div>
            
            <div class="form-group">
                <label for="pdf_file">Upload PDF File:</label>
                <div class="dropzone" id="dropzone">
                    <p>Drag & drop your PDF here or click to browse</p>
                </div>
                <input type="file" id="pdf_file" name="pdf_file" accept="application/pdf" style="display: none;">
            </div>
            
            <div class="form-group">
                <label for="password">Password Protection (Optional):</label>
                <input type="password" id="password" name="password" placeholder="Leave empty for no password">
            </div>
            
            <button type="submit">Upload File</button>
        </form>
    </div>
    
    <script>
        // Drag and drop functionality
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('pdf_file');
        
        dropzone.addEventListener('click', () => {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                dropzone.innerHTML = `<p>Selected file: ${fileInput.files[0].name}</p>`;
            }
        });
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropzone.style.borderColor = '#3B82F6';
            dropzone.style.backgroundColor = '#ebf4ff';
        }
        
        function unhighlight() {
            dropzone.style.borderColor = '#3B82F6';
            dropzone.style.backgroundColor = '#f8faff';
        }
        
        dropzone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fileInput.files = files;
                dropzone.innerHTML = `<p>Selected file: ${files[0].name}</p>`;
            }
        }
    </script>
</body>
</html> 
