<?php
// Include database connection
require_once '../database/db_connection.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

// Retrieve query parameters
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!$id || empty($token)) {
    die("Invalid request. Missing ID or Token.");
}



// Fetch record details from the database
$query = "SELECT 
            m.internal_id, 
            a.account_name, 
            u.name AS user_name, 
            c.main_cause AS cause, 
            m.requirement, 
            ms.status AS marketing_status, 
            m.status, 
            m.m_date,
            cont.name AS contact_name,
            cont.mobile1 AS contact_mobile1,
            cont.mobile2 AS contact_mobile2,
            cont.email AS contact_email
          FROM master_marketing m
          INNER JOIN account a ON m.account_id = a.id
          INNER JOIN master_users u ON m.user_id = u.id
          INNER JOIN master_tickets_main_causes c ON m.main_cause_id = c.id
          INNER JOIN master_marketing_status ms ON m.marketing_id_status = ms.id
          LEFT JOIN contacts cont ON m.contact_id = cont.id
          WHERE m.id = ? AND m.token = ?";


$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query Preparation Failed: " . $conn->error);
}

$stmt->bind_param("is", $id, $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No record found for the given ID and Token.");
}

$record = $result->fetch_assoc();
$stmt->close();


// Fetch follow-up records for the current marketing ID
$query_followups = "
    SELECT 
        f.id,
        f.progress_statement, 
        f.progress_date,
        s.status AS current_marketing_status, 
        f.future_followup_required, 
        f.followup_datetime 
    FROM master_marketing_followups f
    INNER JOIN master_marketing_status s 
        ON f.current_marketing_status = s.id
    WHERE f.marketing_id = ? ORDER BY progress_date desc";

$stmt_followups = $conn->prepare($query_followups);
if ($stmt_followups) {
    $stmt_followups->bind_param("i", $id); // Use $id as the marketing_id
    $stmt_followups->execute();
    $result_followups = $stmt_followups->get_result();
    $followups = [];
    while ($row = $result_followups->fetch_assoc()) {
        $followups[] = $row;
    }
    $stmt_followups->close();
} else {
    die("Error fetching follow-ups: " . $conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing Operations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .header-title {
            font-size: 1.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .info-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h5 class="text-center ">Marketing Operations</h5>
    <h1 class="text-center mb-4 header-title"><?= htmlspecialchars($record['account_name']); ?></h1>

    <!-- Operation Buttons -->
    <div class="text-center mb-4 action-buttons">
        <a href="email_report.php?id=<?= urlencode($id); ?>&token=<?= urlencode($token); ?>" class="btn btn-info">Email Report</a>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <!-- Record Information -->
    <div class="card mb-4 info-card">
        <div class="card-header bg-primary text-white">Marketing Record Details</div>
        <div class="card-body">
            <div class="row">
                <!-- General Details -->
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th>Internal ID:</th>
                            <td><?= htmlspecialchars($record['internal_id']); ?></td>
                        </tr>
                        <tr>
                            <th>Date:</th>
                            <td><?= htmlspecialchars($record['m_date']); ?></td>
                        </tr>

                        
                        <tr>
                            <th>Main Cause:</th>
                            <td><?= htmlspecialchars($record['cause']); ?></td>
                        </tr>
                        <tr>
                            <th>Requirement:</th>
                            <td><?= htmlspecialchars($record['requirement']); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Additional Details -->
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                    <tr>
                            <th>User Name:</th>
                            <td><?= htmlspecialchars($record['user_name']); ?></td>
                        </tr>
                        
                        <tr>
                            <th>Marketing Status:</th>
                            <td><?= htmlspecialchars($record['marketing_status']); ?></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td><?= $record['status'] == 1 ? 'Active' : 'Deactive'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
 <!-- Contact Details -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">Contact Details</div>
        <div class="card-body">
            <table class="table table-sm table-bordered">
                <tr>
                    <th>Contact Name:</th>
                    <td><?= htmlspecialchars($record['contact_name'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Mobile 1:</th>
                    <td><?= htmlspecialchars($record['contact_mobile1'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Mobile 2:</th>
                    <td><?= htmlspecialchars($record['contact_mobile2'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td><?= htmlspecialchars($record['contact_email'] ?? 'N/A'); ?></td>
                </tr>
            </table>
        </div>
    </div>

<!-- Edit Follow-Up Modal -->
<div class="modal fade" id="editFollowupModal" tabindex="-1" aria-labelledby="editFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editFollowupModalLabel">Edit Follow-Up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editFollowupForm">
                    <!-- Hidden Follow-Up ID -->
                    <input type="hidden" id="edit_followup_id" name="followup_id">

                    <!-- Progress Statement -->
                    <div class="mb-3">
                        <label for="edit_progress_statement" class="form-label">Progress Statement</label>
                        <textarea class="form-control" id="edit_progress_statement" name="progress_statement" rows="3" required></textarea>
                    </div>

                    <!-- Status Dropdown -->
                    <div class="mb-3">
                        <label for="edit_progress_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_progress_status" name="progress_status" required>
                            <option value="">Loading...</option>
                        </select>
                    </div>

                    <!-- Future Follow-Up Checkbox -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_future_followup" name="future_followup" value="1">
                        <label class="form-check-label" for="edit_future_followup">
                            Future Follow-Up Required
                        </label>
                    </div>

                    <!-- Follow-Up Date & Time -->
                    <div id="edit_followup_date_time" style="display: none;">
                        <div class="mb-3">
                            <label for="edit_followup_datetime" class="form-label">Follow-Up Date & Time</label>
                            <input type="datetime-local" class="form-control" id="edit_followup_datetime" name="followup_datetime">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="saveEditFollowup">Save Changes</button>
            </div>
        </div>
    </div>
</div>


 <!-- Quotation Information Card -->
 <div class="card mb-4">
    <div class="card-header bg-info text-black d-flex justify-content-between align-items-center">
        <span>Quotations</span>
        <a href="../quotations/quotation_create.php?internal_id=<?= urlencode($record['internal_id']); ?>&token=<?= urlencode($token); ?>&master_userid=<?= urlencode($_SESSION['master_userid']); ?>" 
           class="btn btn-light btn-sm">
           Create Quotation
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Quotation Reference</th>
                        <th>Quotation Number <br>Date</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Valid Until</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="quotation-table-body">
                    <tr>
                        <td colspan="8" class="text-center">Loading quotation details...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>

    
document.addEventListener('DOMContentLoaded', function () {
    const internalId = <?= json_encode($record['internal_id']); ?>;
    const masterUserId = <?= json_encode($_SESSION['master_userid']); ?>;
    const quotationTableBody = document.getElementById('quotation-table-body');

    fetch(`fetch_quotation_details.php?internal_id=${internalId}&master_user_id=${masterUserId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('API Response:', data);

            if (data.error) {
                quotationTableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">${data.error}</td></tr>`;
                return;
            }

            const quotations = data.quotations;
            if (!quotations || quotations.length === 0) {
                quotationTableBody.innerHTML = `<tr><td colspan="8" class="text-center text-info">${data.message || 'No quotations found.'}</td></tr>`;
                return;
            }

            quotationTableBody.innerHTML = quotations.map((quotation, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${quotation.quotation_reference}</td>
                    <td>${quotation.quotation_number}
                    <br>${quotation.quotation_date}</td>
                    <td>${quotation.company_name}</td>
                    <td>${quotation.status_name}</td>
                    <td>${quotation.quotation_valid_upto_date}</td>
                    
                    <td>
                        <a href="../quotations/quotation_view.php?quotation_id=${quotation.quotation_id}&token=${quotation.quotation_token}" 
                            class="btn btn-sm btn-primary">Show / Print / PDF / Email</a>
                        <a href="../quotations/quotation_edit.php?quotation_id=${quotation.quotation_id}&token=${quotation.quotation_token}" 
                            class="btn btn-sm btn-warning">Make Changes</a>
                    </td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('Error fetching quotation details:', error);
            quotationTableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Error loading quotation details. Please try again later.</td></tr>`;
        });
});

</script>


<!-- Pending Actions with Follow-Up Table -->
<div class="card mb-4">
    <div class="card-header bg-warning text-black d-flex justify-content-between align-items-center">
        <span>Customer Call / Visit and Status Reporting</span>
        <!-- Update Progress Button -->
        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#updateProgressModal">
            Create Progress
        </button>
    </div>

    <div class="table-responsive">
    <table class="table table-bordered table-sm">
        <thead class="table-secondary">
            <tr>
                <th>Date</th>
                <th>Progress Statement</th>
                <th>Status</th>
                <th>Follow-Up Required ?</th>
                <th>Follow-up Date </th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($followups as $followup): ?>
                <tr>
                    <td><?= htmlspecialchars($followup['progress_date']); ?></td>
                    <td><?= htmlspecialchars($followup['progress_statement']); ?></td>
                    <td><?= htmlspecialchars($followup['current_marketing_status']); ?></td>
                    <td>
                        <?= $followup['future_followup_required'] ? 'Yes' : 'No'; ?>
                    </td>
                    <td>
                        <?= !empty($followup['followup_datetime']) 
                            ? htmlspecialchars($followup['followup_datetime']) 
                            : 'N/A'; ?>
                    </td>
                    <td>
                        <button 
                            class="btn btn-sm btn-warning editFollowupBtn"
                            data-bs-toggle="modal"
                            data-bs-target="#editFollowupModal"
                            data-id="<?= htmlspecialchars($followup['id']); ?>"
                            data-progress-statement="<?= htmlspecialchars($followup['progress_statement']); ?>"
                            data-progress-date="<?= htmlspecialchars($followup['progress_date']); ?>"
                            data-status-id="<?= htmlspecialchars($followup['current_marketing_status']); ?>"
                            data-future-followup="<?= htmlspecialchars($followup['future_followup_required']); ?>"
                            data-followup-datetime="<?= htmlspecialchars($followup['followup_datetime']); ?>"
                        >
                            Edit
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>



<!-- New Progress Modal -->
<div class="modal fade" id="updateProgressModal" tabindex="-1" aria-labelledby="updateProgressModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="updateProgressModalLabel">Create Progress Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateProgressForm">
                <div class="modal-body">
                    <!-- Hidden Fields -->
                    <input type="hidden" name="record_id" value="<?= htmlspecialchars($id); ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">

                    <!-- Progress Update Text -->
                    <div class="mb-3">
                        <label for="progress_update" class="form-label">Progress Update</label>
                        <textarea class="form-control" id="progress_update" name="progress_update" rows="4" placeholder="Enter progress details..." required></textarea>
                    </div>

                    <!-- Status Dropdown -->
                    <div class="mb-3">
                        <label for="progress_status" class="form-label">Status</label>
                        <select class="form-select" id="progress_status" name="progress_status" required>
                            <option value="">Loading...</option>
                        </select>
                    </div>

                    <!-- Future Follow-Up Required Checkbox -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="future_followup" name="future_followup" value="1">
                        <label class="form-check-label" for="future_followup">
                            Future Follow-Up Required
                        </label>
                    </div>

                    <!-- Follow-Up Date & Time -->
                    <!-- Follow-Up Date & Time -->
                    <div class="mb-3" id="followup_date_time" style="display: none;">
                        <label for="followup_datetime" class="form-label">Follow-Up Date & Time</label>
                        <input type="datetime-local" class="form-control" id="followup_datetime" name="followup_datetime">
                    </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="saveProgress" class="btn btn-primary">Save Progress</button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
    document.addEventListener('DOMContentLoaded', function () {
    const followupCheckbox = document.getElementById('future_followup');
    const followupDateTime = document.getElementById('followup_date_time');
    const followupDatetimeInput = document.getElementById('followup_datetime');

    // Toggle visibility of DateTime field
    followupCheckbox.addEventListener('change', function () {
        if (this.checked) {
            followupDateTime.style.display = 'block';
        } else {
            followupDateTime.style.display = 'none';
            followupDatetimeInput.value = ''; // Clear the input
        }
    });

    // Save Progress Form Submission
    document.getElementById('saveProgress').addEventListener('click', function () {
        const formData = new FormData(document.getElementById('updateProgressForm'));

        fetch('save_progress.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Progress updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('An unexpected error occurred.');
        });
    });
});





document.addEventListener('DOMContentLoaded', function () {
    // Function to fetch statuses and populate a dropdown
    function loadStatuses(dropdownId) {
        fetch('fetch_statuses.php') // Endpoint to fetch statuses
            .then(response => response.json())
            .then(data => {
                const dropdown = document.getElementById(dropdownId);
                if (dropdown) {
                    dropdown.innerHTML = '<option value="">Select Status</option>';
                    data.forEach(status => {
                        dropdown.innerHTML += `<option value="${status.id}">${status.status}</option>`;
                    });
                }
            })
            .catch(error => {
                console.error(`Error fetching statuses for ${dropdownId}:`, error);
            });
    }

    // Populate both dropdowns if they exist
    loadStatuses('progress_status');       // For 'progress_status'
    loadStatuses('edit_progress_status');  // For 'edit_progress_status'
});


</script>

<script>
    // Function to populate status dropdown
    function loadStatusDropdown(selectedStatusId) {
        fetch('fetch_statuses.php') // Fetch status options from server
            .then(response => response.json())
            .then(data => {
                const statusDropdown = document.getElementById('edit_progress_status');
                statusDropdown.innerHTML = '<option value="">Select Status</option>';
                data.forEach(status => {
                    const isSelected = status.id == selectedStatusId ? 'selected' : '';
                    statusDropdown.innerHTML += `<option value="${status.id}" ${isSelected}>${status.status}</option>`;
                });
            })
            .catch(error => console.error('Error fetching statuses:', error));
    }

    document.addEventListener('DOMContentLoaded', function () {
    const futureFollowupCheckbox = document.getElementById('edit_future_followup');
    const followupDateTimeDiv = document.getElementById('edit_followup_date_time');

    // Toggle visibility of Follow-Up Date & Time
    futureFollowupCheckbox.addEventListener('change', function () {
        followupDateTimeDiv.style.display = this.checked ? 'block' : 'none';
    });

    // Populate modal fields on Edit button click
    document.querySelectorAll('.editFollowupBtn').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('edit_followup_id').value = this.getAttribute('data-id');
            document.getElementById('edit_progress_statement').value = this.getAttribute('data-progress-statement');
            document.getElementById('edit_progress_status').value = this.getAttribute('data-status-id');
            document.getElementById('edit_followup_datetime').value = this.getAttribute('data-followup-datetime') || '';
            document.getElementById('edit_future_followup').checked = this.getAttribute('data-future-followup') === '1';

            // Show or hide follow-up datetime input
            followupDateTimeDiv.style.display = this.getAttribute('data-future-followup') === '1' ? 'block' : 'none';
        });
    });

    // Save Changes
    document.getElementById('saveEditFollowup').addEventListener('click', function () {
        const formData = new FormData(document.getElementById('editFollowupForm'));

        fetch('update_followup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Follow-up updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(err => console.error('Error:', err));
    });
});


</script>


</div>

<!-- Bootstrap 5 Bundle with Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
