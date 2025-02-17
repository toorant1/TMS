<?php
require_once '../database/db_connection.php';
session_start();

header('Content-Type: application/json'); // Ensure JSON output

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$master_user_id = $_SESSION['master_userid']; // Get logged-in user ID

// Fetch companies linked to the master user
$sql = "SELECT id, company_name FROM master_company WHERE master_userid = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $master_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $companies = [];
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
    
    echo json_encode($companies);
} else {
    echo json_encode(['error' => 'Query failed']);
}
?>
