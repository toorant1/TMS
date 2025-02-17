<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    die(json_encode(['success' => false, 'message' => 'User not authenticated.']));
}

$master_userid = $_SESSION['master_userid'];
$data_entry_by_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; // Assuming user_id is stored in session

// Retrieve POST data
$ticket_id = isset($_POST['ticket_id']) ? trim($_POST['ticket_id']) : '';
$material_id = isset($_POST['material_id']) ? trim($_POST['material_id']) : '';
$quantity = isset($_POST['quantity']) ? trim($_POST['quantity']) : '';
$remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';
$token = "Challan_" . bin2hex(random_bytes(16)); // Generate a token with 'Challan_' prefix

// Validate inputs
if (empty($ticket_id) || empty($material_id) || empty($quantity) || empty($unit)) {
    die(json_encode(['success' => false, 'message' => 'Missing required fields.']));
}

// Generate the internal_issue_id based on Challan-Year-Month-0001 logic
try {
    $current_year = date('Y');
    $current_month = date('m');
    $prefix = "Challan-$current_year-$current_month-";

    // Fetch the last internal_issue_id for the current year and month
    $query = "
        SELECT MAX(internal_issue_id) AS last_issue_id 
        FROM master_tickets_materials_issue 
        WHERE internal_issue_id LIKE ? AND master_user_id = ?
    ";
    $stmt = $conn->prepare($query);
    $like_prefix = $prefix . "%";
    $stmt->bind_param("si", $like_prefix, $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Extract the last sequence number and increment it
    if ($row && $row['last_issue_id']) {
        $last_sequence = intval(substr($row['last_issue_id'], strrpos($row['last_issue_id'], '-') + 1));
        $next_sequence = str_pad($last_sequence + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $next_sequence = "0001";
    }

    $internal_issue_id = $prefix . $next_sequence;
} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => 'Failed to generate internal issue ID. Error: ' . $e->getMessage()]));
}

// Insert material issue into the database
$query = "
    INSERT INTO master_tickets_materials_issue (
        internal_issue_id, ticket_id, material_id, quantity, remark, master_user_id, data_entry_by_user_id, issue_date, token
    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die(json_encode(['success' => false, 'message' => 'SQL Error: ' . $conn->error]));
}

// Bind parameters
$stmt->bind_param("siississ", $internal_issue_id, $ticket_id, $material_id, $quantity, $remark, $master_userid, $data_entry_by_user_id, $token);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Material issue saved successfully.', 'internal_issue_id' => $internal_issue_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to insert material issue.']);
}

$stmt->close();
$conn->close();
?>
