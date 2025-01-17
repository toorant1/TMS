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

// Ensure the user_id and token are provided in the URL
if (isset($_GET['user_id']) && isset($_GET['token'])) {
    $user_id = $_GET['user_id'];
    $token = $_GET['token'];

    // Fetch user details from the database
    $query = "SELECT id, master_user_id, name, sex, address, state, district, city, pincode, mobile, email, password_reset_token, password_reset_token_status, created_on, updated_on, updated_by, token 
              FROM master_users WHERE id = ? AND token = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the user exists
    if ($result->num_rows === 0) {
        die("User not found or invalid token.");
    }

    $user = $result->fetch_assoc();
} else {
    die("User ID or token not provided.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->

<div class="container mt-5">
    <h1 class="text-center mb-4">User Details</h1>

    <!-- Table inside a Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">User Master Data : <?= htmlspecialchars($user['name']); ?>
                <a href="edit_user.php?user_id=<?= urlencode($user['id']); ?>&token=<?= urlencode($user['token']); ?>" class="btn btn-warning">Edit User Details</a>
                <!-- Generate PDF Button -->
                
                <a href="dashboard.php" class="btn btn-primary">Back</a>
            </h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Name</strong></td>
                        <td><?= htmlspecialchars($user['name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Sex</strong></td>
                        <td><?= htmlspecialchars($user['sex']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Address</strong></td>
                        <td><?= htmlspecialchars($user['address']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>State</strong></td>
                        <td><?= htmlspecialchars($user['state']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>District</strong></td>
                        <td><?= htmlspecialchars($user['district']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>City</strong></td>
                        <td><?= htmlspecialchars($user['city']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Pincode</strong></td>
                        <td><?= htmlspecialchars($user['pincode']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Mobile</strong></td>
                        <td><?= htmlspecialchars($user['mobile']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Email</strong></td>
                        <td><?= htmlspecialchars($user['email']); ?>
                        <a href="#" class="btn btn-warning">Send Reset key on email</a></td>
                    </tr>
                    
                    <tr>
                        <td><strong>Created On</strong></td>
                        <td><?= htmlspecialchars($user['created_on']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Last Updated On</strong></td>
                        <td><?= htmlspecialchars($user['updated_on']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Updated By</strong></td>
                        <td><?= htmlspecialchars($user['updated_by']); ?></td>
                    </tr>
                </tbody>
            </table>
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
