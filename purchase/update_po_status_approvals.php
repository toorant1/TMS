<?php
session_start();
require_once '../database/db_connection.php';

// Ensure user is logged in
if (!isset($_SESSION['master_userid'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit;
}

// Validate required fields
if (!isset($_POST['po_token'], $_POST['po_status'], $_POST['approver_remark'])) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

$po_token = $_POST['po_token'];
$po_status = (int) $_POST['po_status']; // Ensure status is an integer
$approver_remark = trim($_POST['approver_remark']);
$approver_id = $_SESSION['master_userid'];

// Define allowed status updates
$allowed_statuses = [3 => "Approved", 4 => "Rejected", 5 => "Cancelled", 6 => "Revision Required"];
if (!array_key_exists($po_status, $allowed_statuses)) {
    echo json_encode(["status" => "error", "message" => "Invalid status selection"]);
    exit;
}

// Update the PO status in the database
$sql = "UPDATE purchase_orders SET po_status = ?, updated_by = ?, updated_at = NOW() WHERE token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $po_status, $approver_id, $po_token);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    // Insert approval remarks into a log table
    $log_sql = "INSERT INTO purchase_order_remarks (po_token, approver_id, remark, status, created_at) VALUES (?, ?, ?, ?, NOW())";
    $log_stmt = $conn->prepare($log_sql);
    $log_stmt->bind_param("sisi", $po_token, $approver_id, $approver_remark, $po_status);
    $log_stmt->execute();
    $log_stmt->close();

    echo json_encode(["status" => "success", "message" => "Purchase Order status updated successfully!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update status"]);
}

$stmt->close();
$conn->close();
?>
