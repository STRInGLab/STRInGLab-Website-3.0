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
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$countryCode = $_GET['country'];  // Get country code from URL

$stmt = $pdo->prepare("
    SELECT s.name as service, st.task_name, st.hrs, cp.charges, cp.currency_symbol
    FROM Services s
    INNER JOIN ServiceTasks st ON s.id = st.service_id
    INNER JOIN CountryPricing cp ON st.id = cp.task_id
    WHERE cp.country_code = :countryCode
");

$stmt->execute(['countryCode' => $countryCode]);

if ($stmt->rowCount() === 0) {
    echo json_encode(["error" => "No data found for the given country code."]);
    exit;
}


$data = $stmt->fetchAll();
echo json_encode($data);  // Convert data to JSON format
?>