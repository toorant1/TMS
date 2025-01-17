<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

// Use the session variable
$master_userid = $_SESSION['master_userid'];

// Get the ticket ID and token
$ticket_id = isset($_GET['ticket_id']) ? trim($_GET['ticket_id']) : '';

$ticket_token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($ticket_token)) {
    die("Invalid Ticket ID or Token.");
}
// Fetch engineers list (master_users table)
$query_engineers = "SELECT id, name FROM master_users WHERE master_user_id = ?";
$stmt_engineers = $conn->prepare($query_engineers);
$stmt_engineers->bind_param("i", $master_userid);
$stmt_engineers->execute();
$result_engineers = $stmt_engineers->get_result();
$engineers = [];
while ($row = $result_engineers->fetch_assoc()) {
    $engineers[] = $row;
}
// Fetch Material Types with proper names using INNER JOIN
$query_material_types = "
    SELECT DISTINCT mmt.id, mmt.material_type AS type_name
    FROM master_materials mt
    INNER JOIN master_materials_type mmt ON mt.material_type = mmt.id
    WHERE mt.master_user_id = ? OR mt.master_user_id = 0
";

$stmt_material_types = $conn->prepare($query_material_types);

if (!$stmt_material_types) {
    die("SQL Error: " . $conn->error);
}

// Bind the parameter and execute the query
$stmt_material_types->bind_param("i", $master_userid);
$stmt_material_types->execute();
$result_material_types = $stmt_material_types->get_result();

// Fetch results into an array
$material_types = [];
while ($row = $result_material_types->fetch_assoc()) {
    $material_types[] = $row;
}


// Fetch ticket statuses (master_tickets_status table)
$query_statuses = "SELECT id, status_name FROM master_tickets_status WHERE master_user_id = ? or master_user_id = 0";
$stmt_statuses = $conn->prepare($query_statuses);
$stmt_statuses->bind_param("i", $master_userid);
$stmt_statuses->execute();
$result_statuses = $stmt_statuses->get_result();
$statuses = [];
while ($row = $result_statuses->fetch_assoc()) {
    $statuses[] = $row;
}
// Query to validate and fetch ticket details with master_user_id and token condition
$query = "
   
    SELECT 
        mt.id as 'id',
        mt.ticket_id AS `Ticket ID`,
        DATE(mt.ticket_date) AS `Ticket Date`,
        IFNULL(mtt.ticket_type, 'Unknown') AS `Ticket Type`,
        IFNULL(mp.priority, 'Unknown') AS `Priority`,
        IFNULL(ms.status_name, 'Unknown') AS `Status`,
        IFNULL(mmc.main_cause, 'Not Provided') AS `Cause`,
        acc.account_name AS `Account Name`,
        acc.address AS `Account Address`,
        acc.city AS `City`,
        acc.state AS `State`,
        acc.pincode AS `Pincode`,
        acc.country AS `Country`,
        c.name AS `Contact Person`,
        c.mobile1 AS `Mobile 1`,
        c.mobile2 AS `Mobile 2`,
        c.email AS `Email`,
        mt.problem_statement AS `Problem Statement`
    FROM master_tickets mt
    LEFT JOIN master_tickets_types mtt ON mt.ticket_type_id = mtt.id
    LEFT JOIN master_tickets_priority mp ON mt.ticket_priority_id = mp.id
    LEFT JOIN master_tickets_status ms ON mt.ticket_status_id = ms.id
    LEFT JOIN master_tickets_main_causes mmc ON mt.cause_id = mmc.id
    LEFT JOIN account acc ON mt.account_id = acc.id
    LEFT JOIN contacts c ON mt.contact_id = c.id
    WHERE mt.ticket_id = ? AND mt.ticket_token = ? AND mt.master_user_id = ?
";


$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("SQL Prepare Error: " . $conn->error);
}

// Bind the parameters for ticket_id, ticket_token, and master_user_id
$stmt->bind_param("isi", $ticket_id, $ticket_token, $master_userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Ticket not found.");
}

$ticket = $result->fetch_assoc();

// Fetch materials from the database
$query_materials = "SELECT id, name FROM master_materials WHERE master_user_id = ? OR master_user_id = 0";
$stmt_materials = $conn->prepare($query_materials);

if (!$stmt_materials) {
    die("SQL Error: " . $conn->error);
}

$stmt_materials->bind_param("i", $master_userid);
$stmt_materials->execute();
$result_materials = $stmt_materials->get_result();
$materials = [];
while ($row = $result_materials->fetch_assoc()) {
    $materials[] = $row;
}

// Fetch previous services for the given ticket ID
$query_services = "
    SELECT 
        mts.id,
        mts.service_date,
        mts.entry_date,
        mts.remark_internal,
        mts.remark_external,
        mu.name AS engineer_name,
        mts.token,
        mts.ticket_status AS status_id,
        ms.status_name AS status_name
    FROM master_tickets_services mts
    LEFT JOIN master_users mu ON mts.engineer_id = mu.id
    LEFT JOIN master_tickets_status ms ON mts.ticket_status = ms.id
    WHERE mts.ticket_id = ? AND mts.master_user_id = ?
    ORDER BY mts.service_date DESC
";

$k = $ticket['id'];
$stmt_services = $conn->prepare($query_services);
$stmt_services->bind_param("ii", $k, $master_userid);
$stmt_services->execute();
$result_services = $stmt_services->get_result();

$services = [];
while ($row = $result_services->fetch_assoc()) {
    $services[] = $row;
}


// Generate the default placeholder image with the first letter of the contact person
$contactInitial = strtoupper(substr($ticket['Contact Person'], 0, 1));
$defaultImage = "https://via.placeholder.com/150/007bff/ffffff?text=" . urlencode($contactInitial);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Operation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="material_movement_add_mat.js"></script>
    

    <style>
        .image-box {
            width: 150px;
            height: 150px;
            border: 2px solid #007bff;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            margin: auto;
        }

        .image-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .center-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4"><?= htmlspecialchars($ticket['Account Name']); ?></h1>
        <h5 class="text-center mb-4">
            <?= htmlspecialchars($ticket['Account Address']); ?>,
            <?= htmlspecialchars($ticket['City']); ?>, <?= htmlspecialchars($ticket['State']); ?> -
            <?= htmlspecialchars($ticket['Pincode']); ?>, <?= htmlspecialchars($ticket['Country']); ?>
        </h5>

        <!-- Buttons -->
        <div class="center-buttons mb-4">


            <a href="material_challan.php?ticket_id=<?= urlencode($ticket['Ticket ID']); ?>&token=<?= urlencode($ticket_token); ?>" class="btn btn-success">Material Challan</a>
            <a href="rgp.php?ticket_id=<?= urlencode($ticket['Ticket ID']); ?>&token=<?= urlencode($ticket_token); ?>" class="btn btn-warning">RGP</a>
            <a href="email_report.php?ticket_id=<?= urlencode($ticket['Ticket ID']); ?>&token=<?= urlencode($ticket_token); ?>" class="btn btn-info">Report Email to Client</a>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <!-- Ticket Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Ticket Basic Details
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 d-flex flex-column align-items-center">
                        <div class="image-box mb-2">
                            <img src="<?= $defaultImage; ?>" alt="Placeholder Image">
                        </div>
                        <p class="text-center"><strong><?= htmlspecialchars($ticket['Contact Person']); ?></strong></p>
                    </div>
                    <div class="col-md-9">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th>Ticket Type</th>
                                    <td><?= htmlspecialchars($ticket['Ticket Type']); ?></td>
                                </tr>
                                <tr>
                                    <th>Priority</th>
                                    <td><?= htmlspecialchars($ticket['Priority']); ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td><?= htmlspecialchars($ticket['Status']); ?></td>
                                </tr>
                                <tr>
                                    <th>Main Problem</th>
                                    <td><?= htmlspecialchars($ticket['Cause']); ?></td>
                                </tr>
                                <tr>
                                    <th>Problem Statement</th>
                                    <td><?= htmlspecialchars($ticket['Problem Statement']); ?></td>
                                </tr>
                                <tr>
                                    <th>Contact Person</th>
                                    <td><?= htmlspecialchars($ticket['Contact Person']); ?> - <?= htmlspecialchars($ticket['Mobile 1']); ?> / <?= htmlspecialchars($ticket['Mobile 2']); ?> - <?= htmlspecialchars($ticket['Email']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Cards -->
        <!-- Card with Service Entry button -->
        <div class="card mb-4 position-relative">
            <div class="card-header bg-success text-white d-flex justify-content-between">
                <span>Service History</span>
                <!-- Button to trigger the modal -->
                <button type="button" class="btn btn-primary btn-sm" style="position: absolute; top: 10px; right: 10px;"
                    data-bs-toggle="modal" data-bs-target="#serviceEntryModal">
                    Service Entry
                </button>
            </div>
            <div class="card-body">
                <p>Details about the previous services provided for this ticket/account:</p>
                <?php if (count($services) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-primary">
                                <tr>
                                    <th>Service Date</th>
                                    <th>Entry Date</th>
                                    <th>Engineer Name</th>
                                    <th>Internal Remark</th>
                                    <th>External Remark</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td hidden><?= htmlspecialchars($service['id']); ?></td>
                                        <td><?= htmlspecialchars($service['service_date']); ?></td>
                                        <td><?= htmlspecialchars($service['entry_date']); ?></td>
                                        <td><?= htmlspecialchars($service['engineer_name']); ?></td>
                                        <td><?= htmlspecialchars($service['remark_internal']); ?></td>
                                        <td><?= htmlspecialchars($service['remark_external']); ?></td>
                                        <td><?= htmlspecialchars($service['status_name']); ?></td>
                                        <td>
                                            <button
                                                class="btn btn-warning btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editServiceModal"
                                                data-id="<?= htmlspecialchars($service['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-ticket="<?= htmlspecialchars($ticket['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-date="<?= htmlspecialchars($service['service_date'], ENT_QUOTES, 'UTF-8'); ?>"

                                                data-remark-internal="<?= htmlspecialchars($service['remark_internal'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-remark-external="<?= htmlspecialchars($service['remark_external'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-status="<?= htmlspecialchars($service['status_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No service history available for this ticket.</p>
                <?php endif; ?>
            </div>


        </div>

        <div class="card mb-4">
            <div class="card-header bg-info text-white d-flex justify-content-between">
                Material Usage
                <!-- Material Issue Button -->
                <button type="button" class="btn btn-primary btn-sm" style="position: absolute; top: 10px; right: 10px;"
                    data-bs-toggle="modal" data-bs-target="#materialIssueModal">
                    Material Issue
                </button>
            </div>
            <div class="card mb-4">
                <div class="card-body">
                    <!-- Dropdown to Add New Material -->
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-md-4">
                            <label for="material_name" class="form-label">Material</label>
                            <select class="form-select select2" id="material_name" name="material_name">
                                <option value="">Select Material</option>
                                <?php
                                // Fetch materials for the dropdown
                                $materialDropdownQuery = "
                            SELECT id, name 
                            FROM master_materials 
                            WHERE master_user_id = ? AND status = 1 
                            ORDER BY name ASC";
                                $stmt = $conn->prepare($materialDropdownQuery);
                                if ($stmt) {
                                    $stmt->bind_param("i", $master_userid);
                                    $stmt->execute();
                                    $materialDropdownResult = $stmt->get_result();
                                    while ($material = $materialDropdownResult->fetch_assoc()) {
                                        echo "<option value='{$material['id']}'>{$material['name']}</option>";
                                    }
                                    $stmt->close();
                                }
                                ?>
                            </select>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                // Initialize Select2
                                $('.select2').select2({
                                    placeholder: "Select Material", // Add placeholder
                                    allowClear: true // Allow clearing the selection
                                });
                            });
                        </script>

                        <div class="col-md-2">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" min="0" value="0">
                        </div>
                        <div class="col-md-1">
                            <label for="unit" class="form-label">Unit </label>
                            <input type="text" class="form-control" id="unit" readonly>
                        </div>
                        <div class="col-md-3">
                            <label for="remark" class="form-label">Remark</label>
                            <input type="text" class="form-control" id="remark">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-success w-100" id="addMaterialButton">Add Material</button>

                        </div>
                    </div>

                    <div class="card mt-4">
                        <table class="table table-bordered" id="material-history-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Entry Date</th>
                                    <th>Material Name</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>

                                    <th>Remark</th>
                                    <th>Movement Type</th>
                                    <th>Reference</th>

                                    <th>Action</th>

                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be dynamically loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                    Pending Actions
                </div>
                <div class="card-body">
                    <p>List of actions pending for the ticket.</p>
                    <ul>
                        <li>Send follow-up email to client.</li>
                        <li>Prepare RGP document.</li>
                        <li>Schedule next maintenance visit.</li>
                    </ul>
                </div>
            </div>
        </div>


        <!-- Service Entry Modal -->
        <div class="modal fade" id="serviceEntryModal" tabindex="-1" aria-labelledby="serviceEntryModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="serviceEntryModalLabel">Service Entry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="serviceEntryForm">
                        <div class="modal-body">
                            <!-- Hidden Fields -->
                            <input hidden name="ticket_id" value="<?= htmlspecialchars($ticket['id']); ?>">
                            <input hidden name="token" value="<?= htmlspecialchars($ticket_token); ?>">
                            <input hidden name="database_id" value="<?= htmlspecialchars($ticket['id']); ?>">

                            <!-- Service Date -->
                            <div class="mb-3">
                                <label for="service_date" class="form-label">Service Date</label>
                                <input type="date" class="form-control" id="service_date" name="service_date" value="<?= date('Y-m-d'); ?>" required>
                            </div>

                            <!-- Engineer Name Dropdown -->
                            <div class="mb-3">
                                <label for="engineer_name" class="form-label">Engineer Name</label>
                                <select class="form-select" id="engineer_name" name="engineer_name" required>
                                    <option value="">Select Engineer</option>
                                    <?php foreach ($engineers as $engineer): ?>
                                        <option value="<?= htmlspecialchars($engineer['id']); ?>"><?= htmlspecialchars($engineer['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Internal Remark -->
                            <div class="mb-3">
                                <label for="internal_remark" class="form-label">Internal Remark</label>
                                <textarea class="form-control" id="internal_remark" name="internal_remark" rows="2" required></textarea>
                            </div>

                            <!-- External Remark -->
                            <div class="mb-3">
                                <label for="external_remark" class="form-label">External Remark</label>
                                <textarea class="form-control" id="external_remark" name="external_remark" rows="2" required></textarea>
                            </div>

                            <!-- Ticket Status Dropdown -->
                            <div class="mb-3">
                                <label for="ticket_status" class="form-label">Ticket Status</label>
                                <select class="form-select" id="ticket_status" name="ticket_status" required>
                                    <option value="">Select Status</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= htmlspecialchars($status['id']); ?>"><?= htmlspecialchars($status['status_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" id="saveServiceEntry" class="btn btn-primary">Save Entry</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title" id="editServiceModalLabel">Edit Service Entry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editServiceForm">
                        <div class="modal-body">
                            <!-- Hidden Fields  First is line id of services and second is ticket id -->
                            <input hidden name="t_l_id" value="<?= htmlspecialchars($service['id']); ?>">
                            <input hidden name="t_m_id" value="<?= htmlspecialchars($ticket['id']); ?>">

                            <!-- Service Date -->
                            <div class="mb-3">
                                <label for="edit_service_date" class="form-label">Service Date</label>
                                <input type="date" class="form-control" id="edit_service_date" name="service_date" required>
                            </div>

                            <!-- Engineer Name -->
                            <div class="mb-3">
                                <label for="edit_engineer_name" class="form-label">Engineer Name</label>
                                <select class="form-select" id="edit_engineer_name" name="engineer_name" required>
                                    <option value="">Select Engineer</option>
                                    <?php foreach ($engineers as $engineer): ?>
                                        <option value="<?= htmlspecialchars($engineer['id']); ?>"><?= htmlspecialchars($engineer['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Internal Remark -->
                            <div class="mb-3">
                                <label for="edit_internal_remark" class="form-label">Internal Remark</label>
                                <textarea class="form-control" id="edit_internal_remark" name="remark_internal" rows="2" required></textarea>
                            </div>

                            <!-- External Remark -->
                            <div class="mb-3">
                                <label for="edit_external_remark" class="form-label">External Remark</label>
                                <textarea class="form-control" id="edit_external_remark" name="remark_external" rows="2" required></textarea>
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                                <label for="edit_ticket_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_ticket_status" name="ticket_status" required>
                                    <option value="">Select Status</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= htmlspecialchars($status['id']); ?>"><?= htmlspecialchars($status['status_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" id="saveEditService" class="btn btn-warning">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>




        <!-- Include Select2 -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>




        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
$conn->close();
?>


<script>
    // Set default date to today when the modal is shown
    document.addEventListener('DOMContentLoaded', function() {
        const editServiceModal = document.getElementById('editServiceModal');
        const serviceDateInput = document.getElementById('edit_service_date');

        editServiceModal.addEventListener('show.bs.modal', function() {
            const today = new Date().toISOString().split('T')[0]; // Format the date as YYYY-MM-DD
            serviceDateInput.value = today;
        });
    });
</script>

<script>
    function loadServiceData(service) {
        document.getElementById('edit_service_id').value = service.id;
        document.getElementById('edit_service_date').value = service.service_date;
        document.getElementById('edit_engineer_name').value = service.engineer_id;
        document.getElementById('edit_internal_remark').value = service.remark_internal;
        document.getElementById('edit_external_remark').value = service.remark_external;
        document.getElementById('edit_ticket_status').value = service.ticket_status;
    }

    // Handle Save Changes
    document.getElementById('saveEditService').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('editServiceForm'));

        fetch('update_service_entry.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Service entry updated successfully!');
                    location.reload(); // Reload to reflect changes
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred.');
            });
    });
</script>

<script>
    document.getElementById('saveServiceEntry').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('serviceEntryForm'));

        fetch('save_service_entry.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Service entry saved successfully!');
                    location.reload(); // Reload the page or update the UI dynamically
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred.');
            });
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editServiceModal = document.getElementById('editServiceModal');

        // Listen for when the modal is shown
        editServiceModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // Button that triggered the modal

            // Extract data from data-* attributes
            const id = button.getAttribute('data-id');
            const ticket = button.getAttribute('data-ticket');
            const date = button.getAttribute('data-date');
            const engineer = button.getAttribute('data-engineer');
            const remarkInternal = button.getAttribute('data-remark-internal');
            const remarkExternal = button.getAttribute('data-remark-external');
            const status = button.getAttribute('data-status');

            // Populate the modal fields
            editServiceModal.querySelector('[name="t_l_id"]').value = id;
            editServiceModal.querySelector('[name="t_m_id"]').value = ticket;
            editServiceModal.querySelector('[name="service_date"]').value = date;
            editServiceModal.querySelector('[name="engineer_name"]').value = engineer;
            editServiceModal.querySelector('[name="remark_internal"]').value = remarkInternal;
            editServiceModal.querySelector('[name="remark_external"]').value = remarkExternal;
            editServiceModal.querySelector('[name="ticket_status"]').value = status;
        });
    });
</script>