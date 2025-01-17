<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo json_encode([]);
    exit;
}

$master_userid = $_SESSION['master_userid'];
$query = "SELECT id, status FROM master_marketing_status WHERE master_user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $master_userid);
$stmt->execute();
$result = $stmt->get_result();

$statuses = [];
while ($row = $result->fetch_assoc()) {
    $statuses[] = $row;
}

echo json_encode($statuses);
$stmt->close();
$conn->close();
?>
