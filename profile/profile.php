<?php
session_start(); // Start the session

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit;
}

$user_id = $_SESSION['user_id'];
$token = $_SESSION['token'];

// Include the database connection file
include '../database/db_connection.php'; // Include the database connection

// Check for database connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch user data
$query = "SELECT * FROM master_user_registration WHERE user_id = ?"; // Adjusted query to use user_id
$stmt = $conn->prepare($query);

// Check if the prepare statement failed
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param('s', $user_id); // Bind the user_id parameter (text type in the DB)
$stmt->execute();
$result = $stmt->get_result();

// Check if the user exists
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc(); // Fetch the user data as an associative array
    $user_name = $user['fname'] . ' ' . $user['lname']; // Combine fname and lname for full name
    $password = $user['password'];
    $create_on = $user['create_on'];
    $update_on = $user['update_on'];
    $token = $user['token'];
    $token_otp = $user['token_otp'];
    $token_otp_status = $user['token_otp_status'];
    $lic_status = $user['lic_status'];
    $lic_key = $user['lic_key'];
} else {
    echo "User not found!";
    exit;
}

// Close the database connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Section -->
        
        <!-- Main Content Section -->
        <div class="col-12 col-md-9">
            <div class="container mt-5">
                <div class="card">
                    <div class="card-header">
                        <h5>Profile Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th>Full Name</th>
                                    <td><?php echo htmlspecialchars($user_name); ?></td>
                                </tr>
                                <tr>
                                    <th>User ID</th>
                                    <td><?php echo htmlspecialchars($user_id); ?></td>
                                </tr>
                                <tr>
                                    <th>Password</th>
                                    <td>*** *** ***</td>
                                </tr>
                                <tr>
                                    <th>Account Created On</th>
                                    <td><?php echo htmlspecialchars($create_on); ?></td>
                                </tr>
                                <tr>
                                    <th>Account Updated On</th>
                                    <td><?php echo htmlspecialchars($update_on); ?></td>
                                </tr>
                                <tr>
                                    <th>Basic License</th>
                                    <td><?php echo htmlspecialchars($token); ?></td>
                                </tr>
                                
                                <tr>
                                    <th>License Status</th>
                                    <td><?php echo htmlspecialchars($lic_status); ?></td>
                                </tr>
                                <tr>
                                    <th>License Key</th>
                                    <td><?php echo htmlspecialchars($lic_key); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="profile_update.php" class="btn btn-primary">Update Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
