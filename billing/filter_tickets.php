<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$master_userid = $_SESSION['master_userid'];
$data = json_decode(file_get_contents('php://input'), true);
$from_date = $data['from_date'] ?? date('Y-m-01', strtotime('-2 months'));
$to_date = $data['to_date'] ?? date('Y-m-d');
$status_filter = $data['status_filter'] ?? null;

// Fetch updated status counts
$statusCountsQuery = "
    SELECT 
        ms.id, 
        ms.status_name, 
        COUNT(mt.id) AS count 
    FROM 
        master_tickets mt 
    LEFT JOIN 
        master_tickets_status ms 
    ON 
        mt.ticket_status_id = ms.id 
    WHERE 
        mt.master_user_id = ? 
        AND DATE(mt.ticket_date) BETWEEN ? AND ?
    GROUP BY 
        ms.id, ms.status_name
";
$statusStmt = $conn->prepare($statusCountsQuery);
$statusStmt->bind_param("iss", $master_userid, $from_date, $to_date);
$statusStmt->execute();
$statusResult = $statusStmt->get_result();
$statusCounts = $statusResult->fetch_all(MYSQLI_ASSOC);
$statusStmt->close();

// Fetch updated billing counts
$billingCountsQuery = "
    SELECT 
        mbs.id, 
        mbs.status_name AS billing_status, 
        COUNT(i.id) AS count 
    FROM 
        master_invoices i 
    LEFT JOIN 
        master_billing_status mbs 
    ON 
        i.billing_status_id = mbs.id 
    WHERE 
        i.ticket_id IN (
            SELECT id FROM master_tickets 
            WHERE master_user_id = ? 
            AND DATE(ticket_date) BETWEEN ? AND ?
        )
    GROUP BY 
        mbs.id, mbs.status_name
";
$billingStmt = $conn->prepare($billingCountsQuery);
$billingStmt->bind_param("iss", $master_userid, $from_date, $to_date);
$billingStmt->execute();
$billingResult = $billingStmt->get_result();
$billingCounts = $billingResult->fetch_all(MYSQLI_ASSOC);
$billingStmt->close();

// Fetch tickets based on filters
$query = "
    SELECT 
        mt.id AS `Ticket ID`, 
        mt.ticket_token as `Ticket Token`,
        mt.ticket_date as `Ticket Date`,
        mt.ticket_id AS `Internal Ticket ID`, 
        mt.ticket_date AS `Ticket Date`, 
        acc.account_name AS `Account Name`,
        IFNULL(mtt.ticket_type, 'N/A') AS `Ticket Type`,
        IFNULL(mp.priority, 'N/A') AS `Priority`,
        IFNULL(ms.status_name, 'N/A') AS `Status`,
        mt.problem_statement AS `Problem Statement`
    FROM 
        master_tickets mt
    LEFT JOIN 
        master_tickets_types mtt ON mt.ticket_type_id = mtt.id
    LEFT JOIN 
        master_tickets_priority mp ON mt.ticket_priority_id = mp.id
    LEFT JOIN 
        master_tickets_status ms ON mt.ticket_status_id = ms.id
    LEFT JOIN 
        account acc ON mt.account_id = acc.id
    WHERE 
        mt.master_user_id = ? 
        AND DATE(mt.ticket_date) BETWEEN ? AND ?
";

if ($status_filter) {
    $query .= " AND mt.ticket_status_id = ?";
}

$query .= " ORDER BY mt.ticket_date DESC, mt.id DESC";

$stmt = $conn->prepare($query);

if ($status_filter) {
    $stmt->bind_param("issi", $master_userid, $from_date, $to_date, $status_filter);
} else {
    $stmt->bind_param("iss", $master_userid, $from_date, $to_date);
}

$stmt->execute();
$result = $stmt->get_result();
$tickets = [];

while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}

$stmt->close();

echo json_encode([
    'statusCounts' => $statusCounts,
    'billingCounts' => $billingCounts,
    'tickets' => $tickets,
    'from_date' => $from_date,
    'to_date' => $to_date,
]);
exit;
