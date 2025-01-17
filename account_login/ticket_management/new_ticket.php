<?php
require_once '../../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

// Use the session variable
$master_userid = $_SESSION['master_userid'];
$account_id = $_SESSION['account_id'];

// Initialize variables for form submission feedback
$success_message = "";
$error_message = "";

// Fetch customers for the dropdown with address
$customers_query = "SELECT account.id, account.account_name, account.address 
                    FROM account 
                    WHERE account.master_user_id = ? AND account.status = 1 AND account.id = ? limit 1";
$customers_stmt = $conn->prepare($customers_query);
$customers_stmt->bind_param("ii", $master_userid, $account_id);
$customers_stmt->execute();
$customers_result = $customers_stmt->get_result();
$customers = $customers_result->fetch_all(MYSQLI_ASSOC);


// Fetch main causes in ascending order
$causes_query = "SELECT id, main_cause FROM master_tickets_main_causes WHERE master_user_id = ? ORDER BY main_cause ASC";
$causes_stmt = $conn->prepare($causes_query);
$causes_stmt->bind_param("i", $master_userid);
$causes_stmt->execute();
$causes_result = $causes_stmt->get_result();
$main_causes = $causes_result->fetch_all(MYSQLI_ASSOC);

// Fetch priorities (both default and user-specific)
$query_priority = "
    SELECT 
        id, priority 
    FROM 
        master_tickets_priority 
    WHERE 
         master_user_id = ? AND status = 1
    ORDER BY priority asc
";
$stmt_priority = $conn->prepare($query_priority);
$stmt_priority->bind_param("i", $master_userid);
$stmt_priority->execute();
$result_priority = $stmt_priority->get_result();

// Fetch statuses (both default and user-specific)
$query_status = "
    SELECT 
        id, status_name 
    FROM 
        master_tickets_status 
    WHERE 
         master_user_id = ? AND status = 1
    ORDER BY status_name asc
";
$stmt_status = $conn->prepare($query_status);
$stmt_status->bind_param("i", $master_userid);
$stmt_status->execute();
$result_status = $stmt_status->get_result();
// Fetch ticket type
$query = "
    SELECT 
        id, ticket_type 
    FROM 
        master_tickets_types 
    WHERE 
        master_user_id = ?   AND status = 1
    ORDER BY ticket_type asc
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $master_userid);
$stmt->execute();
$result = $stmt->get_result();
$ticket_types = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .form-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            color: #333;
        }

        input[readonly],
        textarea[readonly] {
            background-color: #e9ecef !important;
        }
    </style>
</head>

<body>

    <?php include('../header.php'); ?> <!-- Include the header file here -->
    <div class="form-container">
        <h2 class="form-title text-center mb-4 p-3 bg-primary text-white rounded">Add New Ticket</h2>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"> <?= htmlspecialchars($success_message); ?> </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"> <?= htmlspecialchars($error_message); ?> </div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <div class="row g-3">
                <div hidden class="col-md-6">
                    <label for="ticket_id" class="form-label">Ticket ID</label>
                    <input type="text" id="ticket_id" name="ticket_id" class="form-control" readonly>
                </div>

                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?= date('Y-m-d'); ?>" readonly>
                </div>
                <div class="col-md-3">
                    <label for="ticket_type" class="form-label">Ticket Type</label>
                    <select id="ticket_type" name="ticket_type" class="form-select" required>
                        <option value="" disabled selected>Select Type</option>
                        <?php foreach ($ticket_types as $type): ?>
                            <option value="<?= htmlspecialchars($type['id']); ?>"><?= htmlspecialchars($type['ticket_type']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="priority" class="form-label">Priority</label>
                    <select id="priority" name="priority" class="form-select" required>
                        <option value="" disabled selected>Select Priority</option>
                        <?php while ($row = $result_priority->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['id']); ?>">
                                <?= htmlspecialchars($row['priority']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>



                <div class="col-md-3">
                    <label for="ticket_status" class="form-label">Ticket Status</label> <!-- Changed to 'ticket_status' -->
                    <select id="ticket_status" name="ticket_status" class="form-select" required> <!-- Changed to 'ticket_status' -->
                        <option value="" disabled selected>Select Status</option>
                        <?php while ($row = $result_status->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['id']); ?>">
                                <?= htmlspecialchars($row['status_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <?php
                // Fetch the default customer
                $customer = $customers[0] ?? null;
                $default_customer_name = $customer ? htmlspecialchars($customer['account_name']) : '';
                $default_customer_address = $customer ? htmlspecialchars($customer['address']) : '';
                ?>

                <div class="col-md-6">
                    <label for="customer_name" class="form-label">Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control" value="<?= $default_customer_name; ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <textarea id="address" name="address" class="form-control" readonly tabindex="-1"><?= $default_customer_address; ?></textarea>
                </div>


                <div class="col-md-6">
                    <label for="contact_person" class="form-label">Contact Person</label>
                    <select id="contact_person" name="contact_person" class="form-select select2" required>
                        <option value="" disabled selected>Select Contact</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="designation" class="form-label">Designation</label>
                    <input type="text" id="designation" name="designation" class="form-control" readonly tabindex="-1">
                </div>

                <div class="col-md-3">
                    <label for="phone" class="form-label">Phone - 1</label>
                    <input type="text" id="phone" name="phone" class="form-control" readonly tabindex="-1">
                </div>

                <div class="col-md-3">
                    <label for="phone" class="form-label">Phone- 2</label>
                    <input type="text" id="mobile2" name="mobile2" class="form-control" readonly tabindex="-1">
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" readonly tabindex="-1">
                </div>

                <div class="col-md-4">
                    <label for="main_cause" class="form-label">Main Problem </label>
                    <select id="main_cause" name="main_cause" class="form-select" required>
                        <option value="" disabled selected>Select Problem Category</option>
                        <?php foreach ($main_causes as $cause): ?>
                            <option value="<?= htmlspecialchars($cause['id']); ?>">
                                <?= htmlspecialchars($cause['main_cause']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="col-md-8">
                    <label for="problem_statement" class="form-label">Problem Statement </label>
                    <textarea id="problem_statement" name="problem_statement" class="form-control"></textarea>
                </div>



            </div>
            <div class="d-flex justify-content-between mt-4">
                <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Ticket Data</button>
            </div>
        </form>
    </div>





    <?php
    // Close database connection
    $conn->close();
    ?>


    <script>
        $(document).ready(function() {
            // Initialize Select2 for Customer Name and Contact Person


            $(document).ready(function() {
                // Function to fetch contacts for a given account ID
                function fetchContacts(accountId) {
                    // Clear fields for better clarity
                    $('#designation').val('');
                    $('#phone').val('');
                    $('#mobile2').val('');
                    $('#email').val('');

                    if (accountId) {
                        $.ajax({
                            url: '../../ticket/get_contacts.php', // Path to backend script
                            type: 'POST',
                            data: {
                                account_id: accountId
                            },
                            success: function(response) {
                                const res = JSON.parse(response);
                                const contactDropdown = $('#contact_person');

                                if (res.status === 'success') {
                                    // Clear existing options
                                    contactDropdown.empty();

                                    // Add placeholder option
                                    contactDropdown.append(new Option('Select Contact', '', true, false));

                                    // Populate new options
                                    res.contacts.forEach(contact => {
                                        const option = new Option(contact.text, contact.id, false, false);
                                        $(option).data('details', contact); // Store additional details
                                        contactDropdown.append(option);
                                    });

                                    // Trigger change event
                                    contactDropdown.trigger('change');
                                } else {
                                    alert('Error fetching contacts: ' + res.message);
                                }
                            },
                            error: function() {
                                alert('An error occurred while fetching contacts.');
                            }
                        });
                    }
                }

                // Trigger on page load using default customer ID
                const defaultCustomerId = <?= json_encode($customer['id'] ?? null); ?>;
                if (defaultCustomerId) {
                    fetchContacts(defaultCustomerId);
                }

            });

            // Populate additional contact details on selection
            $('#contact_person').on('change', function() {
                const selectedContact = $(this).find('option:selected').data('details');

                if (selectedContact) {
                    $('#designation').val(selectedContact.designation || '');
                    $('#phone').val(selectedContact.mobile1 || '');
                    $('#mobile2').val(selectedContact.mobile2 || '');
                    $('#email').val(selectedContact.email || '');
                }
            });

        });

        document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const saveButton = document.querySelector('button[type="submit"]');
    
    // Disable the save button and change the text
    saveButton.disabled = true;
    saveButton.textContent = "Saving...";

    fetch('save_ticket.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            // Check if the response status is OK (e.g., 200-299)
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            // Parse the JSON response
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Show a success alert with the ticket ID
                alert(`${data.message} Ticket ID: ${data.ticket_id}`);

                // Redirect the user to the dashboard
                window.location.href = '../dashboard.php';
            } else {
                // Show an error alert if the server responded with an error status
                alert(`Error: ${data.message}`);
            }
        })
        .catch(error => {
            // Log the error for debugging purposes
            console.error('Error:', error);

            // Provide user feedback for unexpected errors
            alert('An unexpected error occurred to send email . Redirecting to dashboard.');
            window.location.href = '../dashboard.php';
        })
        .finally(() => {
            // Re-enable the save button and reset the text (if not redirected)
            saveButton.disabled = false;
            saveButton.textContent = "Save Ticket Data";
        });
});
    </script>