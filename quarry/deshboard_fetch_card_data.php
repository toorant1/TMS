<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo "Unauthorized access!";
    exit;
}

$master_userid = $_SESSION['master_userid'];
$filters = json_decode($_POST['filters'], true);

// Fetch card data logic here (like the original code)
$queries = [
    'status' => "SELECT ms.id, ms.status_name, COUNT(mt.id) AS count 
                 FROM master_tickets mt 
                 LEFT JOIN master_tickets_status ms ON mt.ticket_status_id = ms.id 
                 WHERE mt.master_user_id = ? GROUP BY ms.id, ms.status_name",
    'ticket_type' => "SELECT mtt.id, mtt.ticket_type, COUNT(mt.id) AS count 
                      FROM master_tickets mt 
                      LEFT JOIN master_tickets_types mtt ON mt.ticket_type_id = mtt.id 
                      WHERE mt.master_user_id = ? GROUP BY mtt.id, mtt.ticket_type",
    'priority' => "SELECT mp.id, mp.priority, COUNT(mt.id) AS count 
                   FROM master_tickets mt 
                   LEFT JOIN master_tickets_priority mp ON mt.ticket_priority_id = mp.id 
                   WHERE mt.master_user_id = ? GROUP BY mp.id, mp.priority",
    'main_cause' => "SELECT mc.id, mc.main_cause, COUNT(mt.id) AS count 
                     FROM master_tickets mt 
                     LEFT JOIN master_tickets_main_causes mc ON mt.cause_id = mc.id 
                     WHERE mt.master_user_id = ? GROUP BY mc.id, mc.main_cause"
];

$details = ['status' => [], 'ticket_type' => [], 'priority' => [], 'main_cause' => []];
foreach ($queries as $key => $query) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $details[$key][] = $row;
    }
    $stmt->close();
}

// Generate HTML for updated cards
foreach ($details as $category => $items) {
    echo "<div class='col-md-3'>";
    echo "<div class='card'>";
    echo "<div class='card-header text-center'>" . ucfirst(str_replace('_', ' ', $category)) . "</div>";
    echo "<div class='card-body'>";
    foreach ($items as $item) {
        echo "<div class='form-check'>";
        echo "<input class='form-check-input' type='radio' name='{$category}' value='{$item['id']}' data-filter-type='{$category}'>";
        echo "<label class='form-check-label'>" . htmlspecialchars($item[array_keys($item)[1]]) . " ({$item['count']})</label>";
        echo "</div>";
    }
    echo "</div></div></div>";
}
?>
