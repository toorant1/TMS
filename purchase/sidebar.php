<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get the current page name
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            width: 200px;
            min-height: 100vh;
            background: #343a40;
            color: white;
            position: fixed;
            padding-top: 20px;
        }

        .sidebar a {
            color: white;
            padding: 10px;
            display: block;
            text-decoration: none;
        }

        .sidebar a:hover,
        .sidebar a.active {  /* ✅ Active item style */
            background: #007bff;
            color: white;
            font-weight: bold;
            border-left: 5px solid #fff; /* ✅ Highlight effect */
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h4 class="text-center">Purchase Menu</h4>
    <a href="dashboard.php" id="dashboard" class="menu-link">Purchase Dashboard</a>
    <a href="po_approval_management.php" id="po_approval_management" class="menu-link">Purchase Approval Management</a>
    <a href="po_new.php" id="po_new" class="menu-link">Purchase Orders</a>
    <a href="grn.php" id="grn" class="menu-link">GRN</a>
    <a href="reports.php" id="reports" class="menu-link">Reports</a>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const menuLinks = document.querySelectorAll(".menu-link");

    // Retrieve last clicked menu item from localStorage
    const activeMenu = localStorage.getItem("activeMenu");

    // Remove active class from all links
    menuLinks.forEach(link => {
        link.classList.remove("active");

        // ✅ If this link was stored as active, apply the active class
        if (link.id === activeMenu) {
            link.classList.add("active");
        }

        // ✅ Add event listener to store the clicked menu item
        link.addEventListener("click", function() {
            localStorage.setItem("activeMenu", this.id);
        });
    });
});
</script>

</body>
</html>
