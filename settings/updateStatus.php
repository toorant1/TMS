<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$master_userid = $_SESSION['master_userid'];
$status_id = intval($_POST['id'] ?? 0);
$status_name = trim($_POST['status_name'] ?? '');
$status = intval($_POST['status'] ?? 0);

if (empty($status_name)) {
    echo json_encode(['status' => 'error', 'message' => 'Status name cannot be empty.']);
    exit;
}

// Validate that the status being updated belongs to the user or is default
$checkQuery = "
    SELECT id 
    FROM master_tickets_status 
    WHERE id = ? AND (master_user_id = ? OR master_user_id = 0)
";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("ii", $status_id, $master_userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Status not found or unauthorized to edit.']);
    exit;
}

// Check for duplicate status name for the user or default statuses
$duplicateQuery = "
    SELECT id 
    FROM master_tickets_status 
    WHERE (master_user_id = 0 OR master_user_id = ?) AND status_name = ? AND id != ?
";
$stmt = $conn->prepare($duplicateQuery);
$stmt->bind_param("isi", $master_userid, $status_name, $status_id);
$stmt->execute();
$duplicateResult = $stmt->get_result();

if ($duplicateResult->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Status name already exists.']);
    exit;
}

// Update the status
$updateQuery = "
    UPDATE master_tickets_status 
    SET status_name = ?, status = ? 
    WHERE id = ? AND master_user_id = ?
";
$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("siii", $status_name, $status, $status_id, $master_userid);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Status updated successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update status.']);
}

$stmt->close();
$conn->close();
?>
