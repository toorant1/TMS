<?php
require_once '../database/db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

$masterUserId = $data['master_user_id'];
$statusName = trim($data['status_name']);

if (empty($statusName)) {
    echo json_encode(['error' => 'Status name cannot be empty.']);
    exit;
}

// Check for duplicate status
$query = "SELECT 1 FROM master_quotations_status WHERE status_name = ? AND master_user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $statusName, $masterUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['error' => 'Duplicate status name is not allowed.']);
    exit;
}

// Insert new status
$insertQuery = "INSERT INTO master_quotations_status (master_user_id, status_name, status_active_deactive) VALUES (?, ?, 1)";
$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param("is", $masterUserId, $statusName);

if ($insertStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to add new status.']);
}
?>
