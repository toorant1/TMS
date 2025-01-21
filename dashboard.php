<?php
require_once 'database/db_connection.php'; // Include database connection

session_start();
session_regenerate_id(true);

// Check if the user is logged in
if (!isset($_SESSION['master_userid']) || !isset($_SESSION['token'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Fetch session variables securely
$master_userid = htmlspecialchars($_SESSION['master_userid']);
$user_id = htmlspecialchars($_SESSION['user_id']);
$token = htmlspecialchars($_SESSION['token']);

// Ensure database connection exists
if (!isset($conn)) {
    die("Database connection failed. Please check your configuration.");
}

// Fetch total companies for the logged-in master user
$totalCompanies = 0;
$query = "
    SELECT COUNT(*) AS total_companies 
    FROM master_company 
    WHERE master_userid = ?";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalCompanies = $row['total_companies'] ?? 0;
    $stmt->close();
} else {
    die("Failed to fetch total companies.");
}

// Fetch account details grouped by account type
$accountsByType = [];
$query = "
    SELECT account_type, COUNT(*) AS total_accounts
    FROM account 
    WHERE master_user_id = ? 
    GROUP BY account_type";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $accountsByType[] = $row;
    }

    $stmt->close();
} else {
    die("Failed to fetch account details.");
}

// Fetch material details grouped by material type
$materialsByType = [];
$query = "
    SELECT mmt.material_type , COUNT(mm.id) AS total_materials 
    FROM master_materials AS mm
    JOIN master_materials_type AS mmt ON mm.material_type = mmt.id
    WHERE mm.master_user_id = ? 
    GROUP BY mmt.material_type";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $materialsByType[] = $row;
    }

    $stmt->close();
} else {
    die("Failed to fetch material details.");
}


// Fetch total quotations for the logged-in master user
$totalQuotations = 0;
$query = "
    SELECT COUNT(*) AS total_quotations 
    FROM master_quotations 
    WHERE master_user_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalQuotations = $row['total_quotations'] ?? 0;
    $stmt->close();
} else {
    die("Failed to fetch total quotations.");
}

// Fetch total tickets for the logged-in master user
// Fetch tickets grouped by their status
$ticketsByStatus = [];
$query = "
    SELECT mts.status_name AS ticket_status, COUNT(mt.ticket_id) AS total_tickets 
    FROM master_tickets AS mt
    JOIN master_tickets_status AS mts ON mt.ticket_status_id = mts.id
    WHERE mt.master_user_id = ?
    GROUP BY mts.status_name";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $ticketsByStatus[] = $row;
    }

    $stmt->close();
} else {
    die("Failed to fetch ticket details by status.");
}
// Fetch quotations grouped by their status
$quotationsByStatus = [];
$query = "
    SELECT mqs.status_name AS quotation_status, COUNT(mq.quotation_id) AS total_quotations 
    FROM master_quotations AS mq
    JOIN master_quotations_status AS mqs ON mq.quotation_status_id = mqs.quotation_status_id
    WHERE mq.master_user_id = ?
    GROUP BY mqs.status_name";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $quotationsByStatus[] = $row;
    }

    $stmt->close();
} else {
    die("Failed to fetch quotation details by status.");
}

$totalTickets = 0;
$query = "
    SELECT COUNT(*) AS total_tickets 
    FROM master_tickets 
    WHERE master_user_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalTickets = $row['total_tickets'] ?? 0;
    $stmt->close();
} else {
    die("Failed to fetch total tickets.");
}

// Fetch total users for the logged-in master user
$totalUsers = 0;
$query = "
    SELECT COUNT(*) AS total_users 
    FROM master_users 
    WHERE master_user_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalUsers = $row['total_users'] ?? 0;
    $stmt->close();
} else {
    die("Failed to fetch total users.");
}

// Fetch total marketing for the logged-in master user
// Fetch total marketing entries grouped by status
$marketingByStatus = [];
$query = "
    SELECT mms.status AS marketing_status, COUNT(mm.id) AS total_marketing 
    FROM master_marketing AS mm
    JOIN master_marketing_status AS mms ON mm.marketing_id_status = mms.id
    WHERE mm.master_user_id = ?
    GROUP BY mms.status";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $marketingByStatus[] = $row;
    }

    $stmt->close();
} else {
    die("Failed to fetch marketing details by status.");
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background-color: #f8f9fa;
            height: 100vh;
            padding: 20px;
        }

        .widget {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #fff;

            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>

    <?php include('headers/header.php'); ?> <!-- Include header file -->




    <div class="container-fluid">
        <div class="row">

            <!-- Main Content Section -->
            <div class="col-12 col-md-9"> <!-- On small screens, take full width, on medium screens, 9 columns -->
                <div class="container mt-5">
                    <h3>Welcome, <?php echo htmlspecialchars($user_id); ?>!</h3>
                    <p>Your unique session token: <?php echo htmlspecialchars($token); ?></p>

                    <?php include('headers/header_buttons.php'); ?> <!-- Include buttons file -->
                   


                <!-- Widgets Section -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="widget">
                            <h5>Total Companies</h5>
                            <p><?php echo $totalCompanies; ?></p> <!-- Display total companies dynamically -->
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="widget">
                            <?php
                            $totalAccounts = 0; // Initialize total accounts count

                            // Calculate total accounts by summing up the values
                            foreach ($accountsByType as $account) {
                                $totalAccounts += $account['total_accounts'];
                            }
                            ?>
                            <h5>Total Accounts : <?php echo $totalAccounts; ?></h5> <!-- Display total accounts -->

                            <?php foreach ($accountsByType as $account) { ?>
                                <!-- Display account type and its corresponding count -->
                                <strong><?php echo htmlspecialchars($account['account_type']); ?></strong>:
                                <?php echo htmlspecialchars($account['total_accounts']); ?><br>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="widget">
                            <?php
                            $totalMaterials = 0; // Initialize total materials count

                            // Calculate total materials by summing up the values
                            foreach ($materialsByType as $material) {
                                $totalMaterials += $material['total_materials'];
                            }
                            ?>
                            <h5>Total Products : <?php echo $totalMaterials; ?></h5> <!-- Display total materials -->

                            <?php foreach ($materialsByType as $material) { ?>
                                <!-- Display material type and its corresponding count -->
                                <strong><?php echo htmlspecialchars($material['material_type']); ?></strong>:
                                <?php echo htmlspecialchars($material['total_materials']); ?><br>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="row">
                            <!-- Total Marketing Widget -->
                            <div class="col-md-4">
                                <div class="widget">
                                    <?php
                                    $totalMarketing = 0; // Initialize total marketing count

                                    // Calculate total marketing by summing up the values
                                    foreach ($marketingByStatus as $marketing) {
                                        $totalMarketing += $marketing['total_marketing'];
                                    }
                                    ?>
                                    <h5>Total Marketing: <?php echo $totalMarketing; ?></h5> <!-- Display total marketing -->

                                    <?php foreach ($marketingByStatus as $marketing) { ?>
                                        <!-- Display marketing status and its corresponding count -->
                                        <?php echo htmlspecialchars($marketing['total_marketing']); ?> :
                                        <?php echo htmlspecialchars($marketing['marketing_status']); ?>
                                        <br>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Total Quotations Widget -->
                            <div class="col-md-4">
                                <div class="widget">
                                    <h5>Total Quotations : <?php echo $totalQuotations; ?> </h5>

                                    <!-- Quotations by Status -->
                                    <?php foreach ($quotationsByStatus as $quotation) { ?>
                                        <?php echo htmlspecialchars($quotation['total_quotations']); ?> :
                                        <?php echo htmlspecialchars($quotation['quotation_status']); ?> <br>

                                    <?php } ?>
                                </div>
                            </div>

                            <!-- Total Tickets Widget -->
                            <div class="col-md-4">
                                <div class="widget">
                                    <h5>Total Tickets : <?php echo $totalTickets; ?><!-- Display total tickets dynamically --></h5>


                                    <!-- Tickets by Status -->
                                    <?php foreach ($ticketsByStatus as $ticket) { ?>

                                        <?php echo htmlspecialchars($ticket['total_tickets']); ?> :
                                        <?php echo htmlspecialchars($ticket['ticket_status']); ?><br>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <!-- Total Users Widget -->
                        <div class="col-md-3">
                            <div class="widget">
                                <h5>Total Users</h5>
                                <p><?php echo $totalUsers; ?></p> <!-- Display total users dynamically -->
                            </div>
                        </div>
                    </div>



                    <!-- Charts Section -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="widget">
                                <h5>Recent User Activity</h5>
                                <canvas id="userActivityChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="widget">
                                <h5>Transaction Summary</h5>
                                <canvas id="transactionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Example Chart.js implementation for User Activity
        const userActivityCtx = document.getElementById('userActivityChart').getContext('2d');
        const userActivityChart = new Chart(userActivityCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                datasets: [{
                    label: 'Active Users',
                    data: [50, 75, 60, 80, 90],
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
            }
        });

        // Example Chart.js implementation for Transactions
        const transactionCtx = document.getElementById('transactionChart').getContext('2d');
        const transactionChart = new Chart(transactionCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                datasets: [{
                    label: 'Transactions',
                    data: [1000, 1200, 900, 1500, 1300],
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
            }
        });
    </script>
</body>

</html>