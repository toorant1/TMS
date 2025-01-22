<?php

// This API created to provide the list of bills in time range. 
// Date range max 3 Month
// status id is not mendatory

require_once '../../database/db_connection.php';
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $master_user_id = isset($_GET['master_user_id']) ? intval($_GET['master_user_id']) : null;
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;
    $status_id = isset($_GET['status_id']) ? intval($_GET['status_id']) : null;

    if (!$master_user_id || !$from_date || !$to_date) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'error' => 'Invalid input. Please provide master_user_id, from_date, and to_date.',
            'code' => 400
        ]);
        exit;
    }

    // Validate the date range (max 3 months)
    $startDate = new DateTime($from_date);
    $endDate = new DateTime($to_date);
    $interval = $startDate->diff($endDate);

    if ($interval->m > 3 || $interval->y > 0) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'error' => 'Date range exceeds 3 months. Please provide a valid range.',
            'code' => 400
        ]);
        exit;
    }

    try {
        $ticketQuery = "
            SELECT 
                mt.id AS `Ticket ID`, 
                mt.ticket_id AS `Internal Ticket ID`, 
                mt.ticket_date AS `Ticket Date`, 
                acc.account_name AS `Account Name`,
                IFNULL(mtt.ticket_type, 'N/A') AS `Ticket Type`,
                IFNULL(ms.status_name, 'N/A') AS `Ticket Status`,
                i.bill_no AS `Bill No`,
                i.bill_date AS `Bill Date`,
                i.due_date AS `Due Date`,
                i.amount AS `Bill Amount`,
                IFNULL(SUM(pr.payment_amount), 0) AS `Total Payment Received`,
                (i.amount - IFNULL(SUM(pr.payment_amount), 0)) AS `Outstanding Amount`
            FROM 
                master_tickets mt
            LEFT JOIN 
                master_tickets_types mtt ON mt.ticket_type_id = mtt.id
            LEFT JOIN 
                master_tickets_status ms ON mt.ticket_status_id = ms.id
            LEFT JOIN 
                account acc ON mt.account_id = acc.id
            LEFT JOIN 
                master_invoices i ON mt.id = i.ticket_id
            LEFT JOIN 
                payment_receipts pr ON i.id = pr.invoice_id
            WHERE 
                mt.master_user_id = ?
                AND DATE(mt.ticket_date) BETWEEN ? AND ?
                " . ($status_id ? " AND mt.ticket_status_id = ?" : "") . "
            GROUP BY 
                mt.id, i.id, i.bill_no, i.bill_date, i.due_date, i.amount, acc.account_name, mtt.ticket_type, ms.status_name
            ORDER BY 
                mt.ticket_date DESC, mt.id DESC
        ";

        if ($status_id) {
            $ticketStmt = $conn->prepare($ticketQuery);
            $ticketStmt->bind_param("issi", $master_user_id, $from_date, $to_date, $status_id);
        } else {
            $ticketStmt = $conn->prepare($ticketQuery);
            $ticketStmt->bind_param("iss", $master_user_id, $from_date, $to_date);
        }
        $ticketStmt->execute();
        $ticketResult = $ticketStmt->get_result();

        $tickets = [];
        while ($row = $ticketResult->fetch_assoc()) {
            $tickets[] = $row;
        }
        $ticketStmt->close();

        // Success response with status code 200
        http_response_code(200); // OK
        echo json_encode([
            'code' => 200,
            'message' => 'Tickets retrieved successfully.',
            'tickets' => $tickets
        ]);
    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode([
            'error' => 'Error fetching tickets: ' . $e->getMessage(),
            'code' => 500
        ]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'error' => 'Invalid request method. Use GET.',
        'code' => 405
    ]);
}
?>
