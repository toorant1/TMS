<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

$master_userid = $_SESSION['master_userid'];
$message = '';

// Fetch priorities (both default and user-specific)
$query = "
    SELECT 
        id, priority, master_user_id, status
    FROM 
        master_tickets_priority
    WHERE 
        master_user_id = 0 OR master_user_id = ?
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
    <title>Manage Ticket Priorities</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->

<div class="container mt-5">
    <h1 class="text-center mb-4">Manage Ticket Priorities</h1>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (!$hasData): ?>
        <div class="alert alert-info text-center">
            No priorities found. You can add new priorities using the button below.
        </div>
    <?php endif; ?>

    <!-- Add New Priority Button -->
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPriorityModal">Add New Priority</button>
    </div>

    <!-- Priorities Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th hidden>ID</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($hasData): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td hidden><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['priority']); ?></td>
                            <td><?= $row['status'] == 1 ? 'Active' : 'Inactive'; ?></td>
                            <td>
                                <?php if ($row['master_user_id'] == 0): ?>
                                    <span class="text-muted">Default</span>
                                <?php else: ?>
                                    <button class="btn btn-warning btn-sm edit-priority-btn" 
                                            data-id="<?= htmlspecialchars($row['id']); ?>" 
                                            data-priority="<?= htmlspecialchars($row['priority']); ?>" 
                                            data-status="<?= htmlspecialchars($row['status']); ?>">
                                        Edit
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">No Priorities Found</td>
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

<!-- Add Priority Modal -->
<div class="modal fade" id="addPriorityModal" tabindex="-1" aria-labelledby="addPriorityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="addPriorityForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPriorityModalLabel">Add New Priority</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <input type="text" id="priority" name="priority" class="form-control" required>
                    </div>
                    <div id="addPriorityError" class="text-danger"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Priority Modal -->
<div class="modal fade" id="editPriorityModal" tabindex="-1" aria-labelledby="editPriorityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editPriorityForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPriorityModalLabel">Edit Priority</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_priority_id" name="id">
                    <div class="mb-3">
                        <label for="edit_priority" class="form-label">Priority</label>
                        <input type="text" id="edit_priority" name="priority" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select id="edit_status" name="status" class="form-select" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div id="editPriorityError" class="text-danger"></div>
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
        document.querySelectorAll('.edit-priority-btn').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                const priority = button.getAttribute('data-priority');
                const status = button.getAttribute('data-status');

                // Populate Modal Fields
                document.getElementById('edit_priority_id').value = id;
                document.getElementById('edit_priority').value = priority;
                document.getElementById('edit_status').value = status;

                // Show Modal
                const editModal = new bootstrap.Modal(document.getElementById('editPriorityModal'));
                editModal.show();
            });
        });
    });
</script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
<script>

document.getElementById('addPriorityForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent default form submission

    const formData = new FormData(this); // Create FormData object
    fetch('addPriority.php', {
        method: 'POST',
        body: formData // Send form data
    })
        .then(response => response.json()) // Parse JSON response
        .then(data => {
            const errorDiv = document.getElementById('addPriorityError');
            if (data.status === 'success') {
                // Success: Refresh the page or update the table dynamically
                alert(data.message); // Optional: Show a success message
                location.reload(); // Reload the page
            } else {
                // Error: Show the error message
                errorDiv.textContent = data.message;
            }
        })
        .catch(error => {
            console.error('Error:', error); // Log any errors
        });
});


document.addEventListener('DOMContentLoaded', () => {
    // Handle Edit Button Click
    document.querySelectorAll('.edit-priority-btn').forEach(button => {
        button.addEventListener('click', () => {
            const id = button.getAttribute('data-id');
            const priority = button.getAttribute('data-priority');
            const status = button.getAttribute('data-status');

            // Populate Modal Fields
            document.getElementById('edit_priority_id').value = id;
            document.getElementById('edit_priority').value = priority;
            document.getElementById('edit_status').value = status;

            // Show Modal
            const editModal = new bootstrap.Modal(document.getElementById('editPriorityModal'));
            editModal.show();
        });
    });

    // Handle Edit Priority Form Submission
    document.getElementById('editPriorityForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this); // Create FormData object
        fetch('updatePriority.php', {
            method: 'POST',
            body: formData // Send form data
        })
            .then(response => response.json()) // Parse JSON response
            .then(data => {
                const errorDiv = document.getElementById('editPriorityError');
                if (data.status === 'success') {
                    // Success: Refresh the page or update the table dynamically
                    alert(data.message); // Optional: Show a success message
                    location.reload(); // Reload the page
                } else {
                    // Error: Show the error message
                    errorDiv.textContent = data.message;
                }
            })
            .catch(error => {
                console.error('Error:', error); // Log any errors
            });
    });
});

</script>
