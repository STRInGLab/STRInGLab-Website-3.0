<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'php/login_cred.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Password is correct, start a session and redirect to admin panel
        session_start();
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        header('Location: ../adminpanel.php');
    } else {
        echo "Incorrect username or password!";
    }
}
?>
