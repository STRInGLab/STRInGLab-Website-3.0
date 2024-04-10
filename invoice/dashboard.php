<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
    function fetchData($type, $pdo) { // Notice $pdo is now the correct parameter
        $sql = "SELECT * FROM Invoice WHERE invoiceOrQuote = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$type]);
        $result = $stmt->fetchAll();
    
        if ($result) {
            foreach ($result as $row) {
                echo "<tr data-invoice-no='{$row['invoiceNo']}'>
                        <td>{$row['id']}</td>
                        <td>{$row['invoiceTo']}</td>
                        <td>{$row['invoiceName']}</td>
                        <td>{$row['invoiceNo']}</td>
                        <td>{$row['date']}</td>
                        <td>{$row['advancePaid']}</td>
                        <td>{$row['taxType']}</td>
                        <td>
                            <button class='btn btn-danger delete-btn' data-id='{$row['id']}' data-type='{$type}'>Delete</button>
                            <a href='edit_page.php?id={$row['id']}' class='btn btn-primary'>Edit</a>
                        </td>
                     </tr>";
            }
        } else {
            echo "<tr><td colspan='10'>No Data Found</td></tr>";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>

<div class="container-fluid mt-5">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#invoices">Invoices</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#quotes">Quotes</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#receipts">Receipts</a>
        </li>
    </ul>

    <div class="tab-content">
        <div id="invoices" class="container tab-pane active"><br>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice To</th>
                        <th>Name</th>
                        <th>Number</th>
                        <th>Date</th>
                        <th>Advance Paid</th>
                        <th>Tax Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php fetchData('Invoice', $pdo); ?>
                </tbody>
            </table>
        </div>
        <div id="quotes" class="container tab-pane fade"><br>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice To</th>
                        <th>Name</th>
                        <th>Number</th>
                        <th>Date</th>
                        <th>Advance Paid</th>
                        <th>Tax Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php fetchData('Quote', $pdo); ?>
                </tbody>
            </table>
        </div>
        <div id="receipts" class="container tab-pane fade"><br>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice To</th>
                        <th>Name</th>
                        <th>Number</th>
                        <th>Date</th>
                        <th>Payment Info</th>
                        <th>Terms</th>
                        <th>Advance Paid</th>
                        <th>Tax Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php fetchData('Receipt', $pdo); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
$(document).on('click', '.delete-btn', function() {
    var id = $(this).data('id');
    var type = $(this).data('type');
    if(confirm('Are you sure you want to delete this item?')) {
        $.ajax({
            url: 'dashboard.php', // Pointing to self for deletion
            type: 'post',
            data: {id: id, type: type},
            success: function(response) {
                location.reload();
            }
        });
    }
});
</script>
<script>
$(document).ready(function() {
    // Event listener for row clicks, excluding clicks on the delete button
    $('table').on('click', 'tr', function(e) {
        if (!$(e.target).closest('.delete-btn').length) {
            var invoiceNo = $(this).data('invoice-no');
            if (invoiceNo) {
                window.open('https://stringlab.org/invoice/invoice.php?invoiceNo=' + invoiceNo, '_blank');
            }
        }
    });

    // Existing AJAX call for delete button
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        var type = $(this).data('type');
        if(confirm('Are you sure you want to delete this item?')) {
            $.ajax({
                url: 'dashboard.php',
                type: 'post',
                data: {id: id, type: type},
                success: function(response) {
                    location.reload();
                }
            });
        }
    });
});
</script>
</body>
</html>
