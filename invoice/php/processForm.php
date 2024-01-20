<?php
$host = '82.180.152.1';
$db   = 'u758484694_stringpricing';
$user = 'u758484694_stringpricing'; // replace with your MySQL username
$pass = 'Wj~7j@huK'; // replace with your MySQL password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Begin transaction
    $pdo->beginTransaction();

    // Insert into invoices table
    $stmt = $pdo->prepare("INSERT INTO invoices (invoice_to, invoice_name, invoice_no, invoice_date, payment_info, terms_conditions, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['invoiceTo'],
        $_POST['invoiceName'],
        $_POST['invoiceNo'],
        $_POST['date'],
        $_POST['paymentInfo'],
        $_POST['terms'],
        $_POST['type']
    ]);

    // Get the last insert ID (Invoice ID)
    $invoiceID = $pdo->lastInsertId();

    // Insert into invoice_items table
    $descriptions = $_POST['description'];
    $qtys = $_POST['qty'];
    $prices = $_POST['price'];
    $discounts = $_POST['discount'];

    $stmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, description, qty, price, discount) VALUES (?, ?, ?, ?, ?)");

    for($i = 0; $i < count($descriptions); $i++) {
        $stmt->execute([
            $invoiceID,
            $descriptions[$i],
            $qtys[$i],
            $prices[$i],
            $discounts[$i]
        ]);
    }

    // Commit the transaction
    $pdo->commit();

    // Redirect to a success page or display a success message
    echo "Data successfully inserted!";
} catch (\PDOException $e) {
    // Rollback the transaction in case of any errors
    $pdo->rollback();
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

?>