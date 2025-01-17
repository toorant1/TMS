<?php
require_once '../database/db_connection.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['material_id'], $data['quantity'], $data['unit_price'], $data['remark'])) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters.']);
    exit;
}

$material_id = intval($data['material_id']);
$quantity = floatval($data['quantity']);
$unit_price = floatval($data['unit_price']);
$remark = mysqli_real_escape_string($conn, $data['remark']);

// Update query using the correct table name
$query = "UPDATE master_quotations_materials 
          SET quantity = ?, unit_price = ?, master_quotation_materials_remark = ? 
          WHERE master_quotation_material_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare query.',
        'error' => $conn->error
    ]);
    exit;
}

$stmt->bind_param('ddsi', $quantity, $unit_price, $remark, $material_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Material updated successfully.']);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update material.',
        'error' => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
