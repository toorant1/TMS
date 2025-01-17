<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Validate input
if (!isset($_GET['file_id']) || !isset($_GET['token'])) {
    die("Invalid request.");
}

$file_id = intval($_GET['file_id']);
$token = $_GET['token'];

// Fetch the file details from the database
$query = "
    SELECT 
        file_name, 
        uploaded_file, 
        file_link 
    FROM zip_file_storage 
    WHERE id = ? AND upload_token = ? AND master_user_id = ? AND status = 1
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param("isi", $file_id, $token, $master_userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("File not found.");
}

$file = $result->fetch_assoc();

$stmt->close();

// If the file is a Google Drive link, redirect to it
if (!empty($file['file_link'])) {
    header("Location: " . $file['file_link']);
    exit;
}

// Prepare the file for download as a ZIP
$filename = $file['file_name'] ?: "downloaded_file.zip"; // Default filename
$zip_filename = pathinfo($filename, PATHINFO_FILENAME) . ".zip"; // Ensure it's a ZIP file
$file_content = $file['uploaded_file'];

// Send headers to force download
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=\"$zip_filename\"");
header("Content-Length: " . strlen($file_content));

// Output the file content
echo $file_content;

$conn->close();
exit;
