<?php
require_once '../database/db_connection.php';
session_start();

$master_user_id = $_SESSION['master_userid']; // Get the logged-in user's master_user_id

// Fetch suppliers linked to the master user
$supplierQuery = "SELECT id, account_name FROM account WHERE account_type = 'Supplier' AND master_user_id = ?";
$stmt = mysqli_prepare($conn, $supplierQuery);
mysqli_stmt_bind_param($stmt, "i", $master_user_id);
mysqli_stmt_execute($stmt);
$supplierResult = mysqli_stmt_get_result($stmt);


// Fetch companies linked to the master user
$companyQuery = "SELECT id, company_name FROM master_company WHERE master_userid = ? order by company_name asc";
$companyStmt = mysqli_prepare($conn, $companyQuery);
mysqli_stmt_bind_param($companyStmt, "i", $master_user_id);
mysqli_stmt_execute($companyStmt);
$companyResult = mysqli_stmt_get_result($companyStmt);


?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Purchase Order</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

    <link rel="stylesheet" href="po_styles.css"> <!-- Common Stylesheet -->

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

</head>

<body>

    <?php include('../headers/header.php'); ?>

    <div class="d-flex">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?>

        <!-- Main Content -->
        <div class="content w-100">
            <div class="container mt-4">
                <h4 class="text-center mb-3" id="po_header">Add New Purchase Order</h4>


                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success text-center"><?= $success_message ?></div>
                <?php elseif (isset($error_message)): ?>
                    <div class="alert alert-danger text-center"><?= $error_message ?></div>
                <?php endif; ?>

                <form id="purchase_order_form">
                    <div class="row">
                        <!-- Supplier Details Card -->
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary bg-gradient text-white d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Supplier Details</h6>
                                    <button type="button" class="btn btn-sm btn-light" id="refresh_supplier">Refresh</button>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong class="form-label">Supplier Name</strong>
                                        <select class="form-control select2" name="supplier_id" id="supplier_id" required>
                                            <option value="">Select Supplier</option>
                                            <?php while ($row = mysqli_fetch_assoc($supplierResult)) : ?>
                                                <option value="<?= $row['id']; ?>"><?= htmlspecialchars($row['account_name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>

                                    </div>
                                    <div class="mb-2">
                                        <textarea class="form-control bg-light" name="supplier_address" rows="2" placeholder="Address" readonly tabindex="-1"></textarea>
                                    </div>

                                    <div class="mb-2">
                                        <strong class="form-label">Contact Name</strong>

                                        <select class="form-control select2" name="contact_name" required>
                                            <option value="">Select Contact</option>
                                            <option value="Contact 1">Contact 1</option>
                                            <option value="Contact 2">Contact 2</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <input type="text" class="form-control bg-light" name="mobile_no" placeholder="Mobile No / Email ID " readonly tabindex="-1">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Purchase Order Details Card -->
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary bg-gradient text-white">
                                    <h6 class="mb-0">Purchase Order Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <label class="form-label">Select Company</label>
                                        <select class="form-control" name="company_id" id="company_id" required>
                                            <option value="">Select Company</option>
                                            <?php
                                            mysqli_data_seek($companyResult, 0); // Reset pointer before looping
                                            while ($row = mysqli_fetch_assoc($companyResult)) : ?>
                                                <option value="<?= htmlspecialchars($row['id']); ?>">
                                                    <?= htmlspecialchars($row['company_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>

                                    </div>



                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <input type="text" class="form-control bg-light" name="po_number" placeholder="PO Number - Auto-generated" readonly tabindex="-1">
                                        </div>

                                        <div class="col-md-6 mb-2">
                                            <input type="date" class="form-control bg-light" name="po_date" readonly tabindex="-1">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <input type="text" class="form-control" name="quotation_no" placeholder="Quotation No" required>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <input type="date" class="form-control" name="quotation_date" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <select class="form-control" name="inco_term" required>
                                                <option value="">Select Inco Term</option>
                                                <option value="EXW">EXW: Ex Works</option>
                                                <option value="FCA">FCA: Free Carrier</option>
                                                <option value="FAS">FAS: Free Alongside Ship</option>
                                                <option value="FOB">FOB: Free On Board</option>
                                                <option value="CFR">CFR: Cost and Freight</option>
                                                <option value="CIF">CIF: Cost, Insurance and Freight</option>
                                                <option value="CPT">CPT: Carriage Paid to</option>
                                                <option value="CIP">CIP: Carriage and Insurance Paid to</option>
                                                <option value="DAP">DAP: Delivered at Place</option>
                                                <option value="DPU">DPU: Delivered at Place Unloaded</option>
                                                <option value="DDP">DDP: Delivered Duty Paid</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <input type="text" class="form-control" name="inco_term_remark" placeholder="Inco Term Remark">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <input type="text" class="form-control" name="payment_term" placeholder="Payment Term" required>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <input type="text" class="form-control" name="payment_term_remark" placeholder="Payment Term Remark">
                                        </div>
                                    </div>


                                    <!-- Uploaded Files List -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <ul id="uploadedFilesList" class="list-group mt-2"></ul>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fourth Card (Displays Selected Materials) -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-success bg-gradient text-white">
                                    <h6 class="mb-0">Selected Materials for PO</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Material ID</th>
                                                <th>Material Name</th>
                                                <th>Make</th>
                                                <th>HSN/SAC</th>
                                                <th>Quantity</th>
                                                <th>Unit</th>
                                                <th>Unit Price</th>
                                                <th>Total</th>
                                                <th>GST%</th>
                                                <th>GST Total</th>
                                                <th>Grand Total</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="selected_materials_table">
                                            <!-- Selected materials will be appended here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>




                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-secondary bg-gradient text-white">
                                    <h6 class="mb-0">Materials Details</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Row 1 -->
                                    <div class="row">
                                        <div class="col-md-8 mb-2">
                                            <label class="form-label">Material Name</label>
                                            <select class="form-control select2" name="material_name">
                                                <option value="">Select Material</option>
                                                <option value="Material 1">Material 1</option>
                                                <option value="Material 2">Material 2</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label">Make</label>
                                            <input type="text" class="form-control bg-light" name="make" placeholder="Make" readonly tabindex="-1">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label">HSN/SAC</label>
                                            <input type="text" class="form-control bg-light" name="hsn_sac" placeholder="HSN/SAC" readonly tabindex="-1">
                                        </div>
                                    </div>

                                    <!-- Row 2 (All in the same row) -->
                                    <div class="row">
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label">Quantity</label>
                                            <input type="number" class="form-control" min="0" name="quantity" placeholder="Quantity">
                                        </div>
                                        <div class="col-md-1 mb-2">
                                            <label class="form-label">Unit</label>
                                            <input type="text" class="form-control bg-light" name="unit" placeholder="Unit" readonly tabindex="-1">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label">Unit Price</label>
                                            <input type="number" class="form-control" name="unit_price" min="0" placeholder="Unit Price">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label">Total</label>
                                            <input type="number" class="form-control bg-light" name="total" placeholder="Total" readonly tabindex="-1">
                                        </div>
                                        <div class="col-md-1 mb-2">
                                            <label class="form-label">GST%</label>
                                            <input type="number" class="form-control bg-light" name="gst_percentage" placeholder="GST%" readonly tabindex="-1">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label">GST Total</label>
                                            <input type="number" class="form-control bg-light" name="gst_total" placeholder="GST Total" readonly tabindex="-1">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label">Grand Total</label>
                                            <input type="number" class="form-control bg-light" name="grand_total" placeholder="Grand Total" readonly tabindex="-1">
                                        </div>
                                    </div>


                                    <!-- Row 3 -->
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Material Description </label>
                                            <input type="text" class="form-control" name="remark" placeholder="Remark">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Special Remark</label>
                                            <input type="text" class="form-control" name="special_remark" placeholder="Remark">
                                        </div>
                                    </div>
                                    <!-- Button to Add Material -->
                                    <button type="button" class="btn btn-success mt-3" id="add_material_btn">Select Material for PO</button>

                                </div>

                            </div>

                        </div>

                    </div>



                    <!-- Terms and Conditions Card -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-info bg-gradient text-black">
                                    <h6 class="mb-0">Terms and Conditions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">General Terms</label>
                                        <textarea class="form-control" name="general_terms" rows="3" placeholder="Enter general terms and conditions"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Payment Terms</label>
                                        <textarea class="form-control" name="payment_terms" rows="3" placeholder="Enter payment terms"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Delivery Terms</label>
                                        <textarea class="form-control" name="delivery_terms" rows="3" placeholder="Enter delivery terms"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Additional Notes</label>
                                        <textarea class="form-control" name="additional_notes" rows="3" placeholder="Any additional information"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Personal Notes</label>
                                        <textarea class="form-control" name="personal_notes" rows="3" placeholder="Any additional information, this will not shown to supplier"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Buttons Section -->
                    <!-- Submit Button (Ensure ID is correct) -->
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="submit" id="add_purchase_order_btn" class="btn btn-primary px-4">Add Purchase Order</button>
                        <a href="dashboard.php" class="btn btn-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editMaterialId">
                    <div class="mb-2">
                        <label>Quantity</label>
                        <input type="number" id="editQuantity" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Unit Price</label>
                        <input type="number" id="editUnitPrice" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Material Description</label>
                        <input type="text" id="editDescription" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label>Special Remark</label> <!-- ✅ Fixed Special Remark -->
                        <input type="text" id="editSpecialRemark" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveEdit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                placeholder: "Select an option",
                allowClear: true
            });

            // Refresh Supplier List
            $('#refresh_supplier').click(function() {
                $.ajax({
                    url: 'fetch_suppliers.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        var supplierDropdown = $('#supplier_id');
                        supplierDropdown.empty().append('<option value="">Select Supplier</option>');
                        $.each(response, function(index, supplier) {
                            supplierDropdown.append('<option value="' + supplier.id + '">' + supplier.account_name + '</option>');
                        });
                        supplierDropdown.select2();
                    }
                });
            });
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#supplier_id').change(function() {
                var supplier_id = $(this).val(); // Get selected supplier ID

                if (supplier_id) {
                    $.ajax({
                        url: 'fetch_supplier_details.php',
                        type: 'POST',
                        data: {
                            supplier_id: supplier_id
                        },
                        dataType: 'json',
                        success: function(response) {
                            // Fill the address field
                            $('textarea[name="supplier_address"]').val(response.address);

                            // Populate the contact person dropdown
                            var contactDropdown = $('select[name="contact_name"]');
                            contactDropdown.empty().append('<option value="">Select Contact</option>');

                            $.each(response.contacts, function(index, contact) {
                                contactDropdown.append('<option value="' + contact.id + '">' + contact.name + ' (' + contact.mobile1 + ')</option>');
                            });

                            // Re-initialize Select2 for new data
                            contactDropdown.select2();
                        }
                    });
                } else {
                    $('textarea[name="supplier_address"]').val('');
                    $('select[name="contact_name"]').empty().append('<option value="">Select Contact</option>').select2();
                    $('input[name="mobile_no"]').val(''); // Clear mobile field
                }
            });

            // Fetch contact details when a contact is selected
            $('select[name="contact_name"]').change(function() {
                var contact_id = $(this).val();

                if (contact_id) {
                    $.ajax({
                        url: 'fetch_contact_details.php',
                        type: 'POST',
                        data: {
                            contact_id: contact_id
                        },
                        dataType: 'json',
                        success: function(response) {
                            // Populate mobile field with mobile1 | mobile2 | email_id
                            var contactDetails = response.mobile1 + ' | ' + response.mobile2 + ' | ' + response.email;
                            $('input[name="mobile_no"]').val(contactDetails);
                        }
                    });
                } else {
                    $('input[name="mobile_no"]').val(''); // Clear field if no contact selected
                }
            });
        });
    </script>


    <script>
        $(document).ready(function() {
            // Set default date to today for PO Date and Quotation Date
            var today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format
            $('input[name="po_date"]').val(today);
            $('input[name="quotation_date"]').val(today);

            // Select2 Initialization
            $('.select2').select2({
                width: '100%',
                placeholder: "Select an option",
                allowClear: true
            });
        });
    </script>


    <script>
        $(document).ready(function() {
            // Fetch Materials Based on Master User ID
            $.ajax({
                url: 'fetch_materials.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var materialDropdown = $('select[name="material_name"]');
                    materialDropdown.empty().append('<option value="">Select Material</option>');
                    $.each(response, function(index, material) {
                        materialDropdown.append('<option value="' + material.id + '">' + material.name + '</option>');
                    });
                    materialDropdown.select2();
                }
            });

            // Fetch Material Details on Selection
            $('select[name="material_name"]').change(function() {
                var material_id = $(this).val();
                if (material_id) {
                    $.ajax({
                        url: 'fetch_material_details.php',
                        type: 'POST',
                        data: {
                            material_id: material_id
                        },
                        dataType: 'json',
                        success: function(response) {
                            $('input[name="make"]').val(response.make);
                            $('input[name="hsn_sac"]').val(response.hsn_code);
                            $('input[name="unit"]').val(response.unit);
                            $('input[name="gst_percentage"]').val(response.hsn_percentage);
                        }
                    });
                } else {
                    $('input[name="make"]').val('');
                    $('input[name="hsn_sac"]').val('');
                    $('input[name="unit"]').val('');
                    $('input[name="gst_percentage"]').val('');
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Function to calculate totals
            function calculateTotals() {
                var quantity = parseFloat($('input[name="quantity"]').val()) || 0;
                var unitPrice = parseFloat($('input[name="unit_price"]').val()) || 0;
                var gstPercentage = parseFloat($('input[name="gst_percentage"]').val()) || 0;

                var total = quantity * unitPrice;
                var gstTotal = (total * gstPercentage) / 100;
                var grandTotal = total + gstTotal;

                $('input[name="total"]').val(total.toFixed(2));
                $('input[name="gst_total"]').val(gstTotal.toFixed(2));
                $('input[name="grand_total"]').val(grandTotal.toFixed(2));
            }

            // Trigger calculations on input changes
            $('input[name="quantity"], input[name="unit_price"], input[name="gst_percentage"]').on('input', calculateTotals);
        });
    </script>


    <!-- JavaScript -->
    <script>
        $(document).ready(function() {
            let selectedMaterials = [];

            // Function to calculate totals for individual material entry
            function calculateTotals() {
                let quantity = parseFloat($('input[name="quantity"]').val()) || 0;
                let unitPrice = parseFloat($('input[name="unit_price"]').val()) || 0;
                let gstPercentage = parseFloat($('input[name="gst_percentage"]').val()) || 0;

                let total = quantity * unitPrice;
                let gstTotal = (total * gstPercentage) / 100;
                let grandTotal = total + gstTotal;

                $('input[name="total"]').val(total.toFixed(2));
                $('input[name="gst_total"]').val(gstTotal.toFixed(2));
                $('input[name="grand_total"]').val(grandTotal.toFixed(2));
            }

            // Add Material to the Table
            $('#add_material_btn').on('click', function(event) {
                event.preventDefault();

                let materialId = $('select[name="material_name"]').val();
                let materialName = $('select[name="material_name"] option:selected').text();
                let make = $('input[name="make"]').val();
                let hsnSac = $('input[name="hsn_sac"]').val();
                let quantity = $('input[name="quantity"]').val();
                let unit = $('input[name="unit"]').val();
                let unitPrice = $('input[name="unit_price"]').val();
                let total = $('input[name="total"]').val();
                let gstPercentage = $('input[name="gst_percentage"]').val();
                let gstTotal = $('input[name="gst_total"]').val();
                let grandTotal = $('input[name="grand_total"]').val();
                let materialDescription = $('input[name="remark"]').val(); // ✅ Material Description
                let specialRemark = $('input[name="special_remark"]').val(); // ✅ Special Remark

                if (!materialId || !quantity || !unitPrice) {
                    alert("Please select a material and fill required fields before adding.");
                    return;
                }

                let newRow = `
<tr data-material-id="${materialId}">
    <td>${materialId}</td>
    <td>${materialName}</td>
    <td>${make}</td>
    <td>${hsnSac}</td>
    <td>${quantity}</td>
    <td>${unit}</td>
    <td class="text-end">${unitPrice}</td>
    <td class="text-end">${total}</td>
    <td>${gstPercentage}%</td>
    <td class="text-end">${gstTotal}</td>
    <td class="text-end">${grandTotal}</td>
    <td style="display:none;">
        <input type="hidden" name="material_description[]" value="${materialDescription}">
    </td>
    <td style="display:none;">
        <input type="hidden" name="special_remark[]" value="${specialRemark}">
    </td>
   <td>
    <div class="btn-group">
        <button class="btn btn-warning btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            Actions
        </button>
        <ul class="dropdown-menu">
            <li>
                <a class="dropdown-item edit-btn" href="#">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item delete-btn text-danger" href="#">
                    <i class="bi bi-trash"></i> Delete
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item move-up" href="#">⬆ Move Up</a>
            </li>
            <li>
                <a class="dropdown-item move-down" href="#">⬇ Move Down</a>
            </li>
        </ul>
    </div>
</td>

</tr>`;


                $('#selected_materials_table').append(newRow);

                selectedMaterials.push({
                    materialId,
                    materialName,
                    make,
                    hsnSac,
                    quantity,
                    unit,
                    unitPrice,
                    total,
                    gstPercentage,
                    gstTotal,
                    grandTotal,
                    materialDescription, // ✅ Stored but not displayed in the table
                    specialRemark // ✅ Stored but not displayed in the table
                });

                // Clear input fields after adding
                $('select[name="material_name"]').val('').trigger('change');
                $('input[name="make"], input[name="hsn_sac"], input[name="unit"], input[name="quantity"], input[name="unit_price"], input[name="total"], input[name="gst_percentage"], input[name="gst_total"], input[name="grand_total"], input[name="remark"], input[name="special_remark"]').val('');
            });

            // Open Edit Modal on Clicking Edit Button
            $(document).on('click', '.edit-btn', function() {
                let row = $(this).closest('tr');
                let materialId = row.find('td:eq(0)').text().trim();

                // Find corresponding material from array
                let materialData = selectedMaterials.find(material => material.materialId === materialId);

                if (materialData) {
                    $('#editMaterialId').val(materialData.materialId);
                    $('#editQuantity').val(materialData.quantity);
                    $('#editUnitPrice').val(materialData.unitPrice);
                    $('#editDescription').val(materialData.materialDescription);
                    $('#editSpecialRemark').val(materialData.specialRemark); // ✅ Fixed this to populate correctly
                }

                $('#editModal').data('row', row).modal('show');
            });

            // Save Edited Values
            $('#saveEdit').on('click', function() {
                let row = $('#editModal').data('row');
                let materialId = $('#editMaterialId').val();
                let quantity = $('#editQuantity').val();
                let unitPrice = $('#editUnitPrice').val();
                let description = $('#editDescription').val();
                let specialRemark = $('#editSpecialRemark').val(); // ✅ Now correctly fetched and saved

                if (!quantity || !unitPrice) {
                    alert("Quantity and Unit Price cannot be empty.");
                    return;
                }

                let total = quantity * unitPrice;
                let gstPercentage = parseFloat(row.find('td:eq(8)').text());
                let gstTotal = (total * gstPercentage) / 100;
                let grandTotal = total + gstTotal;

                // Update table values dynamically
                row.find('td:eq(4)').text(quantity);
                row.find('td:eq(6)').text(unitPrice);
                row.find('td:eq(7)').text(total.toFixed(2));
                row.find('td:eq(9)').text(gstTotal.toFixed(2));
                row.find('td:eq(10)').text(grandTotal.toFixed(2));

                // Update values in selectedMaterials array
                selectedMaterials = selectedMaterials.map(material => {
                    if (material.materialId === materialId) {
                        return {
                            ...material,
                            quantity,
                            unitPrice,
                            total,
                            gstTotal,
                            grandTotal,
                            materialDescription: description, // ✅ Updated correctly
                            specialRemark: specialRemark // ✅ Updated correctly
                        };
                    }
                    return material;
                });

                $('#editModal').modal('hide');
            });
        });




        $(document).ready(function() {
            let selectedMaterials = [];

            // Move row UP
            $(document).on("click", ".move-up", function() {
                let row = $(this).closest("tr");
                if (row.prev().length) {
                    row.insertBefore(row.prev());
                    updateArrayOrder();
                }
            });

            // Move row DOWN
            $(document).on("click", ".move-down", function() {
                let row = $(this).closest("tr");
                if (row.next().length) {
                    row.insertAfter(row.next());
                    updateArrayOrder();
                }
            });

            // DELETE Row from Table and Remove from Array
            $(document).on("click", ".delete-btn", function() {
                let row = $(this).closest("tr");
                let materialId = row.data("material-id");

                // Remove row from DOM
                row.remove();

                // Remove material from array
                selectedMaterials = selectedMaterials.filter(material => material.materialId != materialId);

                console.log("Deleted:", materialId);
                console.log("Updated Array:", selectedMaterials); // Debugging

                updateArrayOrder(); // Ensure order remains correct
            });
            // Function to update the selectedMaterials array order based on table row position
            function updateArrayOrder() {
                let newOrder = [];
                $("#selected_materials_table tr").each(function() {
                    let materialId = $(this).data("material-id");
                    let material = selectedMaterials.find(item => item.materialId == materialId);
                    if (material) {
                        newOrder.push(material);
                    }
                });
                selectedMaterials = newOrder; // Update array with new order
                console.log("Updated Array Order:", selectedMaterials); // Debugging
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#company_id').change(function() {
                var companyName = $("#company_id option:selected").text().toUpperCase();
                if (companyName) {
                    $("#po_header").html('Generate New Purchase Order for - <span style="color: red;">' + companyName + '</span>');
                } else {
                    $("#po_header").text("Generate New Purchase Order"); // Reset if no selection
                }
            });
        });
    </script>


    <script>
        $(document).ready(function() {
    $("#add_purchase_order_btn").click(function(e) {
        e.preventDefault(); // Prevent default form submission

        // Check if the materials table has any entries
        if ($("#selected_materials_table tr").length === 0) {
            alert("❌ Error: You must add at least one material before submitting the Purchase Order.");
            return; // Stop further execution
        }

        let formData = new FormData($("#purchase_order_form")[0]);

        // Collect materials from the selected table
        let materials = [];
        $("#selected_materials_table tr").each(function() {
            let row = $(this);
            let material = {
                material_id: row.find("td:eq(0)").text().trim(),
                make: row.find("td:eq(2)").text().trim(),
                hsn_sac: row.find("td:eq(3)").text().trim(),
                quantity: row.find("td:eq(4)").text().trim(),
                unit: row.find("td:eq(5)").text().trim(),
                unit_price: row.find("td:eq(6)").text().trim(),
                total: row.find("td:eq(7)").text().trim(),
                gst_percentage: row.find("td:eq(8)").text().trim(),
                gst_total: row.find("td:eq(9)").text().trim(),
                grand_total: row.find("td:eq(10)").text().trim(),
                material_description: row.find("input[name='material_description[]']").val(),
                special_remark: row.find("input[name='special_remark[]']").val()
            };
            materials.push(material);
        });

        // Convert array to JSON and append to FormData
        formData.append("materials", JSON.stringify(materials));

        $.ajax({
            url: "purchase_order_insert.php", // Server-side script
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            beforeSend: function() {
                $("#add_purchase_order_btn").prop("disabled", true).text("Processing...");
            },
            success: function(response) {
                if (response.status === "success") {
                    alert("✅ " + response.message);
                    window.location.href = "purchase_orders.php"; // Redirect on success
                } else {
                    alert("❌ Error: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert("❌ AJAX Error: " + xhr.responseText);
            },
            complete: function() {
                $("#add_purchase_order_btn").prop("disabled", false).text("Add Purchase Order");
            }
        });
    });
});
    </script>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>








</body>

</html>