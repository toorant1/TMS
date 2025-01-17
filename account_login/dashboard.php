<?php
session_start();

require_once __DIR__ . '../../database/config.php';
require_once __DIR__ . '../../database/helpers.php';

// Check if the user is logged in
if (!isset($_SESSION['master_userid']) || !isset($_SESSION['account_id'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '../../database/db_connection.php';

$master_userid = $_SESSION['master_userid'];
$account_id = $_SESSION['account_id'];

// Fetch account information
$query = "SELECT * FROM account WHERE master_user_id = ? AND id = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("ii", $master_userid, $account_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $account = $result->fetch_assoc();
    } else {
        echo "Error: Account not found.";
        exit();
    }

    $stmt->close();
} else {
    echo "Error: Failed to prepare query.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_id'])) {
    $status_id = intval($_POST['status_id']);

    // Fetch filtered tickets including ticket_token
    $query = "
        SELECT 
            t.id,
            t.ticket_id, 
            t.ticket_date, 
            tt.ticket_type, 
            tp.priority, 
            ts.status_name, 
            c.name AS contact_name, 
            t.problem_statement,
            t.ticket_token
        FROM 
            master_tickets t
        LEFT JOIN master_tickets_types tt ON t.ticket_type_id = tt.id
        LEFT JOIN master_tickets_priority tp ON t.ticket_priority_id = tp.id
        LEFT JOIN master_tickets_status ts ON t.ticket_status_id = ts.id
        LEFT JOIN contacts c ON t.contact_id = c.id
        WHERE 
            t.master_user_id = ? AND t.account_id = ? AND t.ticket_status_id = ?
        ORDER BY t.ticket_date DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $master_userid, $account_id, $status_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $tickets = [];
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }

    echo json_encode($tickets);
    exit();
}

// Fetch all tickets for initial load
$query = "
    SELECT 
        t.id,
        t.ticket_id, 
        t.ticket_date, 
        tt.ticket_type, 
        tp.priority, 
        ts.status_name, 
        c.name AS contact_name, 
        t.problem_statement,
        t.ticket_token
    FROM 
        master_tickets t
    LEFT JOIN master_tickets_types tt ON t.ticket_type_id = tt.id
    LEFT JOIN master_tickets_priority tp ON t.ticket_priority_id = tp.id
    LEFT JOIN master_tickets_status ts ON t.ticket_status_id = ts.id
    LEFT JOIN contacts c ON t.contact_id = c.id
    WHERE 
        t.master_user_id = ? AND t.account_id = ?
    ORDER BY t.id DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $master_userid, $account_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch ticket status summary
$query_summary = "
    SELECT 
        ts.id, 
        ts.status_name, 
        COUNT(t.ticket_id) AS status_count
    FROM 
        master_tickets t
    LEFT JOIN master_tickets_status ts ON t.ticket_status_id = ts.id
    WHERE 
        t.master_user_id = ? AND t.account_id = ?
    GROUP BY ts.status_name, ts.id
";
$stmt_summary = $conn->prepare($query_summary);
$stmt_summary->bind_param("ii", $master_userid, $account_id);
$stmt_summary->execute();
$status_summary_result = $stmt_summary->get_result();

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        table {
            table-layout: fixed;
            width: 100%;
        }

        th,
        td {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>

<body class="bg-light">

    <?php include("header.php"); ?>

    <div class="container mt-5">
        <div class="alert alert-primary text-center">
            <h1>Welcome back, <strong><?= htmlspecialchars($account['account_name']); ?>!</strong></h1>
            <p>Let us know how we can assist you!</p>
        </div>
    </div>

    <div class="container mt-5">
        <h2 class="text-center">Ticket Status Summary</h2>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <ul class="list-group">
                            <?php while ($summary_row = $status_summary_result->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <strong><?= htmlspecialchars($summary_row['status_name']); ?></strong>
                                    <button class="btn btn-primary btn-sm filter-btn" data-status-id="<?= htmlspecialchars($summary_row['id']); ?>">
                                        <?= htmlspecialchars($summary_row['status_count']); ?> Tickets
                                    </button>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Ticket List</h2>
            <div>
                <a href="ticket_management/new_ticket.php" class="btn btn-primary btn-sm">Add New Ticket</a>
                <button onclick="location.reload();" class="btn btn-secondary btn-sm">Refresh</button>
            </div>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Date</th>
                    <th>Contact Person</th>
                    <th>Problem Statement</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="ticket-table-body">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                        <a href="ticket_management/show_tickets.php?ticket_id=<?= htmlspecialchars($row['ticket_id']); ?>&token=<?= htmlspecialchars($row['ticket_token']); ?>" target="_blank">
                            <?= htmlspecialchars($row['ticket_id']); ?>
                        </a>

                        </td>
                        <td><?= htmlspecialchars(date('d-M-Y', strtotime($row['ticket_date']))); ?></td>
                        <td><?= htmlspecialchars($row['contact_name']); ?></td>
                        <td><?= htmlspecialchars($row['problem_statement']); ?></td>
                        <td><?= htmlspecialchars($row['ticket_type']); ?></td>
                        <td><?= htmlspecialchars($row['priority']); ?></td>
                        <td><?= htmlspecialchars($row['status_name']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
    const filterButtons = document.querySelectorAll(".filter-btn");
    const ticketTableBody = document.getElementById("ticket-table-body");

    filterButtons.forEach(button => {
        button.addEventListener("click", function () {
            const statusId = this.getAttribute("data-status-id");

            fetch("<?= $_SERVER['PHP_SELF']; ?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "status_id=" + statusId
            })
            .then(response => response.json())
            .then(data => {
                ticketTableBody.innerHTML = "";
                data.forEach(ticket => {
                    const row = `
                        <tr>
                            <td>
                                <a href="show.php?ticket_id=${ticket.ticket_id}&token=${ticket.ticket_token}" target="_blank">
                                    ${ticket.ticket_id}
                                </a>
                            </td>
                            <td>${new Date(ticket.ticket_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
                            <td>${ticket.contact_name}</td>
                            <td>${ticket.problem_statement}</td>
                            <td>${ticket.ticket_type}</td>
                            <td>${ticket.priority}</td>
                            <td>${ticket.status_name}</td>
                        </tr>
                    `;
                    ticketTableBody.insertAdjacentHTML("beforeend", row);
                });
            })
            .catch(error => console.error("Error:", error));
        });
    });
});
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
