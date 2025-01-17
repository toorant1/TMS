<?php
session_start();
session_regenerate_id(true);
if (!isset($_SESSION['master_userid']) || !isset($_SESSION['token'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$master_userid = htmlspecialchars($_SESSION['master_userid']);
$user_id = htmlspecialchars($_SESSION['user_id']);
$token = htmlspecialchars($_SESSION['token']);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('headers/header.php'); ?> <!-- Include the header file here -->

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Section -->
        
        <!-- Main Content Section -->
        <div class="col-12 col-md-9"> <!-- On small screens, take full width, on medium screens, 9 columns -->
            <div class="container mt-5">
                <h3>Welcome, <?php echo htmlspecialchars($user_id); ?>!</h3>
                <p>Your unique session token: <?php echo htmlspecialchars($token); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
