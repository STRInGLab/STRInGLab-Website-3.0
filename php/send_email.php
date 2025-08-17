<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'rate_limiter.php';

// Database connection
$db_host = '194.59.164.10';
$db_user = 'u758484694_string_contact';
$db_pass = ';@.2SGHOp5!UQ#1';
$db_name = 'u758484694_string_contact';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize rate limiter
createRateLimitingTable($conn);
$rateLimiter = new RateLimiter($conn, 3, 3600); // 3 attempts per hour

function getClientIP() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function validateRecaptcha($captchaResponse) {
    $secretKey = "6LeGI6coAAAAANMEROjZ9F1Nvg_bttNepjEamsSX";
    $verifyUrl = "https://www.google.com/recaptcha/api/siteverify";

    $postData = http_build_query([
        "secret" => $secretKey,
        "response" => $captchaResponse
    ]);

    $options = [
        "http" => [
            "header" => "Content-type: application/x-www-form-urlencoded\r\n",
            "method" => "POST",
            "content" => $postData
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($verifyUrl, false, $context);
    $result = json_decode($response);
    return $result->success ?? false;
}

function advancedSpamDetection($data) {
    $spamScore = 0;
    $reasons = [];

    // Check honeypots
    if (!empty($data['website']) || !empty($data['backup_email']) || !empty($data['fax_number'])) {
        $spamScore += 100;
        $reasons[] = "Honeypot triggered";
    }

    // Time-based validation (minimum 5 seconds to fill form)
    if (isset($data['form_loaded_time'])) {
        $formFillTime = (time() * 1000) - intval($data['form_loaded_time']);
        if ($formFillTime < 5000) { // Less than 5 seconds
            $spamScore += 50;
            $reasons[] = "Form filled too quickly: " . ($formFillTime/1000) . " seconds";
        }
        if ($formFillTime > 1800000) { // More than 30 minutes
            $spamScore += 30;
            $reasons[] = "Form took too long to complete";
        }
    } else {
        $spamScore += 40;
        $reasons[] = "Missing form timing data";
    }

    // Content analysis
    $message = strtolower($data['con_message'] ?? '');
    $email = strtolower($data['con_email'] ?? '');
    $fname = strtolower($data['con_fname'] ?? '');
    $lname = strtolower($data['con_lname'] ?? '');

    // Common spam phrases
    $spamPhrases = [
        'make money', 'bitcoin', 'cryptocurrency', 'investment opportunity',
        'guaranteed income', 'work from home', 'free money', 'click here',
        'limited time offer', 'act now', 'viagra', 'cialis', 'pharmacy',
        'casino', 'poker', 'gambling', 'loan', 'debt relief', 'bankruptcy'
    ];

    foreach ($spamPhrases as $phrase) {
        if (strpos($message, $phrase) !== false) {
            $spamScore += 25;
            $reasons[] = "Contains spam phrase: $phrase";
        }
    }

    // Suspicious patterns
    if (preg_match('/(.)\1{4,}/', $message)) { // Repeated characters
        $spamScore += 20;
        $reasons[] = "Excessive repeated characters";
    }

    if (preg_match_all('/https?:\/\//', $message) > 2) { // Multiple URLs
        $spamScore += 30;
        $reasons[] = "Multiple URLs detected";
    }

    // Name validation
    if (preg_match('/^[a-zA-Z\s\'-]+$/', $fname) === 0) {
        $spamScore += 15;
        $reasons[] = "Invalid first name format";
    }

    if (preg_match('/^[a-zA-Z\s\'-]+$/', $lname) === 0) {
        $spamScore += 15;
        $reasons[] = "Invalid last name format";
    }

    // Email validation beyond basic format
    if (!filter_var($data['con_email'], FILTER_VALIDATE_EMAIL)) {
        $spamScore += 40;
        $reasons[] = "Invalid email format";
    }

    // Check for disposable email domains
    $disposableDomains = [
        '10minutemail.com', 'tempmail.org', 'guerrillamail.com',
        'mailinator.com', 'throwaway.email', 'temp-mail.org'
    ];
    
    $emailDomain = substr(strrchr($email, "@"), 1);
    if (in_array($emailDomain, $disposableDomains)) {
        $spamScore += 35;
        $reasons[] = "Disposable email domain";
    }

    // Phone validation
    $phone = preg_replace('/[^0-9]/', '', $data['con_phone'] ?? '');
    if (strlen($phone) < 10 || strlen($phone) > 15) {
        $spamScore += 20;
        $reasons[] = "Invalid phone number length";
    }

    return ['score' => $spamScore, 'reasons' => $reasons];
}

function logSpamAttempt($conn, $data, $ip, $spamScore, $reasons) {
    $stmt = $conn->prepare("INSERT INTO spam_log (ip, email, spam_score, reasons, form_data, timestamp) VALUES (?, ?, ?, ?, ?, ?)");
    $reasonsJson = json_encode($reasons);
    $formDataJson = json_encode($data);
    $timestamp = date('Y-m-d H:i:s');
    
    $stmt->bind_param("ssisss", $ip, $data['con_email'], $spamScore, $reasonsJson, $formDataJson, $timestamp);
    $stmt->execute();
}

// Main processing
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'sendEmail') {
    $clientIP = getClientIP();
    
    // Rate limiting check
    if (!$rateLimiter->isAllowed($clientIP)) {
        $remainingTime = $rateLimiter->getRemainingTime($clientIP);
        echo json_encode([
            'response' => 'error', 
            'message' => "Too many submissions. Please try again in " . ceil($remainingTime/60) . " minutes."
        ]);
        exit;
    }

    // Record this attempt
    $rateLimiter->recordAttempt($clientIP);

    // Advanced spam detection
    $spamAnalysis = advancedSpamDetection($_REQUEST);
    
    if ($spamAnalysis['score'] >= 50) {
        logSpamAttempt($conn, $_REQUEST, $clientIP, $spamAnalysis['score'], $spamAnalysis['reasons']);
        echo json_encode([
            'response' => 'error', 
            'message' => "Your submission appears to be spam. Please contact us directly if this is an error."
        ]);
        exit;
    }

    // reCAPTCHA validation
    if (!validateRecaptcha($_REQUEST['g-recaptcha-response'] ?? '')) {
        echo json_encode(['response' => 'error', 'message' => "Captcha verification failed. Please try again!"]);
        exit;
    }

    // Proceed with normal processing
    date_default_timezone_set('Asia/Kolkata');
    $timestamp = date('Y-m-d H:i:s');
    $page_source = $_SERVER['HTTP_REFERER'] ?? 'Unknown';
    $section = $_REQUEST['section'] ?? 'Contact Form';

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO contact_submissions (first_name, last_name, phone, email, message, page_source, section, timestamp, ip_address, spam_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssi", 
        $_REQUEST['con_fname'], 
        $_REQUEST['con_lname'], 
        $_REQUEST['con_phone'], 
        $_REQUEST['con_email'], 
        $_REQUEST['con_message'], 
        $page_source, 
        $section, 
        $timestamp, 
        $clientIP, 
        $spamAnalysis['score']
    );

    if ($stmt->execute()) {
        // Send email using PHPMailer (your existing code)
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'no-reply@stringlab.org';
            $mail->Password = '$stringLab@2025';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('no-reply@stringlab.org', 'StringLab Contact Form');
            $mail->addAddress('shikhar@stringlab.org');
            $mail->addReplyTo($_REQUEST['con_email'], $_REQUEST['con_fname'] . ' ' . $_REQUEST['con_lname']);

            $mail->isHTML(false);
            $mail->Subject = 'New Contact Form Submission (Score: ' . $spamAnalysis['score'] . ')';
            
            $body = "You have received a new message from your website contact form.\n\n";
            $body .= "Spam Score: " . $spamAnalysis['score'] . "/100\n";
            $body .= "IP Address: " . $clientIP . "\n\n";
            $body .= "First Name: " . htmlspecialchars($_REQUEST['con_fname']) . "\n";
            $body .= "Last Name: " . htmlspecialchars($_REQUEST['con_lname']) . "\n";
            $body .= "Phone: " . htmlspecialchars($_REQUEST['con_phone']) . "\n";
            $body .= "Email: " . htmlspecialchars($_REQUEST['con_email']) . "\n";
            $body .= "Message:\n" . htmlspecialchars($_REQUEST['con_message']) . "\n\n";
            $body .= "Page Source: " . $page_source . "\n";

            $mail->Body = $body;
            $mail->send();

            echo json_encode(['response' => 'success', 'message' => 'Thank you for reaching out to us! We will revert soon.']);

        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            echo json_encode(['response' => 'success', 'message' => 'Thank you for reaching out to us! We will revert soon.']);
        }
    } else {
        echo json_encode(['response' => 'error', 'message' => "Your message couldn't be sent. Please try again!"]);
    }

    $stmt->close();
    exit;
}

$conn->close();
?>
