<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {

// Database configuration
$db_host = '82.180.152.1'; // usually localhost
$db_user = 'u758484694_stringpricing';
$db_pass = 'Wj~7j@huK';
$db_name = 'u758484694_stringpricing';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log(print_r($_POST, true));
    // Retrieve form data
    $fullName = $_POST['con_fname'] ?? '';
    $phoneNumber = $_POST['con_phone'] ?? '';
    $email = $_POST['con_email'] ?? '';
    $selectedServices = $_POST['selectedServices'] ?? ''; // This should be a JSON string
    $totalAmount = $_POST['totalAmount'] ?? 0;

    // Sanitize the input
    $fullName = $conn->real_escape_string($fullName);
    $phoneNumber = $conn->real_escape_string($phoneNumber);
    $email = $conn->real_escape_string($email);
    $selectedServices = $conn->real_escape_string($selectedServices);
    $totalAmount = $conn->real_escape_string($totalAmount);

    // Prepare an SQL statement to insert the data
    $stmt = $conn->prepare("INSERT INTO user_enquiries (full_name, phone_number, email, selected_services, total_amount) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssd", $fullName, $phoneNumber, $email, $selectedServices, $totalAmount);

    // Execute the statement
    if ($stmt->execute()) {
        echo "Record saved successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement and the connection
    $stmt->close();
    $conn->close();
} else {
    echo "No form data received";
}
} catch (mysqli_sql_exception $exception) {
    die('Database error: ' . $exception->getMessage());
}
?>
