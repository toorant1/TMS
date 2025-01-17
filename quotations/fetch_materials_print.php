<?php
require_once '../database/db_connection.php';

$quotation_id = isset($_GET['quotation_id']) ? intval($_GET['quotation_id']) : 0;
$grouped = isset($_GET['grouped']) ? filter_var($_GET['grouped'], FILTER_VALIDATE_BOOLEAN) : false;

if (!$quotation_id) {
    die("Invalid request. Missing Quotation ID.");
}

$query = "
    SELECT 
        mm.name AS material_name,
        mm.description AS material_description,
        mmake.make AS material_make_name,
        mt.material_type AS material_type_name,
        mu.unit_name AS unit,
        mqm.quantity,
        mqm.unit_price AS price,
        (mqm.quantity * mqm.unit_price) AS total,
        mqm.hsn_code,
        mqm.hsn_percentage,
        ((mqm.quantity * mqm.unit_price) * mqm.hsn_percentage / 100) AS hsn_total,
        ((mqm.quantity * mqm.unit_price) + ((mqm.quantity * mqm.unit_price) * mqm.hsn_percentage / 100)) AS grand_total,
        mqm.master_quotation_materials_remark
    FROM master_quotations_materials mqm
    INNER JOIN master_materials mm ON mqm.material_id = mm.id
    INNER JOIN master_materials_make mmake ON mm.make = mmake.id
    INNER JOIN master_materials_type mt ON mm.material_type = mt.id
    INNER JOIN master_materials_unit mu ON mm.unit = mu.id
    WHERE mqm.master_quotation_id = ?
    " . ($grouped ? "ORDER BY mt.material_type, mm.name" : "ORDER BY mm.name");

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $quotation_id);
$stmt->execute();
$result = $stmt->get_result();

$currentType = null;
$subtotalBasic = 0;
$subtotalHsn = 0;
$subtotalGrand = 0;
$totalBasic = 0;
$totalHsn = 0;
$totalGrand = 0;

$output = '';
while ($row = $result->fetch_assoc()) {
    if ($grouped && $currentType !== $row['material_type_name']) {
        // Close previous group with subtotals (if applicable)
        if ($currentType !== null) {
            $output .= "
                <tr class='table-secondary'>
                    <td colspan='6' class='text-end'><strong>Subtotal for $currentType:</strong></td>
                    <td>" . htmlspecialchars(number_format($subtotalBasic, 2)) . "</td>
                    <td></td>
                    <td></td>
                    <td>" . htmlspecialchars(number_format($subtotalHsn, 2)) . "</td>
                    <td>" . htmlspecialchars(number_format($subtotalGrand, 2)) . "</td>
                    <td></td>
                </tr>
            ";
        }
        // Start new group
        $currentType = $row['material_type_name'];
        $subtotalBasic = 0;
        $subtotalHsn = 0;
        $subtotalGrand = 0;

        $output .= "
            <tr class='table-primary'>
                <td colspan='12'><strong>Material Type: $currentType</strong></td>
            </tr>
        ";
    }

    // Material row
    $output .= "
        <tr>
            <td>" . htmlspecialchars($row['material_type_name']) . "</td>
            <td>" . htmlspecialchars($row['material_make_name']) . "</td>
            <td>" . htmlspecialchars($row['material_name']) . "</td>
            <td>" . htmlspecialchars($row['quantity']) . "</td>
            <td>" . htmlspecialchars($row['unit']) . "</td>
            <td>" . htmlspecialchars(number_format($row['price'], 2)) . "</td>
            <td>" . htmlspecialchars(number_format($row['total'], 2)) . "</td>
            <td>" . htmlspecialchars($row['hsn_code']) . "</td>
            <td>" . htmlspecialchars($row['hsn_percentage']) . "</td>
            <td>" . htmlspecialchars(number_format($row['hsn_total'], 2)) . "</td>
            <td>" . htmlspecialchars(number_format($row['grand_total'], 2)) . "</td>
            <td>" . htmlspecialchars($row['master_quotation_materials_remark'] ?? 'N/A') . "</td>
        </tr>
    ";

    // Update group subtotals
    $subtotalBasic += $row['total'];
    $subtotalHsn += $row['hsn_total'];
    $subtotalGrand += $row['grand_total'];

    // Update grand totals
    $totalBasic += $row['total'];
    $totalHsn += $row['hsn_total'];
    $totalGrand += $row['grand_total'];
}

// Close last group with subtotals
if ($grouped && $currentType !== null) {
    $output .= "
        <tr class='table-secondary'>
            <td colspan='6' class='text-end'><strong>Subtotal for $currentType:</strong></td>
            <td>" . htmlspecialchars(number_format($subtotalBasic, 2)) . "</td>
            <td></td>
            <td></td>
            <td>" . htmlspecialchars(number_format($subtotalHsn, 2)) . "</td>
            <td>" . htmlspecialchars(number_format($subtotalGrand, 2)) . "</td>
            <td></td>
        </tr>
    ";
}

// Add grand total row
echo $output;
$stmt->close();
?>
