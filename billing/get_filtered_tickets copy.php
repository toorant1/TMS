<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo "Unauthorized access";
    exit;
}

$master_userid = $_SESSION['master_userid'];
$status = $_GET['status'] ?? null;
$from_date = $_GET['from_date'] ?? date('Y-m-01', strtotime('-2 months'));
$to_date = $_GET['to_date'] ?? date('Y-m-d');

$query = "
    SELECT 
        mt.id AS `Ticket ID`, 
        mt.ticket_id AS `Internal Ticket ID`, 
        mt.ticket_date AS `Ticket Date`, 
        acc.account_name AS `Account Name`,
        IFNULL(mtt.ticket_type, 'N/A') AS `Ticket Type`,
        IFNULL(ms.status_name, 'N/A') AS `Status`,
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
        mt.master_user_id = ? AND
        (ms.id = ? OR ? IS NULL) AND
        DATE(mt.ticket_date) BETWEEN ? AND ?
    GROUP BY 
        mt.id, i.id, i.bill_no, i.bill_date, i.due_date, i.amount, acc.account_name, mtt.ticket_type, ms.status_name
    ORDER BY 
        mt.ticket_date DESC, mt.id DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iisss", $master_userid, $status, $status, $from_date, $to_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<div class="table-responsive">';
    echo '<table class="table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Ticket ID</th><th>Ticket Date</th><th>Account Name</th><th>Ticket Type</th><th>Ticket Status</th><th>Bill No</th><th>Bill Date</th><th>Due Date</th><th>Bill Amount</th><th>Total Payment Received</th><th>Outstanding Amount</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    $currentTicketId = null;
    while ($row = $result->fetch_assoc()) {
        if ($currentTicketId !== $row['Ticket ID']) {
            $currentTicketId = $row['Ticket ID'];
            echo '<tr>';
            echo '<td rowspan="1"><a href="ticket_details.php?ticket_id=' . urlencode($row['Ticket ID']) . '">' . htmlspecialchars($row['Internal Ticket ID']) . '</a></td>';
            echo '<td rowspan="1">' . htmlspecialchars(date('d-M-Y', strtotime($row['Ticket Date']))) . '</td>';
            echo '<td rowspan="1">' . htmlspecialchars($row['Account Name']) . '</td>';
            echo '<td rowspan="1">' . htmlspecialchars($row['Ticket Type']) . '</td>';
            echo '<td rowspan="1">' . htmlspecialchars($row['Status']) . '</td>';
        } else {
            echo '<tr>';
            echo '<td colspan="5"></td>';
        }
        echo '<td>' . htmlspecialchars($row['Bill No'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars(date('d-M-Y', strtotime($row['Bill Date']))) . '</td>';
        echo '<td style="background-color: ' . ($row['Outstanding Amount'] > 0 ? ($row['Due Date'] < date('Y-m-d') ? '#f8d7da' : ($row['Due Date'] <= date('Y-m-d', strtotime('+7 days')) ? '#fff3cd' : '#d4edda')) : 'transparent') . ';">';
        echo htmlspecialchars(date('d-M-Y', strtotime($row['Due Date']))) . '</td>';
        echo '<td>' . number_format($row['Bill Amount'], 2) . '</td>';
        echo '<td>' . number_format($row['Total Payment Received'], 2) . '</td>';
        echo '<td style="color: ' . ($row['Outstanding Amount'] == 0 ? 'green' : ($row['Outstanding Amount'] < $row['Bill Amount'] ? 'orange' : 'red')) . '; background-color: ' . ($row['Outstanding Amount'] == 0 ? '#d4edda' : ($row['Outstanding Amount'] < $row['Bill Amount'] ? '#fff3cd' : '#f8d7da')) . ';">';
        echo number_format($row['Outstanding Amount'], 2) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
} else {
    echo '<div>No tickets found.</div>';
}
?>
