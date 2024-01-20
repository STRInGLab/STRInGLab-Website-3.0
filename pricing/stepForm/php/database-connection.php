<?php
// Database connection variables
$db_host = '82.180.152.1'; // usually localhost
$db_user = 'u758484694_string_contact';
$db_pass = ';@.2SGHOp5!UQ#1';
$db_name = 'u758484694_string_contact';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Create connection
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

?>

<!-- CREATE TABLE contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    phone integer(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT,
    page_source VARCHAR(255)
);

ALTER TABLE contact_submissions 
ADD timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, 
ADD section VARCHAR(255); -->