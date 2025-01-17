<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid']) || empty($_SESSION['master_userid'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Validate the material ID if provided
$material_id = isset($_POST['material_id']) ? intval($_POST['material_id']) : null;

// If material ID is provided, fetch a single material
if ($material_id) {
    $query = "
        SELECT 
            id AS material_id, 
            name AS material_name, 
            hsn_code, 
            hsn_percentage 
        FROM master_materials 
        WHERE master_user_id = ? AND id = ? AND status = 1
    ";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ii", $_SESSION['master_userid'], $material_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode($row, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['error' => 'Material not found']);
        }
        $stmt->close();
    } else {
        echo json_encode(['error' => 'Failed to prepare query']);
    }
    exit;
}

// Fetch all active materials for the dropdown
$query = "
    SELECT id AS material_id, 
           name AS material_name 
    FROM master_materials 
    WHERE master_user_id = ? AND status = 1
";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['master_userid']);
    $stmt->execute();
    $result = $stmt->get_result();
    $materials = [];
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }
    echo json_encode($materials, JSON_UNESCAPED_UNICODE);
    $stmt->close();
} else {
    echo json_encode(['error' => 'Failed to prepare query']);
}
?>
