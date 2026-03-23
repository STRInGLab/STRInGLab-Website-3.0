<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

require_once 'config.php';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data); // Recursively sanitize arrays
    } else {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        // Remove commas from prices
        if (is_numeric(str_replace(',', '', $data))) {
            return str_replace(',', '', $data);
        }
        return $data;
    }
}

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $options);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $customer_name = sanitize_input($_POST['customer_name']);
        $invoiceName = sanitize_input($_POST['invoiceName']);
        $invoiceNo = sanitize_input($_POST['invoiceNo']);
        $address = sanitize_input($_POST['customer_address']);
        $invoiceType = sanitize_input($_POST['invoice_type']);
        
        // Convert dates to YYYY-MM-DD format
        $date = DateTime::createFromFormat('d, M Y', sanitize_input($_POST['invoice_date']))->format('Y-m-d');
        $dueDate = DateTime::createFromFormat('d, M Y', sanitize_input($_POST['invoice_due_date']))->format('Y-m-d');

        $advancePaid = sanitize_input($_POST['advance_amt'][0]); // Assuming only one advance amount is provided
        $paymentInfo = sanitize_input($_POST['paymentInfo']);
        $terms = sanitize_input($_POST['notes']);
        $taxType = sanitize_input($_POST['tax_type']);
        $gstNumber = sanitize_input($_POST['gst_number']);
        $flag = 0;

        // Insert into Invoice table with correct column names
        $stmt = $pdo->prepare("INSERT INTO Invoice (invoiceTo, name, invoiceName, invoiceNo, date, due_date, advancePaid, paymentInfo, terms, taxType, paid_flag, gst_number, invoiceOrQuote) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$address, $customer_name, $invoiceName, $invoiceNo, $date, $dueDate, $advancePaid, $paymentInfo, $terms, $taxType, $flag, $gstNumber, $invoiceType]);

        $invoiceId = $pdo->lastInsertId();

        // Handle InvoiceData insertion
        for ($i = 0; $i < count($_POST['name']); $i++) {
            if (!empty($_POST['name'][$i])) { // Only process non-empty rows
                $description = sanitize_input($_POST['name'][$i]);
                $qty = sanitize_input($_POST['quantity'][$i]);
                $price = sanitize_input($_POST['price'][$i]);
                $discount = sanitize_input($_POST['discount'][$i]);

                $dataStmt = $pdo->prepare("INSERT INTO InvoiceData (invoiceId, invoiceNo, description, qty, price, discount) VALUES (?, ?, ?, ?, ?, ?)");
                $dataStmt->execute([$invoiceId, $invoiceNo, $description, $qty, $price, $discount]);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => 'Invoice saved successfully!']);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}