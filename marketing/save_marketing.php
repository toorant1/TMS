<?php
require_once '../database/db_connection.php';
session_start();

header('Content-Type: application/json');

// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$master_userid = $_SESSION['master_userid'];

$account_id = isset($_POST['account_id']) ? intval($_POST['account_id']) : 0;
$contact_id = isset($_POST['contact_id']) ? intval($_POST['contact_id']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$main_cause_id = isset($_POST['main_cause_id']) ? intval($_POST['main_cause_id']) : 0;
$requirement = isset($_POST['requirement']) ? trim($_POST['requirement']) : '';
$marketing_id_status = isset($_POST['marketing_id_status']) ? intval($_POST['marketing_id_status']) : 0;
$status = isset($_POST['status']) ? intval($_POST['status']) : 1;
$created_on = date('Y-m-d H:i:s'); // Current date and time in IST
$current_year = date('Y');
$token = uniqid('marketing_', true);

// Validate inputs
$errors = [];
if (!$account_id) $errors[] = 'Account is missing';
if (!$contact_id) $errors[] = 'Contact is missing';
if (!$user_id) $errors[] = 'Sales Executive is missing';
if (!$main_cause_id) $errors[] = 'Customer Requirement is missing';
if (empty($requirement)) $errors[] = 'Details (Requirement) is missing';
if (!$marketing_id_status) $errors[] = 'Marketing Status is missing';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => 'Missing fields', 'missing_fields' => $errors]);
    exit;
}

// Step 1: Generate internal_id (MKT-Year-0001)
$internal_id = '';
$sql_fetch_last_id = "SELECT internal_id 
                      FROM master_marketing 
                      WHERE master_user_id = ? AND YEAR(m_date) = ? 
                      ORDER BY id DESC LIMIT 1";

$stmt = $conn->prepare($sql_fetch_last_id);
$stmt->bind_param('ii', $master_userid, $current_year);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    // Extract the sequence number from the last internal_id
    $last_internal_id = $row['internal_id'];
    $last_sequence = intval(substr($last_internal_id, -4)); // Get last 4 digits
    $new_sequence = str_pad($last_sequence + 1, 4, '0', STR_PAD_LEFT);
} else {
    // Start with 0001 if no records exist for this year
    $new_sequence = '0001';
}

// Generate new internal_id
$internal_id = "MKT-$current_year-$new_sequence";

// Step 2: Insert into database
$sql = "INSERT INTO master_marketing 
        (internal_id, master_user_id, account_id, contact_id, user_id, main_cause_id, requirement, marketing_id_status, status, m_date, token) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param(
        'siiiiisssss',
        $internal_id,
        $master_userid,
        $account_id,
        $contact_id,
        $user_id,
        $main_cause_id,
        $requirement,
        $marketing_id_status,
        $status,
        $created_on,
        $token
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Record inserted successfully', 'internal_id' => $internal_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database Error: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Query Preparation Failed: ' . $conn->error]);
}

$conn->close();
?>
