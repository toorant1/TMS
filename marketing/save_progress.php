<?php
require_once '../database/db_connection.php';
session_start();

header('Content-Type: application/json');

// Verify user session
if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Retrieve POST data
$record_id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$progress_statement = isset($_POST['progress_update']) ? trim($_POST['progress_update']) : '';
$current_status = isset($_POST['progress_status']) ? intval($_POST['progress_status']) : 0;
$future_followup = isset($_POST['future_followup']) ? 1 : 0; // Checkbox value
$followup_datetime = isset($_POST['followup_datetime']) ? trim($_POST['followup_datetime']) : null;
$progress_date = date('Y-m-d H:i:s'); // Current date and time
$followup_token = uniqid('followup_', true); // Generate a unique token

// Validate required fields
if (!$record_id || empty($progress_statement) || !$current_status || empty($token)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

// Begin transaction to ensure data integrity
$conn->begin_transaction();

try {
    // Insert the progress data into the database
    $query = "INSERT INTO master_marketing_followups 
              (marketing_id, progress_statement, current_marketing_status, progress_date, future_followup_required, followup_datetime, token) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('isissss', $record_id, $progress_statement, $current_status, $progress_date, $future_followup, $followup_datetime, $followup_token);
        if (!$stmt->execute()) {
            throw new Exception('Database Error: Failed to insert follow-up record.');
        }
        $stmt->close();
    } else {
        throw new Exception('Query Preparation Failed: Failed to prepare insert query.');
    }

    // Update the marketing_id_status field
    $updateQuery = "UPDATE master_marketing SET marketing_id_status = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    if ($updateStmt) {
        $updateStmt->bind_param('ii', $current_status, $record_id);
        if (!$updateStmt->execute()) {
            throw new Exception('Database Error: Failed to update marketing_id_status.');
        }
        $updateStmt->close();
    } else {
        throw new Exception('Query Preparation Failed: Failed to prepare update query.');
    }

    // Commit the transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Progress saved and marketing status updated successfully']);
} catch (Exception $e) {
    // Rollback the transaction on failure
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again later.']);
}

$conn->close();
?>
