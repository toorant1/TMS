<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Initialize date filter variables
$to_date = date('Y-m-d'); // Today
$from_date = date('Y-m-01', strtotime('-2 months')); // First day of the month, 2 months ago

// Override defaults with user-provided dates
if (!empty($_GET['from_date'])) {
    $from_date = $_GET['from_date'];
}
if (!empty($_GET['to_date'])) {
    $to_date = $_GET['to_date'];
}

// Fetch filter details for Status, Ticket Type, Priority, and Main Cause
$detailedCardsQuery = [
    'status' => "SELECT ms.id, ms.status_name, COUNT(mt.id) AS count 
                 FROM master_tickets mt 
                 LEFT JOIN master_tickets_status ms ON mt.ticket_status_id = ms.id 
                 WHERE mt.master_user_id = ? 
                 GROUP BY ms.id, ms.status_name",
    'ticket_type' => "SELECT mtt.id, mtt.ticket_type, COUNT(mt.id) AS count 
                      FROM master_tickets mt 
                      LEFT JOIN master_tickets_types mtt ON mt.ticket_type_id = mtt.id 
                      WHERE mt.master_user_id = ? 
                      GROUP BY mtt.id, mtt.ticket_type",
    'priority' => "SELECT mp.id, mp.priority, COUNT(mt.id) AS count 
                   FROM master_tickets mt 
                   LEFT JOIN master_tickets_priority mp ON mt.ticket_priority_id = mp.id 
                   WHERE mt.master_user_id = ? 
                   GROUP BY mp.id, mp.priority",
    'main_cause' => "SELECT mc.id, mc.main_cause, COUNT(mt.id) AS count 
                     FROM master_tickets mt 
                     LEFT JOIN master_tickets_main_causes mc ON mt.cause_id = mc.id 
                     WHERE mt.master_user_id = ? 
                     GROUP BY mc.id, mc.main_cause"
];

$details = ['status' => [], 'ticket_type' => [], 'priority' => [], 'main_cause' => []];

foreach ($detailedCardsQuery as $key => $query) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $details[$key][] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .card-body {
            max-height: 500px;
            /* Adjust based on your design */
            overflow-y: auto;
            /* Enable vertical scrolling */
        }
    </style>

</head>

<body>
    <?php include('../headers/header.php'); ?>

    <div class="container mt-2">
        <?php include('../headers/header_buttons.php'); ?>
        <div class="dashboard-header text-center mb-2" style="background: linear-gradient(90deg, #4caf50, #2196f3); color: white; padding: 20px; border-radius: 10px;">
            <h1 class="fw-bold">
                <i class="bi bi-ticket-detailed-fill"></i> Tickets Dashboard
            </h1>
            <p>Work Smarter, Resolve Faster.</p>
        </div>




        <!-- Cards -->
        <div class="row mb-1">
            <?php foreach ($details as $key => $values): ?>
                <div class="col-md-3">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white text-center">
                            <h5 class="mb-0"><?= ucfirst(str_replace('_', ' ', $key)) ?></h5>
                        </div>
                        <div class="card-body p-3" style="max-height: 200px; overflow-y: auto;">
                            <?php if (!empty($values)): ?>
                                <div class="btn-group-vertical w-100">
                                    <?php foreach ($values as $item): ?>
                                        <input type="radio" class="btn-check" name="<?= $key ?>" id="<?= $key . '-' . $item['id'] ?>"
                                            value="<?= $item['id'] ?>" data-filter-type="<?= $key ?>" autocomplete="off">
                                        <label class="btn btn-outline-primary w-100 mb-2 d-flex justify-content-between align-items-center" for="<?= $key . '-' . $item['id'] ?>">
                                            <span><?= htmlspecialchars($item[$key == 'status' ? 'status_name' : $key]) ?></span>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($item['count']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <form id="filterForm" class="mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="accountSearch" class="form-label fw-bold">Search Ticket ID / Account / Contact Person / City:</label>
                    <input type="text" id="accountSearch" class="form-control" placeholder="Enter Ticket ID / Account / Contact Person / City">
                </div>
                <div class="col-md-2">
                    <label for="from_date" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="from_date" value="<?= htmlspecialchars($from_date); ?>">
                </div>
                <div class="col-md-2">
                    <label for="to_date" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="to_date" value="<?= htmlspecialchars($to_date); ?>">
                </div>
                <div class="col-md-4 d-flex justify-content-end">
                    <button id="applyFilters" class="btn btn-primary">Apply Filters</button>
                    <a href="dashboard.php" class="btn btn-secondary ms-2">Reset</a>
                    <a href="add_ticket.php" class="btn btn-md btn-outline-success ms-3">Create New Ticket</a>
                </div>
            </div>
        </form>

        <!-- Tickets Table -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Ticket ID</th>
                        <th>Account Name</th>
                        <th>Ticket Type</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Problem Statement</th>
                    </tr>
                </thead>
                <tbody id="dynamic-table-body">
                    <!-- AJAX-loaded data -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        $(document).ready(function() {

            // Trigger filtering when the Apply Filters button is clicked
            $('#applyFilters').click(function(e) {
                e.preventDefault();
                applyFilters();
            });

            // Automatically apply filters when any input loses focus or a radio button is clicked
            $('#accountSearch, #from_date, #to_date').on('blur change', function() {
                applyFilters();
            });

            // Apply filters when any radio button is clicked
            $('input[type=radio]').on('click', function() {

                applyFilters();
            });

            function applyFilters() {
                let searchValue = $('#accountSearch').val().trim();
                let fromDate = $('#from_date').val();
                let toDate = $('#to_date').val();

                let filters = {
                    account: searchValue, // Search for account name, internal ticket ID, city, or contact person
                    from_date: fromDate,
                    to_date: toDate
                };

                // Get selected radio button values
                $('input[type=radio]:checked').each(function() {
                    let type = $(this).data('filter-type');
                    let value = $(this).val();
                    filters[type] = value;
                });

                $.ajax({
                    url: 'fetch_filtered_tickets.php',
                    type: 'POST',
                    data: {
                        filters: JSON.stringify(filters)
                    },
                    success: function(response) {
                        $('#dynamic-table-body').html(response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching filtered data:', error);
                    }
                });
            }
        });
    </script>
</body>

</html>

<?php
$conn->close();
?>