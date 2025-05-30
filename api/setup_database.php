<?php
/**
 * Database Setup Script
 * 
 * This script creates the necessary database tables for PayslipMax.
 * Run this once to set up the database structure.
 */

require_once 'db_config.php';

try {
    echo "Setting up PayslipMax database...\n";
    
    $db = get_database_connection();
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/setup_database.sql');
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }
        
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        
        if ($db->query($statement) === FALSE) {
            throw new Exception("Error executing statement: " . $db->error);
        }
    }
    
    echo "Database setup completed successfully!\n";
    
    // Test the setup by checking if tables exist
    $result = $db->query("SHOW TABLES");
    echo "\nCreated tables:\n";
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "Error setting up database: " . $e->getMessage() . "\n";
    exit(1);
}
?> 