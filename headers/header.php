<?php

require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../database/helpers.php';

?>

<!-- header.php -->
<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= base_url('dashboard.php'); ?>">Dashboard</a>
        <!-- Navbar Toggler for mobile view -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="<?= base_url('temp/testing.php'); ?>">Testing and Validations </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="<?= base_url('dashboard.php'); ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?= base_url('company/dashboard.php'); ?>">Company</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?= base_url('users/dashboard.php'); ?>">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?= base_url('accounts/dashboard.php'); ?>">Accounts</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?= base_url('marketing/dashboard.php'); ?>">Marketing</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?= base_url('materials/dashboard.php'); ?>">Materials</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?= base_url('quotations/dashboard.php'); ?>">Quotations</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="<?= base_url('ticket/dashboard.php'); ?>">Tickets</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?= base_url('settings/dashboard.php'); ?>">Settings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('profile/profile.php'); ?>">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('tableStructure/table.php'); ?>">Table Structure</a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('file_management/show_files.php'); ?>">Software Downloads</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('logout.php'); ?>">Log Out</a>
                </li>
            </ul>
        </div>
    </div>
</nav>