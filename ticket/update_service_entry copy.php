<?php
require_once '../database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize the input
    $service_id = isset($_POST['t_l_id']) ? (int)$_POST['t_l_id'] : 0;
    $service_date = isset($_POST['service_date']) ? trim($_POST['service_date']) : null;
    $engineer_id = isset($_POST['engineer_name']) ? (int)$_POST['engineer_name'] : 0;
    $remark_internal = isset($_POST['remark_internal']) ? trim($_POST['remark_internal']) : null;
    $remark_external = isset($_POST['remark_external']) ? trim($_POST['remark_external']) : null;
    $ticket_status = isset($_POST['ticket_status']) ? (int)$_POST['ticket_status'] : 0;

    // Validate required fields
    if ($service_id <= 0 || empty($service_date) || $engineer_id <= 0 || $ticket_status <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit;
    }

    // Prepare the query to update the service entry
    $query = "UPDATE master_tickets_services 
              SET 
                service_date = ?, 
                engineer_id = ?, 
                remark_internal = ?, 
                remark_external = ?, 
                ticket_status = ? 
              WHERE id = ?";
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Database preparation error: ' . $conn->error]);
        exit;
    }

    // Bind the parameters
    $stmt->bind_param("sissii", $service_date, $engineer_id, $remark_internal, $remark_external, $ticket_status, $service_id);

    // Execute the query
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Service entry updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update service entry: ' . $stmt->error]);
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
