<?php
require_once '../database/db_connection.php';
session_start();

header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// Validate the input
$ticket_id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$service_date = isset($_POST['service_date']) ? $_POST['service_date'] : '';
$engineer_id = isset($_POST['engineer_name']) ? (int)$_POST['engineer_name'] : 0;
$internal_remark = isset($_POST['internal_remark']) ? trim($_POST['internal_remark']) : '';
$external_remark = isset($_POST['external_remark']) ? trim($_POST['external_remark']) : '';
$ticket_status = isset($_POST['ticket_status']) ? (int)$_POST['ticket_status'] : 0;

// Validate required fields
if ($ticket_id <= 0 || empty($token) || empty($service_date) || $engineer_id <= 0 || $ticket_status <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

// Verify the ticket and token
$query_validate_ticket = "
    SELECT id 
    FROM master_tickets 
    WHERE id = ? AND ticket_token = ? AND master_user_id = ?
";
$stmt_validate = $conn->prepare($query_validate_ticket);
$stmt_validate->bind_param("isi", $ticket_id, $token, $_SESSION['master_userid']);
$stmt_validate->execute();
$result_validate = $stmt_validate->get_result();

if ($result_validate->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Ticket validation failed.']);
    exit;
}

// Insert the service entry
$query_insert_service = "
    INSERT INTO master_tickets_services 
    (ticket_id, master_user_id, service_date, remark_internal, remark_external, engineer_id, ticket_status, token) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
";
$service_token = bin2hex(random_bytes(16)); // Generate a random token
$stmt_insert = $conn->prepare($query_insert_service);
$stmt_insert->bind_param(
    "iisssiis",
    $ticket_id,
    $_SESSION['master_userid'],
    $service_date,
    $internal_remark,
    $external_remark,
    $engineer_id,
    $ticket_status,
    $service_token
);

if ($stmt_insert->execute()) {
    echo json_encode(['success' => true, 'message' => 'Service entry saved successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save service entry.']);
}

// Close connections
$stmt_validate->close();
$stmt_insert->close();
$conn->close();
?>
