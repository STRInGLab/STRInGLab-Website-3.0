<?php

// Database connection variables
$db_host = '82.180.152.1'; // usually localhost
$db_user = 'u758484694_string_contact';
$db_pass = ';@.2SGHOp5!UQ#1';
$db_name = 'u758484694_string_contact';

// Function to validate the reCAPTCHA response
function validateRecaptcha($captchaResponse) {
    $secretKey = "6LeGI6coAAAAANMEROjZ9F1Nvg_bttNepjEamsSX"; // Replace with your reCAPTCHA secret key
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

    return $result->success;
}

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$response = [];

// Check if form is submitted
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'sendEmail') {
    
    date_default_timezone_set('Asia/Kolkata');
    $timestamp = date('Y-m-d H:i:s');
    // Capture source page
    $page_source = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Unknown';
    $section = isset($_REQUEST['section']) ? $_REQUEST['section'] : 'Unknown';

    // Validate the reCAPTCHA
    $captchaResponse = $_REQUEST['g-recaptcha-response'];
    if (!validateRecaptcha($captchaResponse)) {
        $send_arr['response'] = 'error';
        $send_arr['message'] = "Captcha verification failed. Please try again!";
        echo json_encode($send_arr);
        exit;
    }

    // Insert data into the database
    $stmt = $conn->prepare("INSERT INTO contact_submissions (first_name, last_name, phone, email, message, page_source, section, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $_REQUEST['con_fname'], $_REQUEST['con_lname'], $_REQUEST['con_phone'], $_REQUEST['con_email'], $_REQUEST['con_message'], $page_source, $section, $timestamp);

    if ($stmt->execute()) {
		$send_arr['response'] = 'success';
		$send_arr['message'] = 'Thank you for reaching out to us! We will revert soon.';
    } else {
		$send_arr['response'] = 'error';
		$send_arr['message'] = "Your message couldn't be sent. Please try again!";
    }

    $stmt->close();
    
    echo json_encode($send_arr);
    exit;
}

$conn->close();

?>
