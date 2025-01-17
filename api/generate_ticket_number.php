<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Get the current year
$current_year = date("Y");

// Query to get the latest ticket number for the current year and master_user_id
$query = "
    SELECT ticket_id 
    FROM master_tickets 
    WHERE master_user_id = ? AND ticket_id LIKE ? 
    ORDER BY ticket_id DESC 
    LIMIT 1
";

$prefix = $current_year . '-%'; // Prefix for the current year
$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to prepare database query']);
    exit;
}

$stmt->bind_param("is", $master_userid, $prefix);
$stmt->execute();
$result = $stmt->get_result();

// Check if there's an existing ticket ID
if ($result && $row = $result->fetch_assoc()) {
    // Extract the numeric part of the last ticket and increment it
    $last_ticket_number = intval(substr($row['ticket_id'], strlen($current_year) + 1));
    $new_ticket_number = $last_ticket_number + 1;
} else {
    // No tickets found for the current year, start with 1
    $new_ticket_number = 1;
}

// Generate the new ticket ID
$new_ticket_id = sprintf("%s-%03d", $current_year, $new_ticket_number);

// Return the new ticket ID as JSON
echo json_encode(['ticket_id' => $new_ticket_id]);

// Close the database connection
$conn->close();
?>
