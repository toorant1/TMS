<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo "<p class='text-danger'>User not authenticated.</p>";
    exit;
}

$master_userid = $_SESSION['master_userid'];
$filters = json_decode($_POST['filters'], true);
$whereClauses = ["mqd.master_user_id = ?"];
$params = [$master_userid];

if (!empty($filters['search'])) {
    $searchTerm = "%" . $filters['search'] . "%";
    $whereClauses[] = "(mc.company_name LIKE ? OR acc.account_name LIKE ? OR mm.name LIKE ? OR mqd.vehicle LIKE ? )";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

if (!empty($filters['from_date'])) {
    $whereClauses[] = "mqd.entry_date >= ?";
    $params[] = $filters['from_date'];
}

if (!empty($filters['to_date'])) {
    $whereClauses[] = "mqd.entry_date <= ?";
    $params[] = $filters['to_date'];
}

foreach (['company', 'customer', 'material', 'vehicle'] as $filterType) {
    if (!empty($filters[$filterType])) {
        $columnMap = [
            'company' => 'mc.company_name',
            'customer' => 'acc.account_name',
            'material' => 'mm.name',
            'vehicle' => 'mqd.vehicle'
        ];
        
        if (isset($columnMap[$filterType])) {
            $whereClauses[] = "{$columnMap[$filterType]} = ?";
            $params[] = $filters[$filterType];
        }
    }
}

$whereSQL = implode(" AND ", $whereClauses);
$query = "SELECT mqd.id, mqd.entry_date, mc.company_name, acc.account_name AS customer_name, acc.id as account_id,mm.name AS material_name, 
                 mqd.vehicle, mqd.delivery_challan, mqd.gross_weight, mqd.tare_weight, mqd.net_weight, mqd.royalty_weight, 
                 mqd.royalty_enabled, mqd.royalty_name, mqd.royalty_pass_no, mqd.royalty_pass_count, mqd.ssp_no
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

$total_gross_weight = 0;
$total_tare_weight = 0;
$total_net_weight = 0;
$total_royalty_weight = 0;
$total_royalty_pass_count = 0;

if ($result->num_rows > 0) {
    echo "<table class='table table-striped'>
            <thead>
                <tr>
                    <th>Entry Date</th>
                    <th>Challan No</th>
                    <th>Company</th>
                    <th>Customer</th>
                    <th>Material</th>
                    <th>Vehicle</th>
                    <th>Gross Weight</th>
                    <th>Tare Weight</th>
                    <th>Net Weight</th>
                    <th style='color: red;'>Royalty Weight</th>
                    <th style='color: red;'>Royalty Name</th>
                    <th style='color: red;'>Royalty Pass No</th>
                    <th style='color: red;'>Royalty Pass Count</th>
                    <th style='color: red;'>SSP No</th>
                </tr>
            </thead>
            <tbody>";
    while ($row = $result->fetch_assoc()) {
        $total_gross_weight += $row['gross_weight'];
        $total_tare_weight += $row['tare_weight'];
        $total_net_weight += $row['net_weight'];
        $total_royalty_weight += $row['royalty_weight'];
        $total_royalty_pass_count += $row['royalty_pass_count'];

        echo "<tr>
                <td>" . htmlspecialchars($row['entry_date']) . "</td>
                <td>" . htmlspecialchars($row['delivery_challan']) . "</td>
                <td>" . htmlspecialchars($row['company_name']) . "</td>
                 <td><a href='customer_details.php?account_id=" . urlencode($row['account_id']) . "' style='text-decoration: none; color: blue;'>" . htmlspecialchars($row['customer_name']) . "</a></td>
                <td>" . htmlspecialchars($row['material_name']) . "</td>
                <td>" . htmlspecialchars($row['vehicle']) . "</td>
                
                <td>" . ($row['gross_weight'] != 0 ? htmlspecialchars($row['gross_weight']) : "") . "</td>
                <td>" . ($row['tare_weight'] != 0 ? htmlspecialchars($row['tare_weight']) : "") . "</td>
                <td>" . ($row['net_weight'] != 0 ? htmlspecialchars($row['net_weight']) : "") . "</td>
                <td style='color: red;'>" . ($row['royalty_weight'] != 0 ? htmlspecialchars($row['royalty_weight']) : "") . "</td>
                <td style='color: red;'>" . htmlspecialchars($row['royalty_name']) . "</td>
                <td style='color: red;'>" . htmlspecialchars($row['royalty_pass_no']) . "</td>
                <td style='color: red;'>" . htmlspecialchars($row['royalty_pass_count']) . "</td>
                <td style='color: red;'>" . htmlspecialchars($row['ssp_no']) . "</td>
            </tr>";
    }
    echo "<tr style='font-weight: bold;'>
            <td colspan='6' class='text-end'>Total</td>
            <td>{$total_gross_weight}</td>
            <td>{$total_tare_weight}</td>
            <td>{$total_net_weight}</td>
            <td style='color: red;'>{$total_royalty_weight}</td>
            <td colspan='2'></td>
            <td style='color: red;'>{$total_royalty_pass_count}</td>
            <td></td>
          </tr>";
    echo "</tbody></table>";
} else {
    echo "<tr><td colspan='15' class='text-center text-muted'>No records found</td></tr>";
}
$stmt->close();
$conn->close();
?>