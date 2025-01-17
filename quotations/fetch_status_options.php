<?php
require_once '../database/db_connection.php';

$masterUserId = $_GET['master_user_id'];

$query = "SELECT quotation_status_id, status_name FROM master_quotations_status WHERE master_user_id = ? AND status_active_deactive = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $masterUserId);
$stmt->execute();
$result = $stmt->get_result();

$statusOptions = [];
while ($row = $result->fetch_assoc()) {
    $statusOptions[] = $row;
}

echo json_encode($statusOptions);
?>
