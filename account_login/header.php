<?php

require_once __DIR__ . '../../database/config.php';
require_once __DIR__ . '../../database/helpers.php';

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background-color: MediumSeaGreen; /* Green background */
            color: black;
            padding: 8px 50px; /* Customize padding for a compact look */
        }

        .navbar-brand {
            font-size: 1.2rem; /* Slightly smaller font size for the brand */
            padding: 0;
        }

        .nav-link {
            font-size: 1rem; /* Adjusted font size for navigation links */
            padding: 5px 10px; /* Compact padding for the links */
        }

        .navbar-toggler {
            padding: 5px; /* Adjust toggler button size */
        }

        .navbar-brand,
        .nav-link {
            color: white !important;
        }

        .navbar-brand:hover,
        .nav-link:hover {
            color: black !important; /* Light gray for hover effect */
        }

        .content {
            margin-top: 56px; /* Space below navbar for content */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= base_url('account_login/dashboard.php'); ?>">My Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('account_login/dashboard.php');  ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('account_login/ticket_management/new_ticket.php'); ?>">New Ticket</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('account_login/settings/settings.php'); ?>">Update Company Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('account_login/shopping/myshop.php'); ?>">Shopping</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="<?= base_url('../logout.php'); ?>">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</html>
