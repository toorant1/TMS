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

// Initialize details arrays
$details = [
    'company' => [],
    'customer' => [],
    'material' => [],
    'vehicle' => []
];

try {
    // Fetch Company Names
    $company_query = "SELECT mc.company_name, COUNT(mqd.id) AS count
                      FROM master_quarry_dispatch_data mqd
                      JOIN master_company mc ON mqd.company_name_id = mc.id
                      WHERE mqd.master_user_id = ?
                      GROUP BY mc.company_name";
    $stmt = $conn->prepare($company_query);
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $details['company'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch Customer Names
    $customer_query = "SELECT acc.account_name AS customer_name, COUNT(mqd.id) AS count
                       FROM master_quarry_dispatch_data mqd
                       JOIN account acc ON mqd.customer_name_id = acc.id
                       WHERE mqd.master_user_id = ?
                       GROUP BY acc.account_name";
    $stmt = $conn->prepare($customer_query);
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $details['customer'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch Material Names
    $material_query = "SELECT mm.name AS material_name, COUNT(mqd.id) AS count
                       FROM master_quarry_dispatch_data mqd
                       JOIN master_materials mm ON mqd.material_id = mm.id
                       WHERE mqd.master_user_id = ?
                       GROUP BY mm.name";
    $stmt = $conn->prepare($material_query);
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $details['material'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch Vehicles
    $vehicle_query = "SELECT vehicle, COUNT(id) AS count
                      FROM master_quarry_dispatch_data
                      WHERE master_user_id = ?
                      GROUP BY vehicle";
    $stmt = $conn->prepare($vehicle_query);
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $details['vehicle'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quarry Dispatch Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .card-body {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <?php include('../headers/header.php'); ?>

    <div class="container mt-2">
        <?php include('../headers/header_buttons.php'); ?>
        <div class="dashboard-header text-center mb-2" style="background: linear-gradient(90deg, #4caf50, #2196f3); color: white; padding: 20px; border-radius: 10px;">
            <h1 class="fw-bold"> Quarry Dispatch Dashboard </h1>
            <p>Manage dispatch entries efficiently.</p>
        </div>

       <!-- Filter Cards -->
<!-- Filter Cards -->
<div class="row mb-1">
    <!-- Company Card -->
    <div class="col-md-3">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h5 class="mb-0">Company</h5>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($details['company'])): ?>
                    <div class="btn-group-vertical w-100">
                        <?php foreach ($details['company'] as $item): ?>
                            <input type="radio" class="btn-check" name="company" id="company-<?php echo htmlspecialchars($item['company_name']); ?>"
                                value="<?php echo htmlspecialchars($item['company_name']); ?>" data-filter-type="company" autocomplete="off">
                            <label class="btn btn-outline-primary w-100 mb-2 d-flex justify-content-between align-items-center" for="company-<?php echo htmlspecialchars($item['company_name']); ?>">
                                <span><?php echo htmlspecialchars($item['company_name']); ?></span>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($item['count']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">No data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Customer Card -->
    <div class="col-md-3">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h5 class="mb-0">Customer</h5>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($details['customer'])): ?>
                    <div class="btn-group-vertical w-100">
                        <?php foreach ($details['customer'] as $item): ?>
                            <input type="radio" class="btn-check" name="customer" id="customer-<?php echo htmlspecialchars($item['customer_name']); ?>"
                                value="<?php echo htmlspecialchars($item['customer_name']); ?>" data-filter-type="customer" autocomplete="off">
                            <label class="btn btn-outline-primary w-100 mb-2 d-flex justify-content-between align-items-center" for="customer-<?php echo htmlspecialchars($item['customer_name']); ?>">
                                <span><?php echo htmlspecialchars($item['customer_name']); ?></span>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($item['count']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">No data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Material Card -->
    <div class="col-md-3">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h5 class="mb-0">Material</h5>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($details['material'])): ?>
                    <div class="btn-group-vertical w-100">
                        <?php foreach ($details['material'] as $item): ?>
                            <input type="radio" class="btn-check" name="material" id="material-<?php echo htmlspecialchars($item['material_name']); ?>"
                                value="<?php echo htmlspecialchars($item['material_name']); ?>" data-filter-type="material" autocomplete="off">
                            <label class="btn btn-outline-primary w-100 mb-2 d-flex justify-content-between align-items-center" for="material-<?php echo htmlspecialchars($item['material_name']); ?>">
                                <span><?php echo htmlspecialchars($item['material_name']); ?></span>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($item['count']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">No data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Vehicle Card -->
    <div class="col-md-3">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h5 class="mb-0">Vehicle</h5>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($details['vehicle'])): ?>
                    <div class="btn-group-vertical w-100">
                        <?php foreach ($details['vehicle'] as $item): ?>
                            <input type="radio" class="btn-check" name="vehicle" id="vehicle-<?php echo htmlspecialchars($item['vehicle']); ?>"
                                value="<?php echo htmlspecialchars($item['vehicle']); ?>" data-filter-type="vehicle" autocomplete="off">
                            <label class="btn btn-outline-primary w-100 mb-2 d-flex justify-content-between align-items-center" for="vehicle-<?php echo htmlspecialchars($item['vehicle']); ?>">
                                <span><?php echo htmlspecialchars($item['vehicle']); ?></span>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($item['count']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">No data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


        <!-- Filters -->
        <form id="filterForm" class="mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="accountSearch" class="form-label fw-bold">Search Dispatch Data:</label>
                    <input type="text" id="accountSearch" class="form-control" placeholder="Search by company, customer, material, vehicle">
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
                    <a href="add_ticket.php" class="btn btn-md btn-outline-success ms-3">Create New Entry</a>
                </div>
            </div>
        </form>

        <table class="table table-striped">
    </thead>
    <tbody id="dynamic-table-body">
        <!-- Filtered results will be loaded here -->
    </tbody>
</table>

    </div>



    <script>
        $(document).ready(function() {
            $('#applyFilters').click(function(e) {
                e.preventDefault();
                applyFilters();
            });

            $('#accountSearch, #from_date, #to_date').on('blur change', function() {
                applyFilters();
            });

            $('input[type=radio]').on('click', function() {
                applyFilters();
            });

            function applyFilters() {
                let searchValue = $('#accountSearch').val().trim();
                let fromDate = $('#from_date').val();
                let toDate = $('#to_date').val();
                let filters = { search: searchValue, from_date: fromDate, to_date: toDate };

                $('input[type=radio]:checked').each(function() {
                    let type = $(this).data('filter-type');
                    let value = $(this).val();
                    filters[type] = value;
                });

                $.ajax({
                    url: 'fetch_filtered_dispatch.php',
                    type: 'POST',
                    data: { filters: JSON.stringify(filters) },
                    success: function(response) {
                        $('#dynamic-table-body').html(response);
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
