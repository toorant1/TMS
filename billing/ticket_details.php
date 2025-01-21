<?php
require_once '../database/db_connection.php'; // Include database connection
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Fetch ticket details from the database
$query = "
    SELECT 
        master_tickets.id AS id,
        master_tickets.ticket_id AS ticket_id,
        
        master_tickets.ticket_date AS ticket_date,
        master_tickets.problem_statement,
        master_tickets.ticket_token,
        account.account_name,
        master_tickets_types.ticket_type,
        master_tickets_priority.priority,
        master_tickets_status.status_name AS status
    FROM master_tickets
    LEFT JOIN account ON master_tickets.account_id = account.id
    LEFT JOIN master_tickets_types ON master_tickets.ticket_type_id = master_tickets_types.id
    LEFT JOIN master_tickets_priority ON master_tickets.ticket_priority_id = master_tickets_priority.id
    LEFT JOIN master_tickets_status ON master_tickets.ticket_status_id = master_tickets_status.id
    WHERE master_tickets.id = ? AND master_tickets.master_user_id = ?";

$stmt = $conn->prepare($query);

if (!$stmt) {
    echo "Failed to prepare the statement: " . $conn->error;
    exit;
}

$ticket_id = $_GET['ticket_id'] ?? null; // Ensure ticket_id is passed from the parent
if (!$ticket_id) {
    echo "Ticket ID is missing.";
    exit;
}

$stmt->bind_param("ii", $ticket_id, $master_userid);

if (!$stmt->execute()) {
    echo "Error executing the query: " . $stmt->error;
    exit;
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No details found for this ticket.";
    exit;
}

$ticket = $result->fetch_assoc();

$companyQuery = "SELECT id, company_name FROM master_company WHERE master_userid = ?";
$companyStmt = $conn->prepare($companyQuery);

if ($companyStmt) {
    $companyStmt->bind_param("i", $master_userid);
    $companyStmt->execute();
    $companyResult = $companyStmt->get_result();
} else {
    echo "Error preparing company query: " . $conn->error;
    exit;
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>

<div class="d-flex">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?>

        <!-- Main Content -->
        <div class="flex-grow-1 p-4">
            <?php include('../headers/header.php'); ?>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Ticket Details</h1>
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
        <table class="table table-bordered">
            <tbody>
                <tr>
                    <th>Ticket ID</th>
                    <td><?= htmlspecialchars($ticket['ticket_id'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Ticket Date</th>
                    <td><?= htmlspecialchars($ticket['ticket_date'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Account Name</th>
                    <td><?= htmlspecialchars($ticket['account_name'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Ticket Type</th>
                    <td><?= htmlspecialchars($ticket['ticket_type'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Priority</th>
                    <td><?= htmlspecialchars($ticket['priority'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>PaymentStatus</th>
                    <td><?= htmlspecialchars($ticket['status'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Problem Statement</th>
                    <td><?= htmlspecialchars($ticket['problem_statement'] ?? 'N/A'); ?></td>
                </tr>
            </tbody>
        </table>


        <!-- Billing Details Card -->
        <div class="card mt-5">
            <div class="card-header">
                <h5 class="mb-0">Billing Details</h5>
            </div>
            <div class="card-body">
                <?php
                // Fetch billing details for the ticket, including the company name
                $billingQuery = "
            SELECT 
                master_invoices.id,
                master_invoices.bill_no,
                master_invoices.bill_date,
                master_invoices.amount,
                master_invoices.due_date,
                master_invoices.created_at,
                master_billing_status.status_name AS billing_status,
                master_company.company_name,
                master_invoices.bill_token 
            FROM master_invoices
            LEFT JOIN master_billing_status ON master_invoices.billing_status_id = master_billing_status.id
            LEFT JOIN master_company ON master_invoices.company_id = master_company.id
            WHERE master_invoices.ticket_id = ? AND master_invoices.master_user_id = ?
        ";
                $billingStmt = $conn->prepare($billingQuery);

                if (!$billingStmt) {
                    echo "Failed to prepare the billing query: " . $conn->error;
                    exit;
                }

                $billingStmt->bind_param("ii", $ticket_id, $master_userid);

                if (!$billingStmt->execute()) {
                    echo "Error executing the billing query: " . $billingStmt->error;
                    exit;
                }

                $billingResult = $billingStmt->get_result();

                if ($billingResult->num_rows > 0): ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Bill Number</th>
                                <th>Bill Date</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Payment Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($billing = $billingResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($billing['company_name']); ?></td>
                                    <td><?= htmlspecialchars($billing['bill_no']); ?></td>
                                    <td><?= htmlspecialchars($billing['bill_date']); ?></td>
                                    <td><?= htmlspecialchars($billing['amount']); ?></td>
                                    <td><?= htmlspecialchars($billing['due_date']); ?></td>
                                    <td><?= htmlspecialchars($billing['billing_status']); ?></td>
                                    <td>
                                        <!-- Action Dropdown -->
                                        <div class="dropdown">
                                            <button class="btn btn-secondary dropdown-toggle" type="button" id="actionDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="actionDropdown">
                                                <li>
                                                    <a class="dropdown-item" href="download_invoice.php?id=<?= $billing['id']; ?>&token=<?= $billing['bill_token']; ?>">Download Invoice PDF</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="send_invoice.php?id=<?= $billing['id']; ?>&token=<?= $billing['bill_token']; ?>">Send Invoice to Client</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="send_reminder.php?id=<?= $billing['id']; ?>&token=<?= $billing['bill_token']; ?>">Send Payment Reminder</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="save_payment_receipt.php?id=<?= $billing['id']; ?>&token=<?= $billing['bill_token']; ?>">Payment Receipt Entry</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>

                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No billing details available for this ticket.</p>
                <?php endif; ?>
            </div>
        </div>


        <!-- Card with form -->
        <div class="card mt-5">
            <div class="card-header">
                <h5 class="mb-0">Create Bill / Invoice</h5>
            </div>
            <div class="card-body">
                <form action="save_billing.php" method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <!-- Select Company -->
                        <div class="col-md-3">
                            <label for="company" class="form-label">Select Company</label>
                            <select name="company" id="company" class="form-select" required>
                                <?php while ($row = $companyResult->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['company_name']) . '</option>';
                                } ?>
                            </select>
                        </div>

                        <!-- Bill Number -->
                        <div class="col-md-3">
                            <label for="bill_no" class="form-label">Bill Number</label>
                            <input type="text" name="bill_no" id="bill_no" class="form-control" placeholder="Enter Bill Number" required>
                        </div>

                        <!-- Bill Date -->
                        <div class="col-md-3">
                            <label for="bill_date" class="form-label">Bill Date</label>
                            <input type="date" name="bill_date" id="bill_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" name="amount" id="amount" class="form-control" placeholder="Enter Amount" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <!-- Payment Due Date -->
                        <div class="col-md-2">
                            <label for="due_date" class="form-label">Payment Due Date</label>
                            <input type="date" name="due_date" id="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+15 days')); ?>" required>
                        </div>

                        <!-- Bill Attachment -->
                        <div class="col-md-4">
                            <label for="bill_attachment" class="form-label">Bill Attachment (PDF Only, Max 1 MB)</label>
                            <input type="file" name="bill_attachment" id="bill_attachment" class="form-control" accept=".pdf" required>
                        </div>



                        <!-- Billing Status -->
                        <div class="col-md-2">
                            <label for="billing_status_id" class="form-label">Payment Status</label>
                            <select name="billing_status_id" id="billing_status_id" class="form-select" required>
                                <option value="" disabled selected>Select Billing Status</option>
                                <?php
                                // Query the master_billing_status table
                                $billingStatusQuery = "SELECT id, status_name FROM master_billing_status";
                                $billingStatusResult = $conn->query($billingStatusQuery);

                                // Generate options dynamically
                                if ($billingStatusResult && $billingStatusResult->num_rows > 0) {
                                    while ($row = $billingStatusResult->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['status_name']) . '</option>';
                                    }
                                } else {
                                    echo '<option value="" disabled>No Billing Status Available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <!-- Bill Remark -->
                        <div class="col-md-4">
                            <label for="remark" class="form-label">Bill Remark`</label>
                            <input type="text" name="remark" id="remark" class="form-control" placeholder="Enter Bill Remark if any" required>
                        </div>


                    </div>
                    <!-- Ticket ID (Hidden Field) -->
                    <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket['id']); ?>">

                    <!-- Save Button -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </form>
            </div>
        </div>


    </div>
</body>

</html>