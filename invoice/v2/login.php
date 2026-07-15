<?php
// Production-safe error handling: log errors, do not display them.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Secure session cookie params must be set BEFORE session_start().
session_set_cookie_params([
    'lifetime' => 604800, // 1 week
    'path'     => '/',
    'domain'   => '', // Adjust if necessary
    'secure'   => true, // Requires HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once 'php/login_cred.php';
require_once 'php/csrf.php';
require_once 'php/env.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function checkRateLimit(): bool {
    $maxAttempts = 5;
    $windowSeconds = 300; // 5 minutes
    $now = time();

    if (!isset($_SESSION['login_attempts']) || !is_array($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    // Remove attempts outside the window
    $_SESSION['login_attempts'] = array_filter(
        $_SESSION['login_attempts'],
        function ($timestamp) use ($now, $windowSeconds) {
            return ($now - $timestamp) < $windowSeconds;
        }
    );

    return count($_SESSION['login_attempts']) < $maxAttempts;
}

function recordFailedAttempt(): void {
    $_SESSION['login_attempts'][] = time();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    date_default_timezone_set('Asia/Kolkata');

    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid security token. Please try again.";
        header('Location: signin.php');
        exit;
    }

    if (!checkRateLimit()) {
        $_SESSION['error_message'] = "Too many login attempts. Please try again in 5 minutes.";
        header('Location: signin.php');
        exit;
    }

    // OTP verification phase.
    if (isset($_POST['otp_verification']) && $_POST['otp_verification'] == '1') {
        $otp_code = implode('', [
            $_POST['otp_code_1'] ?? '',
            $_POST['otp_code_2'] ?? '',
            $_POST['otp_code_3'] ?? '',
            $_POST['otp_code_4'] ?? '',
            $_POST['otp_code_5'] ?? '',
            $_POST['otp_code_6'] ?? '',
        ]);
        $username = $_SESSION['username'] ?? null;

        if (!$username || !preg_match('/^\d{6}$/', $otp_code)) {
            $_SESSION['error_message'] = "Invalid request. Please log in again.";
            header('Location: signin.php');
            exit;
        }

        $stmt = $pdo->prepare('SELECT otp_code, otp_expires_at FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            $current_time = date('Y-m-d H:i:s');

            if (hash_equals((string)$user['otp_code'], $otp_code) && $current_time <= $user['otp_expires_at']) {
                // OTP verified: now mark the user as fully logged in.
                session_regenerate_id(true);
                $_SESSION['loggedin'] = true;
                unset($_SESSION['otp_pending_user']);
                header('Location: dashboard.php');
                exit;
            } else {
                recordFailedAttempt();
                $_SESSION['error_message'] = "Incorrect or expired OTP!";
                header('Location: signin-otp.php');
                exit;
            }
        } else {
            $_SESSION['error_message'] = "Session expired. Please log in again.";
            header('Location: signin.php');
            exit;
        }
    } else {
        // Initial username/password phase.
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $_SESSION['error_message'] = "Please enter username and password.";
            header('Location: signin.php');
            exit;
        }

        $stmt = $pdo->prepare('SELECT id, username, password, email FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            // Do NOT set loggedin yet; OTP verification is still pending.
            $_SESSION['otp_pending_user'] = $user['id'];
            $_SESSION['username']         = $username;

            $otp = mt_rand(100000, 999999);
            $otp_expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            $stmt = $pdo->prepare('UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?');
            $stmt->execute([$otp, $otp_expires_at, $user['id']]);

            $mail = new PHPMailer(true);
            $emailSent = false;

            try {
                $mail->isSMTP();
                $mail->Host       = env('SMTP_HOST', 'smtp.hostinger.com');
                $mail->SMTPAuth   = true;
                $mail->Username   = env('SMTP_USER', 'careers@stringlab.org');
                $mail->Password   = env('SMTP_PASS', '');
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = (int) env('SMTP_PORT', 465);
                $mail->setFrom(env('SMTP_FROM', 'careers@stringlab.org'), env('SMTP_FROM_NAME', 'S.T.R.In.G Lab'));
                $mail->addAddress($user['email']);
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP Code';
                $mail->Body    = "Your OTP code is <b>$otp</b>. It will expire in 5 minutes.";
                $mail->send();
                $emailSent = true;
            } catch (Exception $e) {
                error_log("PHPMailer error: " . $e->getMessage());
            }

            if (!$emailSent) {
                $_SESSION['error_message'] = "Unable to send OTP. Please try again later.";
                header('Location: signin.php');
                exit;
            }

            header('Location: signin-otp.php');
            exit;
        } else {
            recordFailedAttempt();
            $_SESSION['error_message'] = "Incorrect username or password!";
            header('Location: signin.php');
            exit;
        }
    }
}
