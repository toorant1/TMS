<?php
require_once '../database/db_connection.php';

$material_type = $_POST['material_type'] ?? '';
$make = $_POST['make'] ?? '';

if (!$material_type || !$make) {
    echo json_encode([]);
    exit;
}

$query = "SELECT id, name FROM master_materials WHERE material_type = ? AND make = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $material_type, $make);
$stmt->execute();
$result = $stmt->get_result();

$materials = [];
while ($row = $result->fetch_assoc()) {
    $materials[] = $row;
}

echo json_encode($materials);
?>
