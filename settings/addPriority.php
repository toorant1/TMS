<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$master_userid = $_SESSION['master_userid'];
$priority = trim($_POST['priority'] ?? '');

if (empty($priority)) {
    echo json_encode(['status' => 'error', 'message' => 'Priority cannot be empty.']);
    exit;
}

// Check for duplicate priority
$query = "SELECT COUNT(*) as count FROM master_tickets_priority WHERE priority = ? AND (master_user_id = ? OR master_user_id = 0)";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $priority, $master_userid);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Priority already exists.']);
    exit;
}

// Insert new priority if unique
$query = "INSERT INTO master_tickets_priority (master_user_id, priority, status) VALUES (?, ?, 1)";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $master_userid, $priority);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Priority added successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add priority.']);
}
$stmt->close();
$conn->close();
?>
