<?php
session_start();
// check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // if not logged in, redirect to signin.html
    $_SESSION['redirect_back'] = $_SERVER['REQUEST_URI']; // or you could use a full URL if necessary
    header('Location: signin.php');
    exit;
}
require_once 'config.php';

$conn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($conn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Corrected delete request logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $sql = "DELETE FROM Invoice WHERE id = ? AND invoiceOrQuote = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$id, $type])) {
        echo "<script>alert('Record deleted successfully');</script>";
    } else {
        echo "<script>alert('Error deleting record.');</script>";
    }
}

// Corrected fetchData function definition
function fetchData($pdo) {
    $sql = "SELECT * FROM incomes";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll();

    if ($result) {
        foreach ($result as $row) {
            $formattedDate = date('Y-m-d', strtotime($row['UpdatedOn']));
            echo "<tr>
                    <td>{$formattedDate}</td>
                    <td>{$row['ClientName']}</td>
                    <td>{$row['ProjectName']}</td>
                    <td>{$row['PaymentDescription']}</td>
                    <td>{$row['Amount']}</td>
                    <td>{$row['Source']}</td>
                    <td>{$row['Notes']}</td>
                    <td>{$row['InvoiceID']}</td>
                    <td>{$row['PaymentStatus']}</td>
                    <td>{$row['CreditDate']}</td>
                    <td class='text-end'>
                        <button class='btn btn-bg-light btn-color-muted btn-active-color-primary btn-sm px-4 me-1 delete-btn' data-id='{$row['ID']}'>Delete</button>
                        <a href='edit_page.php?id={$row['ID']}' class='btn btn-bg-light btn-color-muted btn-active-color-primary btn-sm px-4 badge-light-warning'>Edit</a>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='11'>No Data Found</td></tr>";
    }
}

function getTotalInvoices($pdo) {
    $sql = "SELECT COUNT(*) as invoiceTotal FROM Invoice WHERE invoiceOrQuote = 'Invoice'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['invoiceTotal'];
}
$totalInvoices = getTotalInvoices($pdo);

function getPaidInvoices($pdo) {
    $sql = "SELECT COUNT(*) as paidInvoiceTotal FROM Invoice WHERE invoiceOrQuote = 'Invoice' AND paid_flag = '1'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['paidInvoiceTotal'];
}
$paidInvoices = getPaidInvoices($pdo);

function getRevenueFromInvoices($pdo) {
    $sql = "SELECT SUM((qty * price) - discount) AS total_revenue FROM InvoiceData INNER JOIN Invoice ON InvoiceData.invoiceId = Invoice.id WHERE Invoice.invoiceOrQuote = 'Invoice' AND Invoice.paid_flag = '1'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total_revenue'];
}
$revenueFromInvoices = getRevenueFromInvoices($pdo);

function getPaidTaxFromInvoices($pdo) {
    $sql = "SELECT SUM(((qty * price) - discount)*0.18) AS total_paid_tax FROM InvoiceData INNER JOIN Invoice ON InvoiceData.invoiceId = Invoice.id WHERE Invoice.invoiceOrQuote = 'Invoice' AND Invoice.paid_flag = '1'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total_paid_tax'];
}
$paidTaxFromInvoices = getPaidTaxFromInvoices($pdo);

function getUnpaidTaxFromInvoices($pdo) {
    $sql = "SELECT SUM(((qty * price) - discount)*0.18) AS total_unpaid_tax FROM InvoiceData INNER JOIN Invoice ON InvoiceData.invoiceId = Invoice.id WHERE Invoice.invoiceOrQuote = 'Invoice' AND Invoice.paid_flag = '0'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total_unpaid_tax'];
}
$unpaidTaxFromInvoices = getUnpaidTaxFromInvoices($pdo);

function getAccountReceivable($pdo) {
    $sql = "SELECT SUM((qty * price) - discount) AS ar FROM InvoiceData INNER JOIN Invoice ON InvoiceData.invoiceId = Invoice.id WHERE Invoice.invoiceOrQuote = 'Invoice' AND Invoice.paid_flag = '0'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['ar'];
}
$accountReceivable = getAccountReceivable($pdo);

function getClientNames($pdo) {
    $sql = "SELECT Name FROM clientDetails";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$clientNames = getClientNames($pdo);

// Fetch projects by client name
function getProjectsByClientName($pdo, $clientName) {
    $sql = "SELECT Name FROM projectDetails WHERE Client_Name = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clientName]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle AJAX request
if (isset($_GET['clientName'])) {
    $clientName = $_GET['clientName'];
    $projects = getProjectsByClientName($pdo, $clientName);
    echo json_encode($projects);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = 'uploads/';
    $uploadFile = $uploadDir . basename($_FILES['file']['name']);

    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
        echo json_encode(['status' => 'success', 'file' => $_FILES['file']['name']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'File upload failed']);
    }
    exit;
}
?>