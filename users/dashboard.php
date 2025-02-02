<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Fetch existing users from `master_users` table
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT id, name, address, email, mobile, token, status FROM master_users WHERE master_user_id = $master_userid order by status desc, name asc";

if (!empty($search_query)) {
    $search_query = $conn->real_escape_string($search_query);
    $query .= " AND name LIKE '%$search_query%'";
}

$result = $conn->query($query);

if ($result === false) {
    die("Error executing query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php include('../headers/header.php'); ?>
<div class="container mt-5">
    <h1 class="text-center mb-4">Users Dashboard</h1>

    <!-- Search Box and Create New User Button -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="GET" class="d-flex w-75">
            <input type="text" name="search" class="form-control me-2" placeholder="Search by User Name" value="<?= htmlspecialchars($search_query); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <a href="add_user.php" class="btn btn-primary">Create New User</a>
    </div>

    <!-- User Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                <th style="width: 20%;">User Name</th>
                <th style="width: 25%;">Address</th>
                <th style="width: 20%;">Email</th>
                <th style="width: 15%;">Mobile</th>
                <th style="width: 10%;">Current Status</th>
                <th style="width: 10%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><a href="show.php?user_id=<?= urlencode($row['id']); ?>&token=<?= urlencode($row['token']); ?>"><?= htmlspecialchars($row['name']); ?></a></td>
                            <td><?= htmlspecialchars($row['address']); ?></td>
                            <td><?= htmlspecialchars($row['email']); ?></td>
                            <td><?= htmlspecialchars($row['mobile']); ?></td>
                            <td>
                                <span id="status-<?= $row['id']; ?>" class="badge <?= $row['status'] ? 'bg-success' : 'bg-danger'; ?>"
                                style="font-size: 0.9rem;">
                                    
                                    <?= $row['status'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn <?= $row['status'] ? 'btn-danger' : 'btn-success'; ?> btn-sm toggle-status"
                                        data-userid="<?= $row['id']; ?>" 
                                        data-status="<?= $row['status']; ?>">
                                    <?= $row['status'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No Users Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="text-start mt-3">
        <strong>Total Records: <?= $result->num_rows; ?></strong>
    </div>
</div>

<script>
$(document).ready(function() {
    $(".toggle-status").click(function() {
        var userId = $(this).data("userid");
        var newStatus = $(this).data("status") == 1 ? 0 : 1;
        var button = $(this);
        var statusBadge = $("#status-" + userId);

        $.ajax({
            url: "update_user_status.php",
            type: "POST",
            data: { user_id: userId, status: newStatus },
            success: function(response) {
                if (response == "success") {
                    if (newStatus == 1) {
                        statusBadge.removeClass("bg-danger").addClass("bg-success").text("Active");
                        button.removeClass("btn-success").addClass("btn-danger").text("Deactivate");
                    } else {
                        statusBadge.removeClass("bg-success").addClass("bg-danger").text("Inactive");
                        button.removeClass("btn-danger").addClass("btn-success").text("Activate");
                    }
                    button.data("status", newStatus);
                } else {
                    alert("Failed to update status.");
                }
            },
            error: function() {
                alert("Error processing request.");
            }
        });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
