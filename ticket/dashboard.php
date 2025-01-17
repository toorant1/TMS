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
// Default date filters
$to_date = date('Y-m-d'); // Today
$from_date = date('Y-m-01', strtotime('-2 months')); // First day of the month, 2 months ago

// Override defaults with user-provided dates
if (!empty($_GET['from_date'])) {
    $from_date = $_GET['from_date'];
}
if (!empty($_GET['to_date'])) {
    $to_date = $_GET['to_date'];
}





// Fetch detailed counts for cards
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

// Prepare the query with dynamic placeholders

$query = "
    SELECT 
        mt.id AS `Ticket ID`, 
        mt.ticket_id AS `Internal Ticket ID`, 
        mt.ticket_token AS `Token`,
        mt.ticket_date AS `Ticket Date`, 
        mt.problem_statement AS `Problem Statement`,
        acc.account_name AS `Account Name`,
        acc.city AS `City`,
        acc.state AS `State`,
        acc.country AS `Country`,
        c.name AS `Contact Person`,
        c.mobile1 AS `Contact Mobile`,
        IFNULL(mtt.ticket_type, 'N/A') AS `Ticket Type`,
        IFNULL(mp.priority, 'N/A') AS `Priority`,
        IFNULL(ms.status_name, 'N/A') AS `Status`,
        IFNULL(mc.main_cause, 'N/A') AS `Main Cause`
    FROM 
        master_tickets mt
    LEFT JOIN 
        master_tickets_types mtt ON mt.ticket_type_id = mtt.id AND mtt.master_user_id = ?
    LEFT JOIN 
        master_tickets_priority mp ON mt.ticket_priority_id = mp.id AND mp.master_user_id = ?
    LEFT JOIN 
        master_tickets_status ms ON mt.ticket_status_id = ms.id AND ms.master_user_id = ?
    LEFT JOIN 
        master_tickets_main_causes mc ON mt.cause_id = mc.id AND mc.master_user_id = ?
    LEFT JOIN 
        account acc ON mt.account_id = acc.id
    LEFT JOIN 
        contacts c ON mt.contact_id = c.id
    WHERE 
        mt.master_user_id = ?
";

// Append dynamic filters for date range and dropdown selections
$params = [$master_userid, $master_userid, $master_userid, $master_userid, $master_userid];
$types = "iiiii";

if (!empty($_GET['from_date'])) {
    $query .= " AND DATE(mt.ticket_date) >= ?";
    $params[] = $_GET['from_date'];
    $types .= "s";
}

if (!empty($_GET['to_date'])) {
    $query .= " AND DATE(mt.ticket_date) <= ?";
    $params[] = $_GET['to_date'];
    $types .= "s";
}


$query .= " ORDER BY mt.ticket_date DESC, mt.id DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

// Bind parameters dynamically
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YZm9/t7COm39pJp2RXC8" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" integrity="sha384-QsQMA9WRy6j5gpPzCblXV7G8IOwaHEdI5tWBp+DhUUMfYpXI+IflX5ftZR3Niw1" crossorigin="anonymous"></script>
    <script src="fetch_tickets.js"></script>
    <script src="ticket_send.js"></script>
    
    
    


    <style>
        table {
            table-layout: fixed;
            /* Ensures that columns take their fixed width */
            width: 100%;
            /* Optional: Ensures the table spans the container */
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            overflow: hidden;
            /* Hides overflowing text */
            text-overflow: ellipsis;
            /* Adds ellipsis to overflowing text */
            white-space: nowrap;
            /* Prevents text wrapping */
        }

        th {
            background-color: #f2f2f2;
        }

        /* Example fixed widths for columns */
        .col-ticket-id {
            width: 12%;
        }

        .col-account {
            width: 25%;
        }

        .col-contact {
            width: 10%;
        }

        .col-ticket-type {
            width: 10%;
        }

        .col-priority {
            width: 10%;
        }

        .col-status {
            width: 10%;
        }

        .col-problem {
            width: 20%;
        }

        .table-responsive {
            position: relative;
            /* Ensures dropdowns are positioned relative to the table */
        }

      
    </style>

</head>

<body>

    <?php include('../headers/header.php'); ?>
    <div class="container mt-5">

        <div class="dashboard-header text-center mb-4">
            <h1 class="text-white fw-bold">
                <i class="bi bi-ticket-detailed-fill"></i> Tickets Dashboard
            </h1>
            <p class="text-light">Resolve Faster, Work Smarter.</p>
        </div>

        <style>
            .dashboard-header {
                background: linear-gradient(360deg, #6a11cb, #2575fc);
                /* Gradient background */
                padding: 15px;
                border-radius: 15px;
                color: white;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            }

            .dashboard-header h1 {
                font-size: 2.5rem;
                margin-bottom: 5px;
            }

            .dashboard-header p {
                font-size: 1.1rem;
            }

         /* Ensure dropdown menu is not clipped by the table */
.table-responsive {
    position: relative; /* Creates a new positioning context for dropdowns */
}

.dropdown-menu {
    position: absolute; /* Ensures the dropdown is positioned outside of its parent */
    z-index: 1050; /* Ensures it appears above other elements */
    left: 0; /* Align with the dropdown button */
    transform: translate(0, 0); /* Prevent any unwanted transformations */
}

/* Optional: Adjust the position if needed */
.dropdown-menu.show {
    top: 100%; /* Place dropdown below the button */
    margin-top: 0.25rem; /* Add space between button and menu */
}


        </style>




        <div class="row mb-4 gx-4 gy-4">
            <!-- Status Card -->
            <div class="col-md-3">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                    <h5 class="card-title mb-0 d-flex align-items-center";>
                        <i class="bi bi-pencil me-2" style="color: #ffffff;"></i> Statuses
                    </h5>

                    </div>
                    <div class="card-body p-3">
                        <ul class="list-group list-group-flush">
                            <?php if (!empty($details['status'])): ?>
                                <?php foreach ($details['status'] as $status): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($status['status_name'] ?? 'Unknown Status'); ?>
                                        <button
                                            class="btn btn-sm btn-outline-primary filter-btn"
                                            data-filter-type="status"
                                            data-filter-value="<?= htmlspecialchars($status['id'] ?? '0'); ?>">
                                            <?= htmlspecialchars($status['count'] ?? '0'); ?>
                                        </button>

                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center">No statuses available</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Ticket Type Card -->
            <div class="col-md-3">
                <div class="card shadow">
                    <div class="card-header bg-success text-white text-center">
                    <h5 class="card-title mb-0 d-flex align-items-center";>
                        <i class="bi bi-flag me-2" style="color: #ffffff;"></i> Ticket Type
                    </h5>

                    </div>
                    <div class="card-body p-3">
                        <ul class="list-group list-group-flush">
                            <?php if (!empty($details['ticket_type'])): ?>
                                <?php foreach ($details['ticket_type'] as $type): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($type['ticket_type'] ?? 'Unknown Ticket Type'); ?>
                                        <button
                                            class="btn btn-sm btn-outline-success filter-btn"
                                            data-filter-type="ticket_type"
                                            data-filter-value="<?= htmlspecialchars($type['id'] ?? '0'); ?>">
                                            <?= htmlspecialchars($type['count'] ?? '0'); ?>
                                        </button>

                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center">No ticket types available</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Priority Card -->
            <div class="col-md-3">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark text-center">
                        <h5 class="card-title mb-0">Priorities</h5>
                    </div>
                    <div class="card-body p-3">
                        <ul class="list-group list-group-flush">
                            <?php if (!empty($details['priority'])): ?>
                                <?php foreach ($details['priority'] as $priority): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($priority['priority'] ?? 'Unknown Priority'); ?>
                                        <button
                                            class="btn btn-sm btn-outline-warning filter-btn"
                                            data-filter-type="priority"
                                            data-filter-value="<?= htmlspecialchars($priority['id'] ?? '0'); ?>">
                                            <?= htmlspecialchars($priority['count'] ?? '0'); ?>
                                        </button>

                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center">No priorities available</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Cause Card -->
            <div class="col-md-3">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white text-center">
                        <h5 class="card-title mb-0">Main Causes</h5>
                    </div>
                    <div class="card-body p-3">
                        <ul class="list-group list-group-flush">
                            <?php if (!empty($details['main_cause'])): ?>
                                <?php foreach ($details['main_cause'] as $cause): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($cause['main_cause'] ?? 'Unknown Cause'); ?>
                                        <button
                                            class="btn btn-sm btn-outline-danger filter-btn"
                                            data-filter-type="main_cause"
                                            data-filter-value="<?= htmlspecialchars($cause['id'] ?? '0'); ?>">
                                            <?= htmlspecialchars($cause['count'] ?? '0'); ?>
                                        </button>

                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center">No main causes available</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>


        <!-- Date Filters and Dropdowns in a Single Row -->
        <form method="GET" class="mb-4">
            <div class="row g-3 align-items-end">
                <!-- From Date -->
                <div class="col-md-2">
                    <label for="from_date" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="from_date" name="from_date" value="<?= htmlspecialchars($from_date); ?>">
                </div>

                <!-- To Date -->
                <div class="col-md-2">
                    <label for="to_date" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="to_date" name="to_date" value="<?= htmlspecialchars($to_date); ?>">
                </div>

                <div class="col-md-8 d-flex justify-content-end">

                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="dashboard.php" class="btn btn-secondary ms-2">Reset</a>

                    <a href="add_ticket.php" class="btn btn-sm btn-outline-success ms-2">Create New Ticket</a>
                </div>


        </form>

        <div class="table-responsive">
            <div id="dynamic-table">
                <!-- Initial Table Render -->
                <table class="table table-bordered table-striped ">
                    <thead class="table-dark">
                        <tr>
                            <th class="col-ticket-id text-center">Ticket ID</th>
                            <th class="col-account text-center">Account Name</th>
                            <th class="col-ticket-type text-center">Ticket Type</th>
                            <th class="col-priority text-center">Priority</th>
                            <th class="col-status text-center">Status</th>
                            <th class="col-problem text-center">Problem Statement</th>
                            <th class="col-problem text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="dynamic-table-body">

                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td hidden><?= htmlspecialchars($row['Ticket ID']); ?></td>
                                    <td>
                                        <a href="ticket_operation.php?ticket_id=<?= urlencode($row['Internal Ticket ID']); ?>&token=<?= urlencode($row['Token']); ?>"
                                            class="stylish-link">
                                            <?= htmlspecialchars($row['Internal Ticket ID']); ?>
                                        </a>

                                        <style>
                                            .stylish-link {
                                                color: #007bff;
                                                /* Default link color */
                                                font-weight: bold;
                                                /* Make the text bold */
                                                text-decoration: none;
                                                /* Remove underline */
                                                transition: color 0.3s ease, border-bottom 0.3 ease;
                                                /* Smooth hover transition */
                                                border-bottom: 2px solid transparent;
                                                /* Add bottom border */
                                            }

                                            .stylish-link:hover {
                                                color: #0056b3;
                                                /* Change color on hover */
                                                border-bottom: 2px solid #0056b3;
                                                /* Add underline effect on hover */
                                                text-decoration: none;
                                                /* Ensure no underline on hover */
                                            }
                                        </style>

                                        <br><?= htmlspecialchars(date('D - d-M-Y', strtotime($row['Ticket Date']))); ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($row['Account Name']); ?> </strong>(<?= htmlspecialchars($row['City']); ?>, <?= htmlspecialchars($row['State']); ?>)<br><?= htmlspecialchars($row['Contact Person']); ?></td>
                                    <td><?= htmlspecialchars($row['Ticket Type']); ?></td>
                                    <td><?= htmlspecialchars($row['Priority']); ?></td>
                                    <td><?= htmlspecialchars($row['Status']); ?>
                                        <br><?= htmlspecialchars(date('D-d-M-y', strtotime($row['Service Date'] ?? 'N/A'))); ?></br> <!-- Service Date -->
                                    <td><?= htmlspecialchars($row['Problem Statement']); ?></td>
                                    <td class="text-center">
                                        <div>
                                            <button class="btn btn-outline-primary btn-sm d-flex align-items-center mb-2 w-100" type="button"
                                                onclick="sendWhatsAppMessage('client', '<?= htmlspecialchars($row['Internal Ticket ID']); ?>')">
                                                <i class="bi bi-whatsapp me-2" style="color: #25D366;"></i> To Client
                                            </button>
                                            <button class="btn btn-outline-primary btn-sm d-flex align-items-center w-100" type="button"
                                                onclick="sendWhatsAppMessage('engineer', '<?= htmlspecialchars($row['Internal Ticket ID']); ?>')">
                                                <i class="bi bi-whatsapp me-2" style="color: #25D366;"></i> To Engineers
                                            </button>
                                        </div>
                                    </td>


                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No Tickets Found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Display Total Count -->
        <div class="text-start mt-3">
            <strong>Total Records: <?= $result->num_rows; ?></strong>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>


</script>

</body>

</html>

<?php
$conn->close();
?>