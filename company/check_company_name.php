<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$master_userid = $_SESSION['master_userid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $company_name = trim($input['company_name'] ?? '');

    if ($company_name === '') {
        echo json_encode(['error' => 'Company name is required']);
        exit;
    }

    // Prepare SQL to check uniqueness
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM master_company WHERE master_userid = ? AND company_name = ?");
    $stmt->bind_param("is", $master_userid, $company_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        echo json_encode(['exists' => true]); // Company name exists
    } else {
        echo json_encode(['exists' => false]); // Company name is unique
    }

    $stmt->close();
    $conn->close();
}
?>
