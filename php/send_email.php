<?php
require_once 'vendor/autoload.php'; // Adjust the path to the Twilio SDK
use Twilio\Rest\Client;
// Twilio credentials
$account_sid = 'AC7683a836007bf0f287b18df3e5539ffc';
$auth_token = '219622a2bbd645b8ddbd946e13d492da';
$twilio_phone_number = '+12564149704';
$destination_phone_number = '+919769628463'; // The number you want to send the SMS to

// Database connection variables
$db_host = '82.180.152.1';
$db_user = 'u758484694_string_contact';
$db_pass = ';@.2SGHOp5!UQ#1';
$db_name = 'u758484694_string_contact';

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
    $page_source = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Unknown';
    $section = isset($_REQUEST['section']) ? $_REQUEST['section'] : 'Unknown';

    // Insert data into the database
    $stmt = $conn->prepare("INSERT INTO contact_submissions (first_name, last_name, phone, email, message, page_source, section, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $_REQUEST['con_fname'], $_REQUEST['con_lname'], $_REQUEST['con_phone'], $_REQUEST['con_email'], $_REQUEST['con_message'], $page_source, $section, $timestamp);

    if ($stmt->execute()) {
		$send_arr['response'] = 'success';
		$send_arr['message'] = 'Thank you for reaching out to us! We will revert soon.';
        
        // Send SMS using Twilio
        $client = new Client($account_sid, $auth_token);
        $message = $client->messages->create(
            $destination_phone_number,
            [
                'from' => $twilio_phone_number,
                'body' => 'New form submission received from ' . $_REQUEST['con_fname'] . ' ' . $_REQUEST['con_lname'] . '.'
            ]
        );

    } else {
		$send_arr['response'] = 'error';
		$send_arr['message'] = "You message couldn't be sent. Please try again!";
    }

    $stmt->close();
    
	echo json_encode($send_arr);
	exit;

}

$conn->close();