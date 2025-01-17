<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];
$account_id = $_GET['account_id'] ?? '';

if (empty($account_id)) {
    echo "Invalid account ID.";
    exit;
}

// Fetch account details for pre-filling
$query = "SELECT account_name, address FROM account WHERE id = ? AND master_user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $account_id, $master_userid);
$stmt->execute();
$result = $stmt->get_result();
$account = $result->fetch_assoc();

if (!$account) {
    echo "Account not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Contact Modal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h1>Contacts Management</h1>

    <!-- Button to Trigger Modal -->
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createContactModal" data-account-id="1">
        Create New Contact
    </button>

    <!-- Create Contact Modal -->
    <div class="modal fade" id="createContactModal" tabindex="-1" aria-labelledby="createContactModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createContactModalLabel">Create New Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createContactForm" method="POST" action="save_contact.php">
                        <input type="hidden" name="account_id" id="modal_account_id" value="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Contact Name</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Mobile 1</label>
                            <input type="text" id="phone" name="phone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="mobile2" class="form-label">Mobile 2</label>
                            <input type="text" id="mobile2" name="mobile2" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="designation" class="form-label">Designation</label>
                            <input type="text" id="designation" name="designation" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="remark" class="form-label">Remark</label>
                            <textarea id="remark" name="remark" class="form-control"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Contact</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Set the account_id dynamically when opening the modal
        document.querySelectorAll('[data-bs-target="#createContactModal"]').forEach(button => {
            button.addEventListener('click', function () {
                const accountId = this.getAttribute('data-account-id'); // Get the account_id from the button
                document.getElementById('modal_account_id').value = accountId; // Set it in the modal's hidden field
            });
        });
    });
</script>
</body>
</html>
