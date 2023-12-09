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

// Check if there's a request to delete a row
if (isset($_POST['delete_id'])) {
    $id_to_delete = $_POST['delete_id'];

    $delete_stmt = $mysqli->prepare("DELETE FROM contact_submissions WHERE id = ?");
    $delete_stmt->bind_param("i", $id_to_delete);

    if ($delete_stmt->execute()) {
        echo "Row with ID $id_to_delete deleted successfully!";
    } else {
        echo "Error deleting row: " . $mysqli->error;
    }

    $delete_stmt->close();
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