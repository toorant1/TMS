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
$field_value = trim($data['field_value']); // Trim the value to remove unnecessary spaces

// Validate the field name (for security)
$allowedFields = [
    'payment_conditions',
    'delivery_conditions',
    'other_conditions',
    'internal_remark_conditions'
];

if (!in_array($field_name, $allowedFields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field name.']);
    exit;
}

// Escape special characters to avoid SQL injection
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
