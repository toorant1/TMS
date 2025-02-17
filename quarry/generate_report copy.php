<?php
require '../database/db_connection.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
if (!isset($_SESSION['master_userid'])) {
    die("Unauthorized access.");
}

$master_userid = $_SESSION['master_userid'];

if (!isset($_GET['ticket_id']) || !isset($_GET['token'])) {
    die("Invalid request.");
}

$ticket_id = trim($_GET['ticket_id']);
$ticket_token = trim($_GET['token']);

// ✅ Fetch Ticket Details
$query_ticket = "
    SELECT 
        mt.id AS id,
        mt.ticket_id, 
        DATE(mt.ticket_date) AS ticket_date, 
        IFNULL(mtt.ticket_type, 'Unknown') AS ticket_type, 
        IFNULL(mp.priority, 'Unknown') AS priority, 
        IFNULL(ms.status_name, 'Unknown') AS status, 
        IFNULL(mmc.main_cause, 'Not Provided') AS cause,
        acc.account_name, 
        acc.address, 
        acc.city, 
        acc.state, 
        acc.pincode, 
        acc.country, 
        c.name AS contact_person, 
        c.mobile1, 
        c.mobile2,
        c.email, 
        mt.problem_statement
    FROM master_tickets mt
    LEFT JOIN master_tickets_types mtt ON mt.ticket_type_id = mtt.id
    LEFT JOIN master_tickets_priority mp ON mt.ticket_priority_id = mp.id
    LEFT JOIN master_tickets_status ms ON mt.ticket_status_id = ms.id
    LEFT JOIN master_tickets_main_causes mmc ON mt.cause_id = mmc.id
    LEFT JOIN account acc ON mt.account_id = acc.id
    LEFT JOIN contacts c ON mt.contact_id = c.id
    WHERE mt.ticket_id = ? AND mt.ticket_token = ? AND mt.master_user_id = ?
";

$stmt_ticket = $conn->prepare($query_ticket);
$stmt_ticket->bind_param("isi", $ticket_id, $ticket_token, $master_userid);
$stmt_ticket->execute();
$result_ticket = $stmt_ticket->get_result();

if ($result_ticket->num_rows === 0) {
    die("Ticket not found.");
}

$ticket = $result_ticket->fetch_assoc();

// ✅ Fetch Service History
$query_services = "
    SELECT 
        mts.service_date,
        mts.remark_external,
        mu.name AS engineer_name,
        ms.status_name AS status_name
    FROM master_tickets_services mts
    LEFT JOIN master_users mu ON mts.engineer_id = mu.id
    LEFT JOIN master_tickets_status ms ON mts.ticket_status = ms.id
    WHERE mts.ticket_id = ? AND mts.master_user_id = ?
    ORDER BY mts.service_date DESC
";

$stmt_services = $conn->prepare($query_services);
$stmt_services->bind_param("ii", $ticket['id'], $master_userid);
$stmt_services->execute();
$result_services = $stmt_services->get_result();
$services = [];
while ($row = $result_services->fetch_assoc()) {
    $services[] = $row;
}

// ✅ Initialize Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$ticket_date = date('d-M-Y', strtotime($ticket['ticket_date']));

// ✅ Generate HTML for PDF
$html = "
<html>
<head>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; margin: 5px; padding: 5px; }
        .header { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .sub-header { text-align: center; font-size: 14px; margin-bottom: 5px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid black; padding: 4px; }
        .table th { background-color: #f2f2f2; text-align: left; font-size: 12px; }
        .footer { margin-top: 10px; font-size: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class='header'>{$ticket['account_name']} : Service Report</div>
    <div class='sub-header'>Ticket ID: {$ticket['ticket_id']} | Date: {$ticket_date}</div>
    

    <table class='table' style='width: 100%; border: none;'>
        <tr>
        <!-- Left Column: Ticket Details -->
         <td style='width: 50%; vertical-align: top; padding: 2px; border: none;'>
                <table class='table' style='width: 100%;'>
                    <tr><th>Account</th><td>{$ticket['account_name']}</td></tr>
                    <tr><th>Address</th><td>{$ticket['address']}, {$ticket['city']}, {$ticket['state']} - {$ticket['pincode']}, {$ticket['country']}</td></tr>
                    <tr><th>Contact Person</th><td>{$ticket['contact_person']}</td></tr>
                    <tr><th>Mobile</th><td>{$ticket['mobile1']} / {$ticket['mobile2']}</td></tr>
                    <tr><th>Email</th><td>{$ticket['email']}</td></tr>
                </table>
            </td>

            <!-- Right Column: Account & Contact Details -->
            <td style='width: 50%; vertical-align: top; padding: 2px; border: none;'>
                <table class='table' style='width: 100%;'>
                    <tr><th>Ticket Type</th><td>{$ticket['ticket_type']}</td></tr>
                    <tr><th>Priority</th><td>{$ticket['priority']}</td></tr>
                    <tr><th>Status</th><td>{$ticket['status']}</td></tr>
                    <tr><th>Main Problem</th><td>{$ticket['cause']}</td></tr>
                </table>
            </td>

            
           
        </tr>
    </table>

     <div class='header' style='font-size: 14px; text-align: left; margin-bottom: 5px;'>Problem Statement:</div>
    {$ticket['problem_statement']} <!-- Rendered bullet point list -->

    <div class='header' style='margin-top: 10px;'>Service History</div>
    <table class='table'>
        <tr>
            <th>Service Date</th>
            <th>Engineer</th>
            <th>Engineer's Remark</th>
            <th>Status</th>
        </tr>";

if ($result_services->num_rows > 0) {
    foreach ($services as $service) {
        $service_date = date('d-M-Y', strtotime($service['service_date']));
        $html .= "
        <tr>
            <td>{$service_date}</td>
            <td>{$service['engineer_name']}</td>
            <td>{$service['remark_external']}</td>
            <td>{$service['status_name']}</td>
        </tr>";
    }
} else {
    $html .= "<tr><td colspan='4' style='text-align:center;'>No service history available</td></tr>";
}

$html .= "
    </table>

    <div class='footer'>Report Generated on " . date('d-M-Y H:i:s') . "</div>
</body>
</html>";

// ✅ Load HTML into Dompdf
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// ✅ Output PDF to Browser for Download
$dompdf->stream("Ticket_Report_{$ticket['ticket_id']}.pdf", ["Attachment" => true]);

exit;
?>
