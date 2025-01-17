<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$master_userid = $_SESSION['master_userid'];
$status_name = trim($_POST['status_name'] ?? '');

if (empty($status_name)) {
    echo json_encode(['status' => 'error', 'message' => 'Status name cannot be empty.']);
    exit;
}

// Check for duplicate status name for the user or default statuses
$query = "
    SELECT id 
    FROM master_tickets_status 
    WHERE (master_user_id = 0 OR master_user_id = ?) AND status_name = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $master_userid, $status_name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Status name already exists.']);
    exit;
}

// Insert the new status
$insertQuery = "
    INSERT INTO master_tickets_status (master_user_id, status_name, status) 
    VALUES (?, ?, 1)
";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("is", $master_userid, $status_name);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'New status added successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add status.']);
}

$stmt->close();
$conn->close();
?>
