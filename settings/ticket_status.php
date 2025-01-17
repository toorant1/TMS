<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

$master_userid = $_SESSION['master_userid'];
$message = '';

// Fetch statuses (both default and user-specific)
$query = "
    SELECT 
        id, status_name, master_user_id, status
    FROM 
        master_tickets_status
    WHERE 
        master_user_id = 0 OR master_user_id = ?
    Order By master_user_id, status_name
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $master_userid);
$stmt->execute();
$result = $stmt->get_result();
$hasData = $result->num_rows > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ticket Statuses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->

<div class="container mt-5">
    <h1 class="text-center mb-4">Manage Ticket Statuses</h1>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (!$hasData): ?>
        <div class="alert alert-info text-center">
            No statuses found. You can add new statuses using the button below.
        </div>
    <?php endif; ?>

    <!-- Add New Status Button -->
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStatusModal">Add New Status</button>
    </div>

    <!-- Statuses Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th hidden>ID</th>
                    <th>Status Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($hasData): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td hidden><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['status_name']); ?></td>
                            <td><?= $row['status'] == 1 ? 'Active' : 'Inactive'; ?></td>
                            <td>
                                <?php if ($row['master_user_id'] == 0): ?>
                                    <span class="text-muted">Default</span>
                                <?php else: ?>
                                    <button class="btn btn-warning btn-sm edit-status-btn" 
                                            data-id="<?= htmlspecialchars($row['id']); ?>" 
                                            data-status-name="<?= htmlspecialchars($row['status_name']); ?>" 
                                            data-status="<?= htmlspecialchars($row['status']); ?>">
                                        Edit
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">No Statuses Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Display Total Count of Settings -->
    <div class="text-start mt-3">
        <strong>Total Records: <?= $result->num_rows; ?></strong>
    </div>
</div>

<!-- Add Status Modal -->
<div class="modal fade" id="addStatusModal" tabindex="-1" aria-labelledby="addStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="addStatusForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStatusModalLabel">Add New Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status_name" class="form-label">Status Name</label>
                        <input type="text" id="status_name" name="status_name" class="form-control" required>
                    </div>
                    <div id="addStatusError" class="text-danger"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Status Modal -->
<div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editStatusForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStatusModalLabel">Edit Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_status_id" name="id">
                    <div class="mb-3">
                        <label for="edit_status_name" class="form-label">Status Name</label>
                        <input type="text" id="edit_status_name" name="status_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select id="edit_status" name="status" class="form-select" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div id="editStatusError" class="text-danger"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Handle Edit Button Click
        document.querySelectorAll('.edit-status-btn').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                const statusName = button.getAttribute('data-status-name');
                const status = button.getAttribute('data-status');

                // Populate Modal Fields
                document.getElementById('edit_status_id').value = id;
                document.getElementById('edit_status_name').value = statusName;
                document.getElementById('edit_status').value = status;

                // Show Modal
                const editModal = new bootstrap.Modal(document.getElementById('editStatusModal'));
                editModal.show();
            });
        });
    });

    // Handle Add Status Form Submission
    document.getElementById('addStatusForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        fetch('addStatus.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                const errorDiv = document.getElementById('addStatusError');
                if (data.status === 'success') {
                    location.reload();
                } else {
                    errorDiv.textContent = data.message;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });

    // Handle Edit Status Form Submission
    document.getElementById('editStatusForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        fetch('updateStatus.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                const errorDiv = document.getElementById('editStatusError');
                if (data.status === 'success') {
                    location.reload();
                } else {
                    errorDiv.textContent = data.message;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });
</script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
