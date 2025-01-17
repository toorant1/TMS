<?php
// Include database connection
require_once '../database/db_connection.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['master_userid']) || empty($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

// Retrieve query parameters and sanitize
$internal_id = isset($_GET['internal_id']) ? filter_var($_GET['internal_id'], FILTER_SANITIZE_STRING) : null;
$token = isset($_GET['token']) ? filter_var($_GET['token'], FILTER_SANITIZE_STRING) : null;
$master_userid = $_SESSION['master_userid'];

// Validate the required parameters
if (is_null($internal_id) || empty($token) || empty($master_userid)) {
    die("Invalid request. Missing or invalid Internal ID, Token, or Master User ID.");
}

// Fetch status options
$statusOptions = [];
$query = "SELECT quotation_status_id, status_name FROM master_quotations_status WHERE status_active_deactive = 1 AND master_user_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $statusOptions[] = $row;
    }
    $stmt->close();
}

// Fetch company options
$companyOptions = [];
$query = "SELECT id, company_name FROM master_company WHERE master_userid = ? ORDER BY company_name ASC";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $master_userid);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $companyOptions[] = $row;
    }
    $stmt->close();
}

// Fetch record details
$query = "
    SELECT 
    m.internal_id, 
    m.account_id,
    a.account_name, 
    m.user_id,
    u.name AS user_name, 
    m.m_date, 
    m.requirement,
    ms.status AS marketing_status
FROM master_marketing m
INNER JOIN account a ON m.account_id = a.id
INNER JOIN master_users u ON m.user_id = u.id
INNER JOIN master_marketing_status ms ON m.marketing_id_status = ms.id
WHERE TRIM(LOWER(m.internal_id)) = TRIM(LOWER(?))
  AND TRIM(LOWER(m.token)) = TRIM(LOWER(?))
  AND m.master_user_id = ?
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query Preparation Failed: " . $conn->error);
}

$stmt->bind_param("ssi", $internal_id, $token, $master_userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No valid record found for the given Internal ID, Token, or Master User ID.");
}

$record = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quotation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        input[readonly] {
            background-color: #e9ecef; /* Light gray background */
            color: #495057; /* Darker text color */
            cursor: not-allowed; /* Indicate non-editable field */
        }
        .header-title {
            font-size: 1.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .info-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <h5 class="text-center">Quotation Management</h5>
    <h1 class="text-center mb-4 header-title">Create Quotation</h1>

    <!-- Basic Details Section -->
    <div class="row">
        <div class="col-md-6">
            <div class="card info-card mb-4">
                <div class="card-header bg-success text-white">Basic Details</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>Internal ID</th><td><?= htmlspecialchars($record['internal_id']); ?></td></tr>
                        <tr><th>Account Name</th><td><?= htmlspecialchars($record['account_name']); ?></td></tr>
                        <tr><th>User Name</th><td><?= htmlspecialchars($record['user_name']); ?></td></tr>
                        <tr><th>Date</th><td><?= htmlspecialchars($record['m_date']); ?></td></tr>
                        <tr><th>Requirement</th><td><?= htmlspecialchars($record['requirement']); ?></td></tr>
                        <tr><th>Marketing Status</th><td><?= htmlspecialchars($record['marketing_status']); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quotation Details Section -->
        <div class="col-md-6">
            <div class="card info-card mb-4">
                <div class="card-header bg-primary text-white">New Quotation Details</div>
                <div class="card-body">
                    <form id="quotationForm">
                        <div class="mb-3">
                            <label for="company_id" class="form-label">Select Company</label>
                            <select class="form-select" id="company_id" name="company_id" required>
                                <option value="">Select a Company</option>
                                <?php foreach ($companyOptions as $company): ?>
                                    <option value="<?= htmlspecialchars($company['id']); ?>">
                                        <?= htmlspecialchars($company['company_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="quotation_date" class="form-label">Quotation Date</label>
                                <input type="date" class="form-control" id="quotation_date" name="quotation_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="quotation_valid_date" class="form-label">Valid Upto</label>
                                <input type="date" class="form-control" id="quotation_valid_date" name="quotation_valid_date" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="status_id" class="form-label">Status</label>
                            <select class="form-select" id="status_id" name="status_id" required>
                                <option value="">Select Status</option>
                                <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?= htmlspecialchars($status['quotation_status_id']); ?>">
                                        <?= htmlspecialchars($status['status_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Materials Entry Section -->
    <div class="card info-card mb-4">
        <div class="card-header bg-success text-white">Materials Details</div>
        <div class="card-body">
            <form id="materialsForm">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="material_name" class="form-label">Material Name</label>
                        <select class="form-select" id="material_name" name="material_name" required></select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="0" required>
                    </div>
                    <div class="col-md-1">
                        <label for="unit_name" class="form-label">Unit</label>
                        <input type="text" class="form-control" id="unit_name" name="unit_name" readonly tabindex="-1">
                    </div>
                    <div class="col-md-2">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" class="form-control" id="price" name="price" min="0">
                    </div>
                    <div class="col-md-1">
                        <label for="basic_total" class="form-label">Basic Total</label>
                        <input type="number" class="form-control" id="basic_total" name="basic_total" readonly tabindex="-1">
                    </div>
                    <div class="col-md-1">
                        <label for="hsn_code" class="form-label">HSN Code</label>
                        <input type="text" class="form-control" id="hsn_code" name="hsn_code" readonly tabindex="-1">
                    </div>
                    <div class="col-md-1">
                        <label for="hsn_percentage" class="form-label">HSN %</label>
                        <input type="number" class="form-control" id="hsn_percentage" name="hsn_percentage" readonly tabindex="-1">
                    </div>
                    <div class="col-md-2">
                        <label for="hsn_total" class="form-label">HSN Total</label>
                        <input type="number" class="form-control" id="hsn_total" name="hsn_total" readonly tabindex="-1">
                    </div>
                    <div class="col-md-2">
                        <label for="grand_total" class="form-label">Grand Total</label>
                        <input type="number" class="form-control" id="grand_total" name="grand_total" readonly tabindex="-1">
                    </div>

                   

                        <div class="col-md-2">
                        <input hidden type="text" class="form-control" id="material_make" name="material_make" readonly tabindex="-1">
                        <input hidden type="text" class="form-control" id="material_type" name="material_type" readonly tabindex="-1">
                    </div>
                </div>

                <div class="row mb-3">
                <div class="col-md-12">
                        <label for="material_remark" class="form-label">Material Remark, if any...</label>
                        <input type="text" class="form-control" id="material_remark" name="material_remark" >
                    </div>
                </div>
                <div class="text-center mb-3">
                    <button type="button" class="btn btn-success" id="addMaterial">Add Material</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Material Modal -->
<div class="modal fade" id="editMaterialModal" tabindex="-1" aria-labelledby="editMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMaterialModalLabel">Edit Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editMaterialForm">
                    <div class="mb-3">
                        <label for="editQuantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="editQuantity" name="editQuantity" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPrice" class="form-label">Price</label>
                        <input type="number" class="form-control" id="editPrice" name="editPrice" min="0" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveMaterialChanges">Save Changes</button>
            </div>
        </div>
    </div>
</div>

    
    <!-- Dynamic Table to Display Materials -->
    <table class="table table-bordered">
    <thead class="table-light">
        <tr>
            <th>Material Type</th>
            <th>Make</th>
            <th>Material Name</th>
            <th>Quantity</th>
            <th>Unit</th>
            <th>Price</th>
            <th>Basic Total</th>
            <th>HSN Code</th>
            <th>HSN %</th>
            <th>HSN Total</th>
            <th>Grand Total</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody id="materialsTableBody">
        <tr><td colspan="14" class="text-center">No materials added.</td></tr>
    </tbody>
    <tfoot id="totalsRow"></tfoot>
    <tfoot class="table-light">
    </tr>
    
</tfoot>

</table>


<!-- Terms and Conditions Section -->
<div class="card info-card mb-4">
    <div class="card-header bg-info text-black">General Terms and Conditions</div>
    <div class="card-body">
        <form id="termsConditionsForm">
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="payment_conditions" class="form-label">Payment Conditions</label>
                    <textarea class="form-control" id="payment_conditions" name="payment_conditions" rows="3" placeholder="Enter Payment Conditions"></textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="delivery_conditions" class="form-label">Delivery Conditions</label>
                    <textarea class="form-control" id="delivery_conditions" name="delivery_conditions" rows="3" placeholder="Enter Delivery Conditions"></textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="other_conditions" class="form-label">Other Conditions</label>
                    <textarea class="form-control" id="other_conditions" name="other_conditions" rows="3" placeholder="Enter Other Conditions"></textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="internal_remark_conditions" class="form-label">Internal Remark, This will not showing to client</label>
                    <textarea class="form-control" id="internal_remark_conditions" name="internal_remark_conditions" rows="3" placeholder="Enter Internal Remark if any"></textarea>
                </div>
            </div>
        </form>
        
    </div>
</div>

    <div class="card-footer text-center" style="height: 100px;">
        <button type="button" class="btn btn-secondary" id="cancelQuotation">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveQuotation">Save Quotation</button>
    </div>



<script>
    const message = <?= json_encode($variable) ?>; // Safely encodes $variable for JavaScript
</script>

<script>
    
$(document).ready(function () {
    let materialsArray = []; // Initialize the array to store material data

    // Initialize Select2 for material dropdown
    $('#material_name').select2({
        placeholder: 'Select a Material',
        allowClear: true
    });

    $('#quantity, #price').on('input', function () {
        const value = $(this).val();
        if (value < 0) {
            alert('Price and Quantity Value cannot be less than 0');
            $(this).val(0); // Reset the value to 0 if it is less than 0
        }
    });

    $('#quantity, #price, #hsn_percentage').on('input', function () {
        const quantity = parseFloat($('#quantity').val()) || 0;
        const price = parseFloat($('#price').val()) || 0;
        const hsnPercentage = parseFloat($('#hsn_percentage').val()) || 0;

        // Calculate Basic Total
        const basicTotal = quantity * price;
        $('#basic_total').val(basicTotal.toFixed(2));

        // Calculate HSN Total
        const hsnTotal = (basicTotal * hsnPercentage) / 100;
        $('#hsn_total').val(hsnTotal.toFixed(2));

        // Calculate Grand Total
        const grandTotal = basicTotal + hsnTotal;
        $('#grand_total').val(grandTotal.toFixed(2));
    });

    // Fetch materials and populate dropdown
    // Fetch materials and populate dropdown
    function loadMaterials() {
        fetch('fetch_materials.php')
            .then(response => response.json())
            .then(materials => {
                const materialDropdown = $('#material_name');
                materialDropdown.empty(); // Clear previous options
                materialDropdown.append('<option value="">Select Material</option>'); // Add default option

                // Check if there are materials in the response
                if (materials.error) {
                    console.error('Error:', materials.error);
                    alert('Failed to load materials. Please try again.');
                    return;
                }

                // Populate the dropdown with materials
                materials.forEach(material => {
                    materialDropdown.append(
                        `<option value="${material.material_id}">${material.material_name}</option>`
                    );
                });

                // Reinitialize Select2 after adding options
                materialDropdown.select2({
                    placeholder: 'Select a Material',
                    allowClear: true
                });
            })
            .catch(error => {
                console.error('Error fetching materials:', error);
                alert('Error loading materials. Please try again.');
            });
    }

    // Load materials on page load
    $(document).ready(function () {
        loadMaterials();
    });


    // Fetch material details on selection
    $('#material_name').on('change', function () {
        const materialId = $(this).val();

        if (materialId) {
            fetch(`fetch_material_details.php?material_id=${materialId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.error) {
                        $('#unit_name').val(data.unit_name);
                        $('#hsn_code').val(data.gst_code);
                        $('#hsn_percentage').val(data.gst_percentage);
                        $('#material_make').val(data.material_make); // Set the make
                        $('#material_type').val(data.material_type_name); // Set the material type
                        $('#quantity').val('');
                        $('#price').val('');
                        
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => alert('Error fetching material details.'));
        } else {
            $('#unit_name, #hsn_code, #hsn_percentage').val('');
        }
    });

    // Add material to the array and update the table
    $('#addMaterial').on('click', function () {
    const materialID = $('#material_name').val(); // Fetch the material ID
    const materialName = $('#material_name option:selected').text();
    const quantity = parseFloat($('#quantity').val()) || 0;
    const unitName = $('#unit_name').val();
    const price = parseFloat($('#price').val()) || 0;
    const basicTotal = parseFloat($('#basic_total').val()) || 0;
    const hsnCode = $('#hsn_code').val();
    const hsnPercentage = parseFloat($('#hsn_percentage').val()) || 0;
    const hsnTotal = parseFloat($('#hsn_total').val()) || 0;
    const grandTotal = parseFloat($('#grand_total').val()) || 0;

    const make = $('#material_make').val(); // Fetch make dynamically
    const materialType = $('#material_type').val(); // Fetch material type dynamically
    const materialRemark = $('#material_remark').val(); // Fetch the material remark

    // Add the material to the array
    materialsArray.push({
        materialID, // Include the material ID
        materialName,
        quantity,
        unitName,
        price,
        basicTotal,
        hsnCode,
        hsnPercentage,
        hsnTotal,
        grandTotal,
        make,
        materialType,
        materialRemark // Include the material remark
    });

    // Update the table
    updateMaterialsTable();

    // Clear the remark field
    $('#material_remark').val('');
});

function updateMaterialsTable() {
    const tbody = $('#materialsTableBody');
    tbody.empty();

    if (materialsArray.length === 0) {
        tbody.append('<tr><td colspan="12" class="text-center">No materials added.</td></tr>');
        return;
    }

    let totalBasic = 0;
    let totalHsn = 0;
    let totalGrand = 0;

    materialsArray.forEach((material, index) => {
        totalBasic += material.basicTotal;
        totalHsn += material.hsnTotal;
        totalGrand += material.grandTotal;

        const row = `
            <tr>
                <td>${material.materialType}</td>
                <td>${material.make}</td>
                <td>${material.materialName}</td>
                <td>${material.quantity}</td>
                <td>${material.unitName}</td>
                <td>${material.price.toFixed(2)}</td>
                <td>${material.basicTotal.toFixed(2)}</td>
                <td>${material.hsnCode}</td>
                <td>${material.hsnPercentage.toFixed(2)}%</td>
                <td>${material.hsnTotal.toFixed(2)}</td>
                <td>${material.grandTotal.toFixed(2)}</td>
                <td hidden>${material.materialRemark || 'N/A'}</td>
                <td>
                    <button type="button" class="btn btn-warning btn-sm" onclick="editMaterial(${index})">Edit</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeMaterial(${index})">Remove</button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    // Update totals in the footer
    $('#totalsRow').html(`
        <tr>
            <td colspan="7" class="text-end fw-bold">Total:</td>
            <td>${totalBasic.toFixed(2)}</td>
            <td></td>
            <td></td>
            <td>${totalHsn.toFixed(2)}</td>
            <td>${totalGrand.toFixed(2)}</td>
            <td></td>
        </tr>
    `);
}


    window.editMaterial = function (index) {
        const material = materialsArray[index];
        editMaterialIndex = index;

        // Populate modal fields with current values
        $('#editQuantity').val(material.quantity);
        $('#editPrice').val(material.price);

        // Show the modal
        $('#editMaterialModal').modal('show');
    };

    $('#saveMaterialChanges').on('click', function () {
    const quantity = parseFloat($('#editQuantity').val()) || 0;
    const price = parseFloat($('#editPrice').val()) || 0;

    if (editMaterialIndex >= 0 && editMaterialIndex < materialsArray.length) {
        const material = materialsArray[editMaterialIndex];

        // Update material details
        material.quantity = quantity;
        material.price = price;
        material.basicTotal = quantity * price;
        material.hsnTotal = (material.basicTotal * material.hsnPercentage) / 100;
        material.grandTotal = material.basicTotal + material.hsnTotal;

        // Close the modal
        $('#editMaterialModal').modal('hide');

        // Update the table
        updateMaterialsTable();
    }
});


    // Remove material from the array
    window.removeMaterial = function (index) {
        materialsArray.splice(index, 1);
        updateMaterialsTable();
    };
    function convertNumberToWords(amount) {
    const words = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen',
        'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
    ];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    const scales = ['', 'Thousand', 'Lakh', 'Crore'];

    if (amount === 0) return 'Zero Rupees';

    amount = parseFloat(amount).toFixed(2); // Ensure two decimal places
    const [rupeesPart, paisePart] = amount.split('.'); // Split rupees and paise parts
    let numberString = rupeesPart; // Process rupees part
    let wordArray = [];
    let scaleIndex = 0;

    // Process the last three digits (hundreds, tens, and units) for rupees
    let chunk = parseInt(numberString.slice(-3), 10);
    numberString = numberString.slice(0, -3);

    if (chunk) {
        let chunkWords = [];
        if (chunk > 99) {
            chunkWords.push(words[Math.floor(chunk / 100)] + ' Hundred');
            chunk %= 100;
        }
        if (chunk > 19) {
            chunkWords.push(tens[Math.floor(chunk / 10)]);
            chunk %= 10;
        }
        if (chunk) {
            chunkWords.push(words[chunk]);
        }
        wordArray.unshift(chunkWords.join(' '));
    }

    // Process the remaining digits in pairs (thousands, lakhs, crores)
    scaleIndex = 1; // Start with Thousand
    while (numberString.length > 0) {
        chunk = parseInt(numberString.slice(-2), 10); // Take the last 2 digits
        numberString = numberString.slice(0, -2); // Remove the last 2 digits

        if (chunk) {
            let chunkWords = [];
            if (chunk > 19) {
                chunkWords.push(tens[Math.floor(chunk / 10)]);
                chunk %= 10;
            }
            if (chunk) {
                chunkWords.push(words[chunk]);
            }
            if (scales[scaleIndex]) {
                chunkWords.push(scales[scaleIndex]);
            }
            wordArray.unshift(chunkWords.join(' '));
        }
        scaleIndex++;
    }

    let rupeesInWords = wordArray.join(' ') + ' Rupees';

    // Handle paise part
    let paiseInWords = '';
    if (parseInt(paisePart, 10) > 0) {
        let paise = parseInt(paisePart, 10);
        let paiseWords = [];
        if (paise > 19) {
            paiseWords.push(tens[Math.floor(paise / 10)]);
            paise %= 10;
        }
        if (paise) {
            paiseWords.push(words[paise]);
        }
        paiseInWords = ' and ' + paiseWords.join(' ') + ' Paise';
    }

    return rupeesInWords + paiseInWords;
}

$('#saveQuotation').on('click', function () {
    // Gather data from the quotation form
    const companyID = $('#company_id').val();
    const quotationDate = $('#quotation_date').val();
    const quotationValidDate = $('#quotation_valid_date').val();
    const statusID = $('#status_id').val();
    const internalID = "<?= htmlspecialchars($internal_id); ?>"; // Pass internal_id

    // Validate basic quotation form data
    if (!companyID || !quotationDate || !quotationValidDate || !statusID) {
        alert('Please fill in all required fields in the quotation details.');
        return;
    }

    // Gather data from the terms and conditions form
    const paymentConditions = $('#payment_conditions').val();
    const deliveryConditions = $('#delivery_conditions').val();
    const otherConditions = $('#other_conditions').val();
    const internalRemarkConditions = $('#internal_remark_conditions').val();

    // Validate at least one term/condition is filled
    if (!paymentConditions && !deliveryConditions && !otherConditions) {
        alert('Please fill in at least one condition in Terms and Conditions.');
        return;
    }

    // Validate materials array
    if (materialsArray.length === 0) {
        alert('Please add at least one material before saving.');
        return;
    }

    // Prepare the data to send
    const data = {
        companyID,
        quotationDate,
        quotationValidDate,
        statusID,
        internalID, // Include internal ID
        termsConditions: {
            paymentConditions,
            deliveryConditions,
            otherConditions,
            internalRemarkConditions,
        },
        materials: materialsArray, // Pass materials with materialID and materialRemark
    };

    // Send data to backend using fetch
    fetch('save_quotation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    })
        .then((response) => response.json())
        .then((result) => {
            if (result.success) {
                alert(result.message);
                window.location.href = 'dashboard.php'; // dashboard
            } else {
                alert('Error: ' + result.error);
            }
        })
        .catch((error) => {
            console.error('Error saving quotation:', error);
            alert('An error occurred while saving the quotation. Please try again.');
        });
});

});
</script>



</body>
</html>
