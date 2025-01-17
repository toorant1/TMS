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
</head>
<body>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quotation</title>


  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Correct Select2 CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>

<!-- Correct Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    body {
        font-family: Arial, sans-serif;
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

    <!-- Row for Basic Details and Quotation Details -->
    <div class="row">
        <!-- Basic Details Card -->
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

        <!-- New Quotation Details Section -->
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

    <!-- Materials Entry Form -->
    <div class="card info-card mb-4">
        <div class="card-header bg-success text-white">Materials Details</div>
        <div class="card-body">
            <form id="materialsForm">
                <div class="row mb-3">
                    <!-- Material Name Dropdown and Search Button in Same Row -->
                    <div class="col-md-12 d-flex align-items-end">
                        <div class="flex-grow-1 me-2">
                            <label for="material_name" class="form-label">Material Name</label>
                            <select class="form-select" id="material_name" name="material_name" required>
                                <option value="">Select Material</option>
                            </select>
                        </div>
                        <div>
                            <button hidden type="button" class="btn btn-primary" id="searchMaterialButton" style="margin-top: 30px;">Search Material</button>
                        </div>
                    </div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                    const materialNameDropdown = document.getElementById('material_name');

                    // Load Material Names from Backend
                    function loadMaterialNames() {
                        fetch('fetch_materials.php') // Adjust the path as necessary
                            .then(response => response.json())
                            .then(materials => {
                                materialNameDropdown.innerHTML = '<option value="">Select Material</option>';
                                if (materials.length > 0) {
                                    materials.forEach(material => {
                                        materialNameDropdown.innerHTML += `<option value="${material.material_id}">${material.material_name}</option>`;
                                    });
                                } else {
                                    materialNameDropdown.innerHTML = '<option value="">No materials available</option>';
                                }
                            })
                            .catch(error => console.error('Error fetching materials:', error));
                    }

                    // Load Materials on Page Load
                    loadMaterialNames();
                    });

                    $(document).ready(function() {
                    // Initialize Select2 on your dropdown
                    $('#material_name').select2({
                        placeholder: 'Select a Material', // Optional: Adds a placeholder
                        allowClear: true                 // Optional: Enables clear button
                    });

                    // Initialize Select2 on other dropdowns
                    $('#materialTypeDropdown').select2({
                        placeholder: 'Select Type',
                        allowClear: true
                    });

                    $('#materialMakeDropdown').select2({
                        placeholder: 'Select Make',
                        allowClear: true
                    });
                    $(document).ready(function () {
    // Initialize Select2
    $('#material_name').select2({
        placeholder: 'Select a Material',
        allowClear: true
    });

    // Listen for changes in the Select2 dropdown
    $('#material_name').on('change', function () {
        const selectedMaterialId = $(this).val();

        if (selectedMaterialId) {
            // Make an AJAX call to fetch material details
            $.ajax({
                url: 'fetch_material_details.php', // Adjust the path as needed
                type: 'GET',
                data: { material_id: selectedMaterialId },
                success: function (response) {
                    const data = JSON.parse(response);

                    if (!data.error) {
                        // Populate the fields with the fetched data
                        $('#unit_name').val(data.unit_name);          // Set the descriptive unit name
                        $('#hsn_code').val(data.gst_code);           // Set the GST code
                        $('#hsn_percentage').val(data.gst_percentage); // Set t
                    } else {
                        console.error(data.error);
                        alert('Error: ' + data.error);
                    }
                },
                error: function () {
                    console.error('Error fetching material details.');
                    alert('Error fetching material details.');
                }
            });
        } else {
            // Clear the fields if no material is selected
            $('#unit_name').val('');
            $('#hsn_code').val('');
            $('#hsn_percentage').val('');
        }
    });
});

                });

                </script>

                <div class="row mb-3">
                    <div class="col-md-2">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" required>
                    </div>
                    <div class="col-md-2">
                    <label for="unit_name" class="form-label">Unit</label>
                    <input type="text" class="form-control" id="unit_name" name="unit_name" readonly tabindex="-1">
                    </div>
                    <div class="col-md-2">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" class="form-control" id="price" name="price">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="total" class="form-label">Total</label>
                        <input type="number" class="form-control" id="total" name="total" readonly tabindex = "-1">
                    </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label for="hsn_code" class="form-label">HSN Code</label>
                            <input type="text" class="form-control" id="hsn_code" name="hsn_code" readonly tabindex = "-1">
                        </div>
                        <div class="col-md-2">
                            <label for="hsn_percentage" class="form-label">HSN %</label>
                            <input type="number" class="form-control" id="hsn_percentage" name="hsn_percentage" readonly tabindex = "-1" >
                        </div>
                        <div class="col-md-2">
                            <label for="hsn_total" class="form-label">HSN Total</label>
                            <input type="number" class="form-control" id="hsn_total" name="hsn_total" readonly tabindex = "-1">
                        </div>
                        <div class="col-md-2">
                            <label for="grand_total" class="form-label">Grand Total</label>
                            <input type="number" class="form-control" id="grand_total" name="grand_total" readonly tabindex = "-1">
                        </div>
                        

                        <div class="col-md-4">
                            <label for="remark" class="form-label">Remark</label>
                            <textarea class="form-control" id="remark" name="remark" rows="1"></textarea>
                        </div>
                    </div>
                <div class="text-center mb-3">
                    <button type="button" class="btn btn-success" id="addMaterial">Add Material</button>
                    
                </div>
            </form>

            <h6 class="text-center">Added Materials</h6>
            <table class="table table-bordered mt-3">
                <thead class="table-light">
                    <tr>
                        <th>Material Type</th>
                        <th>Make</th>
                        <th>Material Name</th>
                        <th>HSN Code</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>HSN %</th>
                        <th>HSN Total</th>
                        <th>Grand Total</th>
                        <th hidden>Remark</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="materialsTableBody">
                    <tr><td colspan="13" class="text-center">No materials added.</td></tr>
                </tbody>
                <tfoot>
                    <tr id="totalsRow"></tr>
                </tfoot>
            </table>
        </div>
    </div>


</div>


    <!-- Material Search Modal -->
    <div class="modal fade" id="searchMaterialModal" tabindex="-1" aria-labelledby="searchMaterialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchMaterialModalLabel">Search Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="searchMaterialForm">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="materialTypeDropdown" class="form-label">Material Type</label>
                                <select class="form-select" id="materialTypeDropdown" name="materialTypeDropdown">
                                    <option value="">Select Type</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="materialMakeDropdown" class="form-label">Material Make</label>
                                <select class="form-select" id="materialMakeDropdown" name="materialMakeDropdown">
                                    <option value="">Select Make</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="materialNameDropdown" class="form-label">Material Name</label>
                                <select class="form-select" id="materialNameDropdown" name="materialNameDropdown">
                                    <option value="">Select Material</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Material ID</th>
                                <th>Material Name</th>
                                <th>Type</th>
                                <th>Make</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="searchMaterialTableBody">
                            <tr><td colspan="5" class="text-center">No materials found.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
    const materialTypeDropdown = document.getElementById('materialTypeDropdown');
    const materialMakeDropdown = document.getElementById('materialMakeDropdown');
    const materialNameDropdown = document.getElementById('materialNameDropdown');
    const searchMaterialTableBody = document.getElementById('searchMaterialTableBody');

    // Fetch and populate Material Type dropdown
    fetch('fetch_material_types.php')
        .then(response => response.json())
        .then(types => {
            materialTypeDropdown.innerHTML = '<option value="">Select Type</option>';
            types.forEach(type => {
                materialTypeDropdown.innerHTML += `<option value="${type.material_type}">${type.material_type}</option>`;
            });
        });

    // Fetch and populate Material Make dropdown
    fetch('fetch_material_makes.php')
        .then(response => response.json())
        .then(makes => {
            materialMakeDropdown.innerHTML = '<option value="">Select Make</option>';
            makes.forEach(make => {
                materialMakeDropdown.innerHTML += `<option value="${make.make}">${make.make}</option>`;
            });
        });

    // Fetch and populate Material Name dropdown
    fetch('fetch_material_names.php')
        .then(response => response.json())
        .then(names => {
            materialNameDropdown.innerHTML = '<option value="">Select Material</option>';
            names.forEach(name => {
                materialNameDropdown.innerHTML += `<option value="${name.material_id}">${name.material_name}</option>`;
            });
        });

    // Fetch and populate table based on filters
    function fetchFilteredMaterials() {
        const type = materialTypeDropdown.value;
        const make = materialMakeDropdown.value;
        const name = materialNameDropdown.value;

        fetch(`fetch_materials_by_filters.php?type=${type}&make=${make}&name=${name}`)
            .then(response => response.json())
            .then(materials => {
                searchMaterialTableBody.innerHTML = '';
                if (materials.length > 0) {
                    materials.forEach(material => {
                        searchMaterialTableBody.innerHTML += `
                            <tr>
                                <td>${material.id}</td>
                                <td>${material.material_name}</td>
                                <td>${material.material_type}</td>
                                <td>${material.make}</td>
                                <td><button class="btn btn-primary btn-sm">Select</button></td>
                            </tr>`;
                    });
                } else {
                    searchMaterialTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No records found for this combination.</td></tr>';
                }
            });
    }

    // Add event listeners to fetch table data on filter change
    materialTypeDropdown.addEventListener('change', fetchFilteredMaterials);
    materialMakeDropdown.addEventListener('change', fetchFilteredMaterials);
    materialNameDropdown.addEventListener('change', fetchFilteredMaterials);
});

    </script>

    <!-- Save Quotation Button -->
    <div class="text-center">
        <button type="button" class="btn btn-primary btn-lg" id="saveQuotation">Save Quotation</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const materialTypeDropdown = document.getElementById('materialTypeDropdown');
    const materialMakeDropdown = document.getElementById('materialMakeDropdown');
    const materialNameDropdown = document.getElementById('materialNameDropdown');
    const searchMaterialTableBody = document.getElementById('searchMaterialTableBody');
    const parentMaterialNameDropdown = document.getElementById('material_name');

    // Open Modal on Button Click
    const searchMaterialButton = document.getElementById('searchMaterialButton');
    searchMaterialButton.addEventListener('click', function () {
        const modal = new bootstrap.Modal(document.getElementById('searchMaterialModal'));
        modal.show();
    });

    // Load Material Types
    function loadMaterialTypes() {
        fetch('fetch_material_types.php') // Fetch material types
            .then(response => response.json())
            .then(types => {
                materialTypeDropdown.innerHTML = '<option value="">Select Type</option>';
                types.forEach(type => {
                    materialTypeDropdown.innerHTML += `<option value="${type.id}">${type.material_type}</option>`;
                });
            })
            .catch(error => console.error('Error fetching material types:', error));
    }

    // Load Material Makes
    function loadMaterialMakes() {
        fetch('fetch_material_makes.php') // Fetch material makes
            .then(response => response.json())
            .then(makes => {
                materialMakeDropdown.innerHTML = '<option value="">Select Make</option>';
                makes.forEach(make => {
                    materialMakeDropdown.innerHTML += `<option value="${make.id}">${make.make}</option>`;
                });
            })
            .catch(error => console.error('Error fetching material makes:', error));
    }

    // Load Materials Based on Filters
    function loadMaterialNames() {
        const typeId = materialTypeDropdown.value;
        const makeId = materialMakeDropdown.value;

        fetch(`fetch_materials.php?type_id=${typeId}&make_id=${makeId}`)
            .then(response => response.json())
            .then(materials => {
                searchMaterialTableBody.innerHTML = '';
                if (materials.length > 0) {
                    materials.forEach(material => {
                        searchMaterialTableBody.innerHTML += `
                            <tr>
                                <td>${material.id}</td>
                                <td>${material.name}</td>
                                <td>${material.type}</td>
                                <td>${material.make}</td>
                                <td><button class="btn btn-primary btn-sm" onclick="selectMaterial(${material.id}, '${material.name}')">Select</button></td>
                            </tr>`;
                    });
                } else {
                    searchMaterialTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No materials found.</td></tr>';
                }
            })
            .catch(error => console.error('Error fetching materials:', error));
    }

    // Event Listeners for Dropdowns
    materialTypeDropdown.addEventListener('change', loadMaterialNames);
    materialMakeDropdown.addEventListener('change', loadMaterialNames);

    // Select Material from Table and Assign to Parent Dropdown
    window.selectMaterial = function (id, name) {
        parentMaterialNameDropdown.innerHTML = `<option value="${id}" selected>${name}</option>`;
        const modal = bootstrap.Modal.getInstance(document.getElementById('searchMaterialModal'));
        modal.hide();
    };

    // Initialize Dropdowns in Modal
    loadMaterialTypes();
    loadMaterialMakes();
});
</script>

</body>
</html>