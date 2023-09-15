<?php

    $to = "shikhar516@gmail.com";
    $from = $_REQUEST['con_email'];
    $name = $_REQUEST['con_fname'];
    $csubject = $_REQUEST['con_phone'];
    $cmessage = $_REQUEST['con_message'];

    $headers = "From: $from";
	$headers = "From: " . $from . "\r\n";
	$headers .= "Reply-To: ". $from . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

    $subject = "You have a enquiry from website.";
    $logo = 'https://stringlab.org/String2_mail_header.png';
    $link = 'http://stringlab.org/';

	$body = "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>Express Mail</title></head><body>";
	$body .= "<table style='width: 100%;'>";
	$body .= "<thead style='text-align: center;'><tr><td style='border:none;' colspan='2'>";
	$body .= "<a href='{$link}'><img src='{$logo}' alt=''></a><br><br>";
	$body .= "</td></tr></thead><tbody><tr>";
	$body .= "<td style='border:none;'><strong>Name:</strong> {$name}</td>";
	$body .= "<tr><td></td></tr>";
	$body .= "<td style='border:none;'><strong>Email:</strong> {$from}</td>";
	$body .= "<tr><td style='border:none;'><strong>Subject:</strong> {$csubject}</td></tr>";
	$body .= "<tr><td></td></tr>";
	$body .= "<tr><td style='border:none;'><strong>Message:</strong> {$cmessage}</td></tr>";
	$body .= "</tbody></table>";
	$body .= "</body></html>";

    $send = mail($to, $subject, $body, $headers);
	header("location: index.html");
?>