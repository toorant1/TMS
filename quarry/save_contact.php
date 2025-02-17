<?php
require_once '../database/db_connection.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

$account_id = $_POST['account_id'] ?? null;
$contact_name = $_POST['contact_name'] ?? null;
$designation = $_POST['designation'] ?? null;
$phone1 = $_POST['phone1'] ?? null;
$phone2 = $_POST['phone2'] ?? null;
$email = $_POST['email'] ?? null;
$created_by = $_SESSION['master_userid'] ?? null;

// Validate required fields
if (!$account_id || !$contact_name || !$phone1) {
    echo json_encode(['status' => 'error', 'message' => 'Required fields are missing.']);
    exit;
}

// Insert the contact into the database
$query = "INSERT INTO contacts (account_id, name, designation, mobile1, mobile2, email, status, created_on) 
          VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param("isssss", $account_id, $contact_name, $designation, $phone1, $phone2, $email);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Contact added successfully.',
        'contact' => [
            'id' => $conn->insert_id,
            'text' => $contact_name,
        ],
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add contact.']);
}

$stmt->close();
$conn->close();
?>
