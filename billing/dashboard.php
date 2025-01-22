<?php
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
                                    <input type="date" id="from_date" name="from_date" class="form-control text-center" value="<?= htmlspecialchars($from_date); ?>">
                                </div>
                                <!-- To Date -->
                                <div class="mb-3">
                                    <label for="to_date" class="form-label"><strong>To:</strong></label>
                                    <input type="date" id="to_date" name="to_date" class="form-control text-center" value="<?= htmlspecialchars($to_date); ?>">
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
                            <div id="status-summary" class="btn-group-vertical w-100" role="group" aria-label="Ticket Status Summary"></div>
                        </div>
                    </div>

                    <!-- Billing Summary Card -->
                    <div class="card shadow">
                        <div class="card-header bg-success text-white text-center">
                            <h5 class="mb-0">Billing Summary</h5>
                        </div>
                        <div class="card-body">
                            <ul id="billing-summary" class="list-group list-group-flush"></ul>
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
                        <tbody id="tickets-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>

document.getElementById('update-date-range').addEventListener('click', updateDashboard);
document.addEventListener('DOMContentLoaded', updateDashboard);

        const masterUserId = <?= json_encode($master_userid); ?>;

        

        async function fetchSummaryData(fromDate, toDate) {
            const response = await fetch(`../api/billing/billing_update_date_range.php?master_user_id=${masterUserId}&from_date=${fromDate}&to_date=${toDate}`);
            return response.json();
        }


        async function updateDashboard() {
    const ticketsTableBody = document.getElementById('tickets-table-body');
    ticketsTableBody.innerHTML = '<tr><td colspan="11">Loading...</td></tr>'; // Show loading state

    const fromDate = document.getElementById('from_date').value;
    const toDate = document.getElementById('to_date').value;

    try {
        const data = await fetchSummaryData(fromDate, toDate);

        if (data.code !== 200) {
            ticketsTableBody.innerHTML = `<tr><td colspan="11">Error fetching data: ${data.message || 'Unknown error'}</td></tr>`;
            return;
        }

        // Update Tickets Table
        ticketsTableBody.innerHTML = data.tickets?.length
            ? data.tickets.map((ticket, index, tickets) => {
                let isFirstRowForTicket = index === 0 || tickets[index - 1]['Ticket ID'] !== ticket['Ticket ID'];

                return `
                    <tr>
                        ${
                            isFirstRowForTicket
                                ? `
                                    <td rowspan="1">
                                        <a href="ticket_details.php?ticket_id=${encodeURIComponent(ticket['Ticket ID'])}">
                                            ${ticket['Internal Ticket ID']}
                                        </a>
                                    </td>
                                    <td rowspan="1">
                                        ${ticket['Ticket Date'] ? new Date(ticket['Ticket Date']).toLocaleDateString('en-GB') : 'N/A'}
                                    </td>
                                    <td rowspan="1">${ticket['Account Name'] || 'N/A'}</td>
                                    <td rowspan="1">${ticket['Ticket Type'] || 'N/A'}</td>
                                    <td rowspan="1">${ticket['Ticket Status'] || 'N/A'}</td>
                                `
                                : `
                                    <td colspan="5"></td>
                                `
                        }
                        <td>${ticket['Bill No'] || ''}</td>
                        <td>${ticket['Bill Date'] ? new Date(ticket['Bill Date']).toLocaleDateString('en-GB') : ''}</td>
                        <td style="background-color: ${
                            ticket['Outstanding Amount'] > 0
                                ? new Date(ticket['Due Date']) < new Date()
                                    ? '#f8d7da' // Overdue: bg-danger
                                    : new Date(ticket['Due Date']) <= new Date(new Date().setDate(new Date().getDate() + 7))
                                    ? '#fff3cd' // Due in Next 7 Days: bg-warning
                                    : '#d4edda' // Regular Due: bg-success
                                : 'transparent' // No Outstanding Amount
                        }; color: ${
                            ticket['Outstanding Amount'] > 0
                                ? new Date(ticket['Due Date']) < new Date()
                                    ? 'red'
                                    : 'black'
                                : 'green'
                        }">
                            ${ticket['Due Date'] ? new Date(ticket['Due Date']).toLocaleDateString('en-GB') : ''}
                        </td>
                        <td>${parseFloat(ticket['Bill Amount'] || 0).toFixed(2)}</td>
                        <td>${parseFloat(ticket['Total Payment Received'] || 0).toFixed(2)}</td>
                        <td style="color: ${
                            ticket['Outstanding Amount'] === 0
                                ? 'green'
                                : ticket['Outstanding Amount'] < ticket['Bill Amount']
                                ? 'orange'
                                : 'red'
                        }; background-color: ${
                            ticket['Outstanding Amount'] === 0
                                ? '#d4edda'
                                : ticket['Outstanding Amount'] < ticket['Bill Amount']
                                ? '#fff3cd'
                                : '#f8d7da'
                        }">
                            ${parseFloat(ticket['Outstanding Amount'] || 0).toFixed(2)}
                        </td>
                    </tr>
                `;
            }).join('')
            : '<tr><td colspan="11">No tickets found for the selected date range.</td></tr>';
    } catch (error) {
        ticketsTableBody.innerHTML = `<tr><td colspan="11">Error: ${error.message || 'Failed to fetch data.'}</td></tr>`;
        console.error('Error updating dashboard:', error);
    }
}



    </script>
</body>

</html>
