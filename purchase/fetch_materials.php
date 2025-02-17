<?php
require_once '../database/db_connection.php';
session_start();

$master_user_id = $_SESSION['master_userid']; 

$query = "SELECT id, name FROM master_materials WHERE master_user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $master_user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$materials = [];
while ($row = mysqli_fetch_assoc($result)) {
    $materials[] = ['id' => $row['id'], 'name' => $row['name']];
}

header('Content-Type: application/json');
echo json_encode($materials);
?>
