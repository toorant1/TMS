<?php
$servername = "localhost"; // or your database server
$username = "root";        // or your database username
$password = "";            // or your database password
$dbname = "xyz360_registration_master";        // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
