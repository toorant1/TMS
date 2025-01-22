<?php
include 'database/db_connection.php'; // Include the database connection

// Initialize message variables
$registrationMessage = "";
$loginMessage = "";
$activeTab = "register"; // Default active tab



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = trim($_POST['user_id']);

    $sql = "SELECT * FROM master_user_registration WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'exists', 'message' => 'Email ID already registered.']);
    } else {
        echo json_encode(['status' => 'available', 'message' => 'Email ID is available.']);
    }
    exit;
}


// Handle Registration Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $user_id = trim($_POST['user_id']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);

    // Check if any required field is empty
    if (empty($user_id) || empty($password) || empty($confirm_password) || empty($fname) || empty($lname)) {
        $registrationMessage = "All fields are required. Please fill out the form completely.";
    } elseif ($password !== $confirm_password) { // Check if passwords match
        $registrationMessage = "Passwords do not match. Please try again.";
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Generate unique token and license key
        $token = bin2hex(random_bytes(16)); // Generate a 32-character token
        $lic_key = bin2hex(random_bytes(8)); // Generate a 16-character license key

        // Default values for additional fields
        $token_otp = rand(100000, 999999); // Generate a 6-digit OTP
        $token_otp_status = 0; // Default OTP status
        $lic_status = 1; // Default license status

        // Check if the user_id (email) already exists
        $check_sql = "SELECT * FROM master_user_registration WHERE user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $registrationMessage = "Error: Email ID already registered. Please use a different email.";
        } else {
            // Proceed with registration
            $sql = "INSERT INTO master_user_registration 
                    (user_id, password, fname, lname, token, token_otp, token_otp_status, lic_status, lic_key, create_on, update_on) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssiiss", $user_id, $hashedPassword, $fname, $lname, $token, $token_otp, $token_otp_status, $lic_status, $lic_key);

            if ($stmt->execute()) {
                $registrationMessage = "Registration successful! Please <a href='#login' class='text-primary'>login</a> now.";
                $activeTab = "login"; // Redirect user to login tab
            } else {
                $registrationMessage = "Registration failed. Please try again.";
            }
            

            $stmt->close();
        }

        $check_stmt->close();
    }
}

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user_id = $_POST['login_user_id'];
    $password = $_POST['login_password'];

    $sql = "SELECT * FROM master_user_registration WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify the hashed password
        if (password_verify($password, $user['password'])) {
            // Start a session and store the user information
            session_start();

            $_SESSION['user_id'] = $user_id;       // Store user_id
            $_SESSION['master_userid'] = $user['id'];  // Store user primary key
            $_SESSION['token'] = $user['token'];
            $_SESSION['master_api_key'] = $user['token'];


            // Redirect to dashboard
            header("Location: dashboard.php");
            exit; // Ensure no further code is executed
        } else {
            $loginMessage = "Invalid credentials. Please try again.";
            $activeTab = "login"; // Set active tab to login
        }
    } else {
        $loginMessage = "Invalid credentials. Please try again.";
        $activeTab = "login"; // Set active tab to login
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration & Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom Media Queries for responsiveness */
        @media (max-width: 768px) {
            .card {
                margin-top: 20px;
            }

            .card-header h4 {
                font-size: 1.25rem;
            }

            .nav-tabs {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .form-control {
                font-size: 0.875rem;
            }

            .btn {
                font-size: 1rem;
            }

            .card-header h4 {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'register' ? 'active' : '' ?>" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab" aria-controls="register" aria-selected="<?= $activeTab === 'register' ? 'true' : 'false' ?>">Master User Registration</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $activeTab === 'login' ? 'active' : '' ?>" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab" aria-controls="login" aria-selected="<?= $activeTab === 'login' ? 'true' : 'false' ?>">Master User Login</button>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <!-- Registration Tab -->
            <div class="tab-pane fade <?= $activeTab === 'register' ? 'show active' : '' ?>" id="register" role="tabpanel" aria-labelledby="register-tab">
    <div class="card shadow-sm mt-3 mx-auto" style="max-width: 800px;">
        <div class="card-header bg-primary text-white text-center">
            <h4>User Registration</h4>
        </div>
        <div class="card-body">
            <?php if ($registrationMessage): ?>
                <div class="alert alert-<?= strpos($registrationMessage, 'successful') !== false ? 'success' : 'danger' ?> text-center">
                    <?= $registrationMessage ?>
                </div>
            <?php endif; ?>
            <form id="registrationForm" method="post" action="">
                <input type="hidden" name="register" value="1">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="fname" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="fname" name="fname" required placeholder="Enter First Name" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label for="lname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="lname" name="lname" required placeholder="Enter Last Name" autocomplete="off">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="user_id" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="user_id" name="user_id" required placeholder="Enter your Email Address" autocomplete="off">
                    <div id="user_id_feedback" class="form-text text-danger"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Enter Strong Password" autocomplete="off">
                        <div id="password_feedback" class="form-text"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Re-enter Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Re-Enter Password" autocomplete="off">
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-auto mb-2">
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

            <!-- Login Tab -->
            <div class="tab-pane fade <?= $activeTab === 'login' ? 'show active' : '' ?>" id="login" role="tabpanel" aria-labelledby="login-tab">
                <div class="card shadow-sm mt-3 mx-auto" style="max-width: 800px;">
                    <div class="card-header text-white text-center"
                        style="
            background: linear-gradient(135deg, #ff7e5f, #feb47b); 
            border-radius: 0.375rem 0.375rem 0 0;">
                        <h4 class="mb-0" style="font-weight: 600; font-size: 1.5rem;">Master User Login</h4>
                    </div>

                    <div class="card-body">
                        <?php if ($loginMessage): ?>
                            <div class="alert alert-danger"><?= $loginMessage ?></div>
                        <?php endif; ?>
                        <form id="loginForm" method="post" action="">
                            <input type="hidden" name="login" value="1">
                            <div class="mb-3">
                                <label for="login_user_id" class="form-label">Master User ID</label>
                                <input type="text" class="form-control" id="login_user_id" name="login_user_id" required placeholder="Enter your Master User ID" autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label for="login_password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="login_password" name="login_password" required placeholder="Enter your Password" autocomplete="off">
                            </div>
                            <div class="row justify-content-center">
                                <div class="col-auto mb-2">
                                    <button type="submit" class="btn btn-primary">Login</button>
                                </div>
                                <div class="col-auto">
                                    <a href="forgot_password.php" class="btn btn-secondary">Forgot Password?</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
document.getElementById('user_id').addEventListener('blur', function () {
    const userIdField = this;
    const userId = userIdField.value.trim();
    const feedbackElement = document.getElementById('user_id_feedback');

    if (userId !== "") {
        // AJAX request to validate user_id
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "#", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.status === "exists") {
                    feedbackElement.textContent = response.message;
                    feedbackElement.classList.add('text-danger');
                    feedbackElement.classList.remove('text-success');
                } else if (response.status === "available") {
                    feedbackElement.textContent = response.message;
                    feedbackElement.classList.add('text-success');
                    feedbackElement.classList.remove('text-danger');
                }
            }
        };

        xhr.send("user_id=" + encodeURIComponent(userId));
    } else {
        feedbackElement.textContent = "User ID cannot be empty.";
        feedbackElement.classList.add('text-danger');
        feedbackElement.classList.remove('text-success');
    }
});
</script>


<script>
    document.getElementById('user_id').addEventListener('blur', function () {
    const userIdField = this;
    const userId = userIdField.value.trim();
    const feedbackElement = document.getElementById('user_id_feedback');

    if (userId !== "") {
        // AJAX request to validate user_id
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "#", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.status === "exists") {
                    feedbackElement.textContent = response.message;
                    feedbackElement.classList.add('text-danger');
                    feedbackElement.classList.remove('text-success');
                } else if (response.status === "available") {
                    feedbackElement.textContent = response.message;
                    feedbackElement.classList.add('text-success');
                    feedbackElement.classList.remove('text-danger');
                }
            }
        };

        xhr.send("user_id=" + encodeURIComponent(userId));
    } else {
        feedbackElement.textContent = "User ID cannot be empty.";
        feedbackElement.classList.add('text-danger');
        feedbackElement.classList.remove('text-success');
    }
});

    </script>

</body>

</html>