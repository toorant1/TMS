<?php
// Include database connection
require_once '../database/db_connection.php'; // Update with your DB connection file

session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged inss
    exit;
}

// Use the session variable for master_userid
$master_userid = $_SESSION['master_userid'];

// Ensure the contact_id is provided in the URL
if (isset($_GET['contact_id'])) {
    $contact_id = $_GET['contact_id'];

    // Fetch contact details from the database, including account_id and token
    $query = "SELECT c.id, c.account_id, c.name, c.designation, c.mobile1, c.mobile2, c.email, c.remark, c.status, c.created_on, c.updated_on, a.token 
              FROM contacts c
              INNER JOIN account a ON c.account_id = a.id
              WHERE c.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $contact_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the contact exists
    if ($result->num_rows === 0) {
        die("Contact not found.");
    }

    $contact = $result->fetch_assoc();

    // Handle form submission for updating contact
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $designation = $_POST['designation'] ?? null;
        $mobile1 = $_POST['mobile1'];
        $mobile2 = $_POST['mobile2'] ?? null;
        $email = $_POST['email'];
        $remark = $_POST['remark'] ?? null;
        $status = $_POST['status'];
        $updated_on = date('Y-m-d H:i:s');

        // Update contact details in the database
        $update_query = "UPDATE contacts 
                         SET name = ?, designation = ?, mobile1 = ?, mobile2 = ?, email = ?, remark = ?, status = ?, updated_on = ? 
                         WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssssssisi", $name, $designation, $mobile1, $mobile2, $email, $remark, $status, $updated_on, $contact_id);

        if ($update_stmt->execute()) {
            // Redirect to account details with account_id and token
            header("Location: show.php?account_id=" . urlencode($contact['account_id']) . "&token=" . urlencode($contact['token']));
            exit;
        } else {
            $error_message = "Error updating contact: " . $update_stmt->error;
        }
    }
} else {
    die("Contact ID not provided.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Contact</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->

<div class="container mt-5">
    <h1 class="text-center mb-4">Edit Contact</h1>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Name</label>
                <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($contact['name']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="designation" class="form-label">Designation</label>
                <input type="text" name="designation" id="designation" class="form-control" value="<?= htmlspecialchars($contact['designation']); ?>">
            </div>
            <div class="col-md-6">
                <label for="mobile1" class="form-label">Mobile 1</label>
                <input type="text" name="mobile1" id="mobile1" class="form-control" value="<?= htmlspecialchars($contact['mobile1']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="mobile2" class="form-label">Mobile 2</label>
                <input type="text" name="mobile2" id="mobile2" class="form-control" value="<?= htmlspecialchars($contact['mobile2']); ?>">
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($contact['email']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="remark" class="form-label">Remark</label>
                <textarea name="remark" id="remark" class="form-control"><?= htmlspecialchars($contact['remark']); ?></textarea>
            </div>
            <div class="col-md-6">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="1" <?= $contact['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?= $contact['status'] == 0 ? 'selected' : ''; ?>>Deactive</option>
                </select>
            </div>
        </div>
        <div class="mt-4 text-center">
            <button type="submit" class="btn btn-success">Save Changes</button>
            <a href="show.php?account_id=<?= urlencode($contact['account_id']); ?>&token=<?= urlencode($contact['token']); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>
