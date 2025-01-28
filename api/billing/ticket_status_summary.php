<?php
require_once '../../database/db_connection.php';
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $master_user_id = isset($_GET['master_user_id']) ? intval($_GET['master_user_id']) : null;
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : null;
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : null;

    // Validate input
    if (!$master_user_id || !$from_date || !$to_date) {
        echo json_encode(['code' => 400, 'message' => 'Invalid input. Please provide master_user_id, from_date, and to_date.']);
        exit;
    }

    // Check date range does not exceed 3 months
    $start_date = new DateTime($from_date);
    $end_date = new DateTime($to_date);
    $interval = $start_date->diff($end_date);

    if ($interval->m > 3 || ($interval->y > 0 && $interval->m + ($interval->y * 12) > 3)) {
        echo json_encode(['code' => 400, 'message' => 'Date range must not exceed 3 months.']);
        exit;
    }

    try {
        // Fetch status counts
        $query = "
            SELECT 
                ms.id AS status_id,
                ms.status_name,
                COUNT(mt.id) AS count
            FROM 
                master_tickets mt
            LEFT JOIN 
                master_tickets_status ms 
            ON 
                mt.ticket_status_id = ms.id
            WHERE 
                mt.master_user_id = ? 
                AND DATE(mt.ticket_date) BETWEEN ? AND ?
            GROUP BY 
                ms.id
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $master_user_id, $from_date, $to_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $statusCounts = [];
        while ($row = $result->fetch_assoc()) {
            $statusCounts[] = $row;
        }

        $stmt->close();

        echo json_encode(['code' => 200, 'statusCounts' => $statusCounts]);
    } catch (Exception $e) {
        echo json_encode(['code' => 500, 'message' => 'Error fetching ticket status summary: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['code' => 405, 'message' => 'Invalid request method. Use GET.']);
}
?>
