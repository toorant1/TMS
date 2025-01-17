<?php
require_once '../database/db_connection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$master_userid = $_SESSION['master_userid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_id = $_POST['account_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $mobile1 = trim($_POST['phone'] ?? '');
    $mobile2 = trim($_POST['mobile2'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $remark = trim($_POST['remark'] ?? '');
    $token = bin2hex(random_bytes(16)); 
    $status = 1; 
    $created_on = date('Y-m-d H:i:s');
    $updated_on = $created_on;
    $updated_by = $master_userid;

    if (empty($account_id) || empty($name) || empty($mobile1)) {
        echo json_encode(['success' => false, 'error' => 'Account ID, Contact Name, and Mobile 1 are required.']);
        exit;
    }

    try {
        $query = "INSERT INTO contacts 
                  (account_id, name, designation, mobile1, mobile2, email, remark, token, status, created_on, updated_on, updated_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Failed to prepare the query: " . $conn->error);
        }

        $stmt->bind_param(
            "issssssssisi",
            $account_id,
            $name,
            $designation,
            $mobile1,
            $mobile2,
            $email,
            $remark,
            $token,
            $status,
            $created_on,
            $updated_on,
            $updated_by
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'account_id' => $account_id]);
        } else {
            throw new Exception("Failed to execute the query: " . $stmt->error);
        }
    } catch (Exception $e) {
        error_log("Error saving contact: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'An error occurred while saving the contact. Please try again later.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>
