<?php
// Simple API test - just outputs basic info
header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'API is working!',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'path' => $_SERVER['REQUEST_URI'] ?? 'unknown'
]);
?> 