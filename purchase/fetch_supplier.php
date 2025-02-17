<?php
require_once '../database/db_connection.php';
session_start();

$master_user_id = $_SESSION['master_userid']; // Ensure the user is authenticated

header('Content-Type: application/json');

$supplierQuery = "SELECT id, account_name FROM account WHERE account_type = 'Supplier' AND master_user_id = ?";
$stmt = mysqli_prepare($conn, $supplierQuery);
mysqli_stmt_bind_param($stmt, "i", $master_user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$suppliers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $suppliers[] = $row;
}

echo json_encode($suppliers);
?>
