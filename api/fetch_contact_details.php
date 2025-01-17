<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$master_userid = $_SESSION['master_userid'];

if (isset($_GET['account_id']) && !empty($_GET['account_id'])) {
    $account_id = intval($_GET['account_id']);

    // Ensure the account belongs to the master_user_id
    $query = "
        SELECT 
            contacts.id, 
            contacts.account_id, 
            contacts.name, 
            contacts.designation, 
            contacts.mobile1, 
            contacts.mobile2, 
            contacts.email, 
            contacts.remark, 
            contacts.token, 
            contacts.status, 
            contacts.created_on, 
            contacts.updated_on, 
            contacts.updated_by
        FROM contacts
        INNER JOIN account ON contacts.account_id = account.id
        WHERE contacts.account_id = ? AND account.master_user_id = ? AND contacts.status = 1
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $account_id, $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();

    $contacts = [];
    while ($row = $result->fetch_assoc()) {
        $contacts[] = [
            'id' => $row['id'],
            'account_id' => $row['account_id'],
            'name' => $row['name'],
            'designation' => $row['designation'],
            'mobile1' => $row['mobile1'],
            'mobile2' => $row['mobile2'],
            'email' => $row['email'],
            'remark' => $row['remark'],
            'token' => $row['token'],
            'status' => $row['status'],
            'created_on' => $row['created_on'],
            'updated_on' => $row['updated_on'],
            'updated_by' => $row['updated_by']
        ];
    }

    // Return the data as JSON
    echo json_encode($contacts);
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid or missing account ID']);
}

$conn->close();
?>
