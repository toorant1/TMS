<?php
require_once '../database/db_connection.php'; // Include database connection file

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $query = "SELECT master_user_id, account_name, password FROM account WHERE email = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Validate password using password_verify
            if (password_verify($password, $row['password'])) {
                echo '<option value="">-- Select Master User --</option>';
                do {
                    echo '<option value="' . htmlspecialchars($row['master_user_id']) . '">' . htmlspecialchars($row['account_name']) . '</option>';
                } while ($row = $result->fetch_assoc());
            } else {
                echo '<option value="">Invalid email or password</option>';
            }
        } else {
            echo '<option value="">Invalid email or password</option>';
        }

        $stmt->close();
    } else {
        echo '<option value="">Error fetching data</option>';
    }
}
?>
