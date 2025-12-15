<?php
// Minimal placeholder for unread notifications count to avoid 404s.
header('Content-Type: application/json');

// In a full implementation, query the notifications table for the authenticated user.
echo json_encode([
    'success' => true,
    'unread_count' => 0
]);

