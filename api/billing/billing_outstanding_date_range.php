<?php
require_once '../../database/db_connection.php';
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $master_user_id = isset($_GET['master_user_id']) ? intval($_GET['master_user_id']) : null;
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;

    if (!$master_user_id || !$from_date || !$to_date) {
        echo json_encode(['error' => 'Invalid input. Please provide master_user_id, from_date, and to_date.']);
        exit;
    }

    try {
        // Fetch status counts
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
        $statusStmt->bind_param("iss", $master_user_id, $from_date, $to_date);
        $statusStmt->execute();
        $statusResult = $statusStmt->get_result();
        $statusCounts = [];
        while ($row = $statusResult->fetch_assoc()) {
            $statusCounts[] = $row;
        }
        $statusStmt->close();

        // Fetch billing summary
        $billingQuery = "
            SELECT 
                SUM(i.amount) AS totalBillingAmount,
                SUM(IFNULL(pr.payment_amount, 0)) AS totalReceiptAmount,
                SUM(i.amount - IFNULL(pr.payment_amount, 0)) AS totalOutstandingAmount
            FROM 
                master_tickets mt
            LEFT JOIN 
                master_invoices i ON mt.id = i.ticket_id
            LEFT JOIN 
                payment_receipts pr ON i.id = pr.invoice_id
            WHERE 
                mt.master_user_id = ? 
                AND DATE(mt.ticket_date) BETWEEN ? AND ?
        ";
        $billingStmt = $conn->prepare($billingQuery);
        $billingStmt->bind_param("iss", $master_user_id, $from_date, $to_date);
        $billingStmt->execute();
        $billingResult = $billingStmt->get_result();
        $billingSummary = $billingResult->fetch_assoc();
        $billingStmt->close();

        // Calculate overdue and next week due amounts
        $overdueAmount = 0;
        $nextWeekDueAmount = 0;
        $today = strtotime(date('Y-m-d'));
        $nextWeek = strtotime('+7 days', $today);

        $dueDatesQuery = "
            SELECT 
                i.due_date, 
                (i.amount - IFNULL(SUM(pr.payment_amount), 0)) AS outstandingAmount
            FROM 
                master_tickets mt
            LEFT JOIN 
                master_invoices i ON mt.id = i.ticket_id
            LEFT JOIN 
                payment_receipts pr ON i.id = pr.invoice_id
            WHERE 
                mt.master_user_id = ? 
                AND DATE(mt.ticket_date) BETWEEN ? AND ?
            GROUP BY 
                i.id, i.due_date, i.amount
        ";
        $dueDatesStmt = $conn->prepare($dueDatesQuery);
        $dueDatesStmt->bind_param("iss", $master_user_id, $from_date, $to_date);
        $dueDatesStmt->execute();
        $dueDatesResult = $dueDatesStmt->get_result();

        while ($row = $dueDatesResult->fetch_assoc()) {
            $dueDate = strtotime($row['due_date']);
            if ($row['outstandingAmount'] > 0) {
                if ($dueDate < $today) {
                    $overdueAmount += $row['outstandingAmount'];
                }
                if ($dueDate >= $today && $dueDate <= $nextWeek) {
                    $nextWeekDueAmount += $row['outstandingAmount'];
                }
            }
        }
        $dueDatesStmt->close();

        $billingSummary['overdueAmount'] = $overdueAmount;
        $billingSummary['nextWeekDueAmount'] = $nextWeekDueAmount;

        echo json_encode([
            'statusCounts' => $statusCounts,
            'billingSummary' => $billingSummary
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error fetching billing summary: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method. Use GET.']);
}
?>
