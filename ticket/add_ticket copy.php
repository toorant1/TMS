<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

// Use the session variable
$master_userid = $_SESSION['master_userid'];

// Initialize variables for form submission feedback
$success_message = "";
$error_message = "";

// Fetch customers for the dropdown with address
$customers_query = "SELECT account.id, account.account_name, account.address 
                    FROM account 
                    WHERE account.master_user_id = ? AND account.status = 1 order by account_name";
$customers_stmt = $conn->prepare($customers_query);
$customers_stmt->bind_param("i", $master_userid);
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



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve submitted form data
    $ticket_id = trim($_POST['ticket_id']);
    $date = trim($_POST['date']);
    $customer_name = trim($_POST['customer_name']);
    $address = trim($_POST['address']);
    $contact_person = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $priority = trim($_POST['priority']);
    $status = trim($_POST['status']);
    $engineer_name = trim($_POST['engineer_name']);

    // Validate inputs
    if (empty($ticket_id) || empty($date) || empty($customer_name) || empty($priority) || empty($status)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Prepare insert query
        $query = "INSERT INTO tickets (ticket_id, date, customer_name, address, contact_person, email, phone, priority, status, engineer_name, master_user_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssi", $ticket_id, $date, $customer_name, $address, $contact_person, $email, $phone, $priority, $status, $engineer_name, $master_userid);

        if ($stmt->execute()) {
            $success_message = "Ticket added successfully.";
        } else {
            $error_message = "Error adding ticket: " . $conn->error;
        }
    }
}
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

    <style>
        body { background-color: #f8f9fa; }
        .form-container { max-width: 700px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .form-title { color: #333; }
        input[readonly], textarea[readonly] {
            background-color: #e9ecef !important;
        }
    </style>
</head>
<body>

<?php include('../headers/header.php'); ?> <!-- Include the header file here -->
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
            <div class="col-md-6">
                <label for="ticket_id" class="form-label">Ticket ID</label>
                <input type="text" id="ticket_id" name="ticket_id" class="form-control" readonly>
            </div>

            <div class="col-md-6">
                <label for="date" class="form-label">Date</label>
                <input type="date" id="date" name="date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
            </div>

            <div class="col-md-12">
                <label for="customer_name" class="form-label">Customer Name</label>
                <select id="customer_name" name="customer_name" class="form-select select2" required>
                    <option value="" disabled selected>Select Customer</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= htmlspecialchars($customer['id']); ?>" data-address="<?= htmlspecialchars($customer['address']); ?>">
                            <?= htmlspecialchars($customer['account_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="priority" class="form-label">Priority</label>
                <select id="priority" name="priority" class="form-select" required>
                    <option value="" disabled selected>Select Priority</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select" required>
                    <option value="" disabled selected>Select Status</option>
                    <option value="Open">Open</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>


            <div class="col-md-12">
                <label for="address" class="form-label">Address</label>
                <textarea id="address" name="address" class="form-control" readonly></textarea>
            </div>
            <div class="col-md-6">
    <label for="contact_person" class="form-label">Contact Person</label>
    <select id="contact_person" name="contact_person" class="form-select select2" required>
        <option value="" disabled selected>Select Contact</option>
        <!-- Existing contacts will be dynamically populated -->
        <option value="add-new-contact" id="addNewContactOption">Add New Contact</option>
    </select>
</div>
<!-- Add New Contact Modal -->
<div class="modal fade" id="addNewContactModal" tabindex="-1" aria-labelledby="addNewContactModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNewContactModalLabel">Add New Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addNewContactForm">
                    <input type="hidden" id="modal_account_id" name="account_id" value=""> <!-- Placeholder for account_id -->

                    
                    <div class="mb-3">
                        <label for="contact_name" class="form-label">Contact Name</label>
                        <input type="text" id="contact_name" name="contact_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="contact_email" class="form-label">Email</label>
                        <input type="email" id="contact_email" name="contact_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Phone</label>
                        <input type="text" id="contact_phone" name="contact_phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="contact_designation" class="form-label">Designation</label>
                        <input type="text" id="contact_designation" name="contact_designation" class="form-control">
                    </div>
                    <button type="button" class="btn btn-primary" id="saveContactBtn">Save Contact</button>
                </form>
            </div>
        </div>
    </div>
</div>



            <div class="col-md-6">
                <label for="designation" class="form-label">Designation</label>
                <input type="text" id="designation" name="designation" class="form-control" readonly>
            </div>

            <div class="col-md-4">
                <label for="phone" class="form-label">Phone - 1</label>
                <input type="text" id="phone" name="phone" class="form-control" readonly>
            </div>

            <div class="col-md-4">
                <label for="phone" class="form-label">Phone- 2</label>
                <input type="text" id="mobile2" name="mobile2" class="form-control" readonly>
            </div>

            <div class="col-md-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" readonly>
            </div>

            <div class="col-md-6">
    <label for="main_cause" class="form-label">Main Problem Category</label>
    <select id="main_cause" name="main_cause" class="form-select" required>
        <option value="" disabled selected>Select Problem Category</option>
        <?php foreach ($main_causes as $cause): ?>
            <option value="<?= htmlspecialchars($cause['id']); ?>">
                <?= htmlspecialchars($cause['main_cause']); ?>
            </option>
        <?php endforeach; ?>
        <option value="add-category" id="addCategoryOption">Add New Category</option>
    </select>
    <script>
    // Apply specific styles to the "Add Category" option
    document.addEventListener('DOMContentLoaded', function () {
        const addCategoryOption = document.querySelector('option[value="add-category"]');
        if (addCategoryOption) {
            addCategoryOption.style.color = 'yellow'; // Yellow text
            addCategoryOption.style.backgroundColor = 'black'; // Black background
        }
    });
</script>
</div>


<!-- Information Modal -->
<div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="infoModalLabel">Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="infoModalBody">
                <!-- Message will be injected here -->
            </div>
            <div class="modal-footer">
                
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add New Problem Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addCategoryForm">
                    <div class="mb-3">
                        <label for="newCategoryName" class="form-label">Category Name</label>
                        <input type="text" id="newCategoryName" name="main_cause" class="form-control" required>
                    </div>
                    <div id="categoryFeedback" class="text-danger"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveCategoryBtn" class="btn btn-primary">Save Category</button>
            </div>
        </div>
    </div>
</div>


            <div class="col-md-12">
                <label for="problem_statement" class="form-label">Problem Statement </label>
                <textarea id="problem_statement" name="problem_statement" class="form-control" ></textarea>
            </div>
           
            

        </div>
        <div class="d-flex justify-content-between mt-4">
            <a href="tickets_dashboard.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Add Ticket</button>
        </div>
    </form>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>

$(document).ready(function () {
    // Handle opening the nested modal
    $('#createContactButton').on('click', function () {
        // Hide the parent modal (infoModal)
        $('#infoModal').modal('hide');

        // Show the nested modal (createContactModal)
        $('#createContactModal').modal('show');
    });

    // Return focus to the parent modal when the nested modal is closed
    $('#createContactModal').on('hidden.bs.modal', function () {
        $('#infoModal').modal('show');
    });

    // Reset the Create Contact Modal form when it's closed
    $('#createContactModal').on('hidden.bs.modal', function () {
        $('#createContactForm')[0].reset();
    });

    // Handle the Save Contact form submission
    $('#createContactForm').on('submit', function (event) {
        event.preventDefault(); // Prevent default form submission

        const formData = $(this).serialize(); // Serialize form data

        $.ajax({
            url: '../api/save_contact.php',
            type: 'POST',
            data: formData,
            success: function (response) {
                if (response.success) {
                    alert('Contact saved successfully!');
                    $('#createContactModal').modal('hide');
                    refreshContactDropdown(response.account_id);
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function (xhr) {
                alert('Failed to save contact. Please check your network and try again.');
                console.error(xhr.responseText);
            },
        });
    });

    // Detect change in the "contact_person" dropdown
    $('#contact_person').change(function () {
        if ($(this).val() === 'add-new-contact') {
            // Open the modal
            $('#addNewContactModal').modal('show');

            // Reset the dropdown selection
            $(this).val('');
        }
    });

    // Handle Save Contact button click
    $('#saveContactBtn').on('click', function () {
    // Collect form data
    const contactData = {
        account_id: $('#modal_account_id').val(), // Ensure this hidden field has the correct account_id
        name: $('#contact_name').val(),
        email: $('#contact_email').val(),
        phone: $('#contact_phone').val(),
        designation: $('#contact_designation').val()
    };

    // Perform AJAX request
    $.ajax({
        url: '../api/save_contact.php', // Correct endpoint
        type: 'POST',
        data: contactData,
        success: function (response) {
            try {
                const data = JSON.parse(response); // Parse response
                if (data.success) {
                    alert('Contact added successfully!'); // Success feedback
                    $('#addNewContactModal').modal('hide'); // Close modal
                    refreshContactDropdown(contactData.account_id); // Refresh dropdown
                } else {
                    alert('Error: ' + data.error); // Display error message
                }
            } catch (error) {
                console.error('JSON parsing error:', error);
                alert('An unexpected error occurred.');
            }
        },
        error: function (xhr) {
            console.error('AJAX Error:', xhr.responseText);
            alert('Failed to save contact. Please check the network and try again.');
        }
    });
});

    // Function to refresh the contact dropdown
    function refreshContactDropdown(accountId) {
        $.ajax({
            url: '../api/fetch_contact_details.php',
            type: 'GET',
            data: { account_id: accountId },
            success: function (response) {
                try {
                    const contacts = JSON.parse(response);
                    const contactSelect = $('#contact_person');

                    // Clear existing options, but keep "Add New Contact"
                    contactSelect.empty().append('<option value="" disabled selected>Select Contact</option>');

                    // Add fetched contacts
                    contacts.forEach(contact => {
                        contactSelect.append(`
                            <option value="${contact.id}" 
                                    data-email="${contact.email}" 
                                    data-phone="${contact.mobile1}" 
                                    data-designation="${contact.designation}" 
                                    data-mobile2="${contact.mobile2}" 
                                    data-remark="${contact.remark}">
                                ${contact.name}
                            </option>
                        `);
                    });

                    // Re-add the "Add New Contact" option
                    contactSelect.append('<option value="add-new-contact" id="addNewContactOption">Add New Contact</option>');
                } catch (error) {
                    console.error('Failed to refresh dropdown:', error);
                }
            },
            error: function () {
                alert('Failed to refresh contact dropdown.');
            },
        });
    }

    // Trigger modal when "Add Category" is selected
    $('#main_cause').change(function () {
        if ($(this).val() === 'add-category') {
            $('#addCategoryModal').modal('show');
            $(this).val(''); // Reset dropdown selection
        }
    });

    // Save new category via AJAX
    $('#saveCategoryBtn').click(function () {
        const newCategory = $('#newCategoryName').val().trim();

        if (!newCategory) {
            $('#categoryFeedback').text('Category name is required.');
            return;
        }

        $.ajax({
            url: '../api/add_main_causes.php',
            type: 'POST',
            data: { main_cause: newCategory },
            success: function (response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Refresh categories dropdown
                        $.ajax({
                            url: '../api/fetch_main_causes.php',
                            type: 'GET',
                            success: function (response) {
                                const categories = JSON.parse(response);
                                const dropdown = $('#main_cause');
                                dropdown.empty().append('<option value="" disabled selected>Select Problem Category</option>');

                                categories.forEach(category => {
                                    dropdown.append(`<option value="${category.id}">${category.main_cause}</option>`);
                                });

                                dropdown.append('<option value="add-category">Add Category</option>');
                                $('#addCategoryModal').modal('hide');
                                $('#newCategoryName').val('');
                                $('#categoryFeedback').text('');
                            },
                            error: function () {
                                alert('Failed to refresh categories.');
                            }
                        });
                    } else {
                        $('#categoryFeedback').text(data.error || 'Failed to add category.');
                    }
                } catch (error) {
                    console.error('Invalid JSON response:', error);
                    $('#categoryFeedback').text('Error processing the request.');
                }
            },
            error: function () {
                $('#categoryFeedback').text('Failed to save the category. Please try again.');
            },
        });
    });

    // Generate ticket ID on page load
    $.ajax({
        url: '../api/generate_ticket_number.php',
        type: 'GET',
        success: function (response) {
            try {
                const data = JSON.parse(response);
                if (data.ticket_id) {
                    $('#ticket_id').val(data.ticket_id);
                } else {
                    alert('Error: Ticket ID generation failed.');
                }
            } catch (error) {
                console.error('Invalid JSON response:', error);
                alert('Error: Unable to generate Ticket ID.');
            }
        },
        error: function () {
            alert('Failed to generate ticket ID. Please reload the page or contact support.');
        }
    });

    $('.select2').select2();

    // Fetch address and contacts when a customer is selected
    $('#customer_name').change(function () {
        const selectedOption = $(this).find(':selected');
        const accountId = selectedOption.val(); // Get the selected account ID
        $('#modal_account_id').val(accountId); // Assign account_id to hidden field
    });


        const accountId = $(this).val(); // account_id
        if (accountId) {
            // Fetch contacts for the selected account_id
            $.ajax({
                url: '../api/fetch_contact_details.php',
                type: 'GET',
                data: { account_id: accountId },
                success: function (response) {
                    try {
                        const contacts = JSON.parse(response);
                        const contactSelect = $('#contact_person');
                        contactSelect.empty().append('<option value="" disabled selected>Select Contact</option>');

                        if (contacts.length > 0) {
                            contacts.forEach(contact => {
                                contactSelect.append(`
                                    <option value="${contact.id}" 
                                            data-email="${contact.email}" 
                                            data-phone="${contact.mobile1}" 
                                            data-designation="${contact.designation}" 
                                            data-mobile2="${contact.mobile2}" 
                                            data-remark="${contact.remark}">
                                        ${contact.name}
                                    </option>
                                `);
                            });
                        } else {
                            // Show message in modal if no contacts are found
                            $('#infoModalBody').text('No contacts found for the selected customer.');
                            $('#infoModal').modal('show');

                            // Pass account ID to the create button
                            $('#createContactBtn').off('click').on('click', function () {
                                window.location.href = `../api/create_contact.php?account_id=${accountId}`;
                            });
                        }

                        // Re-add the "Add New Contact
                        // Re-add the "Add New Contact" option
                        contactSelect.append('<option value="add-new-contact" id="addNewContactOption">Add New Contact</option>');
                    } catch (error) {
                        console.error('Invalid JSON response:', error);
                        $('#infoModalBody').text('Error processing the response. Please try again.');
                        $('#infoModal').modal('show');
                    }
                },
                error: function () {
                    $('#infoModalBody').text('Error fetching contacts. Please try again.');
                    $('#infoModal').modal('show');
                }
            });
        }
    });

    // Update contact details fields when a contact is selected
    $('#contact_person').change(function () {
        const selectedOption = $(this).find(':selected');
        $('#email').val(selectedOption.data('email') || '');
        $('#phone').val(selectedOption.data('phone') || '');
        $('#designation').val(selectedOption.data('designation') || '');
        $('#mobile2').val(selectedOption.data('mobile2') || '');
        $('#remark').val(selectedOption.data('remark') || '');
    });
</script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>
