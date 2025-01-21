<?php
require_once '../database/db_connection.php'; // Include database connection
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_id = intval($_POST['account_id']);
    $master_userid = $_SESSION['master_userid'];

    // Fetch ticket statuses based on account_id and master_userid using master_tickets
    $stmt = $conn->prepare("
        SELECT DISTINCT mts.id, mts.status_name
        FROM master_tickets mt
        INNER JOIN master_tickets_status mts ON mt.ticket_status_id = mts.id
        WHERE mt.account_id = ? AND mt.master_user_id = ?
    ");
    $stmt->bind_param("ii", $account_id, $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();

    $options = '<option value="" disabled selected>Select Ticket Status</option>';
    while ($row = $result->fetch_assoc()) {
        $options .= '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['status_name']) . '</option>';
    }

    echo $options;
    $stmt->close();
    $conn->close();
}
?>
