<?php
session_start();
// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['redirect_back'] = $_SERVER['REQUEST_URI']; // or you could use a full URL if necessary
    header('Location: signin.php');
    exit;
}

require_once 'config.php';

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function convert_date_format($date) {
    $dateTime = DateTime::createFromFormat('d, M Y, H:i', $date);
    if ($dateTime) {
        return $dateTime->format('Y-m-d H:i:s');
    } else {
        return null;
    }
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
        // Sanitize and collect form data
        $projectType = sanitize_input($_POST['project_type']);
        $clientId = 1; // Assuming Client ID is set to a static value for simplicity, replace with actual logic if needed
        $clientName = sanitize_input($_POST['settings_customer']);
        $projectName = sanitize_input($_POST['settings_name']);
        $description = sanitize_input($_POST['settings_description']);
        $releaseDate = sanitize_input($_POST['settings_release_date']);
        $dueDate = sanitize_input($_POST['settings_due_date']);
        $budget = sanitize_input($_POST['budget_setup']);

        // Process tags
        $tagsArray = json_decode($_POST['target_tags'], true);
        $tags = array_map(function($tag) {
            return $tag['value'];
        }, $tagsArray);
        $tagsCSV = implode(', ', $tags);

        // Convert release date to SQL format
        $releaseDateSQL = convert_date_format($releaseDate);
        if ($releaseDateSQL === null) {
            throw new Exception("Invalid date format for release date.");
        }
        $dueDateSQL = convert_date_format($dueDate);
        if ($dueDateSQL === null) {
            throw new Exception("Invalid date format for release date.");
        }

        $uploadDirectory = 'uploads/';
        $fileNames = [];
        $errors = [];

        // Handle file upload if any
        if (!empty($_FILES['file']['name'][0])) {
            foreach ($_FILES['file']['tmp_name'] as $key => $tmp_name) {
                $file_name = basename($_FILES['file']['name'][$key]);
                $file_tmp = $_FILES['file']['tmp_name'][$key];
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
            if (empty($fileNames)) {
                // Insert project details without documents
                $stmt = $pdo->prepare("INSERT INTO projectDetails (Project_Type, Client_ID, Client_Name, Name, Description, Start_Date, End_Date, Budget, Tags, Updated_On) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$projectType, $clientId, $clientName, $projectName, $description, $releaseDateSQL, $dueDateSQL, $budget, $tagsCSV]);
            } else {
                // Insert project details with documents
                foreach ($fileNames as $filePath) {
                    $stmt = $pdo->prepare("INSERT INTO projectDetails (ProjectType, ClientID, ClientName, Name, Description, Start_Date, End_Date, Budget, Tags, Documents, UpdatedOn) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$projectType, $clientId, $clientName, $projectName, $description, $releaseDateSQL, $dueDateSQL, $budget, $tagsCSV, $filePath]);
                }
            }
            echo "Project details saved successfully!";
        } else {
            foreach ($errors as $error) {
                echo $error . "<br>";
            }
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>