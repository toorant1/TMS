<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo "Unauthorized access!";
    exit;
}

$master_userid = $_SESSION['master_userid'];
$filters = json_decode($_POST['filters'], true);

// Ensure filters is an array
if (!is_array($filters)) {
    $filters = [];
}

// Mapping filters to correct database column names
$column_mapping = [
    'status' => 'ticket_status_id',
    'ticket_type' => 'ticket_type_id',
    'priority' => 'ticket_priority_id',
    'main_cause' => 'cause_id'
];

// Start constructing the query
$query = "SELECT mt.id AS `Ticket ID`, acc.account_name AS `Account Name`, 
                 IFNULL(mtt.ticket_type, 'N/A') AS `Ticket Type`, 
                 IFNULL(mp.priority, 'N/A') AS `Priority`, 
                 IFNULL(ms.status_name, 'N/A') AS `Status`, 
                 mt.problem_statement AS `Problem Statement`
          FROM master_tickets mt
          LEFT JOIN master_tickets_types mtt ON mt.ticket_type_id = mtt.id
          LEFT JOIN master_tickets_priority mp ON mt.ticket_priority_id = mp.id
          LEFT JOIN master_tickets_status ms ON mt.ticket_status_id = ms.id
          LEFT JOIN account acc ON mt.account_id = acc.id
          WHERE mt.master_user_id = ?";

$params = [$master_userid];
$types = "i";

// Apply filters dynamically
foreach ($filters as $key => $value) {
    if (!empty($value) && isset($column_mapping[$key])) {
        $query .= " AND mt." . $column_mapping[$key] . " = ?";
        $params[] = intval($value);
        $types .= "i";
    }
}

$query .= " ORDER BY mt.ticket_date DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

// Bind parameters dynamically
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$output = "";
while ($row = $result->fetch_assoc()) {
    $output .= "<tr>
                    <td>{$row['Ticket ID']}</td>
                    <td>{$row['Account Name']}</td>
                    <td>{$row['Ticket Type']}</td>
                    <td>{$row['Priority']}</td>
                    <td>{$row['Status']}</td>
                    <td>{$row['Problem Statement']}</td>
                </tr>";
}

if ($result->num_rows === 0) {
    $output .= "<tr><td colspan='6' class='text-center'>No Tickets Found</td></tr>";
}

echo $output;
$stmt->close();
$conn->close();
?>
