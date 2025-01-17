<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

$master_userid = $_SESSION['master_userid'];
$message = '';

// Handle Add New Ticket Type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $ticket_type = trim($_POST['ticket_type']);

    if (!empty($ticket_type)) {
        $query = "INSERT INTO master_tickets_types (master_user_id, ticket_type, status) VALUES (?, ?, 1)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $master_userid, $ticket_type);

        if ($stmt->execute()) {
            $message = "New ticket type added successfully.";
        } else {
            $message = "Error adding ticket type.";
        }
        $stmt->close();
    } else {
        $message = "Ticket type cannot be empty.";
    }
}

// Handle Edit Ticket Type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id']);
    $ticket_type = trim($_POST['ticket_type']);
    $status = intval($_POST['status']);

    if (!empty($ticket_type)) {
        $query = "UPDATE master_tickets_types SET ticket_type = ?, status = ? WHERE id = ? AND master_user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("siii", $ticket_type, $status, $id, $master_userid);

        if ($stmt->execute()) {
            $message = "Ticket type updated successfully.";
        } else {
            $message = "Error updating ticket type.";
        }
        $stmt->close();
    } else {
        $message = "Ticket type cannot be empty.";
    }
}

// Fetch ticket types (both default and user-specific)
$query = "
    SELECT 
        id, ticket_type, master_user_id, status
    FROM 
        master_tickets_types
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
    <title>Manage Ticket Types</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->

<div class="container mt-5">
    <h1 class="text-center mb-4">Manage Ticket Types</h1>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (!$hasData): ?>
        <div class="alert alert-info text-center">
            No ticket types found. You can add new ticket types using the button below.
        </div>
    <?php endif; ?>

    <!-- Add New Ticket Type Button -->
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTicketTypeModal">Add New Ticket Type</button>
    </div>

    <!-- Ticket Types Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th hidden>ID</th>
                    <th>Ticket Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($hasData): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td hidden><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['ticket_type']); ?></td>
                            <td><?= $row['status'] == 1 ? 'Active' : 'Inactive'; ?></td>
                            <td>
                                <?php if ($row['master_user_id'] == 0): ?>
                                    <span class="text-muted">Default</span>
                                <?php else: ?>
                                    <button class="btn btn-warning btn-sm edit-ticket-type-btn" 
                                            data-id="<?= htmlspecialchars($row['id']); ?>" 
                                            data-ticket-type="<?= htmlspecialchars($row['ticket_type']); ?>" 
                                            data-status="<?= htmlspecialchars($row['status']); ?>">
                                        Edit
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">No Ticket Types Found</td>
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

<!-- Add Ticket Type Modal -->
<div class="modal fade" id="addTicketTypeModal" tabindex="-1" aria-labelledby="addTicketTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTicketTypeModalLabel">Add New Ticket Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ticket_type" class="form-label">Ticket Type</label>
                        <input type="text" id="ticket_type" name="ticket_type" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Ticket Type Modal -->
<div class="modal fade" id="editTicketTypeModal" tabindex="-1" aria-labelledby="editTicketTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST">
            <input type="hidden" name="action" value="edit">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTicketTypeModalLabel">Edit Ticket Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_ticket_type_id" name="id">
                    <div class="mb-3">
                        <label for="edit_ticket_type" class="form-label">Ticket Type</label>
                        <input type="text" id="edit_ticket_type" name="ticket_type" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select id="edit_status" name="status" class="form-select" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
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
        document.querySelectorAll('.edit-ticket-type-btn').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                const ticketType = button.getAttribute('data-ticket-type');
                const status = button.getAttribute('data-status');

                // Populate Modal Fields
                document.getElementById('edit_ticket_type_id').value = id;
                document.getElementById('edit_ticket_type').value = ticketType;
                document.getElementById('edit_status').value = status;

                // Show Modal
                const editModal = new bootstrap.Modal(document.getElementById('editTicketTypeModal'));
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
