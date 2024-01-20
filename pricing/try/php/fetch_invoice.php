<?php
session_start();
include 'db_config.php';

$invoice_name = $_GET['invoice_name'];

$stmt = $conn->prepare("SELECT * FROM invoices WHERE invoice_name = ?");
$stmt->bind_param("s", $invoice_name);

$stmt->execute();

$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $invoice = $result->fetch_assoc();
    echo json_encode($invoice); // Return data in JSON format
} else {
    echo json_encode(["error" => "No results found"]);
}

$stmt->close();
$conn->close();
?>