<?php
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'sendEmail')
{
	$to = 'info@stringlab.org';
	$subject = 'Contact Page Enquiry';
	$send_arr = array();	
	
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	$headers .= "From: <".$_REQUEST['con_email'].">" . "\r\n";
	$headers .= "Cc: ".$_REQUEST['con_email'] . "\r\n";
	
	$message = "First Name : ".$_REQUEST['con_fname']. "<br />";
	$message .= "Last Name : ".$_REQUEST['con_lname']. "<br />";
	$message .= "Email : ".$_REQUEST['con_email']. "<br />";
	$message .= "Phone : ".$_REQUEST['con_phone']. "<br />";
	$message .= "Message : ".$_REQUEST['con_message']. "<br />";
	
	if (mail($to,$subject,$message,$headers) ){
		
		$send_arr['response'] = 'success';
		$send_arr['message'] = 'Thank you for reaching out to us! We will revert soon.';
		
		} else{
			
		$send_arr['response'] = 'error';
		$send_arr['message'] = "You message couldn't be sent. Please try again!";
			
			}
	echo json_encode($send_arr);
	exit;
	
}

?>