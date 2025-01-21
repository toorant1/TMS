<?php
require_once '../database/db_connection.php'; // Include database connection
session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Fetch all account data
$accounts = $conn->query("SELECT id, account_name FROM account WHERE master_user_id = $master_userid ORDER BY account_name ASC");
if (!$accounts) {
    die("Failed to fetch accounts: " . $conn->error);
}

// Fetch all invoices for the user
// Fetch all invoices for the user
$invoices = $conn->query("
   SELECT 
    mi.id AS invoice_id,
    mc.company_name,
    a.account_name AS invoice_account_name,
    c.mobile1 AS contact_mobile,
    c.email AS contact_email,
    c.name, c.designation,
    mi.bill_no,
    mi.bill_date,
    mi.due_date,
    mi.amount,
    mi.bill_token,
    IFNULL(SUM(pr.payment_amount), 0) AS total_paid,
    (mi.amount - IFNULL(SUM(pr.payment_amount), 0)) AS outstanding_amount
FROM 
    master_invoices mi
LEFT JOIN 
    master_company mc ON mi.company_id = mc.id
LEFT JOIN 
    master_tickets mt ON mi.ticket_id = mt.id
LEFT JOIN 
    account a ON mt.account_id = a.id
LEFT JOIN 
    contacts c ON c.account_id = a.id
LEFT JOIN 
    payment_receipts pr ON mi.id = pr.invoice_id
WHERE 
    mi.master_user_id = $master_userid
GROUP BY 
    mi.id, mc.company_name, a.account_name, c.mobile1, c.email, mi.bill_no, mi.bill_date, mi.due_date, mi.amount, mi.bill_token
ORDER BY 
    mi.created_at DESC;


");

if (!$invoices) {
    die("Failed to fetch invoices: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Invoice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            display: flex;
            height: 100vh;
            background-color: #f8f9fa;
        }

        .content {
            flex: 1;
            padding: 100px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card {
            width: 800px;

            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .invoice-table {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar">
        <?php include "sidebar.php"; ?>
        <?php include('../headers/header.php'); ?>
    </div>

    <!-- Main Content -->
    <div class="content">
        <!-- Invoice Form -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Invoice Details</h5>
            </div>
            <div class="card-body">
                <form action="save_payment_receipt.php" method="POST" id="invoiceForm">
                    <div class="row mb-3">
                        <!-- Account Dropdown -->
                        <div class="col-md-3">
                            <label for="account_id" class="form-label">Select Account</label>
                            <select name="account_id" id="account_id" class="form-select" required>
                                <option value="" disabled selected>Select an Account</option>
                                <?php while ($row = $accounts->fetch_assoc()): ?>
                                    <option value="<?= $row['id']; ?>"><?= htmlspecialchars($row['account_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Ticket Status Dropdown -->
                        <div class="col-md-3">
                            <label for="ticket_status" class="form-label">Select Ticket Status</label>
                            <select name="ticket_status" id="ticket_status" class="form-select">
                                <option value="" disabled selected>Select Ticket Status</option>
                            </select>
                        </div>

                        <!-- Ticket ID Dropdown -->
                        <div class="col-md-3">
                            <label for="ticket_id" class="form-label">Select Ticket ID</label>
                            <select name="ticket_id" id="ticket_id" class="form-select">
                                <option value="" disabled selected>Select Ticket ID</option>
                            </select>
                        </div>

                        <!-- Process Invoice Button -->
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" id="processInvoiceBtn">Process Invoice</button>
                        </div>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" name="hidden_ticket_id" id="id">
                    <input type="hidden" name="hidden_ticket_token" id="token">
                </form>
            </div>
        </div>

        <div class="container mt-4">
    <h5 class="text-secondary">List of Invoices</h5>
    <div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Account</th>
                    <th>Bill Date</th>
                    <th>Bill No</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Total Paid</th>
                    <th>Outstanding Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($invoices->num_rows > 0): ?>
                    <?php while ($invoice = $invoices->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($invoice['company_name']); ?></td>
                            <td><strong>
                                <?= htmlspecialchars($invoice['invoice_account_name']); ?>
                                </strong> <br>
                                <?= htmlspecialchars($invoice['name']); ?> - 
                                <?= htmlspecialchars($invoice['designation']); ?> - 
                                
                                <?= htmlspecialchars($invoice['contact_mobile']); ?> <br>
                                <?= htmlspecialchars($invoice['contact_email']); ?>
                            </td>
                            <td><?= htmlspecialchars($invoice['bill_date']); ?></td>
                            <td><?= htmlspecialchars($invoice['bill_no']); ?></td>
                            <td><?= htmlspecialchars($invoice['due_date']); ?></td>
                            <td><?= number_format($invoice['amount'], 2); ?></td>
                            <td><?= number_format($invoice['total_paid'], 2); ?></td>
                            <td
                                class="<?php
                                    if ($invoice['outstanding_amount'] == 0) {
                                        echo 'bg-success text-white'; // Fully Paid
                                    } elseif ($invoice['outstanding_amount'] > 0) {
                                        echo 'bg-warning text-black'; // Partially Paid
                                    } else {
                                        echo 'bg-danger text-white'; // Overpaid
                                    }
                                ?>">
                                <?= number_format($invoice['outstanding_amount'], 2); ?>
                            </td>
                            <td>
                                <!-- Action Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Select Action
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item send-reminder"
                                                href="#"
                                                data-invoice-id="<?= htmlspecialchars($invoice['invoice_id']); ?>"
                                                data-bill-token="<?= htmlspecialchars($invoice['bill_token']); ?>">
                                                Send Payment Reminder - Email
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item send-reminder"
                                                href="#"
                                                data-invoice-id="<?= htmlspecialchars($invoice['invoice_id']); ?>"
                                                data-bill-token="<?= htmlspecialchars($invoice['bill_token']); ?>">
                                                Send Payment Reminder - WhatsApp
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item receipt-payment"
                                                href="save_payment_receipt.php?id=<?= htmlspecialchars($invoice['invoice_id']); ?>&token=<?= htmlspecialchars($invoice['bill_token']); ?>">
                                                Receipt of Payment
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center">No invoices found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

    </div>

    </div>

    <script>
        $(document).ready(function() {
            // Load ticket statuses when the account dropdown changes
            $('#account_id').on('change', function() {
                const accountId = $(this).val();

                if (accountId) {
                    $.ajax({
                        url: 'fetch_ticket_status.php',
                        type: 'POST',
                        data: {
                            account_id: accountId
                        },
                        success: function(response) {
                            $('#ticket_status').html(response);
                        },
                        error: function() {
                            alert('Failed to fetch ticket statuses.');
                        }
                    });
                } else {
                    $('#ticket_status').html('<option value="" disabled selected>Select Ticket Status</option>');
                }
            });

            // Load ticket IDs when the ticket status dropdown changes
            $('#ticket_status').on('change', function() {
                const ticketStatusId = $(this).val();
                const accountId = $('#account_id').val();

                if (ticketStatusId) {
                    $.ajax({
                        url: 'fetch_ticket_ids.php',
                        type: 'POST',
                        data: {
                            ticket_status_id: ticketStatusId,
                            account_id: accountId
                        },
                        success: function(response) {
                            $('#ticket_id').html(response);
                        },
                        error: function() {
                            alert('Failed to fetch ticket IDs.');
                        }
                    });
                } else {
                    $('#ticket_id').html('<option value="" disabled selected>Select Ticket ID</option>');
                }
            });

            // Update hidden fields when ticket ID is selected
            $('#ticket_id').on('change', function() {
                const selectedValue = $(this).val();
                if (selectedValue) {
                    const [ticketId, ticketToken] = selectedValue.split('|');
                    $('#id').val(ticketId);
                    $('#token').val(ticketToken);
                }
            });

            // Redirect to process invoice
            $('#processInvoiceBtn').on('click', function() {
                const ticketId = $('#id').val();
                const ticketToken = $('#token').val();

                if (!ticketId || !ticketToken) {
                    alert('Please select a ticket before processing the invoice.');
                    return;
                }

                const url = `ticket_details.php?ticket_id=${encodeURIComponent(ticketId)}&token=${encodeURIComponent(ticketToken)}`;
                window.location.href = url;
            });
        });
    </script>
</body>

</html>