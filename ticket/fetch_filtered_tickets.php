<?php
require_once '../database/db_connection.php';

session_start();
if (!isset($_SESSION['master_userid'])) {
    die('Unauthorized');
}

$master_userid = $_SESSION['master_userid'];
$filters = json_decode($_POST['filters'], true);

$query = "
    SELECT 
        mt.id AS `Ticket ID`, 
        mt.ticket_id AS `Internal Ticket ID`, 
        mt.ticket_date AS `Ticket Date`, 
        mt.problem_statement AS `Problem Statement`,
        acc.account_name AS `Account Name`,
        IFNULL(mtt.ticket_type, 'N/A') AS `Ticket Type`,
        IFNULL(mp.priority, 'N/A') AS `Priority`,
        IFNULL(ms.status_name, 'N/A') AS `Status`,
        mt.ticket_token AS `Token`,
        acc.city AS `City`,
        acc.state AS `State`,
        acc.country AS `Country`,
        c.name AS `Contact Person`,
        c.mobile1 AS `Contact Mobile`,
        IFNULL(mc.main_cause, 'N/A') AS `Main Cause`
    FROM 
        master_tickets mt
    LEFT JOIN 
        master_tickets_types mtt ON mt.ticket_type_id = mtt.id
    LEFT JOIN 
        master_tickets_priority mp ON mt.ticket_priority_id = mp.id
    LEFT JOIN 
        master_tickets_status ms ON mt.ticket_status_id = ms.id
    LEFT JOIN 
        master_tickets_main_causes mc ON mt.cause_id = mc.id
    LEFT JOIN 
        account acc ON mt.account_id = acc.id
    LEFT JOIN 
        contacts c ON mt.contact_id = c.id
    WHERE 
        mt.master_user_id = ?
";

$params = [$master_userid];
$types = "i";

// Apply Search Filter (Account Name, Internal Ticket ID, City, Contact Person)
if (!empty($filters['account'])) {
    $query .= " AND (
        acc.account_name LIKE ? OR 
        mt.ticket_id LIKE ? OR 
        acc.city LIKE ? OR 
        c.name LIKE ?
    )";
    $searchValue = "%" . $filters['account'] . "%";
    array_push($params, $searchValue, $searchValue, $searchValue, $searchValue);
    $types .= "ssss";
}

// Apply Date Filters
if (!empty($filters['from_date'])) {
    $query .= " AND mt.ticket_date >= ?";
    $params[] = $filters['from_date'];
    $types .= "s";
}

if (!empty($filters['to_date'])) {
    $query .= " AND mt.ticket_date <= ?";
    $params[] = $filters['to_date'];
    $types .= "s";
}

// Apply Additional Filters (Radio Buttons)
if (!empty($filters['status'])) {
    $query .= " AND mt.ticket_status_id = ?";
    $params[] = $filters['status'];
    $types .= "i";
}

if (!empty($filters['ticket_type'])) {
    $query .= " AND mt.ticket_type_id = ?";
    $params[] = $filters['ticket_type'];
    $types .= "i";
}

if (!empty($filters['priority'])) {
    $query .= " AND mt.ticket_priority_id = ?";
    $params[] = $filters['priority'];
    $types .= "i";
}

if (!empty($filters['main_cause'])) {
    $query .= " AND mt.cause_id = ?";
    $params[] = $filters['main_cause'];
    $types .= "i";
}

// Order Results
$query .= " ORDER BY mt.ticket_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Generate table rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
            <td hidden>" . htmlspecialchars($row['Ticket ID']) . "</td>
            <td>
                <a href='ticket_operation.php?ticket_id=" . urlencode($row['Internal Ticket ID']) . "&token=" . urlencode($row['Token']) . "' 
                   class='text-primary'>
                    " . htmlspecialchars($row['Internal Ticket ID']) . "
                </a>
                <br>" . htmlspecialchars(date('D - d-M-Y', strtotime($row['Ticket Date']))) . "
            </td>
            <td>
                <strong>" . htmlspecialchars($row['Account Name']) . "</strong> (" . htmlspecialchars($row['City']) . ", " . htmlspecialchars($row['State']) . ")
                <br>" . htmlspecialchars($row['Contact Person']) . " - " . htmlspecialchars($row['Contact Mobile']) . "
            </td>
            <td>" . htmlspecialchars($row['Ticket Type']) . "</td>
            <td>" . htmlspecialchars($row['Priority']) . "</td>
            <td>" . htmlspecialchars($row['Status']) . "
                <br>" . htmlspecialchars(date('D - d-M-Y', strtotime($row['Service Date'] ?? 'N/A'))) . "
            </td>
            <td>" . htmlspecialchars($row['Problem Statement']) . "</td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='7' class='text-center'>No Tickets Found</td></tr>";
}

$conn->close();
?>
