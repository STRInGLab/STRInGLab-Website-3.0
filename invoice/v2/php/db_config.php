<?php
session_start();
require_once __DIR__ . '/env.php';

$servername = env('LEGACY_DB_HOST', 'localhost');
$username   = env('LEGACY_DB_USER', 'db_user');
$password   = env('LEGACY_DB_PASS', '');
$dbname     = env('LEGACY_DB_NAME', 'invoice_db');

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
