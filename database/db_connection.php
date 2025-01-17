<?php
// Local server credentials (XAMPP)
$localServername = "localhost";
$localUsername = "root";
$localPassword = "";
$localDbname = "accounts";


$which_data_base = "local";

// Flag to check if the connection is successful
$connected = false;

// Set the default time zone for PHP
date_default_timezone_set('Asia/Kolkata'); // Change to your desired time zone


// If web server connection failed, try connecting to the local server

    try {
        $conn = new mysqli($localServername, $localUsername, $localPassword, $localDbname);

        // If connection is successful, set the flag
        if (!$conn->connect_error) {
            $connected = true;
            $which_data_base = "local";

            // Set the MySQL session time zone
            $conn->query("SET time_zone = '+05:30'"); // Adjust this time zone as per your requirements
        }
    } catch (mysqli_sql_exception $e) {
        // Handle the exception
        die("Connection failed: Unable to connect to either database.");
    }


if (!$connected) {
    die("Connection failed: Unable to connect to either database.");
}
?>
