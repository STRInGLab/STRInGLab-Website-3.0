<?php
session_start();
require_once __DIR__ . '/env.php';

$host    = env('LOGIN_DB_HOST', 'localhost');
$db      = env('LOGIN_DB_NAME', 'contact_db');
$user    = env('LOGIN_DB_USER', 'db_user');
$pass    = env('LOGIN_DB_PASS', '');
$charset = env('DB_CHARSET', 'utf8mb4');

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
