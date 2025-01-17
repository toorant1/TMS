<?php
require_once '../database/db_connection.php';

header('Content-Type: application/json');

// Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($data['quotation_id']) || empty($data['field_name']) || !isset($data['field_value'])) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters.']);
    exit;
}

$quotation_id = intval($data['quotation_id']);
$field_name = $data['field_name'];
$field_value = $data['field_value'];

// Validate the field name (for security)
$allowedFields = [
    'payment_conditions',
    'delivery_conditions',
    'other_conditions',
    'internal_remark_conditions',
    'company_id',
    'quotation_status_id',
    'quotation_date',
    'quotation_valid_upto_date'
];

if (!in_array($field_name, $allowedFields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field name.']);
    exit;
}

// Handle special cases for date fields
if (in_array($field_name, ['quotation_valid_upto_date', 'quotation_date'])) {
    $date = DateTime::createFromFormat('d-m-Y', $field_value);
    if ($date) {
        $field_value = $date->format('Y-m-d'); // Format date as YYYY-MM-DD
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
        exit;
    }
}

// Escape special characters for text fields
$field_value = mysqli_real_escape_string($conn, $field_value);

// Prepare and execute the update query
$query = "UPDATE master_quotations SET $field_name = ? WHERE quotation_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare query.',
        'error' => $conn->error
    ]);
    exit;
}

// Bind parameters and execute the query
$stmt->bind_param('si', $field_value, $quotation_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Field updated successfully.']);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update field.',
        'error' => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
