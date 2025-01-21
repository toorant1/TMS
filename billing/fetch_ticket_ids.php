<?php
require_once '../database/db_connection.php'; // Include database connection
session_start(); // Start the session

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate if session variable is set
    if (!isset($_SESSION['master_userid'])) {
        echo '<option value="" disabled>No user session found</option>';
        exit;
    }

    $ticket_status_id = intval($_POST['ticket_status_id']);
    $account_id = intval($_POST['account_id']);
    $master_userid = $_SESSION['master_userid'];

    // Fetch ticket IDs and tokens for the given ticket status and account
    $stmt = $conn->prepare("
        SELECT mt.id, mt.ticket_id, mt.ticket_token
        FROM master_tickets mt
        WHERE mt.ticket_status_id = ? AND mt.account_id = ? AND mt.master_user_id = ?
    ");
    $stmt->bind_param("iii", $ticket_status_id, $account_id, $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();

    $options = '<option value="" disabled selected>Select Ticket ID</option>';
    while ($row = $result->fetch_assoc()) {
        // Pass both id and token as value, separated by a delimiter (e.g., '|')
        $value = $row['id'] . '|' . $row['ticket_token'];
        $options .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($row['ticket_id']) . '</option>';
    }

    echo $options;
    $stmt->close();
    $conn->close();
} else {
    echo '<option value="" disabled>Invalid request</option>';
}
?>
