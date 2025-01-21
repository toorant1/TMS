<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$master_userid = $_SESSION['master_userid'];
$from_date = $_GET['from_date'] ?? date('Y-m-01', strtotime('-2 months'));
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// Fetch Ticket Summary
$statusQuery = "
    SELECT ms.id, ms.status_name AS name, COUNT(mt.id) AS count
    FROM master_tickets mt
    LEFT JOIN master_tickets_status ms ON mt.ticket_status_id = ms.id
    WHERE mt.master_user_id = ? AND DATE(mt.ticket_date) BETWEEN ? AND ?
    GROUP BY ms.id, ms.status_name
";
$statusStmt = $conn->prepare($statusQuery);
$statusStmt->bind_param("iss", $master_userid, $from_date, $to_date);
$statusStmt->execute();
$statusResult = $statusStmt->get_result();
$ticketSummary = $statusResult->fetch_all(MYSQLI_ASSOC);

// Fetch Billing Summary
$billingQuery = "
    SELECT 
        IFNULL(SUM(i.amount), 0) AS totalBillingAmount,
        IFNULL(SUM(pr.payment_amount), 0) AS totalReceiptAmount,
        (IFNULL(SUM(i.amount), 0) - IFNULL(SUM(pr.payment_amount), 0)) AS totalOutstandingAmount,
        SUM(CASE WHEN i.due_date < CURDATE() THEN (i.amount - IFNULL(SUM(pr.payment_amount), 0)) ELSE 0 END) AS overdueAmount,
        SUM(CASE WHEN i.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN (i.amount - IFNULL(SUM(pr.payment_amount), 0)) ELSE 0 END) AS nextWeekDueAmount
    FROM master_invoices i
    LEFT JOIN payment_receipts pr ON i.id = pr.invoice_id
    WHERE i.master_user_id = ? AND DATE(i.bill_date) BETWEEN ? AND ?
";
$billingStmt = $conn->prepare($billingQuery);
$billingStmt->bind_param("iss", $master_userid, $from_date, $to_date);
$billingStmt->execute();
$billingSummary = $billingStmt->get_result()->fetch_assoc();

// Fetch Ticket Table
ob_start();
include 'get_filtered_tickets.php';
$ticketTable = ob_get_clean();

// Return JSON Response
echo json_encode([
    'ticketSummary' => $ticketSummary,
    'billingSummary' => $billingSummary,
    'ticketTable' => $ticketTable
]);
?>
