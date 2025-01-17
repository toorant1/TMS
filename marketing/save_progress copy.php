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

// Insert the progress data into the database
$query = "INSERT INTO master_marketing_followups 
          (marketing_id, progress_statement, current_marketing_status, progress_date, future_followup_required, followup_datetime, token) 
          VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param('isissss', $record_id, $progress_statement, $current_status, $progress_date, $future_followup, $followup_datetime, $followup_token);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Progress saved successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database Error: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Query Preparation Failed: ' . $conn->error]);
}

$conn->close();
?>
