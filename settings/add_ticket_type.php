<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

$master_userid = $_SESSION['master_userid'];
$ticket_type = $_POST['ticket_type'] ?? '';

if (empty($ticket_type)) {
    header("Location: ticket_types.php?error=Ticket type is required");
    exit;
}

// Check for duplicate ticket type
$query = "SELECT id FROM master_tickets_types WHERE master_user_id = ? AND ticket_type = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $master_userid, $ticket_type);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $conn->close();
    header("Location: ticket_types.php?error=Duplicate ticket type");
    exit;
}

// Insert new ticket type
$query = "INSERT INTO master_tickets_types (master_user_id, ticket_type, status) VALUES (?, ?, 1)";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $master_userid, $ticket_type);

if ($stmt->execute()) {
    header("Location: ticket_types.php?success=Ticket type added successfully");
} else {
    header("Location: ticket_types.php?error=Failed to add ticket type");
}

$stmt->close();
$conn->close();
?>
