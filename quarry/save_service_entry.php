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

// Start a transaction
$conn->begin_transaction();

try {
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
        throw new Exception('Ticket validation failed.');
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

    if (!$stmt_insert->execute()) {
        throw new Exception('Failed to save service entry.');
    }

    // Update ticket_status_id in master_tickets
    $query_update_ticket = "
        UPDATE master_tickets
        SET ticket_status_id = ?
        WHERE id = ? AND master_user_id = ?
    ";
    $stmt_update = $conn->prepare($query_update_ticket);
    $stmt_update->bind_param("iii", $ticket_status, $ticket_id, $_SESSION['master_userid']);

    if (!$stmt_update->execute()) {
        throw new Exception('Failed to update ticket status.');
    }

    // Commit the transaction if all queries succeed
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Service entry saved and ticket status updated successfully.']);

} catch (Exception $e) {
    // Rollback the transaction on any error
    $conn->rollback();

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // Close all prepared statements
    if (isset($stmt_validate)) $stmt_validate->close();
    if (isset($stmt_insert)) $stmt_insert->close();
    if (isset($stmt_update)) $stmt_update->close();

    // Close the database connection
    $conn->close();
}
?>
