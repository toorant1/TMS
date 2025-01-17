<?php
require_once '../database/db_connection.php';
session_start(); // Ensure session is started

$message = "";

// Check if the user ID is available in the session
if (!isset($_SESSION['master_userid'])) {
    die("Error: User is not logged in.");
}

$master_userid = $_SESSION['master_userid']; // Retrieve the user ID from the session
$account_id = $_GET['account_id'] ?? null; // Get account ID from the URL
$account_token = $_GET['token'] ?? null; // Get account token from the URL

if (!$account_id || !$account_token) {
    die("Error: Missing account ID or token.");
}

// Fetch account data from the database based on account_id and token
$sql = "SELECT * FROM account WHERE id = ? AND token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $account_id, $account_token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $account = $result->fetch_assoc();
} else {
    die("Error: Account not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // List of fields to update
    $fields = [
        'account_name', 'address', 'state', 'district', 'city', 'pincode', 'country',
        'account_type', 'mobile', 'email', 'remark'
    ];

    // Prepare SQL placeholders for the update
    $setClauses = [];
    foreach ($fields as $field) {
        $setClauses[] = "$field = ?";
    }
    $sql = "UPDATE account SET " . implode(', ', $setClauses) . ", updated_on = NOW(), updated_by = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters dynamically
        $types = str_repeat('s', count($fields)) . 'ii'; // Adding 'ii' for updated_by and account_id
        $data = array_map(fn($field) => $_POST[$field] ?? null, $fields);
        $params = array_merge($data, [$master_userid, $account_id]); // Combine form data, updated_by, and account_id
        $stmt->bind_param($types, ...$params);

        // Execute the query
        if ($stmt->execute()) {
            $message = "Account updated successfully!";
        } else {
            $message = "Error executing query: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $message = "Error preparing statement: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .form-container { max-width: 700px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .form-title { color: #333; }
        .form-label { font-weight: bold; }
        .btn-submit { background: #28a745; border: none; }
        .btn-submit:hover { background: #218838; }
        .btn-back { color: #fff; }
    </style>
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->

<div class="form-container">
    <h2 class="form-title text-center mb-4 p-3 bg-primary text-white rounded">Edit Account</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row g-3">
            <div class="col-md-8">
                <label for="account_name" class="form-label">Account Name</label>
                <input type="text" name="account_name" id="account_name" class="form-control" value="<?= htmlspecialchars($account['account_name']) ?>" required>
            </div>
            <div class="col-md-4">
                <label for="account_type" class="form-label">Account Type</label>
                <select name="account_type" id="account_type" class="form-control" required>
                    <option value="">Select Account Type</option>
                    <option value="Supplier" <?= $account['account_type'] === 'Supplier' ? 'selected' : '' ?>>Supplier</option>
                    <option value="Customer" <?= $account['account_type'] === 'Customer' ? 'selected' : '' ?>>Customer</option>
                    <option value="Both" <?= $account['account_type'] === 'Both' ? 'selected' : '' ?>>Both</option>
                </select>
            </div>

            <div class="col-12">
                <label for="address" class="form-label">Address</label>
                <textarea name="address" id="address" class="form-control" rows="2" required><?= htmlspecialchars($account['address']) ?></textarea>
            </div>

            <div class="col-md-2">
                <label for="pincode" class="form-label">Pincode</label>
                <input type="text" name="pincode" id="pincode" class="form-control" value="<?= htmlspecialchars($account['pincode']) ?>" required>
            </div>
            
            <div class="col-md-4">
                <label for="city" class="form-label">City</label>
                <input type="text" name="city" id="city" class="form-control" value="<?= htmlspecialchars($account['city']) ?>">
            </div>
            <div class="col-md-6">
                <label for="district" class="form-label">District</label>
                <input type="text" name="district" id="district" class="form-control" value="<?= htmlspecialchars($account['district']) ?>" required>
            </div>

            <div class="col-md-6">
                <label for="state" class="form-label">State</label>
                <input type="text" name="state" id="state" class="form-control" value="<?= htmlspecialchars($account['state']) ?>">
            </div>

            <div class="col-md-6">
                <label for="country" class="form-label">Country</label>
                <input type="text" name="country" id="country" class="form-control" value="<?= htmlspecialchars($account['country']) ?>">
            </div>

            <div class="col-md-6">
                <label for="mobile" class="form-label">Mobile</label>
                <input type="text" name="mobile" id="mobile" class="form-control" value="<?= htmlspecialchars($account['mobile']) ?>">
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($account['email']) ?>">
            </div>

            <div class="col-12">
                <label for="remark" class="form-label">Remark</label>
                <textarea name="remark" id="remark" class="form-control" rows="3"><?= htmlspecialchars($account['remark']) ?></textarea>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="dashboard.php" class="btn btn-secondary btn-back">Back to Dashboard</a>
            <button type="submit" class="btn btn-success btn-submit">Update Account</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
