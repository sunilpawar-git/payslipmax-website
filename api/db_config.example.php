<?php
/**
 * PayslipMax Database Configuration Example
 * 
 * Copy this file to db_config.php and update with your database credentials
 * 
 * IMPORTANT: Never commit db_config.php to version control as it contains sensitive information
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'payslipmax');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');

// Security Configuration
define('CSRF_SECRET', 'your_random_secret_key_here');
define('UPLOAD_SECRET', 'another_random_secret_for_uploads');

// Upload Configuration
define('UPLOAD_MAX_SIZE', 15728640); // 15MB in bytes
define('UPLOAD_ALLOWED_TYPES', ['application/pdf']);
define('UPLOAD_DIR', '../uploads/');

// Rate Limiting Configuration
define('RATE_LIMIT_REQUESTS', 5);
define('RATE_LIMIT_WINDOW', 60); // seconds

// Environment Configuration
define('ENVIRONMENT', 'development'); // development, staging, production
define('DEBUG_MODE', true); // Set to false in production

// Database Connection Function
function getDatabaseConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        } else {
            error_log("Database connection failed: " . $e->getMessage());
            throw new PDOException("Database connection failed", 500);
        }
    }
}

// Validate configuration
if (DB_USER === 'your_username' || DB_PASS === 'your_password') {
    if (ENVIRONMENT === 'production') {
        die('Please configure your database credentials in db_config.php');
    }
}
?> 