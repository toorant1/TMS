<?php
session_start();
require_once '../database/db_connection.php'; // Include database connection file

$message = "";

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect user inputs
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $master_userid = trim($_POST['master_userid']);

    // Validate inputs
    if (empty($email) || empty($password) || empty($master_userid)) {
        $message = "Please fill in all fields.";
    } else {
        // Query to check account credentials
        $query = "SELECT id, master_user_id, account_name, password FROM account WHERE email = ? AND master_user_id = ?";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param("si", $email, $master_userid);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $account = $result->fetch_assoc();

                // Use password_verify for hashed password validation
                if (password_verify($password, $account['password'])) {
                    // Set session variables
                    $_SESSION['account_id'] = $account['id'];
                    $_SESSION['master_userid'] = $account['master_user_id'];
                    $_SESSION['ticket_entry_type'] = "Self Service";
                    

                    // Redirect to the dashboard
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $message = "Invalid email or password.";
                }
            } else {
                $message = "Invalid email or master user selection.";
            }

            $stmt->close();
        } else {
            $message = "Error preparing the database query.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }

        .btn-primary {
            width: 100%;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="login-container">
        <h2>Account Login</h2>

        <?php if (!empty($message)): ?>
            <p class="error-message"><?= htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="master_userid" class="form-label">Select Master User</label>
                <select name="master_userid" id="master_userid" class="form-select" required>
                    <option value="">-- Select Master User --</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>

    <script>
        $(document).ready(function () {
            $('#email, #password').on('blur', function () {
                var email = $('#email').val();
                var password = $('#password').val();

                if (email && password) {
                    $.ajax({
                        url: 'validate_email.php', // AJAX endpoint for validating email and password
                        type: 'POST',
                        data: { email: email, password: password },
                        success: function (data) {
                            $('#master_userid').html(data);
                        }
                    });
                } else {
                    $('#master_userid').html('<option value="">-- Select Master User --</option>');
                }
            });
        });
    </script>
</body>

</html>
