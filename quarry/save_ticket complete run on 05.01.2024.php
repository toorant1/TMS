<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// Generate a new ticket token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Use the session variable
$master_userid = $_SESSION['master_userid'];

// Get POST data
$ticket_date = $_POST['date'] ?? null;
$ticket_type_id = $_POST['ticket_type'] ?? null;
$ticket_priority_id = $_POST['priority'] ?? null;
$ticket_status_id = $_POST['ticket_status'] ?? null;
$account_id = $_POST['customer_name'] ?? null;
$contact_id = $_POST['contact_person'] ?? null;
$cause_id = $_POST['main_cause'] ?? null;
$problem_statement = $_POST['problem_statement'] ?? null;
$ticket_token = generateToken(); // Generate the unique ticket token

// Validate input data
if (!$ticket_date || !$ticket_type_id || !$ticket_priority_id || !$ticket_status_id || !$account_id || !$contact_id || !$cause_id || empty($problem_statement)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

// Generate ticket_id with format YEAR-0001
try {
    $current_year = date('Y', strtotime($ticket_date));
    
    // Query to get the last ticket_id for the current year
    $query = "SELECT MAX(ticket_id) AS last_ticket FROM master_tickets WHERE master_user_id = ? AND ticket_id LIKE ?";
    $stmt = $conn->prepare($query);
    $prefix = $current_year . '-%';
    $stmt->bind_param("is", $master_userid, $prefix);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Generate the next ticket number
    if ($row && $row['last_ticket']) {
        $last_number = intval(substr($row['last_ticket'], -4));
        $next_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $next_number = '0001';
    }
    $ticket_id = $current_year . '-' . $next_number;

    // Insert data into master_tickets table
    $query = "
        INSERT INTO master_tickets 
        (ticket_id, ticket_date, master_user_id, ticket_type_id, ticket_priority_id, ticket_status_id, account_id, contact_id, cause_id, problem_statement, ticket_token) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssiiiiiiiss",
        $ticket_id,
        $ticket_date,
        $master_userid,
        $ticket_type_id,
        $ticket_priority_id,
        $ticket_status_id,
        $account_id,
        $contact_id,
        $cause_id,
        $problem_statement, 
        $ticket_token
    );

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Ticket added successfully.', 'ticket_id' => $ticket_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save ticket data.']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
