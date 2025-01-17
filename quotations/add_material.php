<?php
require_once '../database/db_connection.php';
session_start();

header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['master_userid']) || empty($_SESSION['master_userid'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
$quotation_id = isset($data['quotation_id']) ? intval($data['quotation_id']) : null;
$material_id = isset($data['material_id']) ? intval($data['material_id']) : null;
$quantity = isset($data['quantity']) ? floatval($data['quantity']) : 0;
$unit_price = isset($data['unit_price']) ? floatval($data['unit_price']) : 0;
$remark = isset($data['remark']) ? trim($data['remark']) : '';

if (!$quotation_id || !$material_id || $quantity <= 0 || $unit_price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

// Fetch HSN details for the material
$hsn_code = '1234'; // Default HSN Code
$hsn_percentage = 18; // Default HSN Percentage
$hsn_query = "SELECT hsn_code, hsn_percentage FROM master_materials WHERE id = ? AND master_user_id = ?";
$stmt = $conn->prepare($hsn_query);
$stmt->bind_param("ii", $material_id, $_SESSION['master_userid']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $hsn_code = $row['hsn_code'];
    $hsn_percentage = $row['hsn_percentage'];
}
$stmt->close();

// Insert the new material
$insert_query = "
    INSERT INTO master_quotations_materials 
    (master_quotation_id, material_id, quantity, unit_price, hsn_code, hsn_percentage, master_quotation_materials_remark) 
    VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param(
    "iiddsss",
    $quotation_id,
    $material_id,
    $quantity,
    $unit_price,
    $hsn_code,
    $hsn_percentage,
    $remark
);

if ($stmt->execute()) {
    $new_material_id = $stmt->insert_id;
    echo json_encode([
        'success' => true,
        'new_material' => [
            'master_quotation_material_id' => $new_material_id,
            'material_name' => $material_id, // This should match the name from your DB
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'hsn_code' => $hsn_code,
            'hsn_percentage' => $hsn_percentage,
            'master_quotation_materials_remark' => $remark,
        ],
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to insert material']);
}
$stmt->close();
$conn->close();
?>
