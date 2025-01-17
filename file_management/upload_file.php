<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    die(json_encode(['success' => false, 'message' => 'User not authenticated.']));
}

$master_userid = $_SESSION['master_userid'];

// Input data
$file_name = isset($_POST['file_name']) ? trim($_POST['file_name']) : null;
$description = isset($_POST['description']) ? trim($_POST['description']) : null;
$google_drive_link = isset($_POST['google_drive_link']) ? trim($_POST['google_drive_link']) : null;

// Validation: Check if file name is provided
if (empty($file_name)) {
    die(json_encode(['success' => false, 'message' => 'File name is required.']));
}

// Check if Google Drive link or file upload is provided
if (empty($google_drive_link) && (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] === UPLOAD_ERR_NO_FILE)) {
    die(json_encode(['success' => false, 'message' => 'Please provide either a Google Drive link or upload a zip file.']));
}

// Initialize variables
$file_content = null;
$upload_token = bin2hex(random_bytes(32)); // Generate a unique upload token

if (!empty($google_drive_link)) {
    // Google Drive link provided
    $file_link = $google_drive_link;
    $file_link_name = null; // No file content for Google Drive links
} elseif (isset($_FILES['zip_file']) && $_FILES['zip_file']['error'] === UPLOAD_ERR_OK) {
    // File upload provided
    $file_tmp = $_FILES['zip_file']['tmp_name'];
    $file_size = $_FILES['zip_file']['size'];
    $file_type = mime_content_type($file_tmp);
    $allowed_types = ['application/zip', 'application/x-zip-compressed'];

    // Validate file type
    if (!in_array($file_type, $allowed_types)) {
        die(json_encode(['success' => false, 'message' => 'Invalid file type. Only zip files are allowed.']));
    }

    // Validate file size (Max 10MB)
    $max_file_size = 10 * 1024 * 1024; // 10MB
    if ($file_size > $max_file_size) {
        die(json_encode(['success' => false, 'message' => 'File size exceeds the maximum limit of 10MB.']));
    }

    // Read file content
    $file_content = file_get_contents($file_tmp);
    $file_link = null; // No Google Drive link when uploading a file
}

// Insert record into the database
$query = "
    INSERT INTO zip_file_storage 
    (master_user_id, file_name, uploaded_file, upload_token, description, file_link, file_link_name) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]));
}

// Bind parameters
$stmt->bind_param(
    "issssss",
    $master_userid,
    $file_name,
    $file_content,
    $upload_token,
    $description,
    $file_link,
    $file_name
);

// Execute and return result
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'File uploaded successfully.', 'upload_token' => $upload_token]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
