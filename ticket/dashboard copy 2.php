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
</head>

<body>
    <?php include('../headers/header.php'); ?>

    <div class="container mt-2">
        <?php include('../headers/header_buttons.php'); ?>
        <div class="dashboard-header text-center mb-4">
            <h1 class="text-white fw-bold">
                <i class="bi bi-ticket-detailed-fill"></i> Tickets Dashboard
            </h1>
            <p class="text-light">Resolve Faster, Work Smarter.</p>
        </div>

        <!-- Filters -->
        <form id="filterForm" class="mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="accountSearch" class="form-label fw-bold">Search Account:</label>
                    <input type="text" id="accountSearch" class="form-control" placeholder="Enter account name">
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
                </div>
            </div>
        </form>

        <!-- Cards -->
        <div class="row mb-4">
            <?php foreach ($details as $key => $values): ?>
                <div class="col-md-3">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white text-center">
                            <h5 class="mb-0"><?= ucfirst(str_replace('_', ' ', $key)) ?></h5>
                        </div>
                        <div class="card-body p-3">
                            <?php if (!empty($values)): ?>
                                <div class="btn-group-vertical w-100">
                                    <?php foreach ($values as $item): ?>
                                        <input type="radio" class="btn-check" name="<?= $key ?>" id="<?= $key . '-' . $item['id'] ?>"
                                            value="<?= $item['id'] ?>" data-filter-type="<?= $key ?>" autocomplete="off">
                                        <label class="btn btn-outline-primary w-100 mb-2" for="<?= $key . '-' . $item['id'] ?>">
                                            <?= htmlspecialchars($item[$key == 'status' ? 'status_name' : $key]) ?> (<?= htmlspecialchars($item['count']); ?>)
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
    $(document).ready(function () {
        $('#applyFilters').click(function (e) {
            e.preventDefault();
            updateTable();
        });

        function updateTable() {
            let filters = {
                account: $('#accountSearch').val().trim(),
                from_date: $('#from_date').val(),
                to_date: $('#to_date').val()
            };

            $('input[type=radio]:checked').each(function () {
                let type = $(this).data('filter-type');
                let value = $(this).val();
                filters[type] = value;
            });

            $.ajax({
                url: 'fetch_filtered_tickets.php',
                type: 'POST',
                data: { filters: JSON.stringify(filters) },
                success: function (response) {
                    $('#dynamic-table-body').html(response);
                },
                error: function (xhr, status, error) {
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
