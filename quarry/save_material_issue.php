<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$master_userid = $_SESSION['master_userid'];
$ticket_id = $_POST['ticket_id'] ?? '';
$token = $_POST['token'] ?? '';
$material_name = $_POST['material_name'] ?? '';
$material_quantity = $_POST['material_quantity'] ?? 0;
$material_cost = $_POST['material_cost'] ?? 0.0;
$material_remark = $_POST['material_remark'] ?? '';

if (empty($ticket_id) || empty($token) || empty($material_name) || $material_quantity <= 0 || $material_cost < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    exit;
}

// Insert material issue into the database
$query = "
    INSERT INTO master_tickets_material_usage 
    (ticket_id, master_user_id, material_name, quantity, cost, remark, token) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iisidss", $ticket_id, $master_userid, $material_name, $material_quantity, $material_cost, $material_remark, $token);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Material issue saved successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save material issue.']);
}

$stmt->close();
$conn->close();
?>
