<?php
require_once '../database/db_connection.php';
require_once '../vendor/autoload.php'; // Load Dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();

if (!isset($_SESSION['master_userid'])) {
    die("User not authenticated.");
}

$master_userid = $_SESSION['master_userid'];
$filters = $_GET;

$whereClauses = ["mqd.master_user_id = ?"];
$params = [$master_userid];

// Ensure the account_id is included and valid
if (!empty($filters['account_id'])) {
    $whereClauses[] = "acc.id = ?";
    $params[] = $filters['account_id'];
} else {
    die("Invalid or missing account ID.");
}

if (!empty($filters['from_date'])) {
    $whereClauses[] = "mqd.entry_date >= ?";
    $params[] = $filters['from_date'];
}

if (!empty($filters['to_date'])) {
    $whereClauses[] = "mqd.entry_date <= ?";
    $params[] = $filters['to_date'];
}

if (!empty($filters['group_by']) && $filters['group_by'] !== "") {
    $groupByColumn = $filters['group_by'];
} else {
    $groupByColumn = null;
}

$whereSQL = implode(" AND ", $whereClauses);
$query = "SELECT mqd.entry_date, mc.company_name, acc.account_name AS customer_name, mm.name AS material_name,
                 mqd.vehicle, mqd.delivery_challan, mqd.gross_weight, mqd.tare_weight, mqd.net_weight, mqd.royalty_weight,
                 mqd.royalty_name, mqd.royalty_pass_no, mqd.royalty_pass_count, mqd.ssp_no
          FROM master_quarry_dispatch_data mqd
          INNER JOIN master_company mc ON mqd.company_name_id = mc.id
          INNER JOIN account acc ON mqd.customer_name_id = acc.id
          INNER JOIN master_materials mm ON mqd.material_id = mm.id
          WHERE $whereSQL
          ORDER BY mqd.entry_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Generate HTML for PDF
$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dispatch Records PDF</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 8px; text-align: center; }
        th { background-color: #007bff; color: #fff; }
    </style>
</head>
<body>
    <div class="header">Dispatch Records</div>
    <table>
        <thead>
            <tr>
                <th>Entry Date</th>
                <th>Company</th>
                <th>Customer</th>
                <th>Material</th>
                <th>Vehicle</th>
                <th>Challan No</th>
                <th>Gross Weight</th>
                <th>Tare Weight</th>
                <th>Net Weight</th>
                <th>Royalty Weight</th>
                <th>Royalty Name</th>
                <th>Royalty Pass No</th>
                <th>Royalty Pass Count</th>
                <th>SSP No</th>
            </tr>
        </thead>
        <tbody>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . htmlspecialchars($row['entry_date']) . '</td>
        <td>' . htmlspecialchars($row['company_name']) . '</td>
        <td>' . htmlspecialchars($row['customer_name']) . '</td>
        <td>' . htmlspecialchars($row['material_name']) . '</td>
        <td>' . htmlspecialchars($row['vehicle']) . '</td>
        <td>' . htmlspecialchars($row['delivery_challan']) . '</td>
        <td>' . htmlspecialchars($row['gross_weight']) . '</td>
        <td>' . htmlspecialchars($row['tare_weight']) . '</td>
        <td>' . htmlspecialchars($row['net_weight']) . '</td>
        <td>' . htmlspecialchars($row['royalty_weight']) . '</td>
        <td>' . htmlspecialchars($row['royalty_name']) . '</td>
        <td>' . htmlspecialchars($row['royalty_pass_no']) . '</td>
        <td>' . htmlspecialchars($row['royalty_pass_count']) . '</td>
        <td>' . htmlspecialchars($row['ssp_no']) . '</td>
    </tr>';
}

$html .= '</tbody></table></body></html>';

$stmt->close();
$conn->close();

// Generate PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'landscape');
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream("Dispatch_Records.pdf", ["Attachment" => true]);
?>
