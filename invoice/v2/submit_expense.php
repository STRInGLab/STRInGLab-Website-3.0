<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: signin.php');
    exit;
}

require_once 'config.php';

// Establish database connection
$conn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($conn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['id'])) {  
    $id = $_POST['id'];  
    $sql = "DELETE FROM expenseDetails WHERE id = ?";  
    $stmt = $pdo->prepare($sql);  
    if ($stmt->execute([$id])) {  
        echo "Record deleted successfully";  
    } else {  
        echo "Error deleting record.";  
    }  
    exit();  
}  
  
// Handle fetch request  
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'fetch' && isset($_GET['id'])) {  
    $id = $_GET['id'];  
    $sql = "SELECT * FROM expenseDetails WHERE id = ?";  
    $stmt = $pdo->prepare($sql);  
    $stmt->execute([$id]);  
    $row = $stmt->fetch(PDO::FETCH_ASSOC);  
    if ($row) {  
        echo json_encode($row);  
    } else {  
        echo json_encode(['error' => 'No data found']);  
    }  
    exit();  
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and validate the form data
    $resourceName = $_POST['ResourceName'];
    $identification = $_POST['identification'];
    $expenseDescription = $_POST['expenseDescription'];
    $projectName = $_POST['ProjectName'];
    $payoutAmount = $_POST['payoutAmount'];
    $payoutSource = $_POST['payoutSource'];
    $payoutDate = $_POST['PayoutDate'];
    $notes = $_POST['notes'];

    // Handle file upload
    $attachment = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $fileName = basename($_FILES['file']['name']);
        $targetFilePath = $uploadDir . $fileName;

        // Ensure the upload directory exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true); // Create directory if it doesn't exist
        }

        // Move the uploaded file to the server's directory
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFilePath)) {
            $attachment = $targetFilePath; // Store the relative path to the file
        } else {
            die("Error: Could not upload the file.");
        }
    }

    // Insert the data into the database
    $sql = "INSERT INTO expenseDetails 
        (Resource_Name, Identification, Expense_Description, Project_Name, Payout_Amount, Payout_Source, Payout_Date, Attachment, Notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$resourceName, $identification, $expenseDescription, $projectName, $payoutAmount, $payoutSource, $payoutDate, $attachment, $notes]);

    echo "Expense details have been successfully submitted.";
}
?>