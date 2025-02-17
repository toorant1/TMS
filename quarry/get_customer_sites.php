<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$master_userid = $_SESSION['master_userid'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['customer_id'])) {
    $customer_id = $_POST['customer_id'];

    $query = "SELECT id, site_name FROM customer_sites WHERE account_id = ? AND master_user_id = ? ORDER BY site_name ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $customer_id, $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $sites = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($sites);
    exit;
}
?>
