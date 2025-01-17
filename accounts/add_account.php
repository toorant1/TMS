<?php
require_once '../database/db_connection.php';
require_once '../headers/functions.php';
session_start();

$message = "";

// Check if the user ID is available in the session
if (!isset($_SESSION['master_userid'])) {
    die("Error: User is not logged in.");
}

$master_userid = $_SESSION['master_userid']; // Retrieve the user ID from the session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'account_name', 'address', 'state', 'district', 'city', 'pincode',
        'country', 'account_type', 'mobile', 'email', 'password',
        'remark', 'status', 'gst', 'pan', 'tan', 'msme',
        'bank_name', 'branch', 'ifsc', 'account_no'
    ];

    // Check for duplicate account name
    $account_name = $_POST['account_name'] ?? null;
    if (!$account_name) {
        $message = "Account Name is required.";
    } else {
        $duplicate_check_sql = "SELECT id FROM account WHERE master_user_id = ? AND account_name = ?";
        $duplicate_check_stmt = $conn->prepare($duplicate_check_sql);
        if (!$duplicate_check_stmt) {
            die("Error preparing duplicate check statement: " . $conn->error);
        }
        $duplicate_check_stmt->bind_param('is', $master_userid, $account_name);
        $duplicate_check_stmt->execute();
        $duplicate_check_result = $duplicate_check_stmt->get_result();

        if ($duplicate_check_result->num_rows > 0) {
            $message = "An account with the name '" . htmlspecialchars($account_name) . "' already exists.";
            $duplicate_check_stmt->close();
        } else {
            $duplicate_check_stmt->close();

            // Proceed with the existing logic to insert the account and its contacts
            try {
                $token = uniqid('acct_', true);
                $password_reset_token = uniqid('pwd_reset_', true);
                $created_on = $updated_on = date('Y-m-d H:i:s');
                $password_reset_token_status = 0;

                $hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);

                $sql = "INSERT INTO account (master_user_id, token, password_reset_token, password_reset_token_status, 
                        created_on, updated_on, " . implode(',', $fields) . ") 
                        VALUES (?, ?, ?, ?, ?, ?, " . implode(',', array_fill(0, count($fields), '?')) . ")";

                $conn->begin_transaction();

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Error preparing statement: " . $conn->error);
                }

                $types = 'isssss' . str_repeat('s', count($fields));
                $data = array_map(fn($field) => $_POST[$field] ?? null, $fields);
                $params = array_merge(
                    [$master_userid, $token, $password_reset_token, $password_reset_token_status, $created_on, $updated_on],
                    $data
                );

                $params[array_search('password', $fields) + 6] = $hashed_password;

                if (!$stmt->bind_param($types, ...$params)) {
                    throw new Exception("Error binding parameters: " . $stmt->error);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Error executing query: " . $stmt->error);
                }

                $account_id = $conn->insert_id;

                if (!empty($_POST['contacts'])) {
                    $contact_sql = "INSERT INTO contacts (account_id, name, designation, mobile1, mobile2, email, remark, token, status, created_on, updated_on, updated_by)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $contact_stmt = $conn->prepare($contact_sql);
                    if (!$contact_stmt) {
                        throw new Exception("Error preparing contact statement: " . $conn->error);
                    }

                    foreach ($_POST['contacts'] as $contact) {
                        $contact_token = uniqid('contact_', true);
                        $contact_status = 1;
                        $contact_created_on = $contact_updated_on = date('Y-m-d H:i:s');
                        $contact_updated_by = $master_userid;

                        $contact_params = [
                            $account_id,
                            $contact['name'],
                            $contact['designation'] ?? null,
                            $contact['mobile1'],
                            $contact['mobile2'] ?? null,
                            $contact['email'],
                            $contact['remark'] ?? null,
                            $contact_token,
                            $contact_status,
                            $contact_created_on,
                            $contact_updated_on,
                            $contact_updated_by
                        ];

                        if (!$contact_stmt->bind_param(
                            'issssssisssi',
                            ...$contact_params
                        )) {
                            throw new Exception("Error binding contact parameters: " . $contact_stmt->error);
                        }

                        if (!$contact_stmt->execute()) {
                            throw new Exception("Error executing contact query: " . $contact_stmt->error);
                        }
                    }

                    $contact_stmt->close();
                }
                $message = "Account and contacts added successfully!";
                $conn->commit();
                
                echo json_encode(['status' => 'success', 'message' => $message]);
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Transaction failed: " . $e->getMessage();
                echo json_encode(['status' => 'error', 'message' => $message]);
                exit;
            } finally {
                if (isset($stmt)) {
                    $stmt->close();
                }
            }
        }
    }
}
?>




<!-- Your existing HTML form here -->





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .form-container { max-width: 700px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .form-title { color: #333; }
    </style>
</head>
<body>

<?php include('../headers/header.php'); ?>

<div class="form-container">
    <h2 class="form-title text-center mb-4 p-3 bg-primary text-white rounded">Create New Account</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row g-3">
            <!-- Form Fields -->
            <hr style="border-width:5px">
    <div class="row">
        <div class="col-md-8">
            <label for="account_name" class="form-label">Account Name</label>
            <input type="text" name="account_name" id="account_name" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label for="account_type" class="form-label">Account Type</label>
            <select name="account_type" id="account_type" class="form-control" required>
                <option value="">Select Account Type</option>
                <option value="Supplier">Supplier</option>
                <option value="Customer">Customer</option>
                <option value="Both">Both</option>
            </select>
        </div>
    </div>


            <div class="col-12">
                <label for="address" class="form-label">Address</label>
                <textarea name="address" id="address" class="form-control" rows="2" required></textarea>
            </div>
            <div class="col-md-6">
                <label for="pincode" class="form-label">Pincode</label>
                <input type="text" name="pincode" id="pincode" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label for="city" class="form-label">City</label>
                <input type="text" name="city" id="city" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="state" class="form-label">State</label>
                <input type="text" name="state" id="state" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="district" class="form-label">District</label>
                <input type="text" name="district" id="district" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label for="country" class="form-label">Country</label>
                <input type="text" name="country" id="country" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label for="mobile" class="form-label">Mobile</label>
                <input type="text" name="mobile" id="mobile" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="col-md-12">
                <label for="remark" class="form-label">Remark</label>
                <textarea name="remark" id="remark" class="form-control"></textarea>
            </div>
            <div class="col-md-6" hidden>
                <label for="status" class="form-label">Status</label>
                <input type="number" name="status" id="status" class="form-control" value="1" required>
            </div>
            <hr style="border-width:5px">
            <div class="col-md-6">
                <label for="gst" class="form-label">GST</label>
                <input type="text" name="gst" id="gst" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="pan" class="form-label">PAN</label>
                <input type="text" name="pan" id="pan" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="tan" class="form-label">TAN</label>
                <input type="text" name="tan" id="tan" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="msme" class="form-label">MSME</label>
                <input type="text" name="msme" id="msme" class="form-control">
            </div>
            <hr style="border-width:5px">
            <div class="col-md-6">
                <label for="bank_name" class="form-label">Bank Name</label>
                <input type="text" name="bank_name" id="bank_name" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="branch" class="form-label">Branch</label>
                <input type="text" name="branch" id="branch" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="ifsc" class="form-label">IFSC Code</label>
                <input type="text" name="ifsc" id="ifsc" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="account_no" class="form-label">Account Number</label>
                <input type="text" name="account_no" id="account_no" class="form-control">
            </div>
            <hr style="border-width:5px">
            <h5 class="mb-3">Contacts</h5>
            <div id="dynamic-fields-container">
                <!-- Template for Dynamic Fields -->
                <div class="row g-3 mb-3 contact-row">
                    <div class="col-md-8">
                        <label for="name_0" class="form-label">Name</label>
                        <input type="text" name="contacts[0][name]" id="name_0" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label for="designation_0" class="form-label">Designation</label>
                        <input type="text" name="contacts[0][designation]" id="designation_0" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label for="mobile1_0" class="form-label">Mobile 1</label>
                        <input type="text" name="contacts[0][mobile1]" id="mobile1_0" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label for="mobile2_0" class="form-label">Mobile 2</label>
                        <input type="text" name="contacts[0][mobile2]" id="mobile2_0" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="email_0" class="form-label">Email</label>
                        <input type="email" name="contacts[0][email]" id="email_0" class="form-control" required>
                    </div>
                    <div class="col-md-10">
                        <label for="remark_0" class="form-label">Remark</label>
                        <textarea name="contacts[0][remark]" id="remark_0" class="form-control"></textarea>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-remove" disabled>Delete</button>
                    </div>
                </div>
            
                
            </div>
        <div class="d-flex justify-content-between mt-4">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <button type="button" id="add-new-field" class="btn btn-primary">Add New Contact</button>
            <button type="submit" class="btn btn-success">Save Account</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.getElementById("accountForm").addEventListener("submit", function(event) {
        event.preventDefault();

        const formData = new FormData(this);
        fetch("", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                alert(data.message);
                // Send WhatsApp message
                fetch("send_whatsapp.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        phone: formData.get("mobile"),
                        message: `Dear ${formData.get("account_name")}, your account has been successfully created. Welcome!`
                    })
                })
                .then(response => response.json())
                .then(whatsappData => {
                    alert(whatsappData.message);
                });
            } else {
                alert(data.message);
            }
        });
    });
</script>



<script>
    document.addEventListener("DOMContentLoaded", function () {
        const dynamicFieldsContainer = document.getElementById('dynamic-fields-container');
        const addNewFieldButton = document.getElementById('add-new-field');
        let fieldCount = 1;

        addNewFieldButton.addEventListener('click', function () {
            const newRow = document.createElement('div');
            newRow.className = 'row g-3 mb-3 contact-row';
            newRow.innerHTML = ` <hr style="border-width:3px">
                <div class="col-md-8">
                    <label for="name_${fieldCount}" class="form-label">Name</label>
                    <input type="text" name="contacts[${fieldCount}][name]" id="name_${fieldCount}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label for="designation_${fieldCount}" class="form-label">Designation</label>
                    <input type="text" name="contacts[${fieldCount}][designation]" id="designation_${fieldCount}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="mobile1_${fieldCount}" class="form-label">Mobile 1</label>
                    <input type="text" name="contacts[${fieldCount}][mobile1]" id="mobile1_${fieldCount}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="mobile2_${fieldCount}" class="form-label">Mobile 2</label>
                    <input type="text" name="contacts[${fieldCount}][mobile2]" id="mobile2_${fieldCount}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="email_${fieldCount}" class="form-label">Email</label>
                    <input type="email" name="contacts[${fieldCount}][email]" id="email_${fieldCount}" class="form-control" required>
                </div>
                
                <div class="col-md-10">
                    <label for="remark_${fieldCount}" class="form-label">Remark</label>
                    <textarea name="contacts[${fieldCount}][remark]" id="remark_${fieldCount}" class="form-control"></textarea>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-remove">Delete</button>
                </div>
            `;
            dynamicFieldsContainer.appendChild(newRow);
            fieldCount++;
        });

        dynamicFieldsContainer.addEventListener('click', function (event) {
            if (event.target.classList.contains('btn-remove')) {
                const row = event.target.closest('.contact-row');
                row.remove();
            }
        });
    });
</script>
</body>
</html>

<?php
$conn->close();
?>
