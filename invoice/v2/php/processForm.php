<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

http_response_code(410);
echo json_encode([
    'error' => 'This endpoint is no longer active. Please use the invoice creation form at create-invoice.php.'
]);
exit;
