<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../database/db_connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Fetch email configurations for the logged-in user
$query = "
    SELECT 
        id, smtp_host, smtp_port, smtp_user, smtp_status 
    FROM 
        master_email_configuration 
    WHERE 
        master_user_id = ?
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
    <title>Manage Email Configurations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<?php include('../headers/header.php'); ?>
<div class="container mt-5">
    <h1 class="text-center mb-4">Manage Email Configurations</h1>

    <?php if (!$hasData): ?>
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmailConfigModal">Add New Email Configuration</button>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>SMTP Host</th>
                    <th>SMTP Port</th>
                    <th>SMTP User</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($hasData): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['smtp_host']); ?></td>
                            <td><?= htmlspecialchars($row['smtp_port']); ?></td>
                            <td><?= htmlspecialchars($row['smtp_user']); ?></td>
                            <td><?= $row['smtp_status'] == 1 ? 'Active' : 'Inactive'; ?></td>
                            <td>
                                <button 
                                    class="btn btn-warning btn-sm edit-config-btn" 
                                    data-id="<?= htmlspecialchars($row['id']); ?>" 
                                    data-host="<?= htmlspecialchars($row['smtp_host']); ?>"
                                    data-port="<?= htmlspecialchars($row['smtp_port']); ?>"
                                    data-user="<?= htmlspecialchars($row['smtp_user']); ?>"
                                    data-status="<?= htmlspecialchars($row['smtp_status']); ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editEmailConfigModal"
                                >
                                    Edit
                                </button>
                                <button 
                                    class="btn btn-success btn-sm test-config-btn" 
                                    data-id="<?= htmlspecialchars($row['id']); ?>" 
                                    data-master-userid="<?= htmlspecialchars($master_userid); ?>" 
                                    data-bs-toggle="modal"
                                    data-bs-target="#testEmailConfigModal"
                                >
                                    Test
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No Email Configurations Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Email Configuration Modal -->
<div class="modal fade" id="addEmailConfigModal" tabindex="-1" aria-labelledby="addEmailConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="addEmailConfigForm" method="POST">
            <input type="hidden" name="action" value="add_email_config">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEmailConfigModalLabel">Add New Email Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="smtp_host" class="form-label">SMTP Host</label>
                        <input type="text" id="smtp_host" name="smtp_host" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_port" class="form-label">SMTP Port</label>
                        <input type="number" id="smtp_port" name="smtp_port" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_user" class="form-label">SMTP User</label>
                        <input type="email" id="smtp_user" name="smtp_user" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_password" class="form-label">SMTP Password</label>
                        <input type="password" id="smtp_password" name="smtp_password" class="form-control" required>
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

<!-- Edit Email Configuration Modal -->
<div class="modal fade" id="editEmailConfigModal" tabindex="-1" aria-labelledby="editEmailConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editEmailConfigForm">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmailConfigModalLabel">Edit Email Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_smtp_host" class="form-label">SMTP Host</label>
                        <input type="text" id="edit_smtp_host" name="smtp_host" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_smtp_port" class="form-label">SMTP Port</label>
                        <input type="number" id="edit_smtp_port" name="smtp_port" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_smtp_user" class="form-label">SMTP User</label>
                        <input type="email" id="edit_smtp_user" name="smtp_user" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_smtp_password" class="form-label">SMTP Password</label>
                        <input type="password" id="edit_smtp_password" name="smtp_password" class="form-control">
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


<!-- Test Email Configuration Modal -->
<div class="modal fade" id="testEmailConfigModal" tabindex="-1" aria-labelledby="testEmailConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="testEmailConfigForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testEmailConfigModalLabel">Test Email Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="test_email" class="form-label">Recipient Email Address</label>
                        <input type="email" id="test_email" name="test_email" class="form-control" required>
                    </div>
                    <input type="hidden" id="test_master_userid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Test Email</button>
                </div>
            </div>
        </form>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        let currentMasterUserId = '';

        document.querySelectorAll('.edit-config-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.getElementById('edit_id').value = button.dataset.id;
                document.getElementById('edit_smtp_host').value = button.dataset.host;
                document.getElementById('edit_smtp_port').value = button.dataset.port;
                document.getElementById('edit_smtp_user').value = button.dataset.user;
                document.getElementById('edit_smtp_password').value = ''; // Leave blank for security
            });
        });

        document.getElementById('editEmailConfigForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = {
                id: document.getElementById('edit_id').value,
                smtp_host: document.getElementById('edit_smtp_host').value,
                smtp_port: document.getElementById('edit_smtp_port').value,
                smtp_user: document.getElementById('edit_smtp_user').value,
                smtp_password: document.getElementById('edit_smtp_password').value || null,
            };

            fetch('edit_email_configuration.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData),
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('An unexpected error occurred.');
            });
        });

    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Set master_user_id in the test email modal
        document.querySelectorAll('.test-config-btn').forEach(button => {
            button.addEventListener('click', () => {
                const masterUserId = button.getAttribute('data-master-userid');
                document.getElementById('test_master_userid').value = masterUserId;
            });
        });

        // Handle the Test Email Form submission
        document.getElementById('testEmailConfigForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const email = document.getElementById('test_email').value;
            const masterUserId = document.getElementById('test_master_userid').value;
            const submitButton = e.target.querySelector('button[type="submit"]');

            // Disable the button and show "Sending..."
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';

            fetch('test_email_configuration.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, master_userid: masterUserId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred.');
            })
            .finally(() => {
                // Re-enable the button and reset the text
                submitButton.disabled = false;
                submitButton.textContent = 'Send Test Email';
            });
        });
    });
</script>
<!--

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Set master_user_id in the test email modal
        document.querySelectorAll('.test-config-btn').forEach(button => {
            button.addEventListener('click', () => {
                const masterUserId = button.getAttribute('data-master-userid');
                document.getElementById('test_master_userid').value = masterUserId;
            });
        });

        // Handle the Test Email Form submission
        document.getElementById('testEmailConfigForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const email = document.getElementById('test_email').value;
            const masterUserId = document.getElementById('test_master_userid').value;

            fetch('test_email_configuration.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, master_userid: masterUserId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred.');
            });
        });
    });
</script>

-->

</body>
</html>
