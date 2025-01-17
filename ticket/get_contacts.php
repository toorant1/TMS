<?php
require_once '../database/db_connection.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

// Validate and fetch account ID
$account_id = $_POST['account_id'] ?? null;
if (!$account_id) {
    echo json_encode(['status' => 'error', 'message' => 'Customer ID is required.']);
    exit;
}

// Fetch associated contacts from the database
$query = "SELECT id, name, designation, mobile1, mobile2, email 
          FROM contacts 
          WHERE account_id = ? AND status = 1 ORDER BY name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$result = $stmt->get_result();

$contacts = [];
while ($row = $result->fetch_assoc()) {
    $contacts[] = [
        'id' => $row['id'],
        'text' => $row['name'],
        'designation' => $row['designation'],
        'mobile1' => $row['mobile1'],
        'mobile2' => $row['mobile2'],
        'email' => $row['email'],
    ];
}

echo json_encode(['status' => 'success', 'contacts' => $contacts]);

$stmt->close();
$conn->close();
?>
