<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$master_userid = $_SESSION['master_userid'];
$main_cause = trim($_POST['main_cause'] ?? ''); // Trim whitespace for better validation

if (empty($main_cause)) {
    echo json_encode(['status' => 'error', 'message' => 'Category name is required.']);
    exit;
}

// Check for duplicate main_cause for the same master_user_id
$check_query = "SELECT id FROM master_tickets_main_causes WHERE master_user_id = ? AND main_cause = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("is", $master_userid, $main_cause);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Category already exists.']);
    $check_stmt->close();
    exit;
}

$check_stmt->close();

// Insert the new category into the database
$query = "INSERT INTO master_tickets_main_causes (master_user_id, main_cause, status) VALUES (?, ?, 1)";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $master_userid, $main_cause);

if ($stmt->execute()) {
    $new_id = $stmt->insert_id;
    echo json_encode([
        'status' => 'success',
        'message' => 'Category added successfully.',
        'main_cause' => ['id' => $new_id, 'text' => $main_cause],
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error saving category.']);
}

$stmt->close();
$conn->close();
?>
