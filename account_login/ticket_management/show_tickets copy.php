<?php
session_start();
require_once __DIR__ . '../../../database/config.php';
require_once __DIR__ . '../../../database/helpers.php';

if (!isset($_SESSION['master_userid']) || !isset($_SESSION['account_id'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '../../../database/db_connection.php';

$master_userid = $_SESSION['master_userid'];
$account_id = $_SESSION['account_id'];

// Validate and fetch ticket details
if (!isset($_GET['ticket_id']) || !isset($_GET['token'])) {
    echo "Invalid request. Ticket ID or token missing.";
    exit();
}

$ticket_id = htmlspecialchars($_GET['ticket_id']);
$ticket_token = htmlspecialchars($_GET['token']);

// Fetch ticket details from the database
$query = "
    SELECT 
        t.id as id,
        t.ticket_id,
        t.ticket_date,
        tt.ticket_type,
        tp.priority,
        ts.status_name,
        c.name AS contact_name,
        c.email AS contact_email,
        t.problem_statement,
        a.account_name,
        a.address,
        t.ticket_token
    FROM 
        master_tickets t
    LEFT JOIN master_tickets_types tt ON t.ticket_type_id = tt.id
    LEFT JOIN master_tickets_priority tp ON t.ticket_priority_id = tp.id
    LEFT JOIN master_tickets_status ts ON t.ticket_status_id = ts.id
    LEFT JOIN contacts c ON t.contact_id = c.id
    LEFT JOIN account a ON t.account_id = a.id
    WHERE 
        t.master_user_id = ? AND t.account_id = ? AND t.ticket_id = ? AND t.ticket_token = ?
";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo "Error: Failed to prepare the query.";
    exit();
}

$stmt->bind_param("iiss", $master_userid, $account_id, $ticket_id, $ticket_token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $ticket = $result->fetch_assoc();
} else {
    echo "Ticket not found or invalid token.";
    exit();
}

// Fetch related services from master_tickets_services table
// Fetch related services from master_tickets_services table
$services_query = "
    SELECT 
    mts.remark_external, 
    mts.service_date, 
    mts.entry_date, 
    mts.token,
    mts.ticket_status,
    mu.name AS engineer_name,
    ts.status_name AS ticket_status_name
FROM 
    master_tickets_services mts
LEFT JOIN master_users mu ON mts.engineer_id = mu.id
LEFT JOIN master_tickets_status ts ON mts.ticket_status = ts.id
WHERE 
    mts.master_user_id = ? AND mts.id = ?
ORDER BY mts.service_date DESC

";

$services_stmt = $conn->prepare($services_query);

if (!$services_stmt) {
    echo "Error: Failed to prepare services query.";
    exit();
}

$k=$ticket['kid'];
$services_stmt->bind_param("ii", $master_userid, $k);
$services_stmt->execute();
$services_result = $services_stmt->get_result();
$services = $services_result->fetch_all(MYSQLI_ASSOC);

$services_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include("../header.php"); ?>
    <div class="container mt-5">
        <!-- Ticket Details -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>Ticket Details</h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr class="table-light">
                            <th>Field</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>Ticket ID</th>
                            <td><?= htmlspecialchars($ticket['ticket_id']); ?></td>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <td><?= htmlspecialchars(date('d-M-Y', strtotime($ticket['ticket_date']))); ?></td>
                        </tr>
                        <tr>
                            <th>Ticket Type</th>
                            <td><?= htmlspecialchars($ticket['ticket_type']); ?></td>
                        </tr>
                        <tr>
                            <th>Priority</th>
                            <td><?= htmlspecialchars($ticket['priority']); ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><?= htmlspecialchars($ticket['status_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Contact Person</th>
                            <td><?= htmlspecialchars($ticket['contact_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Contact Email</th>
                            <td><?= htmlspecialchars($ticket['contact_email']); ?></td>
                        </tr>
                        <tr>
                            <th>Problem Statement</th>
                            <td><?= htmlspecialchars($ticket['problem_statement']); ?></td>
                        </tr>
                        <tr>
                            <th>Customer Name</th>
                            <td><?= htmlspecialchars($ticket['account_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Customer Address</th>
                            <td><?= htmlspecialchars($ticket['address']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Service History -->
        <div class="card mt-4">
            <div class="card-header bg-secondary text-white">
                <h4>Service History</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($services)): ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr class="table-light">
                                <th>Service Date</th>
                                <th>Entry Date</th>
                                <th>Engineer</th>
                                <th>Service Remark</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('d-M-Y H:i', strtotime($service['service_date']))); ?></td>
                                    <td><?= htmlspecialchars(date('d-M-Y H:i', strtotime($service['entry_date']))); ?></td>
                                    <td><?= htmlspecialchars($service['engineer_name'] ?? 'N/A'); ?></td>
                                    <td><?= htmlspecialchars($service['remark_external']); ?></td>
                                    <td><?= htmlspecialchars($service['ticket_status_name'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">No service history available for this ticket.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4 text-center">
            <a href="../dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
