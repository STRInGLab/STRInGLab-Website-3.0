<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

require_once 'php/csrf.php';
require_once 'php/db.php';
require_once 'php/invoice_config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid security token. Please refresh the page and try again.']);
    exit;
}

/**
 * Clean input for persistence. Do NOT HTML-escape here; escape at output time.
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    if (is_numeric(str_replace(',', '', $data))) {
        return str_replace(',', '', $data);
    }
    return $data;
}

function parseDate(string $raw): ?string {
    $raw = sanitize_input($raw);
    if ($raw === '') {
        return null;
    }
    $dt = DateTime::createFromFormat('d, M Y', $raw);
    return $dt ? $dt->format('Y-m-d') : null;
}

try {
    $pdo = getPdo();

    $customer_name = sanitize_input($_POST['customer_name'] ?? '');
    $invoiceName   = sanitize_input($_POST['invoiceName'] ?? '');
    $invoiceNo     = sanitize_input($_POST['invoiceNo'] ?? '');
    $address       = sanitize_input($_POST['customer_address'] ?? '');
    $invoiceType   = sanitize_input($_POST['invoice_type'] ?? '');

    if ($customer_name === '' || $invoiceName === '' || $invoiceNo === '' || $address === '' || $invoiceType === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Required fields are missing.']);
        exit;
    }

    $date    = parseDate($_POST['invoice_date'] ?? '');
    $dueDate = parseDate($_POST['invoice_due_date'] ?? '');

    if ($date === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid invoice date.']);
        exit;
    }

    $advancePaid = floatval(sanitize_input($_POST['advance_amt'][0] ?? '0'));
    $paymentInfo = sanitize_input($_POST['paymentInfo'] ?? '');
    $terms       = sanitize_input($_POST['notes'] ?? '');
    $taxType     = InvoiceConfig::normalizeTaxType(sanitize_input($_POST['tax_type'] ?? ''));
    $gstNumber   = sanitize_input($_POST['gst_number'] ?? '');
    $hsnsac      = sanitize_input($_POST['hsnsac'] ?? '998314');
    // NOTE: storing HSN/SAC requires an `hsnsac` column in the Invoice table.
    // It is left out of the INSERT until the schema is updated.
    $flag        = 0;

    $lineNames = $_POST['name'] ?? [];
    if (!is_array($lineNames) || count(array_filter($lineNames, 'strlen')) === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'At least one invoice item is required.']);
        exit;
    }

    $pdo->beginTransaction();

    // Insert with a temporary invoice number; we will replace it with a
    // guaranteed-unique value based on the auto-increment ID after insert.
    $tempInvoiceNo = 'TEMP-' . bin2hex(random_bytes(4));

    $stmt = $pdo->prepare("INSERT INTO Invoice
        (invoiceTo, name, invoiceName, invoiceNo, date, due_date, advancePaid, paymentInfo, terms, taxType, paid_flag, gst_number, invoiceOrQuote)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $address,
        $customer_name,
        $invoiceName,
        $tempInvoiceNo,
        $date,
        $dueDate,
        $advancePaid,
        $paymentInfo,
        $terms,
        $taxType,
        $flag,
        $gstNumber,
        $invoiceType
    ]);

    $invoiceId = $pdo->lastInsertId();

    // Generate the human-readable invoice number from the real DB ID.
    $prefix     = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $invoiceName), 0, 3));
    $prefix     = $prefix === '' ? 'INV' : $prefix;
    $invoiceNo  = $prefix . '-' . str_pad((string) $invoiceId, 2, '0', STR_PAD_LEFT);

    // Update the header and line items with the final invoice number.
    $updStmt = $pdo->prepare("UPDATE Invoice SET invoiceNo = ? WHERE id = ?");
    $updStmt->execute([$invoiceNo, $invoiceId]);

    $dataStmt = $pdo->prepare("INSERT INTO InvoiceData
        (invoiceId, invoiceNo, description, qty, price, discount)
        VALUES (?, ?, ?, ?, ?, ?)");

    for ($i = 0; $i < count($lineNames); $i++) {
        if (!empty($lineNames[$i])) {
            $description = sanitize_input($lineNames[$i]);
            $qty         = floatval(sanitize_input($_POST['quantity'][$i] ?? '0'));
            $price       = floatval(sanitize_input($_POST['price'][$i] ?? '0'));
            $discount    = floatval(sanitize_input($_POST['discount'][$i] ?? '0'));

            $dataStmt->execute([$invoiceId, $invoiceNo, $description, $qty, $price, $discount]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success'   => 'Invoice saved successfully!',
        'invoiceNo' => $invoiceNo,
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log('Invoice save error: ' . $e->getMessage());
    echo json_encode(['error' => 'Unable to save invoice. Please try again later.']);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log('Invoice save error: ' . $e->getMessage());
    echo json_encode(['error' => 'Unable to save invoice. Please try again later.']);
}
