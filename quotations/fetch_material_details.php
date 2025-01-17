<?php
require_once '../database/db_connection.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['master_userid']) || empty($_SESSION['master_userid'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get the selected material ID from the request
$material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : 0;

if ($material_id <= 0) {
    echo json_encode(['error' => 'Invalid material ID']);
    exit;
}

// Query the database for the material details
$query = "
    SELECT 
        m.unit AS unit_id,
        u.unit_name,
        m.hsn_code AS gst_code,
        m.hsn_percentage AS gst_percentage,
        mm.make AS material_make, 
        mt.material_type AS material_type_name
    FROM master_materials m
    LEFT JOIN master_materials_make mm ON m.make = mm.id
    LEFT JOIN master_materials_type mt ON m.material_type = mt.id
    LEFT JOIN master_materials_unit u ON m.unit = u.id
    WHERE m.id = ? AND m.master_user_id = ? AND m.status = 1";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("ii", $material_id, $_SESSION['master_userid']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $materialDetails = $result->fetch_assoc();
        echo json_encode($materialDetails);
    } else {
        echo json_encode(['error' => 'Material not found']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
}
?>
