<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Initialize date filter variables
$to_date = date('Y-m-d'); // Today
$from_date = date('Y-m-01', strtotime('-2 months')); // First day of the month, 2 months ago
if (!empty($_GET['from_date'])) {
    $from_date = $_GET['from_date'];
}
if (!empty($_GET['to_date'])) {
    $to_date = $_GET['to_date'];
}

// Fetch status-wise counts for tickets
$statusCountsQuery = "
    SELECT 
        ms.id, 
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
    GROUP BY 
        ms.id, ms.status_name
";
$statusStmt = $conn->prepare($statusCountsQuery);
$statusStmt->bind_param("i", $master_userid);
$statusStmt->execute();
$statusResult = $statusStmt->get_result();
$statusCounts = [];
while ($row = $statusResult->fetch_assoc()) {
    $statusCounts[] = $row;
}
$statusStmt->close();


// Fetch tickets and calculate totals



$query = "
    SELECT 
        mt.id AS `Ticket ID`, 
        mt.ticket_id AS `Internal Ticket ID`, 
        mt.ticket_date AS `Ticket Date`, 
        acc.account_name AS `Account Name`,
        IFNULL(mtt.ticket_type, 'N/A') AS `Ticket Type`,
        IFNULL(ms.status_name, 'N/A') AS `Status`,
        i.bill_no AS `Bill No`,
        i.bill_date AS `Bill Date`,
        i.due_date AS `Due Date`,
        i.amount AS `Bill Amount`,
        IFNULL(SUM(pr.payment_amount), 0) AS `Total Payment Received`,
        (i.amount - IFNULL(SUM(pr.payment_amount), 0)) AS `Outstanding Amount`
    FROM 
        master_tickets mt
    LEFT JOIN 
        master_tickets_types mtt ON mt.ticket_type_id = mtt.id
    LEFT JOIN 
        master_tickets_status ms ON mt.ticket_status_id = ms.id
    LEFT JOIN 
        account acc ON mt.account_id = acc.id
    LEFT JOIN 
        master_invoices i ON mt.id = i.ticket_id
    LEFT JOIN 
        payment_receipts pr ON i.id = pr.invoice_id
    WHERE 
        mt.master_user_id = ?
        AND DATE(mt.ticket_date) BETWEEN ? AND ?
    GROUP BY 
        mt.id, i.id, i.bill_no, i.bill_date, i.due_date, i.amount, acc.account_name, mtt.ticket_type, ms.status_name
    ORDER BY 
        mt.ticket_date DESC, mt.id DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $master_userid, $from_date, $to_date);
$stmt->execute();
$result = $stmt->get_result();


// Initialize variables for totals
$totalBillingAmount = 0;
$totalReceiptAmount = 0;
$totalOutstandingAmount = 0;
$overdueAmount = 0;
$nextWeekDueAmount = 0;

// Today's and next week's timestamps
$today = strtotime(date('Y-m-d'));
$nextWeek = strtotime('+7 days');

// Reset the result pointer
$result->data_seek(0);

// Calculate totals dynamically from the fetched data
while ($row = $result->fetch_assoc()) {
    $totalBillingAmount += $row['Bill Amount'];
    $totalReceiptAmount += $row['Total Payment Received'];
    $totalOutstandingAmount += $row['Outstanding Amount'];
    $dueDate = strtotime($row['Due Date']);
    $today = strtotime(date('Y-m-d')); // Ensure `today` is calculated within the loop

    // Calculate Overdue Payment Total
    if ($row['Outstanding Amount'] > 0 && $dueDate < $today) {
        $overdueAmount += $row['Outstanding Amount'];
    }

    // Calculate Next Week Due Amount
    $nextWeek = strtotime('+7 days', $today);
    if ($row['Outstanding Amount'] > 0 && $dueDate >= $today && $dueDate <= $nextWeek) {
        $nextWeekDueAmount += $row['Outstanding Amount'];
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .dashboard-header {
            background: linear-gradient(360deg, #6a11cb, #2575fc);
            padding: 15px;
            border-radius: 15px;
            color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            margin-bottom: 5px;
        }

        .dashboard-header p {
            font-size: 1.1rem;
        }

        .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card {
            width: 350px;
        }

        .card-container {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?>

        <!-- Main Content -->
        <div class="flex-grow-1 p-4">
            <?php include('../headers/header.php'); ?>

            <div class="container mt-5">
                <!-- Dashboard Header -->
                <div class="dashboard-header text-center mb-4">
                    <h1 class="text-white fw-bold">
                        <i class="bi bi-ticket-detailed-fill"></i> Billing Dashboard
                    </h1>
                    <p class="text-light">Resolve Faster, Work Smarter.</p>
                </div>

                <!-- Card Section -->
                <div class="card-container">

                <!-- Ticket Date Range Card -->
                <div class="card shadow">
                        <div class="card-header bg-info text-white text-center">
                            <h5 class="mb-0">Ticket Date Range</h5>
                        </div>
                        <div class="card-body text-center">
                            <form id="date-range-form">
                                <!-- From Date -->
                                <div class="mb-3">
                                    <label for="from_date" class="form-label"><strong>From:</strong></label>
                                    <input
                                        type="date"
                                        id="from_date"
                                        name="from_date"
                                        class="form-control text-center"
                                        value="<?= htmlspecialchars($from_date); ?>">
                                </div>
                                <!-- To Date -->
                                <div class="mb-3">
                                    <label for="to_date" class="form-label"><strong>To:</strong></label>
                                    <input
                                        type="date"
                                        id="to_date"
                                        name="to_date"
                                        class="form-control text-center"
                                        value="<?= htmlspecialchars($to_date); ?>">
                                </div>
                                <!-- Submit Button -->
                                <button type="button" id="update-date-range" class="btn btn-primary w-100">Update Date Range</button>
                            </form>
                        </div>
                    </div>

                    <!-- Ticket Status Summary Card -->
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white text-center">
                            <h5 class="mb-0">Ticket Status Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="btn-group-vertical w-100" role="group" aria-label="Ticket Status Summary">
                                <?php foreach ($statusCounts as $index => $status): ?>
                                    <input
                                        type="radio"
                                        class="btn-check"
                                        name="status_filter"
                                        id="status_<?= htmlspecialchars($status['id']); ?>"
                                        value="<?= htmlspecialchars($status['id']); ?>"
                                        <?= $index === 0 ? 'checked' : ''; ?>>
                                    <label
                                        class="btn btn-outline-primary d-flex justify-content-between align-items-center w-100 mb-2"
                                        for="status_<?= htmlspecialchars($status['id']); ?>">
                                        <span class="text-start"><?= htmlspecialchars($status['status_name'] ?? 'Unknown Status'); ?></span>
                                        <span class="text-end badge bg-primary"><?= htmlspecialchars($status['count'] ?? '0'); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                        </div>
                    </div>

                    <!-- Billing Summary Card -->
                    <div class="card shadow">
                        <div class="card-header bg-success text-white text-center">
                            <h5 class="mb-0">Billing Summary</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Total Billing Amount:</strong>
                                    <span class="float-end"><?= number_format($totalBillingAmount, 2); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <strong>Total Receipt Amount:</strong>
                                    <span class="float-end"><?= number_format($totalReceiptAmount, 2); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <strong>Total Outstanding Amount:</strong>
                                    <span class="float-end"><?= number_format($totalOutstandingAmount, 2); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <strong>Overdue Payment Total:</strong>
                                    <span class="float-end text-danger"><?= number_format($overdueAmount, 2); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <strong>Next Week Due Amount:</strong>
                                    <span class="float-end text-warning"><?= number_format($nextWeekDueAmount, 2); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>



                    
                </div>
            </div>

            <!-- Dynamic Table Section -->
            <div class="mt-5" id="tickets-table-container">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Ticket Date</th>
                                <th>Account Name</th>
                                <th>Ticket Type</th>
                                <th>Ticket Status</th>
                                <th>Bill No</th>
                                <th>Bill Date</th>
                                <th>Due Date</th>
                                <th>Bill Amount</th>
                                <th>Total Payment Received</th>
                                <th>Outstanding Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $currentTicketId = null;
                            $result->data_seek(0); // Reset result pointer
                            if ($result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                                    if ($currentTicketId !== $row['Ticket ID']):
                                        $currentTicketId = $row['Ticket ID'];
                            ?>
                                        <tr>
                                            <td rowspan="1">
                                                <a href="ticket_details.php?ticket_id=<?= urlencode($row['Ticket ID']); ?>&token=<?= urlencode($_SESSION['token']); ?>">
                                                    <?= htmlspecialchars($row['Internal Ticket ID']); ?>
                                                </a>
                                            </td>
                                            <td rowspan="1"><?= htmlspecialchars(date('d-M-Y', strtotime($row['Ticket Date']))) ?? ''; ?></td>
                                            <td rowspan="1"><?= htmlspecialchars($row['Account Name']); ?></td>
                                            <td rowspan="1"><?= htmlspecialchars($row['Ticket Type']); ?></td>
                                            <td rowspan="1"><?= htmlspecialchars($row['Status']); ?></td>
                                            <td><?= htmlspecialchars($row['Bill No'] ?? ''); ?></td>
                                            <td><?= !empty($row['Bill Date']) ? htmlspecialchars(date('d-M-Y', strtotime($row['Bill Date']))) : ''; ?></td>
                                            <td
                                                style="
        background-color: <?php
                                        $dueDate = strtotime($row['Due Date']);
                                        $today = strtotime(date('Y-m-d'));
                                        $daysToDue = ($dueDate - $today) / (60 * 60 * 24); // Difference in days

                                        echo $row['Outstanding Amount'] > 0
                                            ? ($daysToDue < 0
                                                ? '#f8d7da' // Overdue: bg-danger
                                                : ($daysToDue <= 7
                                                    ? '#fff3cd' // Due in Next 7 Days: bg-warning
                                                    : '#d4edda')) // Regular Due: bg-success
                                            : 'transparent'; // No Outstanding Amount
                            ?>;
    ">
                                                <?= !empty($row['Due Date']) ? htmlspecialchars(date('d-M-Y', strtotime($row['Due Date']))) : ''; ?>
                                            </td>



                                            <td><?= number_format($row['Bill Amount'], 2) ?? ''; ?></td>
                                            <td><?= number_format($row['Total Payment Received'], 2) ?? ''; ?></td>
                                            <td
                                                style="
                                                    color: <?= $row['Outstanding Amount'] == 0 ? 'green' : ($row['Outstanding Amount'] < $row['Bill Amount'] ? 'orange' : 'red'); ?>;
                                                    background-color: <?= $row['Outstanding Amount'] == 0 ? '#d4edda' : ($row['Outstanding Amount'] < $row['Bill Amount'] ? '#fff3cd' : '#f8d7da'); ?>;
                                                ">
                                                <?= number_format($row['Outstanding Amount'], 2) ?? ''; ?>
                                            </td>

                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5"></td>
                                            <td><?= htmlspecialchars($row['Bill No'] ?? ''); ?></td>
                                            <td><?= !empty($row['Bill Date']) ? htmlspecialchars(date('d-M-Y', strtotime($row['Bill Date']))) : ''; ?></td>

                                            <td
                                                style="
        background-color: <?php
                                        $dueDate = strtotime($row['Due Date']);
                                        $today = strtotime(date('Y-m-d'));
                                        $daysToDue = ($dueDate - $today) / (60 * 60 * 24); // Difference in days

                                        echo $row['Outstanding Amount'] > 0
                                            ? ($daysToDue < 0
                                                ? '#f8d7da' // Overdue: bg-danger
                                                : ($daysToDue <= 7
                                                    ? '#fff3cd' // Due in Next 7 Days: bg-warning
                                                    : '#d4edda')) // Regular Due: bg-success
                                            : 'transparent'; // No Outstanding Amount
                            ?>;
    ">
                                                <?= !empty($row['Due Date']) ? htmlspecialchars(date('d-M-Y', strtotime($row['Due Date']))) : ''; ?>
                                            </td>


                                            <td><?= number_format($row['Bill Amount'], 2) ?? ''; ?></td>
                                            <td><?= number_format($row['Total Payment Received'], 2) ?? ''; ?></td>
                                            <td
                                                style="
        color: <?= $row['Outstanding Amount'] == 0 ? 'green' : ($row['Outstanding Amount'] < $row['Bill Amount'] ? 'orange' : 'red'); ?>;
        background-color: <?= $row['Outstanding Amount'] == 0 ? '#d4edda' : ($row['Outstanding Amount'] < $row['Bill Amount'] ? '#fff3cd' : '#f8d7da'); ?>;
    ">
                                                <?= number_format($row['Outstanding Amount'], 2) ?? ''; ?>
                                            </td>

                                        </tr>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11">No tickets found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
       document.getElementById('update-date-range').addEventListener('click', function () {
    const fromDate = document.getElementById('from_date').value;
    const toDate = document.getElementById('to_date').value;

    // Fetch updated ticket summary and billing summary
    fetch(`get_summary_data.php?from_date=${fromDate}&to_date=${toDate}`)
        .then(response => response.json())
        .then(data => {
            // Update Ticket Status Summary
            const ticketSummaryContainer = document.querySelector('.btn-group-vertical');
            ticketSummaryContainer.innerHTML = data.ticketSummary.map(status => `
                <input
                    type="radio"
                    class="btn-check"
                    name="status_filter"
                    id="status_${status.id}"
                    value="${status.id}">
                <label
                    class="btn btn-outline-primary d-flex justify-content-between align-items-center w-100 mb-2"
                    for="status_${status.id}">
                    <span class="text-start">${status.name}</span>
                    <span class="text-end badge bg-primary">${status.count}</span>
                </label>
            `).join('');

            // Update Billing Summary
            document.querySelector('.card-body ul').innerHTML = `
                <li class="list-group-item">
                    <strong>Total Billing Amount:</strong>
                    <span class="float-end">${data.billingSummary.totalBillingAmount.toFixed(2)}</span>
                </li>
                <li class="list-group-item">
                    <strong>Total Receipt Amount:</strong>
                    <span class="float-end">${data.billingSummary.totalReceiptAmount.toFixed(2)}</span>
                </li>
                <li class="list-group-item">
                    <strong>Total Outstanding Amount:</strong>
                    <span class="float-end">${data.billingSummary.totalOutstandingAmount.toFixed(2)}</span>
                </li>
                <li class="list-group-item">
                    <strong>Overdue Payment Total:</strong>
                    <span class="float-end text-danger">${data.billingSummary.overdueAmount.toFixed(2)}</span>
                </li>
                <li class="list-group-item">
                    <strong>Next Week Due Amount:</strong>
                    <span class="float-end text-warning">${data.billingSummary.nextWeekDueAmount.toFixed(2)}</span>
                </li>
            `;

            // Update Ticket Table
            document.getElementById('tickets-table-container').innerHTML = data.ticketTable;
        })
        .catch(error => console.error('Error fetching summary data:', error));
});


document.querySelectorAll('input[name="status_filter"]').forEach(radio => {
    radio.addEventListener('change', function () {
        const statusId = this.value;
        const fromDate = document.getElementById('from_date').value;
        const toDate = document.getElementById('to_date').value;

        fetch(`get_filtered_tickets.php?status=${statusId}&from_date=${fromDate}&to_date=${toDate}`)
            .then(response => response.text())
            .then(data => {
                // Update the table with the new data
                document.getElementById('tickets-table-container').innerHTML = data;

                // Optionally, refresh the ticket summary if needed
                fetch(`get_summary_data.php?from_date=${fromDate}&to_date=${toDate}`)
                    .then(response => response.json())
                    .then(summary => {
                        const ticketSummaryContainer = document.querySelector('.btn-group-vertical');
                        ticketSummaryContainer.innerHTML = summary.ticketSummary.map(status => `
                            <input
                                type="radio"
                                class="btn-check"
                                name="status_filter"
                                id="status_${status.id}"
                                value="${status.id}"
                                ${statusId == status.id ? 'checked' : ''}>
                            <label
                                class="btn btn-outline-primary d-flex justify-content-between align-items-center w-100 mb-2"
                                for="status_${status.id}">
                                <span class="text-start">${status.name}</span>
                                <span class="text-end badge bg-primary">${status.count}</span>
                            </label>
                        `).join('');
                    })
                    .catch(error => console.error('Error fetching summary data:', error));
            })
            .catch(error => console.error('Error fetching filtered tickets:', error));
    });
});


    </script>
</body>

</html>