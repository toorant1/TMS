<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    http_response_code(403); // Unauthorized
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$master_userid = $_SESSION['master_userid'];

$query = "SELECT id, main_cause FROM master_tickets_main_causes WHERE master_user_id = ? ORDER BY main_cause ASC";
$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to prepare fetch query.']);
    exit;
}
$stmt->bind_param("i", $master_userid);
$stmt->execute();
$result = $stmt->get_result();
$categories = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($categories);
?>
