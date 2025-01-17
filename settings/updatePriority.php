<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$master_userid = $_SESSION['master_userid'];
$id = intval($_POST['id'] ?? 0);
$priority = trim($_POST['priority'] ?? '');
$status = intval($_POST['status'] ?? -1);

if (empty($priority)) {
    echo json_encode(['status' => 'error', 'message' => 'Priority cannot be empty.']);
    exit;
}

// Check if priority is a duplicate
$query = "SELECT COUNT(*) as count FROM master_tickets_priority WHERE priority = ? AND id != ? AND (master_user_id = ? OR master_user_id = 0)";
$stmt = $conn->prepare($query);
$stmt->bind_param("sii", $priority, $id, $master_userid);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Priority already exists.']);
    exit;
}

// Update the priority
$query = "UPDATE master_tickets_priority SET priority = ?, status = ? WHERE id = ? AND master_user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("siii", $priority, $status, $id, $master_userid);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Priority updated successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update priority.']);
}
$stmt->close();
$conn->close();
?>
