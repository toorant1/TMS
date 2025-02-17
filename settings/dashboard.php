<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

// Use the session variable
$master_userid = $_SESSION['master_userid'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings and Configuration Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->

<div class="container mt-5">
    <h1 class="text-center mb-4">Settings and Configuration Dashboard</h1>

    <!-- Card Grid -->
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <!-- Card 1 -->
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Ticket Types</h5>
                    <p class="card-text">Manage ticket Types.</p>
                    <a href="ticket_types.php" class="btn btn-primary">Show</a>
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Ticket Priority</h5>
                    <p class="card-text">Manage Ticket Priority</p>
                    <a href="ticket_priority.php" class="btn btn-primary">Show</a>
                </div>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Ticket Status</h5>
                    <p class="card-text">Manage Ticket Status</p>
                    <a href="ticket_status.php" class="btn btn-primary">Show</a>
                </div>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="col">
            <div class="card h-100">
                <div class="card-body disabled">
                    <h5 class="card-title">API Integration</h5>
                    <p class="card-text">Set up and manage API configurations.</p>
                    <a href="api_integration.php" class="btn btn-primary">Manage API</a>
                </div>
            </div>
        </div>

        <!-- Card 5 -->
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Payment Settings</h5>
                    <p class="card-text">Configure payment gateways and related settings.</p>
                    <a href="payment_settings.php" class="btn btn-primary">Configure Payments</a>
                </div>
            </div>
        </div>

        <!-- Card 6 -->
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Security Settings</h5>
                    <p class="card-text">Manage application security configurations.</p>
                    <a href="security_settings.php" class="btn btn-primary">View Security</a>
                </div>
            </div>
        </div>

        <!-- Card 7 -->
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Periodic Email Setup</h5>
                    <p class="card-text">View and manage system Periodic Setups</p>
                    <a href="../settings/periodic_reports_on_emails/dashboard.php" class="btn btn-primary">Manage</a>
                </div>
            </div>
        </div>

        <!-- Card 8 -->
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Email Configuration</h5>
                    <p class="card-text">Set up and manage email server settings.</p>
                    <a href="email_configuration.php" class="btn btn-primary">Configure Email</a>
                </div>
            </div>
        </div>

        <!-- Card 9 -->
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Backup and Restore</h5>
                    <p class="card-text">Manage database backups and restore operations.</p>
                    <a href="backup_restore.php" class="btn btn-primary">Backup/Restore</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
