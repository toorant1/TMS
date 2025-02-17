<?php
require_once '../../database/db_connection.php';
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

    <?php include('../../headers/header.php'); ?> <!-- Include the header file here -->

    <div class="container mt-5">
        <h1 class="text-center mb-4">Settings and Configuration Dashboard</h1>

       <!-- Card Grid -->
<div class="row row-cols-1 row-cols-md-3 g-4">
    <!-- Card 1 -->
    <div class="col">
        <div class="card h-100 shadow-lg border-0 rounded-4">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-envelope-fill text-primary" style="font-size: 3rem;"></i>
                </div>
                <h5 class="card-title fw-bold text-dark">Ticket Reports on Email</h5>
                <p class="card-text text-muted">Daily Ticket Report.</p>
                <a href="report_management.php" class="btn btn-primary px-4 py-2">Manage</a>
            </div>
        </div>
    </div>
</div>


    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>