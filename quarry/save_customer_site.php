<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$master_userid = $_SESSION['master_userid'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account_id = $_POST['account_id'] ?? '';
    $site_name = $_POST['site_name'] ?? '';

    if (empty($account_id) || empty($site_name)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Insert new customer site into the database
    $insert_query = "INSERT INTO customer_sites (account_id, site_name, master_user_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("isi", $account_id, $site_name, $master_userid);

    if ($stmt->execute()) {
        $new_site_id = $stmt->insert_id;
        echo json_encode([
            'status' => 'success',
            'message' => 'Customer Site added successfully!',
            'site' => [
                'id' => $new_site_id,
                'site_name' => $site_name
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save customer site.']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
