<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

// Use the session variable
$master_userid = $_SESSION['master_userid'];

// SQL Query to fetch tickets with proper joined names and contact details
$query = "
    SELECT 
        mt.ticket_id AS `Ticket ID`,
        DATE(mt.ticket_date) AS `Ticket Date`,
        IFNULL(mtt_user.ticket_type, mtt_default.ticket_type) AS `Ticket Type`,
        IFNULL(mp_user.priority, mp_default.priority) AS `Priority`,
        IFNULL(ms_user.status_name, ms_default.status_name) AS `Status`,
        acc.id AS `Account ID`,
        acc.account_name AS `Account Name`,
        acc.address AS `Account Address`,
        acc.state AS `State`,
        acc.city AS `City`,
        acc.district AS `District`,
        acc.pincode AS `Pincode`,
        acc.country AS `Country`,
        acc.token AS `Token`,
        c.id AS `Contact ID`, -- Ensure Contact ID is included
        c.name AS `Contact Person`,
        c.designation AS `Designation`,
        c.mobile1 AS `Mobile 1`,
        c.mobile2 AS `Mobile 2`,
        c.email AS `Email`,
        IFNULL(mmc_user.main_cause, mmc_default.main_cause) AS `Cause`,
        mt.problem_statement AS `Problem Statement`
    FROM 
        master_tickets mt

    -- Ticket Type: Prioritize master_user_id-specific, fallback to master_user_id=0
    LEFT JOIN master_tickets_types mtt_user 
        ON mt.ticket_type_id = mtt_user.id AND mtt_user.master_user_id = ?
    LEFT JOIN master_tickets_types mtt_default 
        ON mt.ticket_type_id = mtt_default.id AND mtt_default.master_user_id = 0

    -- Priority
    LEFT JOIN master_tickets_priority mp_user 
        ON mt.ticket_priority_id = mp_user.id AND mp_user.master_user_id = ?
    LEFT JOIN master_tickets_priority mp_default 
        ON mt.ticket_priority_id = mp_default.id AND mp_default.master_user_id = 0

    -- Status
    LEFT JOIN master_tickets_status ms_user 
        ON mt.ticket_status_id = ms_user.id AND ms_user.master_user_id = ?
    LEFT JOIN master_tickets_status ms_default 
        ON mt.ticket_status_id = ms_default.id AND ms_default.master_user_id = 0

    -- Accounts and Contacts
    LEFT JOIN account acc 
        ON mt.account_id = acc.id
    LEFT JOIN contacts c 
        ON mt.contact_id = c.id

    -- Causes
    LEFT JOIN master_tickets_main_causes mmc_user 
        ON mt.cause_id = mmc_user.id AND mmc_user.master_user_id = ?
    LEFT JOIN master_tickets_main_causes mmc_default 
        ON mt.cause_id = mmc_default.id AND mmc_default.master_user_id = 0

    WHERE 
        mt.master_user_id = ?
    ORDER BY 
        mt.ticket_id DESC, mt.ticket_date DESC
";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("SQL Prepare Error: " . $conn->error);
}

// Bind the parameters for master_user_id in multiple places
$stmt->bind_param("iiiii", $master_userid, $master_userid, $master_userid, $master_userid, $master_userid);

if (!$stmt->execute()) {
    die("SQL Execution Error: " . $stmt->error);
}

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('../headers/header.php'); ?>
<div class="container mt-5">
    <h1 class="text-center mb-4">Tickets Dashboard</h1>
    <!-- Create New Ticket Button -->
    <div class="d-flex justify-content-end mb-3">
        <a href="add_ticket.php" class="btn btn-primary">Create New Ticket</a>
    </div>
    <!-- Table to Display Tickets -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Ticket ID</th>
                    <th>Account Name / Address</th>
                    <th>Contact Person</th>
                    <th>Ticket Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Cause</th>
                    <th>Problem Statement</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Ticket ID']); ?><br>
                                <?= htmlspecialchars($row['Ticket Date']); ?></td>
                            <td>
                                <a href="account_tickets.php?account_id=<?= urlencode($row['Account ID']); ?>&token=<?= urlencode($row['Token']); ?>" 
                                   class="text-primary">
                                    <strong><?= htmlspecialchars($row['Account Name']); ?></strong>
                                </a><br>
                                <?= htmlspecialchars($row['Account Address']); ?><br>
                                <?= htmlspecialchars($row['City']); ?>, <?= htmlspecialchars($row['District']); ?>,<br>
                                <?= htmlspecialchars($row['State']); ?>, <?= htmlspecialchars($row['Country']); ?> - <?= htmlspecialchars($row['Pincode']); ?>
                            </td>
                            <td>
                                <a href="contact_tickets.php?contact_id=<?= urlencode($row['Contact ID']); ?>" 
                                   class="text-primary" target="_blank">
                                    <?= htmlspecialchars($row['Contact Person']); ?>
                                </a> - <?= htmlspecialchars($row['Designation']); ?><br>
                                <?= htmlspecialchars($row['Mobile 1']); ?> - <?= htmlspecialchars($row['Mobile 2']); ?><br>
                                <?= htmlspecialchars($row['Email']); ?>
                            </td>
                            <td><?= htmlspecialchars($row['Ticket Type']); ?></td>
                            <td><?= htmlspecialchars($row['Priority']); ?></td>
                            <td><?= htmlspecialchars($row['Status']); ?></td>
                            <td><?= htmlspecialchars($row['Cause']); ?></td>
                            <td><?= htmlspecialchars($row['Problem Statement']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No Tickets Found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Display Total Count -->
    <div class="text-start mt-3">
        <strong>Total Records: <?= $result->num_rows; ?></strong>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
