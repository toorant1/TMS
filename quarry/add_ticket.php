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



// Fetch materials from the database
$materials_query = "SELECT id, name as material_name FROM master_materials WHERE master_user_id = ? ORDER BY material_name ASC";
$materials_stmt = $conn->prepare($materials_query);
$materials_stmt->bind_param("i", $master_userid);
$materials_stmt->execute();
$materials_result = $materials_stmt->get_result();
$materials = $materials_result->fetch_all(MYSQLI_ASSOC);





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
            margin-top: 100px;
            background-color: #f8f9fa;
        }

        .form-container {
            max-width: 700px;
            margin: 10px auto;
            padding: 10px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-style: dashed;
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

    <?php include('../headers/header.php'); ?> <!-- Include the header file here -->
    <div class="form-container">
        <h2 class="form-title text-center mb-4 p-3 bg-primary text-white rounded">Dispatch - Gate Pass Entry</h2>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"> <?= htmlspecialchars($success_message); ?> </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"> <?= htmlspecialchars($error_message); ?> </div>
        <?php endif; ?>

        <form method="POST" class="needs-validation p-4 bg-white shadow-sm rounded" novalidate>
            <div class="row g-3">

                <!-- Hidden Ticket ID -->
                <div hidden class="col-md-6">
                    <label for="ticket_id" class="form-label fw-bold">Ticket ID</label>
                    <input type="text" id="ticket_id" name="ticket_id" class="form-control" readonly>
                </div>


                <div class="col-md-6">
            <label for="company_name" class="form-label fw-bold">Company Name</label>
            <select id="company_name" name="company_name" class="form-select select2" required>
                <option value="" disabled selected>Select Company</option>
                <?php
                $companies_query = "SELECT id, company_name FROM master_company WHERE master_userid = ? ORDER BY company_name ASC";
                $companies_stmt = $conn->prepare($companies_query);
                $companies_stmt->bind_param("i", $master_userid);
                $companies_stmt->execute();
                $companies_result = $companies_stmt->get_result();
                while ($company = $companies_result->fetch_assoc()):
                ?>
                    <option value="<?= htmlspecialchars($company['id']); ?>">
                        <?= htmlspecialchars($company['company_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>


                <!-- Basic Details -->
                <div class="col-md-12">
                    <h5 class="fw-bold text-primary mt-3">Basic Information</h5>
                </div>

                <!-- Date -->
                <div class="col-md-6">
                    <label for="date" class="form-label fw-bold">Date</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Delivery Challan No </label>
                    <input type="text" name="delivery_challan" class="form-control">
                </div>
                <!-- Customer Name -->
                <div class="col-md-8">
                    <label for="customer_name" class="form-label fw-bold">Customer Name</label>
                    <select id="customer_name" name="customer_name" class="form-select select2" required>
                        <option value="" disabled selected>Select Customer</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= htmlspecialchars($customer['id']); ?>"
                                data-address="<?= htmlspecialchars($customer['address']); ?>">
                                <?= htmlspecialchars($customer['account_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Customer Site Name Text Box -->
                <div class="col-md-4">
                    <label for="customer_site" class="form-label fw-bold">Customer Site Name</label>
                    <input type="text" id="customer_site" name="customer_site" class="form-control" placeholder="Enter Customer Site Name" required>
                </div>



                <!-- Address -->
                <div class="col-md-12">
                    <textarea id="address" name="address" class="form-control" readonly tabindex="-1"></textarea>
                </div>

                <!-- Contact Person -->
                <div class="col-md-8">
                    <select id="contact_person" name="contact_person" class="form-select select2" required>
                        <option value="" disabled selected>Select Contact</option>
                        <option value="add-new-contact">Add New Contact</option>
                    </select>
                </div>

                <!-- Phone Number -->
                <div class="col-md-4">
                    <input type="text" id="phone" name="phone" class="form-control" readonly tabindex="-1">
                </div>





                <!-- Material Details -->
                <div class="col-md-12">
                    <h5 class="fw-bold text-primary mt-3">Material Information</h5>
                </div>
                <div class="col-md-12">
                    <label for="material_name" class="form-label fw-bold">Material Name</label>
                    <select id="material_name" name="material_name" class="form-select select2" required>
                        <option value="" disabled selected>Select Material</option>
                        <?php foreach ($materials as $material): ?>
                            <option value="<?= htmlspecialchars($material['id']); ?>">
                                <?= htmlspecialchars($material['material_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <script>
                    $(document).ready(function() {
                        // Initialize Select2 for Material Name Dropdown
                        $('#material_name').select2({
                            placeholder: "Select Material",
                            allowClear: true,
                            theme: "bootstrap-5",
                            width: '100%'
                        });
                    });
                </script>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Gross WT</label>
                        <input type="number" id="gross_weight" name="gross_weight" class="form-control" step="0.100" placeholder="0.000">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tare WT</label>
                        <input type="number" id="tare_weight" name="tare_weight" class="form-control" step="0.100" placeholder="0.000">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Net WT</label>
                        <input type="text" id="net_weight" name="net_weight" class="form-control" readonly placeholder="0.000">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Royalty WT</label>
                        <input type="number" id="royalty_weight" name="royalty_weight" class="form-control royalty-field" step="0.100" disabled placeholder="0.000">
                    </div>
                </div>

                <script>
                    // Function to calculate Net WT
                    function calculateNetWeight() {
                        const grossWeight = parseFloat(document.getElementById('gross_weight').value) || 0;
                        const tareWeight = parseFloat(document.getElementById('tare_weight').value) || 0;

                        // Calculate Net Weight with up to 3 decimal places
                        const netWeight = grossWeight - tareWeight;

                        // Update the Net WT field with 3 decimal precision
                        document.getElementById('net_weight').value = netWeight > 0 ? netWeight.toFixed(3) : "0.000";
                    }

                    // Add event listeners to Gross WT and Tare WT fields
                    document.getElementById('gross_weight').addEventListener('blur', () => {
                        setTimeout(calculateNetWeight, 10); // Wait for 10ms before calculating
                    });

                    document.getElementById('tare_weight').addEventListener('blur', () => {
                        setTimeout(calculateNetWeight, 10); // Wait for 10ms before calculating
                    });

                    // Optional: Update dynamically while typing (if needed)
                    document.getElementById('gross_weight').addEventListener('input', calculateNetWeight);
                    document.getElementById('tare_weight').addEventListener('input', calculateNetWeight);
                </script>



                <!-- Material Details -->
                <div class="col-md-12">
                    <h5 class="fw-bold text-primary mt-3">Vehicle Information</h5>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Truck Vehicle No</label>
                    <input type="text" name="vehicle" class="form-control">
                </div>
                <div class="col-md-7">
                    <label class="form-label">Our Loader</label>
                    <input type="text" name="loader" class="form-control">
                </div>






                <div class="col-md-12 d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                    <h5 class="fw-bold text-primary mt-3">Royalty Information</h5>

                    <!-- Toggle Switch -->
                    <label style="position: relative; display: inline-block; width: 150px; height: 50px; cursor: pointer;">
                        <input type="checkbox" checked style="display: none;">
                        <!-- Slider Background -->
                        <span style="
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            border-radius: 50px;
            transition: background-color 0.3s ease;">
                        </span>
                        <!-- Knob -->
                        <span style="
            position: absolute;
            z-index: 2;
            content: '';
            height: 44px;
            width: 44px;
            left: 3px;
            top: 3px;
            background-color: white;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;">
                        </span>
                        <!-- "YES" Text -->
                        <span style="
            position: absolute;
            left: 20px;
            font-size: 16px;
            color: white;
            z-index: 1;
            line-height: 50px;
            opacity: 1;
            transition: opacity 0.3s ease;">
                            YES
                        </span>
                        <!-- "NO" Text -->
                        <span style="
            position: absolute;
            right: 20px;
            font-size: 16px;
            color: darkslategray;
            z-index: 1;
            line-height: 50px;
            opacity: 0;
            transition: opacity 0.3s ease;">
                            NO
                        </span>
                    </label>
                </div>



            </div>

            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Name</label>
                    <input type="text" name="royalty_name" class="form-control royalty-field" disabled>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Pass No</label>
                    <input type="number" name="royalty_pass_no" class="form-control royalty-field" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Pass No Count</label>
                    <input type="number" name="royalty_pass_count" class="form-control royalty-field" disabled>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">SSP No</label>
                    <input type="number" name="ssp_no" class="form-control royalty-field" disabled>
                </div>
            </div>

            <script>
                // JavaScript for Toggle Switch Functionality
                document.querySelector('input[type="checkbox"]').addEventListener('change', function() {
                    const slider = this.nextElementSibling;
                    const knob = slider.nextElementSibling;
                    const yesText = knob.nextElementSibling;
                    const noText = yesText.nextElementSibling;

                    // Get all fields to enable/disable
                    const fields = document.querySelectorAll('.royalty-field');

                    if (this.checked) {
                        slider.style.backgroundColor = "#4CAF50"; // Green background when ON
                        knob.style.transform = "translateX(100px)"; // Knob to the right
                        yesText.style.opacity = "1"; // Show "YES"
                        noText.style.opacity = "0"; // Hide "NO"

                        // Enable all fields
                        fields.forEach(field => {
                            field.disabled = false;
                        });
                    } else {
                        slider.style.backgroundColor = "#ccc"; // Gray background when OFF
                        knob.style.transform = "translateX(0)"; // Knob to the left
                        yesText.style.opacity = "0"; // Hide "YES"
                        noText.style.opacity = "1"; // Show "NO"

                        // Disable all fields
                        fields.forEach(field => {
                            field.disabled = true;
                            field.value = "";
                        });
                    }
                });
            </script>






            <!-- Form Actions -->
            <div class="d-flex justify-content-between mt-4">
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Gate Pass</button>
            </div>
        </form>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>




    <!-- Add Contact Modal -->
    <div class="modal fade" id="addContactModal" tabindex="-1" aria-labelledby="addContactModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addContactForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addContactModalLabel">Add New Contact</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Account Name and ID -->
                        <div class="mb-3">
                            <label for="modal_account_name" class="form-label">Account Name</label>
                            <input type="text" id="modal_account_name" class="form-control" readonly>
                            <input type="hidden" id="modal_account_id" name="account_id" value="">
                        </div>
                        <!-- Contact Name -->
                        <div class="mb-3">
                            <label for="modal_contact_name" class="form-label">Contact Name</label>
                            <input type="text" class="form-control" id="modal_contact_name" name="contact_name" required>
                        </div>
                        <!-- Designation -->
                        <div class="mb-3">
                            <label for="modal_designation" class="form-label">Designation</label>
                            <input type="text" class="form-control" id="modal_designation" name="designation">
                        </div>
                        <!-- Phone Fields in the Same Row -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_phone1" class="form-label">Phone 1</label>
                                <input type="text" class="form-control" id="modal_phone1" name="phone1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_phone2" class="form-label">Phone 2</label>
                                <input type="text" class="form-control" id="modal_phone2" name="phone2">
                            </div>
                        </div>
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="modal_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="modal_email" name="email">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Contact</button>
                    </div>
                </form>
            </div>
        </div>

    </div>


    <?php
    // Close database connection
    $conn->close();
    ?>

    <script>
        $(document).ready(function() {
            $('#addContactForm').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                // Disable the Save Ticket Data button
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.textContent = "Saving..."; // Optional: Change the button text


                $.ajax({
                    url: 'save_contact.php', // Path to the backend script
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        const res = JSON.parse(response);
                        if (res.status === 'success') {
                            alert(res.message); // Show success message

                            // Add the new contact to the dropdown
                            const contactDropdown = $('#contact_person');
                            const newOption = new Option(res.contact.text, res.contact.id, false, true);
                            contactDropdown.append(newOption).trigger('change');

                            // Close the modal and reset the form
                            $('#addContactModal').modal('hide');
                            $('#addContactForm')[0].reset();
                        } else {
                            alert('Error: ' + res.message);
                            submitButton.disabled = false;
                            submitButton.textContent = "Save Ticket Data"; // Reset the button text
                        }
                    },
                    error: function() {
                        alert('An error occurred while adding the contact.');
                        submitButton.disabled = false;
                        submitButton.textContent = "Save Ticket Data"; // Reset the button text
                    },
                });
            });



        });
    </script>


    <script>
        $(document).ready(function() {
            // Show the modal when "Add New Contact" is selected
            $('#contact_person').on('change', function() {
                if ($(this).val() === 'add-new-contact') {
                    // Get selected account name and ID
                    const selectedAccount = $('#customer_name').find('option:selected');
                    const accountName = selectedAccount.text().trim(); // Trim unnecessary spaces
                    const accountId = selectedAccount.val();

                    // Populate the modal with trimmed account name
                    $('#modal_account_name').val(accountName);
                    $('#modal_account_id').val(accountId);

                    // Show the modal
                    $('#addContactModal').modal('show');
                    $(this).val(''); // Reset dropdown selection
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 on the Customer Name dropdown
            $('#customer_name').select2({
                placeholder: "Select Customer",
                allowClear: true,
                theme: "bootstrap-5", // Use the Select2 Bootstrap 5 theme
                width: '100%' // Ensures the dropdown spans the full width
            });

            // Populate the address field dynamically when a customer is selected
            $('#customer_name').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const address = selectedOption.data('address') || '';
                $('#address').val(address);
            });

            // Initialize Select2 for the Contact Person dropdown
            $('#contact_person').select2({
                placeholder: "Select Contact",
                allowClear: true,
                theme: "bootstrap-5", // Use the Select2 Bootstrap 5 theme
                width: '100%' // Ensures the dropdown spans the full width
            });
        });
    </script>


    <script>
        $(document).ready(function() {
            // Initialize Select2 for Customer Name and Contact Person
            $('#customer_name').select2({
                placeholder: "Select Customer",
                allowClear: true,
                theme: "bootstrap-5",
                width: '100%'
            });

            $('#contact_person').select2({
                placeholder: "Select Contact",
                allowClear: true,
                theme: "bootstrap-5",
                width: '100%'
            });

            $(document).ready(function() {
                $('#customer_name').on('change', function() {
                    const customerId = $(this).val();

                    if (customerId) {

                        // Fetch Customer Contacts
                        $.ajax({
                            url: 'get_contacts.php', // Backend script to fetch contacts
                            type: 'POST',
                            data: {
                                account_id: customerId
                            },
                            success: function(response) {
                                const res = JSON.parse(response);
                                const contactDropdown = $('#contact_person');

                                if (res.status === 'success') {
                                    contactDropdown.empty(); // Clear existing options
                                    contactDropdown.append(new Option('Select Contact', '', true, false));

                                    res.contacts.forEach(contact => {
                                        const option = new Option(contact.text, contact.id, false, false);
                                        $(option).data('details', contact); // Store additional details
                                        contactDropdown.append(option);
                                    });

                                    contactDropdown.append(new Option('Add New Contact', 'add-new-contact'));
                                    contactDropdown.trigger('change');

                                    fetchTicketHistory(customerId);
                                } else {
                                    alert('Error fetching contacts: ' + res.message);
                                }
                            },
                            error: function() {
                                alert('An error occurred while fetching contacts.');
                            }
                        });

                        // Clear the fields when a new customer is selected
                        $('#designation, #phone, #mobile2, #email').val('');
                    }
                });
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

            // Show the Add Contact modal when "Add New Contact" is selected
            $('#contact_person').on('change', function() {
                if ($(this).val() === 'add-new-contact') {
                    const selectedAccount = $('#customer_name').find('option:selected');
                    const accountName = selectedAccount.text();
                    const accountId = selectedAccount.val();

                    $('#modal_account_name').val(accountName);
                    $('#modal_account_id').val(accountId);

                    $('#addContactModal').modal('show');
                    $(this).val(''); // Reset dropdown selection
                }
            });
        });


        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');

            // Prevent duplicate submissions
            if (submitButton.disabled) return;

            submitButton.disabled = true;
            submitButton.textContent = "Saving...";

            fetch('save_master_quarry_dispatch_data.php', {
    method: 'POST',
    body: formData,
})
.then(response => response.text()) // Get raw text first
.then(text => {
    try {
        const data = JSON.parse(text); // Try to parse JSON

        if (data.status === 'success') {
            alert(`${data.message} Ticket ID: ${data.ticket_id}`);
            window.location.href = 'dashboard.php';
        } else {
            alert(`Error: ${data.message}`);
            submitButton.disabled = false;
            submitButton.textContent = "Save Ticket Data";
        }
    } catch (error) {
        console.error('Invalid JSON response:', text);
        alert('Unexpected response from the server. Check console.');
        submitButton.disabled = false;
        submitButton.textContent = "Save Ticket Data";
    }
})
.catch(error => {
    console.error('Fetch Error:', error);
    alert('Network error occurred.');
    submitButton.disabled = false;
    submitButton.textContent = "Save Ticket Data";
});

        });
    </script>
    <script>
        $(document).ready(function() {
            $.ajax({
                url: 'get_materials.php',
                type: 'GET',
                success: function(response) {
                    const res = JSON.parse(response);
                    const materialDropdown = $('#material_name');

                    materialDropdown.empty();
                    materialDropdown.append(new Option('Select Material', '', true, false));

                    res.forEach(material => {
                        const option = new Option(material.material_name, material.id, false, false);
                        materialDropdown.append(option);
                    });

                    materialDropdown.trigger('change');
                },
                error: function() {
                    alert("Error fetching materials.");
                }
            });

            // Initialize Select2 for Material Name Dropdown
            $('#material_name').select2({
                placeholder: "Select Material",
                allowClear: true,
                theme: "bootstrap-5",
                width: '100%'
            });
        });
    </script>
</body>