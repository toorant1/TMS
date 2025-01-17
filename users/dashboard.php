<?php
// Include database connection
require_once '../database/db_connection.php'; // Update with your DB connection file

session_start();
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

// Use the session variable
$master_userid = $_SESSION['master_userid'];

// Fetch existing users from `master_users` table
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT id, name, address, email, mobile, token FROM master_users WHERE master_user_id = $master_userid";

// Add search condition if search query is provided
if (!empty($search_query)) {
    $search_query = $conn->real_escape_string($search_query);
    $query .= " AND name LIKE '%$search_query%'";
}

$result = $conn->query($query);

// Check if query was successful
if ($result === false) {
    die("Error executing query: " . $conn->error);  // Show detailed error if query fails
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->
<div class="container mt-5">
    <h1 class="text-center mb-4">Users Dashboard</h1>

    <!-- Search Box and Create New User Button in the Same Row -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Search Box -->
        <form method="GET" class="d-flex w-75">
            <input type="text" name="search" class="form-control me-2" placeholder="Search by User Name" value="<?= htmlspecialchars($search_query); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <!-- Create New User Button -->
        <a href="add_user.php" class="btn btn-primary">Create New User</a>
    </div>

    <!-- Table to Display Existing Users -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th hidden>ID</th>
                    <th>User Name</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Mobile</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td hidden><?= htmlspecialchars($row['id']); ?></td>
                            <!-- Create a link for the user name that passes user_id and token -->
                            <td><a href="show.php?user_id=<?= urlencode($row['id']); ?>&token=<?= urlencode($row['token']); ?>"><?= htmlspecialchars($row['name']); ?></a></td>
                            <td><?= htmlspecialchars($row['address']); ?></td>
                            <td><?= htmlspecialchars($row['email']); ?></td>
                            <td><?= htmlspecialchars($row['mobile']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No Users Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Display Total Count of Users -->
    <div class="text-start mt-12">
        <strong>Total Records: <?= $result->num_rows; ?></strong>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>
