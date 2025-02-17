<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo "<tr><td colspan='13' class='text-center text-danger'>User not authenticated.</td></tr>";
    exit;
}

$master_userid = $_SESSION['master_userid'];
$customer_id = $_POST['account_id'] ?? '';
$group_by = $_POST['group_by'] ?? '';
$from_date = $_POST['from_date'] ?? date('Y-m-01');
$to_date = $_POST['to_date'] ?? date('Y-m-t');

$whereClauses = ["mqd.master_user_id = ?", "acc.id = ?", "mqd.entry_date BETWEEN ? AND ?"];
$params = [$master_userid, $customer_id, $from_date, $to_date];

$groupBySQL = "";
$groupColumn = "";
$validGroups = [
    'company' => 'company_name',
    'material' => 'material_name',
    'vehicle' => 'vehicle',
    'entry_date' => 'entry_date',
    'royalty_name' => 'royalty_name'
];
if (!empty($group_by) && array_key_exists($group_by, $validGroups)) {
    $groupBySQL = "GROUP BY " . $validGroups[$group_by] . " ORDER BY " . $validGroups[$group_by] . ", mqd.entry_date DESC";
    $groupColumn = $validGroups[$group_by];
} else {
    $groupBySQL = "ORDER BY mqd.id DESC";
}

$whereSQL = implode(" AND ", $whereClauses);
$query = "SELECT acc.account_name, mqd.entry_date, mc.company_name AS company_name, mm.name AS material_name, 
                 mqd.vehicle, mqd.delivery_challan, mqd.gross_weight, mqd.tare_weight, mqd.net_weight, 
                 mqd.royalty_weight, mqd.royalty_name, mqd.royalty_pass_no, mqd.royalty_pass_count, mqd.ssp_no
          FROM master_quarry_dispatch_data mqd
          INNER JOIN master_company mc ON mqd.company_name_id = mc.id
          INNER JOIN account acc ON mqd.customer_name_id = acc.id
          INNER JOIN master_materials mm ON mqd.material_id = mm.id
          WHERE $whereSQL
          $groupBySQL";  


$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

$total_gross_weight = 0;
$total_tare_weight = 0;
$total_net_weight = 0;
$total_royalty_weight = 0;
$total_royalty_pass_count = 0;

$current_group = null;
$subtotal_gross_weight = 0;
$subtotal_tare_weight = 0;
$subtotal_net_weight = 0;
$subtotal_royalty_weight = 0;
$subtotal_royalty_pass_count = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $groupValue = ($groupColumn && isset($row[$groupColumn])) ? $row[$groupColumn] : "No Data";

        if ($groupColumn && $current_group !== $groupValue) {
            if ($current_group !== null) {
                // Print Subtotal Row for the previous group
                echo "<tr style='font-weight: bold; background: #f8f9fa;'>
                        <td colspan='5' class='text-end'>Subtotal ({$current_group})</td>
                        <td>{$subtotal_gross_weight}</td>
                        <td>{$subtotal_tare_weight}</td>
                        <td>{$subtotal_net_weight}</td>
                        <td style='color: red;'>{$subtotal_royalty_weight}</td>
                        <td colspan='2'></td>
                        <td style='color: red;'>{$subtotal_royalty_pass_count}</td>
                        <td></td>
                      </tr>";
            }

            // Ensure even empty groups get printed
            echo "<tr style='background: #007bff; color: white; font-weight: bold;'>
                    <td colspan='13'>{$groupValue}</td>
                  </tr>";

            // Reset Subtotals for the new group
            $current_group = $groupValue;
            $subtotal_gross_weight = 0;
            $subtotal_tare_weight = 0;
            $subtotal_net_weight = 0;
            $subtotal_royalty_weight = 0;
            $subtotal_royalty_pass_count = 0;
        }

        // Accumulate Totals & Subtotals
        $total_gross_weight += $row['gross_weight'];
        $total_tare_weight += $row['tare_weight'];
        $total_net_weight += $row['net_weight'];
        $total_royalty_weight += $row['royalty_weight'];
        $total_royalty_pass_count += $row['royalty_pass_count'];

        $subtotal_gross_weight += $row['gross_weight'];
        $subtotal_tare_weight += $row['tare_weight'];
        $subtotal_net_weight += $row['net_weight'];
        $subtotal_royalty_weight += $row['royalty_weight'];
        $subtotal_royalty_pass_count += $row['royalty_pass_count'];

        // Print Data Row
        echo "<tr>
                <td>" . htmlspecialchars($row['entry_date']) . "</td>
                <td>" . htmlspecialchars($row['company_name']) . "</td>
                <td>" . htmlspecialchars($row['material_name']) . "</td>
                <td>" . htmlspecialchars($row['vehicle']) . "</td>
                <td>" . htmlspecialchars($row['delivery_challan']) . "</td>
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

    // Print Final Subtotal Row (for the last group)
    if ($current_group !== null) {
        echo "<tr style='font-weight: bold; background: #f8f9fa;'>
                <td colspan='5' class='text-end'>Subtotal ({$current_group})</td>
                <td>{$subtotal_gross_weight}</td>
                <td>{$subtotal_tare_weight}</td>
                <td>{$subtotal_net_weight}</td>
                <td style='color: red;'>{$subtotal_royalty_weight}</td>
                <td colspan='2'></td>
                <td style='color: red;'>{$subtotal_royalty_pass_count}</td>
                <td></td>
              </tr>";
    }

    // Print Grand Total Row at the bottom
    echo "<tr style='font-weight: bold; background: #dee2e6;'>
            <td colspan='5' class='text-end'>Grand Total</td>
            <td>{$total_gross_weight}</td>
            <td>{$total_tare_weight}</td>
            <td>{$total_net_weight}</td>
            <td style='color: red;'>{$total_royalty_weight}</td>
            <td colspan='2'></td>
            <td style='color: red;'>{$total_royalty_pass_count}</td>
            <td></td>
          </tr>";
}

$stmt->close();
$conn->close();
?>