<?php
require_once '../database/db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $statusId = $data['status_id'] ?? null;
    $fromDate = $data['from_date'] ?? date('Y-m-01', strtotime('-2 months'));
    $toDate = $data['to_date'] ?? date('Y-m-d');
    $masterUserId = $_SESSION['master_userid'] ?? null;

    if (!$masterUserId || !$statusId) {
        echo '<div class="alert alert-danger">Invalid request.</div>';
        exit;
    }

    // Fetch filtered tickets
    $query = "
        SELECT 
            mt.id AS `Ticket ID`, 
            mt.ticket_id AS `Internal Ticket ID`, 
            mt.ticket_date AS `Ticket Date`, 
            acc.account_name AS `Account Name`,
            IFNULL(mtt.ticket_type, 'N/A') AS `Ticket Type`,
            IFNULL(mp.priority, 'N/A') AS `Priority`,
            IFNULL(ms.status_name, 'N/A') AS `Status`,
            mt.problem_statement AS `Problem Statement`
        FROM 
            master_tickets mt
        LEFT JOIN 
            master_tickets_types mtt ON mt.ticket_type_id = mtt.id
        LEFT JOIN 
            master_tickets_priority mp ON mt.ticket_priority_id = mp.id
        LEFT JOIN 
            master_tickets_status ms ON mt.ticket_status_id = ms.id
        LEFT JOIN 
            account acc ON mt.account_id = acc.id
        WHERE 
            mt.master_user_id = ? 
            AND mt.ticket_status_id = ?
            AND DATE(mt.ticket_date) BETWEEN ? AND ?
        ORDER BY 
            mt.ticket_date DESC, mt.id DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiss", $masterUserId, $statusId, $fromDate, $toDate);
    $stmt->execute();
    $result = $stmt->get_result();

    // Generate the HTML table rows
    if ($result->num_rows > 0) {
        echo '<table class="table"><thead><tr>
                <th><input type="checkbox" id="select-all"></th>
                <th>Ticket ID</th>
                <th>Account Name</th>
                <th>Ticket Type</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Problem Statement</th>
              </tr></thead><tbody>';
        while ($row = $result->fetch_assoc()) {
            echo '<tr>
                    <td><input type="checkbox" name="ticket_ids[]" value="' . htmlspecialchars($row['Ticket ID']) . '"></td>
                    <td>' . htmlspecialchars($row['Internal Ticket ID']) . '</td>
                    <td>' . htmlspecialchars($row['Account Name']) . '</td>
                    <td>' . htmlspecialchars($row['Ticket Type']) . '</td>
                    <td>' . htmlspecialchars($row['Priority']) . '</td>
                    <td>' . htmlspecialchars($row['Status']) . '</td>
                    <td>' . htmlspecialchars($row['Problem Statement']) . '</td>
                  </tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<div class="alert alert-info">No tickets found for this status.</div>';
    }
    $stmt->close();
}
?>
