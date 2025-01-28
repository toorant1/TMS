<?php
require_once '../../database/db_connection.php';
header('Content-Type: application/json');

// Fetch and validate input parameters
$master_user_id = $_POST['master_user_id'] ?? null;
$account_id = $_POST['account_id'] ?? null;
$from_date = $_POST['from_date'] ?? null;
$to_date = $_POST['to_date'] ?? null;

if (!$master_user_id || !$account_id || !$from_date || !$to_date) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required parameters: master_user_id, account_id, from_date, or to_date."
    ]);
    exit;
}

try {
    // Corrected SQL query without `updated_at`
    $query = "
        SELECT ticket_id, problem_statement AS ticket_title, ticket_status_id, created_at
        FROM master_tickets
        WHERE master_user_id = ? AND account_id = ?
        AND DATE(created_at) BETWEEN ? AND ?
        ORDER BY created_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iiss', $master_user_id, $account_id, $from_date, $to_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = [];
    while ($row = $result->fetch_assoc()) {
        $response[] = [
            "card_title" => $row['ticket_title'],
            "card_status" => $row['ticket_status_id'], // Replace with a status lookup if needed
            "card_details" => [
                "Ticket ID" => $row['ticket_id'],
                "Created At" => $row['created_at']
            ],
        ];
    }

    echo json_encode([
        "status" => "success",
        "data" => $response,
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "An error occurred while fetching ticket history: " . $e->getMessage(),
    ]);
}
?>
