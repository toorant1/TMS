<?php
// Include database connection
require_once '../database/db_connection.php'; // Update with your DB connection file

session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

// Use the session variable for master_userid
$master_userid = $_SESSION['master_userid'];

// Ensure the account_id and token are provided in the URL
if (isset($_GET['account_id']) && isset($_GET['token'])) {
    $account_id = $_GET['account_id'];
    $token = $_GET['token'];

    // Fetch account details from the database
    $query = "SELECT id, master_user_id, account_name, address, state, district, city, pincode, country, account_type, mobile, email, 
                     password_reset_token, password_reset_token_status, token, remark, created_on, updated_on, updated_by, status, 
                     gst, pan, tan, msme, bank_name, branch, ifsc, account_no 
              FROM account 
              WHERE id = ? AND token = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $account_id, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the account exists
    if ($result->num_rows === 0) {
        die("Account not found or invalid token.");
    }

    $account = $result->fetch_assoc();

    // Fetch contact details for the account
    $contact_query = "SELECT id, name, status, designation, mobile1, mobile2, email, remark, created_on, updated_on, token 
                      FROM contacts 
                      WHERE account_id = ?";
    $contact_stmt = $conn->prepare($contact_query);
    $contact_stmt->bind_param("i", $account_id);
    $contact_stmt->execute();
    $contacts_result = $contact_stmt->get_result();

    // Fetch all contacts
    $contacts = $contacts_result->fetch_all(MYSQLI_ASSOC);
} else {
    die("Account ID or token not provided.");
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card { margin-bottom: 20px; }
        .table-responsive { margin-top: 20px; }
    </style>
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->

<div class="container mt-4">
    <h1 class="text-center mb-4">Account Details</h1>

    <!-- Account Details Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title d-flex justify-content-between align-items-center">
                <span>Account Details: <?= htmlspecialchars($account['account_name']); ?></span>
                <div>
                    <a href="edit_account.php?account_id=<?= urlencode($account['id']); ?>&token=<?= urlencode($account['token']); ?>" class="btn btn-warning btn-sm me-2">Edit Account</a>
                    <a href="dashboard.php" class="btn btn-primary btn-sm">Back</a>
                </div>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>Account Name</strong></td><td><?= htmlspecialchars($account['account_name']); ?></td></tr>
                        <tr><td><strong>Address</strong></td><td><?= htmlspecialchars($account['address']); ?></td></tr>
                        <tr><td><strong>State</strong></td><td><?= htmlspecialchars($account['state']); ?></td></tr>
                        <tr><td><strong>District</strong></td><td><?= htmlspecialchars($account['district']); ?></td></tr>
                        <tr><td><strong>City</strong></td><td><?= htmlspecialchars($account['city']); ?></td></tr>
                        <tr><td><strong>Pincode</strong></td><td><?= htmlspecialchars($account['pincode']); ?></td></tr>
                        <tr><td><strong>Country</strong></td><td><?= htmlspecialchars($account['country']); ?></td></tr>
                        <tr><td><strong>Account Type</strong></td><td><?= htmlspecialchars($account['account_type']); ?></td></tr>
                        <tr><td><strong>Mobile</strong></td><td><?= htmlspecialchars($account['mobile']); ?></td></tr>
                        <tr><td><strong>Email</strong></td><td><?= htmlspecialchars($account['email']); ?></td></tr>
                        <tr><td><strong>GST</strong></td><td><?= htmlspecialchars($account['gst']); ?></td></tr>
                        <tr><td><strong>PAN</strong></td><td><?= htmlspecialchars($account['pan']); ?></td></tr>
                        <tr><td><strong>TAN</strong></td><td><?= htmlspecialchars($account['tan']); ?></td></tr>
                        <tr><td><strong>MSME</strong></td><td><?= htmlspecialchars($account['msme']); ?></td></tr>
                        <tr><td><strong>Bank Name</strong></td><td><?= htmlspecialchars($account['bank_name']); ?></td></tr>
                        <tr><td><strong>Branch</strong></td><td><?= htmlspecialchars($account['branch']); ?></td></tr>
                        <tr><td><strong>IFSC</strong></td><td><?= htmlspecialchars($account['ifsc']); ?></td></tr>
                        <tr><td><strong>Account No</strong></td><td><?= htmlspecialchars($account['account_no']); ?></td></tr>
                        <tr><td><strong>Remark</strong></td><td><?= htmlspecialchars($account['remark']); ?></td></tr>
                        <tr><td><strong>Created On</strong></td><td><?= htmlspecialchars($account['created_on']); ?></td></tr>
                        <tr><td><strong>Updated On</strong></td><td><?= htmlspecialchars($account['updated_on']); ?></td></tr>
                        <tr><td><strong>Status</strong></td><td><?= htmlspecialchars($account['status']); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Contacts Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Contacts</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Mobile 1</th>
                            <th>Mobile 2</th>
                            <th>Email</th>
                            <th>Remark</th>
                            <th>Status</th>
                            <th>Created On</th>
                            <th>Updated On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $contact): ?>
                            <tr>
                                <td><?= htmlspecialchars($contact['name']); ?></td>
                                <td><?= htmlspecialchars($contact['designation']); ?></td>
                                <td><?= htmlspecialchars($contact['mobile1']); ?></td>
                                <td><?= htmlspecialchars($contact['mobile2']); ?></td>
                                <td><?= htmlspecialchars($contact['email']); ?></td>
                                <td><?= htmlspecialchars($contact['remark']); ?></td>
                                <td><?= $contact['status'] == 1 ? 'Active' : 'Deactive'; ?></td>
                                <td><?= htmlspecialchars($contact['created_on']); ?></td>
                                <td><?= htmlspecialchars($contact['updated_on']); ?></td>
                                <td>
                                    <a href="edit_contact.php?contact_id=<?= urlencode($contact['id']); ?>" class="btn btn-sm btn-warning">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>
