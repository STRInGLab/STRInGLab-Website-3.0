<?php
session_start();
// db_config.php

$servername = "82.180.152.1";
$username = "u758484694_stringpricing";
$password = "Wj~7j@huK";
$dbname = "u758484694_stringpricing";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>