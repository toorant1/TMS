<?php
require_once '../database/db_connection.php'; // Include database connection
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Validate and sanitize input
$id = $_GET['id'] ?? null;
$token = $_GET['token'] ?? null;

if (!$id || !$token) {
    echo "Invalid request. ID and token are required.";
    exit;
}

// Fetch the invoice data from the database
$query = "
   SELECT 
    master_invoices.bill_no,
    master_invoices.amount AS bill_amount,
    master_invoices.bill_date,
    master_invoices.due_date,
    master_company.company_name,
    account.account_name,
    IFNULL(SUM(CASE WHEN payment_receipts.invoice_id = master_invoices.id THEN payment_receipts.payment_amount ELSE 0 END), 0) AS payment_receipts_amount,
    (master_invoices.amount - IFNULL(SUM(CASE WHEN payment_receipts.invoice_id = master_invoices.id THEN payment_receipts.payment_amount ELSE 0 END), 0)) AS outstanding_amount
FROM master_invoices
LEFT JOIN master_company ON master_invoices.company_id = master_company.id
LEFT JOIN account ON master_invoices.master_user_id = account.master_user_id
LEFT JOIN payment_receipts ON master_invoices.id = payment_receipts.invoice_id
WHERE master_invoices.id = ? AND master_invoices.bill_token = ? AND master_invoices.master_user_id = ?
GROUP BY 
    master_invoices.id,
    master_invoices.bill_no,
    master_invoices.amount,
    master_invoices.bill_date,
    master_invoices.due_date,
    master_company.company_name,
    account.account_name;

";

$stmt = $conn->prepare($query);

if (!$stmt) {
    echo "Failed to prepare the statement: " . $conn->error;
    exit;
}

$stmt->bind_param("isi", $id, $token, $master_userid);

if (!$stmt->execute()) {
    echo "Error executing the query: " . $stmt->error;
    exit;
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Invoice not found or invalid token.";
    exit;
}

$invoice = $result->fetch_assoc();

// Fetch past payment receipts from the database
$receipt_query = "
    SELECT 
        receipt_number,
        payment_date,
        payment_mode,
        payment_amount,
        payment_reference
    FROM payment_receipts
    WHERE invoice_id = ?
";

$receipt_stmt = $conn->prepare($receipt_query);

if ($receipt_stmt) {
    $receipt_stmt->bind_param("i", $id);
    $receipt_stmt->execute();
    $receipt_result = $receipt_stmt->get_result();
} else {
    $receipt_result = null;
}

$total_payment = 0;
if ($receipt_result && $receipt_result->num_rows > 0) {
    while ($receipt = $receipt_result->fetch_assoc()) {
        $total_payment += $receipt['payment_amount'];
    }
    // Reset result pointer for display
    $receipt_result->data_seek(0);
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt Entry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="d-flex">
<?php 
         include "sidebar.php";
         include('../headers/header.php'); ?>
    <div class="container mt-5">
        <h1 class="mb-4">Payment Receipt Entry</h1>

        <!-- Invoice Details -->
        <div class="card mb-4">
            <div class="card-header bg-info text-black">
                <h5 class="mb-0">Invoice Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Account Name</th>
                            <th>Bill Number</th>
                            <th>Bill Date</th>
                            <th>Due Date</th>
                            <th>Bill Amount</th>
                            <th>Payment Receipts Amount</th>
                            <th>Outstanding Amount</th>

                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= htmlspecialchars($invoice['company_name']); ?></td>
                            <td><?= htmlspecialchars($invoice['account_name']); ?></td>
                            <td><?= htmlspecialchars($invoice['bill_no']); ?></td>
                            <td><?= htmlspecialchars($invoice['bill_date']); ?></td>
                            <td><?= htmlspecialchars($invoice['due_date']); ?></td>
                            <td><?= htmlspecialchars($invoice['bill_amount']); ?></td>
                            <td><?= number_format($invoice['payment_receipts_amount'], 2); ?></td>
                            <td class="<?php
                                        if ($invoice['bill_amount'] == $invoice['payment_receipts_amount']) {
                                            echo 'bg-success text-white'; // Green background, black font for fully paid
                                        } elseif ($invoice['payment_receipts_amount'] == '0') {
                                            echo 'bg-warning text-black'; // Yellow background, black font for no payments
                                        } elseif ($invoice['bill_amount'] < $invoice['payment_receipts_amount']) {
                                            echo 'bg-danger text-white'; // Red background, white font for overpaid
                                        } elseif ($invoice['bill_amount'] > $invoice['payment_receipts_amount']) {
                                            echo 'bg-info text-black'; // Yellow background, black font for underpaid
                                        }
                                        ?>">
                                <?= number_format($invoice['outstanding_amount'], 2); ?>
                            </td>


                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Past Payment Receipts -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-black">
                <h5 class="mb-0">History: Payment Receipts</h5>
            </div>
            <div class="card-body">
                <?php if ($receipt_result && $receipt_result->num_rows > 0): ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Receipt Number</th>
                                <th>Payment Date</th>
                                <th>Payment Mode</th>
                                <th>Payment Amount</th>
                                <th>Payment Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($receipt = $receipt_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($receipt['receipt_number']); ?></td>
                                    <td><?= htmlspecialchars($receipt['payment_date']); ?></td>
                                    <td><?= htmlspecialchars($receipt['payment_mode']); ?></td>
                                    <td><?= htmlspecialchars($receipt['payment_amount']); ?></td>
                                    <td><?= htmlspecialchars($receipt['payment_reference']); ?></td>
                                    <td>
                                        <!-- Edit Button -->
                                        <button
                                            type="button"
                                            class="btn btn-warning btn-sm edit-receipt-btn"
                                            data-id="<?= htmlspecialchars($receipt['receipt_number']); ?>"
                                            data-invoice-id="<?= htmlspecialchars($id); ?>"
                                            data-payment-date="<?= htmlspecialchars($receipt['payment_date']); ?>"
                                            data-payment-mode="<?= htmlspecialchars($receipt['payment_mode']); ?>"
                                            data-receipt-number="<?= htmlspecialchars($receipt['receipt_number']); ?>"
                                            data-payment-amount="<?= htmlspecialchars($receipt['payment_amount']); ?>"
                                            data-payment-reference="<?= htmlspecialchars($receipt['payment_reference']); ?>">
                                            Edit
                                        </button>

                                        <!-- Delete Button -->
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-sm delete-receipt-btn"
                                            data-id="<?= htmlspecialchars($receipt['receipt_number']); ?>"
                                            data-invoice-id="<?= htmlspecialchars($id); ?>">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <h5 class="fw-bold text-center text-danger">Total Payment Receipt : <?= number_format($total_payment, 2); ?></h5>
                <?php else: ?>
                    <p class="text-muted">No past payment receipts found for this invoice.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Receipt Form -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Payment Receipt</h5>
            </div>
            <div class="card-body">
                <form action="save_payment_reciept_query.php" method="POST">
                    <input type="hidden" name="invoice_id" value="<?= htmlspecialchars($id); ?>">
                    <input type="hidden" name="invoice_token" value="<?= htmlspecialchars($token); ?>">

                    <div class="row mb-3">
                        <!-- First Row -->
                        <div class="col-md-2">
                            <label for="payment_date" class="form-label">Payment Receipt Date</label>
                            <input type="date" name="payment_date" id="payment_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="payment_mode" class="form-label">Payment Mode</label>
                            <select name="payment_mode" id="payment_mode" class="form-control" required>
                                <option value="Cash">By Cash</option>
                                <option value="Bank">By Bank / CQ</option>
                                <option value="Round Off">By Round Off Discount</option>
                                <option value="TDS">By TDS/TCS Adjustment</option>
                                <option value="Other">By Other Adjustments</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="receipt_number" class="form-label">Receipt Number</label>
                            <input type="text" name="receipt_number" id="receipt_number" class="form-control" placeholder="Enter Receipt Number" required>
                        </div>
                        <div class="col-md-4">
                            <label for="payment_amount" class="form-label">Payment Amount</label>
                            <input type="number" name="payment_amount" id="payment_amount" class="form-control" placeholder="Enter Payment Amount" step="0.01" max="<?= htmlspecialchars($invoice['bill_amount']); ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <!-- Payment Reference -->
                        <div class="col-md-12">
                            <label for="payment_reference" class="form-label">Payment Reference</label>
                            <input type="text" name="payment_reference" id="payment_reference" class="form-control" placeholder="Enter Payment Reference" required>
                        </div>
                    </div>
                    <!-- Save Button -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-success" id="save-button">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function disableButton(form) {
            const button = form.querySelector('#save-button');
            button.disabled = true; // Disable the button
            button.textContent = 'Processing...'; // Optional: Change button text
        }
    </script>



    <script>
        // Handle Edit Button Click
        document.querySelectorAll('.edit-receipt-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Get receipt data from button attributes
                const receiptId = this.getAttribute('data-id');
                const invoiceId = this.getAttribute('data-invoice-id');
                const paymentDate = this.getAttribute('data-payment-date');
                const paymentMode = this.getAttribute('data-payment-mode');
                const receiptNumber = this.getAttribute('data-receipt-number');
                const paymentAmount = this.getAttribute('data-payment-amount');
                const paymentReference = this.getAttribute('data-payment-reference');

                // Populate modal fields
                document.getElementById('edit_receipt_id').value = receiptId;
                document.getElementById('edit_payment_date').value = paymentDate;
                document.getElementById('edit_payment_mode').value = paymentMode;
                document.getElementById('edit_receipt_number').value = receiptNumber;
                document.getElementById('edit_payment_amount').value = paymentAmount;
                document.getElementById('edit_payment_reference').value = paymentReference;

                // Open the modal
                const editModal = new bootstrap.Modal(document.getElementById('editReceiptModal'));
                editModal.show();
            });
        });
    </script>


    <script>
        // Handle Delete Button Click
        document.querySelectorAll('.delete-receipt-btn').forEach(button => {
            button.addEventListener('click', function() {
                const receiptId = this.getAttribute('data-id');
                const invoiceId = this.getAttribute('data-invoice-id');
                if (confirm('Are you sure you want to delete this receipt?')) {
                    // Perform delete action (implement delete_receipt.php)
                    fetch(`delete_receipt.php?receipt_id=${receiptId}&invoice_id=${invoiceId}`, {
                            method: 'GET'
                        })
                        .then(response => response.text())
                        .then(data => {
                            alert(data);
                            location.reload(); // Reload the page to refresh the list
                        })
                        .catch(error => alert('An error occurred while deleting the receipt.'));
                }
            });
        });
    </script>



    <!-- Edit Payment Receipt Modal -->
    <div class="modal fade" id="editReceiptModal" tabindex="-1" aria-labelledby="editReceiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editReceiptModalLabel">Edit Payment Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editReceiptForm" action="update_payment_receipt.php" method="POST">
                    <div class="modal-body">
                        <!-- Hidden Input for Receipt ID -->
                        <input type="hidden" name="receipt_id" id="edit_receipt_id">
                        <input type="hidden" name="invoice_id" value="<?= htmlspecialchars($id); ?>">

                        <div class="row mb-3">
                            <!-- Payment Date -->
                            <div class="col-md-6">
                                <label for="edit_payment_date" class="form-label">Payment Date</label>
                                <input type="date" name="payment_date" id="edit_payment_date" class="form-control" required>
                            </div>
                            <!-- Payment Mode -->
                            <div class="col-md-6">
                                <label for="edit_payment_mode" class="form-label">Payment Mode</label>
                                <select name="payment_mode" id="edit_payment_mode" class="form-control" required>
                                    <option value="Cash">By Cash</option>
                                    <option value="Bank">By Bank / CQ</option>
                                    <option value="Round Off">By Round Off Discount</option>
                                    <option value="TDS">By TDS/TCS Adjustment</option>
                                    <option value="Other">By Other Adjustments</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <!-- Receipt Number -->
                            <div class="col-md-6">
                                <label for="edit_receipt_number" class="form-label">Receipt Number</label>
                                <input type="text" name="receipt_number" id="edit_receipt_number" class="form-control" required>
                            </div>
                            <!-- Payment Amount -->
                            <div class="col-md-6">
                                <label for="edit_payment_amount" class="form-label">Payment Amount</label>
                                <input type="number" name="payment_amount" id="edit_payment_amount" class="form-control" step="0.01" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <!-- Payment Reference -->
                            <div class="col-md-12">
                                <label for="edit_payment_reference" class="form-label">Payment Reference</label>
                                <input type="text" name="payment_reference" id="edit_payment_reference" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>