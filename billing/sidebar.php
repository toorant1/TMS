<?php
// Get the current page's file name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="d-flex flex-column flex-shrink-0 bg-light" style="width: 250px; height: 100vh;">
    <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none">
        <span class="fs-4 ms-3 mt-3">My Dashboard</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : 'text-dark'; ?>" aria-current="page">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="invoices.php" class="nav-link <?= $current_page == 'invoices.php' ? 'active' : 'text-dark'; ?>">
                <i class="bi bi-file-earmark-text"></i> Invoices
            </a>
        </li>
        <li>
            <a href="payments.php" class="nav-link <?= $current_page == 'payments.php' ? 'active' : 'text-dark'; ?>">
                <i class="bi bi-cash-stack"></i> Payments
            </a>
        </li>
        <li>
            <a href="reports.php" class="nav-link <?= $current_page == 'reports.php' ? 'active' : 'text-dark'; ?>">
                <i class="bi bi-bar-chart-line"></i> Reports
            </a>
        </li>
        <li>
            <a href="settings.php" class="nav-link <?= $current_page == 'settings.php' ? 'active' : 'text-dark'; ?>">
                <i class="bi bi-gear"></i> Settings
            </a>
        </li>
    </ul>
    <hr>
</div>
