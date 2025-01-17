<?php
require_once '../database/db_connection.php';
session_start();

$message = "";

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    die("Error: User is not logged in.");
}

$master_userid = $_SESSION['master_userid'];

// Function to fetch dropdown data
function fetchDropdownData($conn, $query, $param, &$dataArray) {
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('i', $param);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $dataArray[] = $row;
        }
        $stmt->close();
    }
}

// Fetch dropdown options
$accounts = $users = $causes = $marketing_statuses = [];
fetchDropdownData($conn, "SELECT id, account_name FROM account WHERE master_user_id = ? ORDER BY account_name", $master_userid, $accounts);
fetchDropdownData($conn, "SELECT id, name FROM master_users WHERE master_user_id = ? ORDER BY name", $master_userid, $users);
fetchDropdownData($conn, "SELECT id, main_cause FROM master_tickets_main_causes WHERE master_user_id = ? ORDER BY main_cause", $master_userid, $causes);
fetchDropdownData($conn, "SELECT id, status FROM master_marketing_status WHERE master_user_id = ? ORDER BY status", $master_userid, $marketing_statuses);

// Fetch Marketing Status Data
$marketing_statuses = [];
$stmt = $conn->prepare("SELECT id, status FROM master_marketing_status WHERE master_user_id = ?");
if ($stmt) {
    $stmt->bind_param('i', $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $marketing_statuses[] = $row;
    }
    $stmt->close();
} else {
    die("Error fetching marketing statuses: " . $conn->error);
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Marketing Record</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('../headers/header.php'); ?>

<div class="container mt-5">
    <h2 class="text-center bg-primary text-white p-3 rounded">Add Marketing Record</h2>

    <form id="marketingForm">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="account_id" class="form-label">Select Account</label>
                <select name="account_id" id="account_id" class="form-control" required>
                    <option value="">Select Account</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?= $account['id']; ?>"><?= htmlspecialchars($account['account_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="contact_id" class="form-label">Select Contact</label>
                <select name="contact_id" id="contact_id" class="form-control" required>
                    <option value="">Select Contact</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="user_id" class="form-label">Sales Executive</label>
                <select name="user_id" id="user_id" class="form-control" required>
                    <option value="">Select Sales Person</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id']; ?>"><?= htmlspecialchars($user['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="main_cause_id" class="form-label">Customer Requirement</label>
                <select name="main_cause_id" id="main_cause_id" class="form-control" required>
                    <option value="">Select Requirement</option>
                    <?php foreach ($causes as $cause): ?>
                        <option value="<?= $cause['id']; ?>"><?= htmlspecialchars($cause['main_cause']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label for="requirement" class="form-label">Details</label>
                <textarea name="requirement" id="requirement" class="form-control" rows="3" required></textarea>
            </div>
            <!-- Marketing Status -->
            <div class="col-md-6">
    <label for="marketing_id_status" class="form-label">Marketing Status</label>
    <select name="marketing_id_status" id="marketing_id_status" class="form-control" required>
        <option value="">Select Status</option>
        <?php foreach ($marketing_statuses as $status): ?>
            <option value="<?= htmlspecialchars($status['id']); ?>">
                <?= htmlspecialchars($status['status']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


            <div class="col-md-6">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="1">Active</option>
                    <option value="0">De-active</option>
                </select>
            </div>
        </div>
        <button type="button" id="saveButton" class="btn btn-success mt-4">Save Record</button>
    </form>
</div>

<script>
    document.getElementById('account_id').addEventListener('change', function () {
        const accountId = this.value;
        const contactSelect = document.getElementById('contact_id');
        contactSelect.innerHTML = '<option value="">Select Contact</option>';

        if (accountId) {
            fetch('fetch_contacts.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ account_id: accountId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.contacts.forEach(contact => {
                        const option = document.createElement('option');
                        option.value = contact.id;
                        option.textContent = `${contact.name} - ${contact.mobile1}`;
                        contactSelect.appendChild(option);
                    });
                } else {
                    alert('No contacts found.');
                }
            });
        }
    });

    // Save form data
    document.getElementById('saveButton').addEventListener('click', function () {
            const formData = new FormData(document.getElementById('marketingForm'));

            fetch('save_marketing.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                if (data.success) {
                    alert('Marketing record added successfully!');
                    window.location.href = 'dashboard.php'; // Redirect to the dashboard
                    
                } else {
                    if (data.missing_fields && data.missing_fields.length > 0) {
                        alert('Missing Fields:\n' + data.missing_fields.join('\n'));
                    } else {
                        alert('Error: ' + data.error);
                    }
                }
            })
            .catch(err => {
                console.error('Error occurred:', err);
                alert('An unexpected error occurred.');
            });
        });

</script>



</body>
</html>
