<?php
/**
 * Test Schedule API
 * Simple test endpoint to verify API functionality
 */

header('Content-Type: application/json');

// Simple test response
echo json_encode([
    'status' => 'success',
    'message' => 'API is working correctly',
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'get_data' => $_GET
]);
