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

// Query to validate and fetch ticket details with master_user_id and token condition
$query = "
    SELECT 
        mt.ticket_id AS `Ticket ID`,
        DATE(mt.ticket_date) AS `Ticket Date`,
        IFNULL(mtt.ticket_type, 'Unknown') AS `Ticket Type`,
        IFNULL(mp.priority, 'Unknown') AS `Priority`,
        IFNULL(ms.status_name, 'Unknown') AS `Status`,
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

// Generate the default placeholder image with the first letter of the contact person
$contactInitial = strtoupper(substr($ticket['Contact Person'], 0, 1));
$defaultImage = "https://via.placeholder.com/150/007bff/ffffff?text=" . urlencode($contactInitial);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <a href="ticket_service.php?ticket_id=<?= urlencode($ticket['Ticket ID']); ?>&token=<?= urlencode($ticket_token); ?>"
                class="btn btn-primary">Service Entry</a>
            <a href="material_challan.php?ticket_id=<?= urlencode($ticket['Ticket ID']); ?>&token=<?= urlencode($ticket_token); ?>"
                class="btn btn-success">Material Challan</a>
            <a href="rgp.php?ticket_id=<?= urlencode($ticket['Ticket ID']); ?>&token=<?= urlencode($ticket_token); ?>"
                class="btn btn-warning">RGP</a>
            <a href="email_report.php?ticket_id=<?= urlencode($ticket['Ticket ID']); ?>&token=<?= urlencode($ticket_token); ?>"
                class="btn btn-info">Report Email to Client</a>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <!-- Ticket Card -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                Ticket ID: <?= htmlspecialchars($ticket['Ticket ID']); ?> --- <?= htmlspecialchars($ticket['Ticket Date']); ?>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Image Box -->
                    <div class="col-md-3 d-flex flex-column align-items-center">
                        <div class="image-box mb-2">
                            <img src="<?= $defaultImage; ?>" alt="Placeholder Image">
                        </div>
                        <p class="text-center"><strong><?= htmlspecialchars($ticket['Contact Person']); ?></strong></p>
                    </div>

                    <!-- Details Table -->
                    <div class="col-md-9">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th style="width: 30%;">Ticket Type</th>
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
                                    <th>Contact Person</th>
                                    <td>
                                        <?= htmlspecialchars($ticket['Contact Person']); ?> -
                                        <?= htmlspecialchars($ticket['Mobile 1']); ?> /
                                        <?= htmlspecialchars($ticket['Mobile 2']); ?> -
                                        <?= htmlspecialchars($ticket['Email']); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
$conn->close();
?>