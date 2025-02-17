<?php
session_start();
require_once '../database/db_connection.php';

// Ensure user is logged in
if (!isset($_SESSION['master_userid'])) {
    die("Unauthorized access!");
}

// Validate token parameter
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid request: Missing PO token.");
}

$po_token = $_GET['token'];
$master_userid = $_SESSION['master_userid'];

// Set headers for Excel file download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=purchase_order_{$po_token}.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch PO details from database
$sql = "SELECT po.po_number, po.po_date, mc.company_name, acc.account_name AS supplier, 
               mm.name AS material_name, 
               pom.make, pom.hsn_sac, pom.quantity, pom.unit_price, 
               pom.total, pom.gst_percentage, pom.gst_total, pom.grand_total,
               pom.material_description, pom.special_remark, mmu.unit_name as unit
        FROM purchase_orders po
        LEFT JOIN account acc ON po.supplier_id = acc.id
        LEFT JOIN master_company mc ON po.company_name_id = mc.id
        LEFT JOIN purchase_order_materials pom ON po.id = pom.po_id
        LEFT JOIN master_materials mm ON pom.material_id = mm.id
        LEFT Join master_materials_unit mmu on mmu.id = mm.id
        WHERE po.token = ? AND po.master_user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $po_token, $master_userid);
$stmt->execute();
$result = $stmt->get_result();

// Print column headers
echo "PO Number\tPO Date\tMaterial Name\tMaterial Description\tSpecial Remark\tMake\tHSN/SAC\tQuantity\tUnit\tUnit Price \tBasic Total \tGST %\tGST Total \tGrand Total \n";

// Print PO data in tab-separated format
while ($row = $result->fetch_assoc()) {
    echo "{$row['po_number']}\t{$row['po_date']}\t{$row['material_name']}\t{$row['material_description']}\t{$row['special_remark']}\t{$row['make']}\t{$row['hsn_sac']}\t{$row['quantity']}\t{$row['unit']}\n";
}


$stmt->close();
$conn->close();
?>
