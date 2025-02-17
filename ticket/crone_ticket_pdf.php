<?php
require_once '../database/db_connection.php';
require '../vendor/autoload.php'; // Load dependencies

use Dompdf\Dompdf;
use Dompdf\Options;

// Define the master user ID for cron job
$master_userid = 1; // Replace with your admin ID

// Set date range (last 7 days)
$to_date = date('Y-m-d');
$from_date = date('Y-m-d', strtotime('-120 days'));

// Fetch tickets assigned to the master user
$query = "SELECT mt.id, acc.account_name, mtt.ticket_type, mp.priority, ms.status_name, mt.problem_statement 
          FROM master_tickets mt
          LEFT JOIN account acc ON mt.account_id = acc.id
          LEFT JOIN master_tickets_types mtt ON mt.ticket_type_id = mtt.id
          LEFT JOIN master_tickets_priority mp ON mt.ticket_priority_id = mp.id
          LEFT JOIN master_tickets_status ms ON mt.ticket_status_id = ms.id
          WHERE mt.master_user_id = ? AND mt.ticket_date BETWEEN ? AND ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $master_userid, $from_date, $to_date);
$stmt->execute();
$result = $stmt->get_result();

// Generate HTML for PDF
$html = '<h2 style="text-align:center;">Weekly Tickets Report</h2>';
$html .= '<p><strong>Report Date:</strong> ' . date("Y-m-d H:i:s") . '</p>';
$html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Account Name</th>
                    <th>Ticket Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Problem Statement</th>
                </tr>
            </thead>
            <tbody>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
                <td>' . htmlspecialchars($row['id']) . '</td>
                <td>' . htmlspecialchars($row['account_name']) . '</td>
                <td>' . htmlspecialchars($row['ticket_type']) . '</td>
                <td>' . htmlspecialchars($row['priority']) . '</td>
                <td>' . htmlspecialchars($row['status_name']) . '</td>
                <td>' . htmlspecialchars($row['problem_statement']) . '</td>
              </tr>';
}
$html .= '</tbody></table>';

// Initialize DomPDF
$options = new Options();
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Save PDF to File
$pdfPath = __DIR__ . "/weekly_tickets_report.pdf";
file_put_contents($pdfPath, $dompdf->output());

echo "PDF Report Generated Successfully: " . $pdfPath;

$conn->close();
?>
