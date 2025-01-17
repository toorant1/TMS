<?php

require_once __DIR__ . '../../database/config.php';
require_once __DIR__ . '../../database/helpers.php';

?>


<!-- header.php -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Dashboard</a>
        <!-- Navbar Toggler for mobile view -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="../dashboard.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href= "company/dashboard.php">Company</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="users/dashboard.php">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="accounts/dashboard.php">Accounts</a>
                    <li class="nav-item">
                    <a class="nav-link active" href="materials/dashboard.php">Marketing</a>
                </li>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link active" href="materials/dashboard.php">Materials</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="ticket/dashboard.php">Tickets</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="settings/dashboard.php">Settings</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href=  "profile/profile.php">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="tableStructure/table.php">Table Structure </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
