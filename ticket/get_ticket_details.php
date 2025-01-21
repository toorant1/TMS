<?php
require_once '../database/db_connection.php';

header('Content-Type: application/json');
session_start();

try {
    // Ensure the user is authenticated
    if (!isset($_SESSION['master_userid'])) {
        throw new Exception('Unauthorized access.');
    }

    // Get the ticket ID from the POST request
    $ticket_id = $_POST['ticket_id'] ?? null;

    if (!$ticket_id) {
        throw new Exception('Ticket ID is required.');
    }

    $master_userid = $_SESSION['master_userid'];

    // Fetch ticket details with proper joins
    $query = "
        SELECT 
            mt.ticket_id,
            mt.ticket_date,
            tp.priority AS ticket_priority,
            ts.status_name AS ticket_status,
            mc.main_cause AS main_cause,
            a.account_name AS customer_name,
            c.name AS contact_name,
            c.mobile1 AS contact_phone1,
            c.mobile2 AS contact_phone2,
            mt.problem_statement,
            a.email AS account_email_id,
            CONCAT(
                IFNULL(a.address, ''), ', ',
                IFNULL(a.district, ''), ', ',
                IFNULL(a.city, ''), ', ',
                IFNULL(a.pincode, '')
            ) AS address
        FROM master_tickets mt
        LEFT JOIN master_tickets_priority tp ON mt.ticket_priority_id = tp.id
        LEFT JOIN master_tickets_status ts ON mt.ticket_status_id = ts.id
        LEFT JOIN master_tickets_main_causes mc ON mt.cause_id = mc.id
        LEFT JOIN account a ON mt.account_id = a.id
        LEFT JOIN contacts c ON mt.contact_id = c.id
        WHERE mt.id = ? AND mt.master_user_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $ticket_id, $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('No ticket found with the provided ID.');
    }

    $ticket_data = $result->fetch_assoc();

    // Return the ticket data as JSON
    echo json_encode([
        'status' => 'success',
        'data' => $ticket_data

    ]);
} catch (Exception $e) {
    // Return an error response
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Close the database connection
$conn->close();
