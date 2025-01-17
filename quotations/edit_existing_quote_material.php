<?php
// Include database connection
require_once '../database/db_connection.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['master_userid']) || empty($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

// Retrieve and validate query parameters
$quotation_id = isset($_GET['quotation_id']) ? intval($_GET['quotation_id']) : null;
$master_userid = $_SESSION['master_userid'];

if (is_null($quotation_id) || empty($master_userid)) {
    die("Invalid request. Missing or invalid Quotation ID or Master User ID.");
}

// Fetch materials associated with the quotation
$materials = [];
$query = "
    SELECT
        mqm.master_quotation_material_id,
        mqm.material_id,
        mm.name AS material_name,
        mqm.quantity,
        mm.hsn_code,
        mm.hsn_percentage,
        mqm.unit_price AS price,
        (mqm.quantity * mqm.unit_price) AS basic_total,
        ((mqm.quantity * mqm.unit_price) * mm.hsn_percentage / 100) AS hsn_total,
        ((mqm.quantity * mqm.unit_price) + ((mqm.quantity * mqm.unit_price) * mm.hsn_percentage / 100)) AS grand_total,
        mm.make AS material_make,
        mmt.material_type AS material_type_name,
        mqm.master_quotation_materials_remark AS material_remark
    FROM master_quotations_materials mqm
    JOIN master_materials mm ON mqm.material_id = mm.id
    JOIN master_materials_type mmt ON mm.material_type = mmt.id
    WHERE mqm.master_quotation_id = ?
";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $quotation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }
    $stmt->close();
}

// Return materials as JSON
header('Content-Type: application/json');
echo json_encode($materials);
?>
