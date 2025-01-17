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
$quotation_id = isset($_GET['quotation_id']) ? intval($_GET['quotation_id']) : null;
$token = isset($_GET['token']) ? filter_var($_GET['token'], FILTER_SANITIZE_STRING) : null;
$master_userid = $_SESSION['master_userid'];

// Validate the required parameters
if (is_null($quotation_id) || empty($token) || empty($master_userid)) {
    die("Invalid request. Missing or invalid Quotation ID, Token, or Master User ID.");
}

// Fetch quotation details
$query = "
    SELECT 
        q.quotation_id, 
        q.company_id,
        q.quotation_date,
        q.quotation_valid_upto_date,
        q.quotation_status_id,
        q.payment_conditions,
        q.delivery_conditions,
        q.other_conditions,
        q.internal_remark_conditions,
        m.internal_id
    FROM 
        master_quotations q
    INNER JOIN 
        master_marketing m ON q.quotation_reference = m.internal_id
    WHERE 
        q.quotation_id = ? 
        AND q.quotation_token = ? 
        AND q.master_user_id = ?
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query Preparation Failed: " . $conn->error);
}
$stmt->bind_param("isi", $quotation_id, $token, $master_userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No valid record found for the given Quotation ID, Token, or Master User ID.");
}

$quotation = $result->fetch_assoc();
$stmt->close();

// Fetch existing materials
$materials_query = "
    SELECT 
        mqm.master_quotation_material_id,
        mm.id AS material_id,
        mm.name AS material_name,
        mqm.quantity,
        mqm.unit_price,
        mqm.hsn_code,
        mqm.hsn_percentage,
        mqm.master_quotation_materials_remark
    FROM 
        master_quotations_materials mqm
    INNER JOIN 
        master_materials mm ON mqm.material_id = mm.id
    WHERE 
        mqm.master_quotation_id = ?
";
$stmt = $conn->prepare($materials_query);
if (!$stmt) {
    die("Materials Query Preparation Failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $quotation_id);
$stmt->execute();
$materials_result = $stmt->get_result();

$materials = [];
while ($row = $materials_result->fetch_assoc()) {
    $materials[] = $row;
}
$stmt->close();

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quotation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add these in the <head> section -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>

     <!-- Embed materials data as JSON -->
     <script id="materialsData" type="application/json"><?= json_encode($materials); ?></script>
     <script src="add_material.js"></script>
    <!-- Include the delete_material.js script -->
    <script src="delete_material.js"></script>
    
    <!-- Include the update terms and conditions .js script -->
    
    <script src="update_headers.js"></script>
    <script src="update_conditions.js"></script>
    <script src="update_material.js"></script>
    
    



</head>
<body>
<div class="container mt-4">
    <h1 class="text-center">Edit Quotation</h1>

    <!-- Quotation Details Section -->
<form id="quotationDetailsForm" action="process_quotation_details.php" method="POST">
    <input type="hidden" name="quotation_id" value="<?= htmlspecialchars($quotation_id); ?>">
    <input type="hidden" name="quotation_token" value="<?= htmlspecialchars($token); ?>">
    <div class="card mb-4">
    <div class="card-header bg-primary text-white">Quotation Details</div>
    <div class="card-body">
    <div class="row mb-3">
    <div class="col-md-6">
        <label for="company_id" class="form-label">Company</label>
        <select class="form-select update-field" id="company_id" name="company_id" data-field="company_id" required>
            <option value="">Select a Company</option>
            <?php foreach ($companyOptions as $company): ?>
                <option value="<?= htmlspecialchars($company['id']); ?>" <?= $company['id'] == $quotation['company_id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($company['company_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label for="quotation_status_id" class="form-label">Status</label>
        <select class="form-select update-field" id="quotation_status_id" name="quotation_status_id" data-field="quotation_status_id" required>
            <option value="">Select Status</option>
            <?php foreach ($statusOptions as $status): ?>
                <option value="<?= htmlspecialchars($status['quotation_status_id']); ?>" <?= $status['quotation_status_id'] == $quotation['quotation_status_id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($status['status_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-6">
        <label for="quotation_date" class="form-label">Quotation Date</label>
        <input type="date" class="form-control update-field" id="quotation_date" name="quotation_date" data-field="quotation_date" value="<?= htmlspecialchars($quotation['quotation_date']); ?>" required>
    </div>
    <div class="col-md-6">
        <label for="quotation_valid_upto_date" class="form-label">Valid Upto</label>
        <input type="date" class="form-control update-field" id="quotation_valid_upto_date" name="quotation_valid_upto_date" data-field="quotation_valid_upto_date" value="<?= htmlspecialchars($quotation['quotation_valid_upto_date']); ?>" required>
    </div>
</div>

    </div>
</div>

</form>

<!-- Materials Section -->
<form id="materialsForm" action="process_materials.php" method="POST">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">Materials</div>
            <div class="card-body">
                <!-- Dropdown to Add New Material -->
                <div class="row g-3 align-items-end mb-3">
                <div class="col-md-4">
                    <label for="material_name" class="form-label">Material</label>
                    <select class="form-select select2" id="material_name" name="material_name">
                        <option value="">Select Material</option>
                        <?php
                        // Fetch materials for the dropdown
                        $materialDropdownQuery = "
                            SELECT id, name 
                            FROM master_materials 
                            WHERE master_user_id = ? AND status = 1 
                            ORDER BY name ASC";
                        $stmt = $conn->prepare($materialDropdownQuery);
                        if ($stmt) {
                            $stmt->bind_param("i", $master_userid);
                            $stmt->execute();
                            $materialDropdownResult = $stmt->get_result();
                            while ($material = $materialDropdownResult->fetch_assoc()) {
                                echo "<option value='{$material['id']}'>{$material['name']}</option>";
                            }
                            $stmt->close();
                        }
                        ?>
                    </select>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        // Initialize Select2
                        $('.select2').select2({
                            placeholder: "Select Material", // Add placeholder
                            allowClear: true // Allow clearing the selection
                        });
                    });

                    </script>

                    <div class="col-md-2">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" min="0" value="0">
                    </div>
                    <div class="col-md-2">
                        <label for="unit_price" class="form-label">Unit Price</label>
                        <input type="number" class="form-control" id="unit_price" min="0" value="0">
                    </div>
                    <div class="col-md-2">
                        <label for="remark" class="form-label">Remark</label>
                        <input type="text" class="form-control" id="remark">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-success w-100" id="addMaterialButton">Add Material</button>
                    </div>
                </div>

                <!-- Table to Display Existing and New Materials -->
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th hidden>Material ID</th>
                            <th>Material Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Basic Total</th>
                            <th>HSN Code</th>
                            <th>HSN %</th>
                            <th>HSN Total</th>
                            <th>Grand Total</th>
                            <th>Remark</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="materialsTableBody">
                        <!-- Dynamic rows will be added here -->
                    </tbody>
                </table>
            </div>
        </div>
    </form>


          <!-- Terms and Conditions Section -->
          <div class="card mb-4">
    <div class="card-header bg-info text-white">Terms and Conditions</div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-12">
                <label for="payment_conditions" class="form-label">Payment Conditions</label>
                <textarea 
                    class="form-control" 
                    id="payment_conditions" 
                    name="payment_conditions" 
                    rows="3"><?= htmlspecialchars($quotation['payment_conditions']); ?></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-12">
                <label for="delivery_conditions" class="form-label">Delivery Conditions</label>
                <textarea 
                    class="form-control" 
                    id="delivery_conditions" 
                    name="delivery_conditions" 
                    rows="3"><?= htmlspecialchars($quotation['delivery_conditions']); ?></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-12">
                <label for="other_conditions" class="form-label">Other Conditions</label>
                <textarea 
                    class="form-control" 
                    id="other_conditions" 
                    name="other_conditions" 
                    rows="3"><?= htmlspecialchars($quotation['other_conditions']); ?></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-12">
                <label for="internal_remark_conditions" class="form-label">Internal Remark</label>
                <textarea 
                    class="form-control" 
                    id="internal_remark_conditions" 
                    name="internal_remark_conditions" 
                    rows="3"><?= htmlspecialchars($quotation['internal_remark_conditions']); ?></textarea>
            </div>
        </div>
    </div>
</div>


      

<div class="modal fade" id="editMaterialModal" tabindex="-1" aria-labelledby="editMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMaterialModalLabel">Edit Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="modalMaterialName" class="form-label">Material Name</label>
                    <input type="text" class="form-control" id="modalMaterialName" readonly  style="background-color: #e9ecef; cursor: not-allowed; color: #495057; border: 1px solid #ced4da;">
                </div>
                <div class="mb-3">
                    <label for="modalQuantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="modalQuantity">
                </div>
                <div class="mb-3">
                    <label for="modalPrice" class="form-label">Price</label>
                    <input type="number" class="form-control" id="modalPrice">
                </div>
                <div class="mb-3">
                    <label for="modalRemark" class="form-label">Remark</label>
                    <textarea class="form-control" id="modalRemark"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveChangesButton">Save Changes</button>
            </div>
        </div>
    </div>
</div>


     

</body>
</html>
