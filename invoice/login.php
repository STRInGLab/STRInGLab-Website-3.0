<?php
session_start();
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
        // Password is correct, set session variables
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        
        // Redirect to the page from where the user came or to the default page
        if (isset($_SESSION['redirect_back'])) {
            $redirect_url = $_SESSION['redirect_back'];
            unset($_SESSION['redirect_back']); // clear the session variable after using it
            header('Location: ' . $redirect_url);
        } else {
            header('Location: invoicing.php'); // redirect to a default page if the referrer isn't set
        }
        exit; // Exit after redirection
    } else {
        echo "Incorrect username or password!";
    }
}
?>