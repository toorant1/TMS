<?php

session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid']) || !isset($_SESSION['child_userid'])) {
    // Redirect to the login page if the user is not logged in
    header("Location: index.php");
    exit();
}

require_once '../database/db_connection.php'; // Include database connection file

// Fetch user information from the database
$master_userid = $_SESSION['master_userid'];
$child_userid = $_SESSION['child_userid'];

$query = "SELECT * FROM master_users WHERE master_user_id = ? AND id = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("ii", $master_userid, $child_userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        echo "Error: User not found.";
        exit();
    }

    $stmt->close();
} else {
    echo "Error: Failed to prepare query.";
    exit();
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            margin-top: 50px;
        }
        .welcome-message {
            background: #007bff;
            color: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .user-info {
            margin-top: 20px;
        }
    </style>
    <?php include('../headers/header.php'); ?> <!-- Include the header file here -->
</head>
<body>
    <div class="container">
        <div class="welcome-message">
            <h1>Welcome, <?= htmlspecialchars($user['name']); ?>!</h1>
            <p>You are successfully logged in.</p>
        </div>

        <div class="user-info">
            <h3>Your Information</h3>
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th>Master User ID</th>
                        <td><?= htmlspecialchars($user['master_user_id']); ?></td>
                    </tr>
                    <tr>
                        <th>Child User ID</th>
                        <td><?= htmlspecialchars($child_userid); ?></td>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <td><?= htmlspecialchars($user['name']); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?= htmlspecialchars($user['email']); ?></td>
                    </tr>
                    <tr>
                        <th>Gender</th>
                        <td><?= htmlspecialchars($user['sex']); ?></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td><?= htmlspecialchars($user['address']); ?></td>
                    </tr>
                    <tr>
                        <th>State</th>
                        <td><?= htmlspecialchars($user['state']); ?></td>
                    </tr>
                    <tr>
                        <th>District</th>
                        <td><?= htmlspecialchars($user['district']); ?></td>
                    </tr>
                    <tr>
                        <th>City</th>
                        <td><?= htmlspecialchars($user['city']); ?></td>
                    </tr>
                    <tr>
                        <th>Pincode</th>
                        <td><?= htmlspecialchars($user['pincode']); ?></td>
                    </tr>
                    <tr>
                        <th>Mobile</th>
                        <td><?= htmlspecialchars($user['mobile']); ?></td>
                    </tr>
                    <tr>
                        <th>Created On</th>
                        <td><?= htmlspecialchars($user['created_on']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-4">
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
