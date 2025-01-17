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
<?php include('../headers/header.php'); ?> <!-- Include the header file here -->
<div class="container mt-5">
    <h1 class="text-center mb-4">Manage Email Configurations</h1>

    <?php if (!$hasData): ?> <!-- Show button only if there is no data -->
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmailConfigModal">Add New Email Configuration</button>
        </div>
    <?php endif; ?>

    <!-- Email Configurations Table -->
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
                                    class="btn btn-success btn-sm test-config-btn" 
                                    data-id="<?= htmlspecialchars($row['id']); ?>" 
                                    data-master-userid="<?= htmlspecialchars($master_userid); ?>" 
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

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1" aria-labelledby="testEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="testEmailForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testEmailModalLabel">Test Email Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="test_email" class="form-label">Recipient Email Address</label>
                        <input type="email" id="test_email" name="test_email" class="form-control" required>
                    </div>
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

        // When "Test" button is clicked
        document.querySelectorAll('.test-config-btn').forEach(button => {
            button.addEventListener('click', () => {
                currentMasterUserId = button.getAttribute('data-master-userid');
                const testModal = new bootstrap.Modal(document.getElementById('testEmailModal'));
                testModal.show();
            });
        });

        // Handle Test Email Form Submission
        document.getElementById('testEmailForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const email = document.getElementById('test_email').value;
            const sendButton = e.target.querySelector('button[type="submit"]');

            if (!email) {
                alert('Please enter a valid email address.');
                return;
            }

            // Disable the "Send Test Email" button
            sendButton.disabled = true;
            sendButton.textContent = 'Sending...'; // Optional: show a loading message

            fetch('test_email_configuration.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ master_userid: currentMasterUserId, email: email }),
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('An unexpected error occurred. Please check the console for details.');
            })
            .finally(() => {
                // Re-enable the "Send Test Email" button
                sendButton.disabled = false;
                sendButton.textContent = 'Send Test Email'; // Restore original text
            });
        });
    });
</script>

</body>
</html>
