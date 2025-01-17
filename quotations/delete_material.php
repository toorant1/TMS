<?php
// Include database connection
require_once '../database/db_connection.php';

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate the material_id
if (empty($data['material_id'])) {
    echo json_encode(['success' => false, 'message' => 'Material ID is required.']);
    exit;
}

$material_id = intval($data['material_id']);

// Delete the material from the database
$query = "DELETE FROM master_quotations_materials WHERE master_quotation_material_id = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $material_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Material deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Material not found or already deleted.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare the delete query.']);
}

$conn->close();
?>
