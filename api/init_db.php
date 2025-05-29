<?php
require_once 'db_config.php';
require_once 'helpers.php';

try {
    echo "Starting database initialization...<br>";
    initialize_database();
    echo "Database initialization completed successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>