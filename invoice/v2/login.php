<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'php/login_cred.php';
require 'vendor/autoload.php'; // Include PHPMailer autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Ensure the session is started
}

// Log the entire session to the browser console
echo "<script>console.log('Session Data: " . json_encode($_SESSION) . "');</script>";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    date_default_timezone_set('Asia/Kolkata');
    // Check if the form submission is for OTP verification
    if (isset($_POST['otp_verification']) && $_POST['otp_verification'] == '1') {
        // Concatenate the OTP parts into one string
        $otp_code = $_POST['otp_code_1'] . $_POST['otp_code_2'] . $_POST['otp_code_3'] . 
                    $_POST['otp_code_4'] . $_POST['otp_code_5'] . $_POST['otp_code_6'];
        $username = $_SESSION['username']; // Assuming the username is stored in the session
        echo "<script>console.log('Username from session: " . addslashes($username) . "');</script>";
    
        $stmt = $pdo->prepare('SELECT otp_code, otp_expires_at FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
    
        if ($user) {
            $current_time = date('Y-m-d H:i:s');
    
            // Ensure OTP is compared as strings and check for expiration
            if ($otp_code === $user['otp_code'] && $current_time <= $user['otp_expires_at']) {
                // OTP is correct and not expired
                header('Location: dashboard.php');
                exit;
            } else {
                // OTP is incorrect or expired
                $_SESSION['error_message'] = "Incorrect or expired OTP!";
                header('Location: signin-otp.php'); // Redirect back to the OTP form
                exit;
            }
        } else {
            // User not found, session might be invalid
            $_SESSION['error_message'] = "Session expired. Please log in again.";
            header('Location: signin.php'); // Redirect back to the sign-in page
            exit;
        }
    }else {
        // Handle the normal login process here (as before)
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_set_cookie_params([
                'lifetime' => 604800, // 1 week
                'path' => '/',
                'domain' => '', // Adjust if necessary
                'secure' => true, // Use HTTPS
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            // Password is correct, generate OTP
            $otp = mt_rand(100000, 999999); // Generate a 6-digit OTP
            $otp_expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes')); // Set OTP expiration time

            // Save the OTP and its expiration time in the database
            $stmt = $pdo->prepare('UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?');
            $stmt->execute([$otp, $otp_expires_at, $user['id']]);

            // Send the OTP to the user's email using PHPMailer
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.hostinger.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'careers@stringlab.org'; // Your SMTP username
                $mail->Password   = '$tringLab@2025'; // Your SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
    
                //Recipients
                $mail->setFrom('careers@stringlab.org', 'Your Name');
                $mail->addAddress($user['email']); // Add a recipient

                //Content
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP Code';
                $mail->Body    = "Your OTP code is <b>$otp</b>. It will expire in 5 minutes.";

                $mail->send();
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }

            // Redirect to signin-otp.php
            header('Location: signin-otp.php');
            exit; // Exit after redirection
        } else {
            // Password is incorrect, return a toast message
            $_SESSION['error_message'] = "Incorrect username or password!";
            header('Location: signin.php'); // Redirect back to the sign-in page
            exit; // Exit after redirection
        }
    }
}
?>
