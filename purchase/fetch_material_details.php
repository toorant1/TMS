<?php
require_once '../database/db_connection.php';

if (isset($_POST['material_id'])) {
    $material_id = $_POST['material_id'];

    $query = "
        SELECT 
            mm.hsn_code,
            mm.hsn_percentage,
            mmt.make AS make,
            mmu.unit_name AS unit
        FROM master_materials mm
        INNER JOIN master_materials_make mmt ON mm.make = mmt.id
        INNER JOIN master_materials_unit mmu ON mm.unit = mmu.id
        WHERE mm.id = ?
    ";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn)); // Debugging
    }

    mysqli_stmt_bind_param($stmt, "i", $material_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result) {
        echo json_encode(mysqli_fetch_assoc($result) ?: []);
    } else {
        die("Query failed: " . mysqli_error($conn)); // Debugging
    }
}
?>
