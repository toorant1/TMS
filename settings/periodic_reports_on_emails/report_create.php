<?php
session_start();
require_once '../../database/db_connection.php'; // Ensure database connection is correct

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Fetch ticket types from the database
$query_ticket_types = "SELECT id, ticket_type FROM master_tickets_types WHERE master_user_id = ?";
$stmt_ticket_types = $conn->prepare($query_ticket_types);
$stmt_ticket_types->bind_param("i", $master_userid);
$stmt_ticket_types->execute();
$result_ticket_types = $stmt_ticket_types->get_result();

// Fetch priorities from the database
$query_priorities = "SELECT id, priority FROM master_tickets_priority WHERE master_user_id = ?";
$stmt_priorities = $conn->prepare($query_priorities);
$stmt_priorities->bind_param("i", $master_userid);
$stmt_priorities->execute();
$result_priorities = $stmt_priorities->get_result();

// Fetch statuses from the database
$query_statuses = "SELECT id, status_name FROM master_tickets_status WHERE master_user_id = ?";
$stmt_statuses = $conn->prepare($query_statuses);
$stmt_statuses->bind_param("i", $master_userid);
$stmt_statuses->execute();
$result_statuses = $stmt_statuses->get_result();

// Fetch causes from the database
$query_causes = "SELECT id, main_cause FROM master_tickets_main_causes WHERE master_user_id = ?";
$stmt_causes = $conn->prepare($query_causes);
$stmt_causes->bind_param("i", $master_userid);
$stmt_causes->execute();
$result_causes = $stmt_causes->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Auto Generated Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>

    <?php include('../../headers/header.php'); ?>

    <div class="container mt-5">
        <h1 class="text-center mb-4">Create New Auto Generated Report</h1>

        <div class="row">
            <!-- Left Side - Form -->
            <div class="col-md-6">
                <div class="card shadow-lg p-4">
                    <form action="process_report.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Report Name</label>
                            <input type="text" class="form-control" name="report_name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Report Schedule</label>
                            <select class="form-control" name="report_schedule" required>
                                <option value="">Select Schedule</option>
                                <option value="Every Day">📅 Every Day</option>
                                <option value="Week Start">🚀 Week Start (Monday)</option>
                                <option value="Week End">📊 Week End (Sunday)</option>
                                <option value="Fortnightly">🔄 Fortnightly (Every 2 Weeks)</option>
                                <option value="Monthly">📆 Monthly</option>
                                <option value="Quarterly">📈 Quarterly</option>
                                <option value="Yearly">🎉 Yearly</option>
                            </select>
                        </div>

                        <!-- Ticket Type -->
                        <div class="mb-3">
                            <label class="form-label">Ticket Type</label>
                            <select class="form-control" name="ticket_type" required>
                                <option value="">Select Ticket Type</option>
                                <?php
                                if ($result_ticket_types->num_rows > 0) {
                                    while ($row = $result_ticket_types->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['ticket_type']}</option>";
                                    }
                                } else {
                                    echo "<option value=''>No Ticket Types Available</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Priority -->
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-control" name="priority" required>
                                <option value="">Select Priority</option>
                                <?php
                                if ($result_priorities->num_rows > 0) {
                                    while ($row = $result_priorities->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['priority']}</option>";
                                    }
                                } else {
                                    echo "<option value=''>No Priorities Available</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" required>
                                <option value="">Select Status</option>
                                <?php
                                if ($result_statuses->num_rows > 0) {
                                    while ($row = $result_statuses->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['status_name']}</option>";
                                    }
                                } else {
                                    echo "<option value=''>No Statuses Available</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Cause -->
                        <div class="mb-3">
                            <label class="form-label">Cause</label>
                            <select class="form-control" name="cause" required>
                                <option value="">Select Cause</option>
                                <?php
                                if ($result_causes->num_rows > 0) {
                                    while ($row = $result_causes->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['main_cause']}</option>";
                                    }
                                } else {
                                    echo "<option value=''>No Causes Available</option>";
                                }
                                ?>
                            </select>
                        </div>

                       
        </div>
    </div>

</body>

</html>
