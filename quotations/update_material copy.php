<?php
require_once '../database/db_connection.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_clean(); // Clear any unexpected output

// Get the JSON input
$dataRaw = file_get_contents('php://input');
$data = json_decode($dataRaw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit;
}

// Validate input
if (empty($data['material_id']) || empty($data['field_name']) || !isset($data['field_value'])) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters.']);
    exit;
}

$material_id = intval($data['material_id']);
$field_name = $data['field_name'];
$field_value = $data['field_value'];

// Validate the field name
$allowedFields = ['quantity', 'unit_price', 'master_quotation_materials_remark'];
if (!in_array($field_name, $allowedFields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field name.']);
    exit;
}

// Validate numeric fields
if (($field_name === 'quantity' || $field_name === 'unit_price') && (!is_numeric($field_value) || $field_value <= 0)) {
    echo json_encode(['success' => false, 'message' => 'Invalid value for quantity or unit price.']);
    exit;
}

// Escape special characters to avoid SQL injection
$field_value = mysqli_real_escape_string($conn, $field_value);

// Prepare and execute the update query
$query = "UPDATE master_quotation_materials SET $field_name = ? WHERE master_quotation_material_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare query.', 'error' => $conn->error]);
    exit;
}

$stmt->bind_param('si', $field_value, $material_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Material updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update material.', 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
