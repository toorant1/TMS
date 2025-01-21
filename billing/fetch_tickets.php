<?php
require_once '../database/db_connection.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_id = intval($_POST['account_id']);
    $ticket_status = htmlspecialchars($_POST['ticket_status']);

    // Fetch tickets based on account_id and ticket_status
    $stmt = $conn->prepare("SELECT ticket_no FROM tickets WHERE account_id = ? AND status = ?");
    $stmt->bind_param("is", $account_id, $ticket_status);
    $stmt->execute();
    $result = $stmt->get_result();

    $options = '<option value="" disabled selected>Select Ticket Number</option>';
    while ($row = $result->fetch_assoc()) {
        $options .= '<option value="' . htmlspecialchars($row['ticket_no']) . '">' . htmlspecialchars($row['ticket_no']) . '</option>';
    }

    echo $options;
    $stmt->close();
    $conn->close();
}
?>
