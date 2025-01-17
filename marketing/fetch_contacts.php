<?php
require_once '../database/db_connection.php';
session_start();

header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$account_id = isset($_POST['account_id']) ? intval($_POST['account_id']) : 0;

if ($account_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid account ID']);
    exit;
}

$contacts = [];

// Fetch contacts for the selected account
$stmt = $conn->prepare("SELECT id, name, mobile1 FROM contacts WHERE account_id = ? AND status = 1");
if ($stmt) {
    $stmt->bind_param('i', $account_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $contacts[] = $row;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'contacts' => $contacts]);
} else {
    echo json_encode(['success' => false, 'error' => 'Query preparation failed: ' . $conn->error]);
}

$conn->close();
?>
