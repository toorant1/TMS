<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

$master_userid = $_SESSION['master_userid'];
$filter_type = $_POST['filter_type'] ?? null;
$filter_value = $_POST['filter_value'] ?? null;

if (!$filter_type || !$filter_value) {
    http_response_code(400);
    echo "Invalid filter parameters";
    exit;
}

$filter_column_map = [
    'status' => 'mt.ticket_status_id',
    'ticket_type' => 'mt.ticket_type_id',
    'priority' => 'mt.ticket_priority_id',
    'main_cause' => 'mt.cause_id'
];

if (!array_key_exists($filter_type, $filter_column_map)) {
    http_response_code(400);
    echo "Invalid filter type";
    exit;
}

$filter_column = $filter_column_map[$filter_type];

$sql = "
    SELECT 
        mt.id AS `Ticket ID`,
        mt.ticket_id AS `Internal Ticket ID`, 
        mt.ticket_token AS `Token`,
        mt.ticket_date AS `Ticket Date`,
        mt.problem_statement AS `Problem Statement`,
        acc.account_name AS `Account Name`,
        acc.city AS `City`,
        acc.state AS `State`,
        acc.country AS `Country`,
        c.name AS `Contact Person`,
        c.mobile1 AS `Contact Mobile`,
        IFNULL(mtt.ticket_type, 'N/A') AS `Ticket Type`,
        IFNULL(mp.priority, 'N/A') AS `Priority`,
        IFNULL(ms.status_name, 'N/A') AS `Status`,
        IFNULL(mc.main_cause, 'N/A') AS `Main Cause`
    FROM master_tickets mt
    LEFT JOIN master_tickets_types mtt ON mt.ticket_type_id = mtt.id AND mtt.master_user_id = ?
    LEFT JOIN master_tickets_priority mp ON mt.ticket_priority_id = mp.id AND mp.master_user_id = ?
    LEFT JOIN master_tickets_status ms ON mt.ticket_status_id = ms.id AND ms.master_user_id = ?
    LEFT JOIN master_tickets_main_causes mc ON mt.cause_id = mc.id AND mc.master_user_id = ?
    LEFT JOIN account acc ON mt.account_id = acc.id
    LEFT JOIN contacts c ON mt.contact_id = c.id
    WHERE mt.master_user_id = ? AND $filter_column = ?
    ORDER BY mt.ticket_date DESC, mt.id DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("SQL Prepare Error: " . $conn->error);
    http_response_code(500);
    echo "Internal Server Error";
    exit;
}

$stmt->bind_param("iiiiii", $master_userid, $master_userid, $master_userid, $master_userid, $master_userid, $filter_value);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>



            
                <td hidden>" . htmlspecialchars($row['Ticket ID']) . "</td>
               <td>
        <a href='ticket_operation.php?ticket_id=" . urlencode($row['Internal Ticket ID']) . "&token=" . urlencode($row['Token']) . "' 
           class='text-primary'>
            " . htmlspecialchars($row['Internal Ticket ID']) . "
        </a>
        <br>" . htmlspecialchars(date('D - d-M-Y', strtotime($row['Ticket Date']))) ."
      </td>
                
                
                <td><strong>" . htmlspecialchars($row['Account Name']) . "</strong> (" . htmlspecialchars($row['City']) . ", " . htmlspecialchars($row['State']) . ")
                <br>" . htmlspecialchars($row['Contact Person']) . "</td>
                <td>" . htmlspecialchars($row['Ticket Type']) . "</td>
                <td>" . htmlspecialchars($row['Priority']) . "</td>
                <td>" . htmlspecialchars($row['Status']) . "
                <br>" . htmlspecialchars(date('D-d-M-y', strtotime($row['Service Date'] ?? 'N/A'))) . "</br> <!-- Service Date -->
                <td>" . htmlspecialchars($row['Problem Statement']) . "</td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='text-center'>No Tickets Found</td></tr>";
    }
} else {
    error_log("Query Execution Error: " . $stmt->error);
    http_response_code(500);
    echo "Internal Server Error";
}

$stmt->close();
$conn->close();
?>
