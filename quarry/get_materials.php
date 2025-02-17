<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$master_userid = $_SESSION['master_userid'];

$materials_query = "SELECT id, material_name FROM materials WHERE master_user_id = ? ORDER BY material_name ASC";
$materials_stmt = $conn->prepare($materials_query);
$materials_stmt->bind_param("i", $master_userid);
$materials_stmt->execute();
$materials_result = $materials_stmt->get_result();
$materials = $materials_result->fetch_all(MYSQLI_ASSOC);

echo json_encode($materials);
?>
