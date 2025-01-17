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

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Update the service entry in master_tickets_services
        $query_update_service = "UPDATE master_tickets_services 
                                 SET 
                                   service_date = ?, 
                                   engineer_id = ?, 
                                   remark_internal = ?, 
                                   remark_external = ?, 
                                   ticket_status = ? 
                                 WHERE id = ?";
        $stmt_service = $conn->prepare($query_update_service);
        if (!$stmt_service) {
            throw new Exception('Database preparation error for service update: ' . $conn->error);
        }

        $stmt_service->bind_param("sissii", $service_date, $engineer_id, $remark_internal, $remark_external, $ticket_status, $service_id);
        if (!$stmt_service->execute()) {
            throw new Exception('Failed to update service entry: ' . $stmt_service->error);
        }

        // Fetch the related ticket ID from master_tickets_services
        $query_fetch_ticket_id = "SELECT ticket_id FROM master_tickets_services WHERE id = ?";
        $stmt_fetch_ticket = $conn->prepare($query_fetch_ticket_id);
        if (!$stmt_fetch_ticket) {
            throw new Exception('Database preparation error for ticket fetch: ' . $conn->error);
        }

        $stmt_fetch_ticket->bind_param("i", $service_id);
        $stmt_fetch_ticket->execute();
        $result_fetch_ticket = $stmt_fetch_ticket->get_result();
        if ($result_fetch_ticket->num_rows === 0) {
            throw new Exception('No related ticket found for the given service ID.');
        }

        $ticket_row = $result_fetch_ticket->fetch_assoc();
        $ticket_id = $ticket_row['ticket_id'];

        // Update ticket_status_id in master_tickets
        $query_update_ticket = "UPDATE master_tickets 
                                SET ticket_status_id = ? 
                                WHERE id = ?";
        $stmt_ticket = $conn->prepare($query_update_ticket);
        if (!$stmt_ticket) {
            throw new Exception('Database preparation error for ticket update: ' . $conn->error);
        }

        $stmt_ticket->bind_param("ii", $ticket_status, $ticket_id);
        if (!$stmt_ticket->execute()) {
            throw new Exception('Failed to update ticket status: ' . $stmt_ticket->error);
        }

        // Commit the transaction
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Service entry and ticket status updated successfully.']);
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();

        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        // Close statements and connection
        if (isset($stmt_service)) $stmt_service->close();
        if (isset($stmt_fetch_ticket)) $stmt_fetch_ticket->close();
        if (isset($stmt_ticket)) $stmt_ticket->close();
        $conn->close();
    }
}
?>
