<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'db_config.php';

$invoice_name = trim($_GET['invoice_name'] ?? '');

if ($invoice_name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Invoice name is required']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM invoices WHERE invoice_name = ?");
$stmt->bind_param("s", $invoice_name);
$stmt->execute();

$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $invoice = $result->fetch_assoc();
    echo json_encode($invoice);
} else {
    http_response_code(404);
    echo json_encode(["error" => "No results found"]);
}

$stmt->close();
$conn->close();
?>
