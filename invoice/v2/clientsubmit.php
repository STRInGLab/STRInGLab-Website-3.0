<?php
session_start();  
header('Content-Type: application/json');  
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {  
    echo json_encode(['error' => 'Session expired. Please log in again.']);  
    exit;  
}  

require_once 'config.php';

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

try {
    $conn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($conn, $user, $pass, $options);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Determine the type of form submitted (clientDetails or vendorDetails)
        if (isset($_POST['form_type']) && $_POST['form_type'] == 'clientDetails') {
            $table = 'clientDetails';
        } else if (isset($_POST['form_type']) && $_POST['form_type'] == 'vendorDetails') {
            $table = 'vendorDetails';
        } else if (isset($_POST['form_type']) && $_POST['form_type'] == 'resourceDetails') {
            $table = 'resourceDetails';
        } else {
            echo "Error: Invalid form submission.";
            exit;
        }

        $id = isset($_POST['id']) ? sanitize_input($_POST['id']) : null;

        $name = sanitize_input($_POST['target_title']);
        $contactNumber = sanitize_input($_POST['contact_number']);
        $gstNumber = sanitize_input($_POST['gst_number']);
        $address = sanitize_input($_POST['target_details']);
        $city = sanitize_input($_POST['city']);
        $state = sanitize_input($_POST['state']);
        $pincode = sanitize_input($_POST['pincode']);
        $documentDescription = sanitize_input($_POST['document_description']);
        
        // Check for existing records with the same Name, Contact Number, or GST Number
        // Build the query dynamically based on the values provided  
        $conditions = [];  
        $params = [];  
        
        // Always check for Name uniqueness  
        $conditions[] = "Name = ?";  
        $params[] = $name;  
        
        // Always check for Contact Number uniqueness  
        $conditions[] = "Contact_Number = ?";  
        $params[] = $contactNumber;  
        
        // Only check GST Number if it's not empty  
        if (!empty($gstNumber)) {  
            $conditions[] = "GST_Number = ?";  
            $params[] = $gstNumber;  
        }  
        
        $conditionSql = implode(' OR ', $conditions);  
        
        // Exclude the current record if updating (avoid false positive on self)  
        if ($id) {  
            $conditionSql = '(' . $conditionSql . ') AND ID != ?';  
            $params[] = $id;  
        }  
        
        $checkStmt = $pdo->prepare("SELECT * FROM $table WHERE $conditionSql");  
        $checkStmt->execute($params);  
        
        $existingRecord = $checkStmt->fetch();  
        
        if ($existingRecord) {  
            if ($existingRecord['Name'] == $name) {  
                echo json_encode(['error' => 'A record with the same Name already exists.']);  
            } elseif ($existingRecord['Contact_Number'] == $contactNumber) {  
                echo json_encode(['error' => 'A record with the same Contact Number already exists.']);  
            } elseif (!empty($gstNumber) && $existingRecord['GST_Number'] == $gstNumber) {  
                echo json_encode(['error' => 'A record with the same GST Number already exists.']);  
            }  
            exit;  
        } 

        $uploadDirectory = 'uploads/';
        $fileNames = [];
        $errors = [];

        if (!empty($_FILES['document']['name'][0])) {
            foreach ($_FILES['document']['tmp_name'] as $key => $tmp_name) {
                $file_name = basename($_FILES['document']['name'][$key]);
                $file_tmp = $_FILES['document']['tmp_name'][$key];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

                if (in_array($file_ext, $allowed_extensions)) {
                    $newFileName = uniqid() . '-' . $file_name;
                    if (move_uploaded_file($file_tmp, $uploadDirectory . $newFileName)) {
                        $fileNames[] = $uploadDirectory . $newFileName;
                    } else {
                        $errors[] = "Failed to upload file: $file_name";
                    }
                } else {
                    $errors[] = "File type not allowed: $file_name";
                }
            }
        }

        if (empty($errors)) {  
            if ($id) {  
                // Update operation  
                if (empty($fileNames)) {  
                    // No new documents uploaded  
                    $stmt = $pdo->prepare("UPDATE $table SET Name=?, Contact_Number=?, Address=?, City=?, State=?, Pincode=?, GST_Number=?, Document_Description=?, Updated_On=NOW() WHERE ID=?");  
                    $stmt->execute([$name, $contactNumber, $address, $city, $state, $pincode, $gstNumber, $documentDescription, $id]);  
                } else {  
                    // New documents uploaded  
                    $documentPaths = implode(',', $fileNames); // Adjust as needed  
                    $stmt = $pdo->prepare("UPDATE $table SET Name=?, Contact_Number=?, Address=?, City=?, State=?, Pincode=?, GST_Number=?, Document_Path=?, Document_Description=?, Updated_On=NOW() WHERE ID=?");  
                    $stmt->execute([$name, $contactNumber, $address, $city, $state, $pincode, $gstNumber, $documentPaths, $documentDescription, $id]);  
                }  
                echo json_encode(['success' => 'Details updated successfully!']);  
            } else {  
                // Insert operation  
                if (empty($fileNames)) {  
                    // Insert without documents  
                    $stmt = $pdo->prepare("INSERT INTO $table (Name, Contact_Number, Address, City, State, Pincode, GST_Number, Document_Description, Updated_On) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");  
                    $stmt->execute([$name, $contactNumber, $address, $city, $state, $pincode, $gstNumber, $documentDescription]);  
                } else {  
                    // Insert with documents  
                    foreach ($fileNames as $filePath) {  
                        $stmt = $pdo->prepare("INSERT INTO $table (Name, Contact_Number, Address, City, State, Pincode, GST_Number, Document_Path, Document_Description, Updated_On) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");  
                        $stmt->execute([$name, $contactNumber, $address, $city, $state, $pincode, $gstNumber, $filePath, $documentDescription]);  
                    }  
                }  
                echo json_encode(['success' => 'Details saved successfully!']);  
            }  
        } else {  
            foreach ($errors as $error) {  
                echo json_encode(['error' => implode("<br>", $errors)]);
            }  
        }  
    }  
} catch (PDOException $e) {  
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]); 
}  
?>