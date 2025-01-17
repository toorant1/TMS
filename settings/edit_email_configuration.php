<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../database/db_connection.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Decode JSON request
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
$id = $data['id'] ?? null;
$smtp_host = $data['smtp_host'] ?? '';
$smtp_port = $data['smtp_port'] ?? '';
$smtp_user = $data['smtp_user'] ?? '';
$smtp_password = $data['smtp_password'] ?? null; // Null indicates no update to the password
$smtp_status = $data['smtp_status'] ?? 1;

if (!$id || empty($smtp_host) || empty($smtp_port) || empty($smtp_user)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit;
}

$master_userid = $_SESSION['master_userid'];

try {
    if ($smtp_password === null || $smtp_password === '') {
        // Update without changing the password
        $query = "
            UPDATE master_email_configuration
            SET smtp_host = ?, smtp_port = ?, smtp_user = ?, smtp_status = ?
            WHERE id = ? AND master_user_id = ?
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sisiii', $smtp_host, $smtp_port, $smtp_user, $smtp_status, $id, $master_userid);
    } else {
        // Update including the password
        $query = "
            UPDATE master_email_configuration
            SET smtp_host = ?, smtp_port = ?, smtp_user = ?, smtp_password = ?, smtp_status = ?
            WHERE id = ? AND master_user_id = ?
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sissiii', $smtp_host, $smtp_port, $smtp_user, $smtp_password, $smtp_status, $id, $master_userid);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Email configuration updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update email configuration']);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
