<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Decode the incoming JSON data
$input = json_decode(file_get_contents('php://input'), true);

// Debug: Check if the input is received
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request payload.']);
    exit;
}

// Check if the ID is provided
if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'File ID is missing.']);
    exit;
}

$file_id = intval($input['id']);

// Debug: Log the file ID and user ID
error_log("Deleting file ID: $file_id by user ID: $master_userid");

// Check if the file belongs to the user
$query = "SELECT id FROM zip_file_storage WHERE id = ? AND master_user_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ii", $file_id, $master_userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'File not found or permission denied.']);
    exit;
}

$stmt->close();

// Delete the file record (soft delete by setting status to 0)
$delete_query = "UPDATE zip_file_storage SET status = 0 WHERE id = ?";
$delete_stmt = $conn->prepare($delete_query);

if (!$delete_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$delete_stmt->bind_param("i", $file_id);

if ($delete_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'File deleted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete file: ' . $delete_stmt->error]);
}

$delete_stmt->close();
$conn->close();
?>
