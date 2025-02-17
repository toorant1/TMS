<?php
session_start();
require_once '../database/db_connection.php';

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit;
}

if (!isset($_POST['token']) || !isset($_POST['status_id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request parameters"]);
    exit;
}

$token = $_POST['token'];
$status_id = (int) $_POST['status_id'];  // Convert to integer for security

// Define allowed status updates (only these can be set)
$allowed_statuses = [1 => "Draft", 2 => "Under Approval", 3 => "Approved", 4 => "Rejected", 5 => "Cancelled", 6 => "Revision Required"];

if (!array_key_exists($status_id, $allowed_statuses)) {
    echo json_encode(["status" => "error", "message" => "Invalid status ID"]);
    exit;
}

// Update the PO status in the database
$sql = "UPDATE purchase_orders SET po_status = ? WHERE token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $status_id, $token);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["status" => "success", "message" => "PO status updated to '{$allowed_statuses[$status_id]}'"]);
} else {
    echo json_encode(["status" => "error", "message" => "No changes made"]);
}

exit();
?>
