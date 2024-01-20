<?php
// Include your config.php here
include('config.php');

// Connect to your database
// Perform the query to get the steps
// Assuming you have a function getSteps() that returns the steps from the database

$steps = getSteps();

// Set header to return JSON
header('Content-Type: application/json');
echo json_encode($steps);
