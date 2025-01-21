<?php
require_once '../database/db_connection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$master_userid = $_SESSION['master_userid'];
$data = json_decode(file_get_contents('php://input'), true);
$from_date = $data['from_date'] ?? date('Y-m-01', strtotime('-2 months'));
$to_date = $data['to_date'] ?? date('Y-m-d');

// Fetch Ticket Status Summary
$statusCountsQuery = "
    SELECT ms.id, ms.status_name, COUNT(mt.id) AS count
    FROM master_tickets mt
    LEFT JOIN master_tickets_status ms ON mt.ticket_status_id = ms.id
    WHERE mt.master_user_id = ? AND DATE(mt.ticket_date) BETWEEN ? AND ?
    GROUP BY ms.id, ms.status_name
";
$statusStmt = $conn->prepare($statusCountsQuery);
$statusStmt->bind_param("iss", $master_userid, $from_date, $to_date);
$statusStmt->execute();
$statusResult = $statusStmt->get_result();
$statusCounts = $statusResult->fetch_all(MYSQLI_ASSOC);
$statusStmt->close();

// Fetch Tickets
$ticketsQuery = "
    SELECT mt.id AS `Ticket ID`, mt.ticket_id AS `Internal Ticket ID`, mt.ticket_date AS `Ticket Date`,
           acc.account_name AS `Account Name`, IFNULL(mtt.ticket_type, 'N/A') AS `Ticket Type`,
           IFNULL(mp.priority, 'N/A') AS `Priority`, IFNULL(ms.status_name, 'N/A') AS `Status`,
           mt.problem_statement AS `Problem Statement`
    FROM master_tickets mt
    LEFT JOIN master_tickets_types mtt ON mt.ticket_type_id = mtt.id
    LEFT JOIN master_tickets_priority mp ON mt.ticket_priority_id = mp.id
    LEFT JOIN master_tickets_status ms ON mt.ticket_status_id = ms.id
    LEFT JOIN account acc ON mt.account_id = acc.id
    WHERE mt.master_user_id = ? AND DATE(mt.ticket_date) BETWEEN ? AND ?
    ORDER BY mt.ticket_date DESC, mt.id DESC
";
$ticketsStmt = $conn->prepare($ticketsQuery);
$ticketsStmt->bind_param("iss", $master_userid, $from_date, $to_date);
$ticketsStmt->execute();
$ticketsResult = $ticketsStmt->get_result();
$tickets = $ticketsResult->fetch_all(MYSQLI_ASSOC);
$ticketsStmt->close();

echo json_encode([
    'statusCounts' => $statusCounts,
    'tickets' => $tickets,
    'from_date' => $from_date,
    'to_date' => $to_date,
]);
