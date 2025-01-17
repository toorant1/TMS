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

// Fetch existing marketing records from the `master_marketing` table based on master_user_id
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT 
            m.id, 
            m.internal_id,
            a.account_name, 
            u.name AS user_name, 
            c.main_cause AS cause, 
            m.requirement, 
            ms.status AS marketing_status, 
            m.status, 
            m.m_date, 
            m.token 
          FROM master_marketing m
          INNER JOIN account a ON m.account_id = a.id
          INNER JOIN master_users u ON m.user_id = u.id
          INNER JOIN master_tickets_main_causes c ON m.main_cause_id = c.id
          INNER JOIN master_marketing_status ms ON m.marketing_id_status = ms.id
          WHERE m.master_user_id = $master_userid
          ORDER BY m_date desc";


// Add search condition if search query is provided
if (!empty($search_query)) {
    $search_query = $conn->real_escape_string($search_query);
    $query .= " AND requirement LIKE '%$search_query%'";
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
    <title>Marketing Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom CSS to make table font size smaller */
        table.table {
            font-size: 14px; /* Adjust the font size here */
        }

        table.table th, table.table td {
            padding: 8px; /* Reduce padding for better compactness */
            vertical-align: middle; /* Center align table content vertically */
        }

        h1 {
            font-size: 1.5rem; /* Reduce title font size */
        }
    </style>
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->
<div class="container mt-5">
    <h1 class="text-center mb-4">Marketing Dashboard</h1>

    <!-- Search Box and Create New Marketing Record Button -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Search Box -->
        <form method="GET" class="d-flex w-75">
            <input type="text" name="search" class="form-control me-2" placeholder="Search by Requirement" value="<?= htmlspecialchars($search_query); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <!-- Create New Marketing Record Button -->
        <a href="add_marketing.php" class="btn btn-primary">Create New Visit</a>
    </div>

    <!-- Table to Display Existing Marketing Records -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Sr. No.</th>
                    <th>Account Name</th>
                    <th>User Name</th>
                    <th>Main Cause</th>
                    <th>Requirement</th>
                    <th>Marketing Status</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <a href="marketing_operations.php?id=<?= urlencode($row['id']); ?>&token=<?= urlencode($row['token']); ?>" 
                                target="_blank" 
                                class="text-decoration-none">
                                    <?= htmlspecialchars($row['internal_id']); ?>
                                </a>
                            </td>

                            <td><?= htmlspecialchars($row['account_name']); ?></td>
                            <td><?= htmlspecialchars($row['user_name']); ?></td>
                            <td><?= htmlspecialchars($row['cause']); ?></td>
                            <td><?= htmlspecialchars($row['requirement']); ?></td>
                            <td><?= htmlspecialchars($row['marketing_status']); ?></td>
                            <td><?= $row['status'] == 1 ? 'Active' : 'Deactive'; ?></td>
                            <td><?= htmlspecialchars($row['m_date']); ?></td>
                            <td>
                                <a href="edit_marketing.php?id=<?= urlencode($row['id']); ?>" class="btn btn-sm btn-warning">Edit</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">No Marketing Records Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Display Total Count of Records -->
    <div class="text-start mt-3">
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

