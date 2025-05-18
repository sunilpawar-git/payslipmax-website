<?php
/**
 * Database Configuration
 * 
 * This file contains database connection configuration and helper functions.
 */

// Database connection parameters
$db_config = [
    'host' => 'localhost', // Usually localhost for Hostinger
    'username' => 'root', // Your Hostinger database username
    'password' => 'root', // Replace with your actual database password
    'database' => 'payslipmax_local' // Your database name
];

// Create database connection
function get_database_connection() {
    global $db_config;
    
    $db = new mysqli(
        $db_config['host'],
        $db_config['username'],
        $db_config['password'],
        $db_config['database']
    );
    
    // Check connection
    if ($db->connect_error) {
        throw new Exception('Database connection failed: ' . $db->connect_error);
    }
    
    // Set charset
    $db->set_charset('utf8mb4');
    
    return $db;
}

// Initialize database tables if they don't exist
function initialize_database() {
    $db = get_database_connection();
    
    // Create devices table
    $db->query("CREATE TABLE IF NOT EXISTS devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_token VARCHAR(64) NOT NULL UNIQUE,
        device_name VARCHAR(255) NOT NULL,
        device_type VARCHAR(64) NOT NULL,
        os_version VARCHAR(32) NOT NULL,
        app_version VARCHAR(32) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        last_active DATETIME NULL,
        INDEX idx_device_token (device_token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Create uploads table
    $db->query("CREATE TABLE IF NOT EXISTS uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        string_id VARCHAR(32) NOT NULL UNIQUE,
        filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(512) NOT NULL,
        file_size INT NOT NULL,
        is_password_protected TINYINT(1) NOT NULL DEFAULT 0,
        secure_token VARCHAR(64) NOT NULL,
        device_id INT NULL,
        source VARCHAR(32) NOT NULL,
        status VARCHAR(32) NOT NULL DEFAULT 'pending',
        uploaded_at DATETIME NOT NULL,
        processed_at DATETIME NULL,
        INDEX idx_string_id (string_id),
        INDEX idx_device_id (device_id),
        INDEX idx_status (status),
        FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Create activity_log table
    $db->query("CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        activity_type VARCHAR(64) NOT NULL,
        activity_data JSON NULL,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        INDEX idx_activity_type (activity_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Create error_log table
    $db->query("CREATE TABLE IF NOT EXISTS error_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        error_type VARCHAR(64) NOT NULL,
        error_message TEXT NOT NULL,
        stack_trace TEXT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        INDEX idx_error_type (error_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    $db->close();
}

// Initialize database on first run if needed
try {
    initialize_database();
} catch (Exception $e) {
    // Log error to file if database initialization fails
    error_log('Database initialization failed: ' . $e->getMessage());
} 
