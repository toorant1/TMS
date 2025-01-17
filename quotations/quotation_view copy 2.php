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
$quotation_id = isset($_GET['quotation_id']) ? intval($_GET['quotation_id']) : 0;
$token = isset($_GET['token']) ? filter_var($_GET['token'], FILTER_SANITIZE_STRING) : '';

if (!$quotation_id || empty($token)) {
    die("Invalid request. Missing Quotation ID or Token.");
}

// Fetch quotation details

$query = "
    SELECT 
    q.quotation_id,
    q.quotation_reference,
    q.quotation_number,
    q.quotation_date,
    q.quotation_valid_upto_date,
    q.terms_conditions,
    q.payment_conditions,
    q.delivery_conditions,
    q.other_conditions,
    q.internal_remark_conditions,
    c.company_name,
    c.address AS company_address,
    c.state AS company_state,
    c.district AS company_district,
    c.city AS company_city,
    c.pincode AS company_pincode,
    c.country AS company_country,
    s.status_name,
    a.account_name AS customer_name,
    a.address AS customer_address,
    a.state AS customer_state,
    a.district AS customer_district,
    a.city AS customer_city,
    a.pincode AS customer_pincode,
    a.country AS customer_country
FROM 
    master_quotations q
INNER JOIN 
    master_company c ON q.company_id = c.id
INNER JOIN 
    master_quotations_status s ON q.quotation_status_id = s.quotation_status_id
INNER JOIN 
    master_marketing m ON q.quotation_reference = m.internal_id AND q.master_user_id = m.master_user_id
INNER JOIN 
    account a ON m.account_id = a.id
WHERE 
    q.quotation_id = ? AND 
    q.quotation_token = ? AND 
    q.master_user_id = ?;

";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query Preparation Failed: " . htmlspecialchars($conn->error));
}

// Bind parameters and execute
$stmt->bind_param("isi", $quotation_id, $token, $_SESSION['master_userid']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No valid record found for the given Quotation ID, Token, or Master User ID.");
}

$quotation = $result->fetch_assoc();
$stmt->close();

// Fetch materials for the quotation
$query = "
    SELECT 
        mm.name AS material_name,
        mm.description AS material_description,
        mmake.make AS material_make_name,
        mt.material_type AS material_type_name,
        mu.unit_name AS unit,
        mqm.quantity,
        mqm.unit_price AS price,
        (mqm.quantity * mqm.unit_price) AS total,
        mqm.hsn_code,
        mqm.hsn_percentage,
        ((mqm.quantity * mqm.unit_price) * mqm.hsn_percentage / 100) AS hsn_total,
        ((mqm.quantity * mqm.unit_price) + ((mqm.quantity * mqm.unit_price) * mqm.hsn_percentage / 100)) AS grand_total,
        mqm.master_quotation_materials_remark
    FROM master_quotations_materials mqm
    INNER JOIN master_materials mm ON mqm.material_id = mm.id
    INNER JOIN master_materials_make mmake ON mm.make = mmake.id
    INNER JOIN master_materials_type mt ON mm.material_type = mt.id
    INNER JOIN master_materials_unit mu ON mm.unit = mu.id
    WHERE mqm.master_quotation_id = ?
    ORDER BY mm.name
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query Preparation Failed: " . htmlspecialchars($conn->error));
}

// Bind parameters
$stmt->bind_param("i", $quotation_id);

// Execute and fetch results
$stmt->execute();
$materialsResult = $stmt->get_result();
$materials = [];

// Iterate through the results
while ($row = $materialsResult->fetch_assoc()) {
    $materials[] = $row;
}
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
            margin-top: 20px;
        }

        /* Print Styles */
        /* Print Styles */
@media print {
    body {
        margin: 0;
        padding: 0;
    }
    .container {
        width: 100%;
        max-width: 100%;
        padding: 0;
        margin: 0;
    }
    .card-header {
        background-color: #007bff !important;
        color: white !important;
    }
    .table {
        page-break-inside: auto;
        border-collapse: collapse;
    }
    .table th, .table td {
        border: 2px solid #000 !important; /* Darker border for visibility */
    }
    .table thead {
        display: table-header-group;
    }
    .table tfoot {
        display: table-footer-group;
    }
    .no-print {
        display: none; /* Hide any elements you don't want to print */
    }
    .page-break {
        page-break-after: always;
    }
    .d-flex {
        display: flex !important;
        justify-content: space-between !important;
        flex-wrap: nowrap !important;
        gap: 1rem;
    }
    .card {
        border: 1px solid #000 !important; /* Ensure borders are visible in print */
    }
    .table th, .table td {
        border: 1px solid #000 !important; /* Ensure table borders are visible in print */
    }
    .card-header {
        background-color: #000 !important; /* Dark background for printing */
        color: black !important; /* White text for visibility */
        font-weight: bold !important;
        font-size: 1.2rem !important;
        border: 2px solid #000; /* Optional: Add a border for extra emphasis */
    }
    .card-body {
        border: 1px solid #000 !important; /* Ensure card body has a border in print */
    }
    .table th, .table td {
        border: 2px solid #000 !important; /* Ensure table borders are visible */
    }
    
}
    </style>
</head>
<body>

   

    <div class="container mt-4 ">
        <h5 class="text-center">Quotation</h5>
    <div>
        <h1 class="text-center mb-2 header-title"><?= htmlspecialchars($quotation['company_name']); ?></h1>
        <h5 class="text-center mb-2 ">
            <?= htmlspecialchars($quotation['company_address']); ?>
            <?= htmlspecialchars($quotation['company_city'] . ", " . $quotation['company_district']); ?>
            <?= htmlspecialchars($quotation['company_state'] . " - " . $quotation['company_pincode']); ?>
            <?= htmlspecialchars($quotation['company_country']); ?>
        </h5>
    </div>

    <div class="no-print text-center mb-4">
        <button onclick="window.print();" class="btn btn-primary">Print Quotation</button>
        <button id="sendEmailButton" class="btn btn-success">Send Quotation via Email</button>
    </div>



    
    <div class="row mt-4">
    

    <!-- Customer Information Card -->
    <div class="col-md-6">
        <div class="card info-card">
            <div class="card-header bg-dark text-white text-center" style="font-weight: bold; font-size: 1.2rem;">
                Customer Information
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th>Name</th>
                        <td><?= htmlspecialchars($quotation['customer_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td>
                            <?= htmlspecialchars($quotation['customer_address']); ?><br>
                            <?= htmlspecialchars($quotation['customer_city'] . ", " . $quotation['customer_district']); ?><br>
                            <?= htmlspecialchars($quotation['customer_state'] . " - " . $quotation['customer_pincode']); ?><br>
                            <?= htmlspecialchars($quotation['customer_country']); ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <!-- Quotation Summary Card -->
    <div class="col-md-6">
        <div class="card info-card">
            <div class="card-header bg-dark text-white text-center" style="font-weight: bold; font-size: 1.2rem;">
                Quotation Summary
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th>Quotation Number</th>
                        <td><?= htmlspecialchars($quotation['quotation_number']); ?> Date : <?= htmlspecialchars($quotation['quotation_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Quotation Reference</th>
                        <td><?= htmlspecialchars($quotation['quotation_reference']); ?></td>
                    </tr>
                    <tr>
                        <th>Valid Upto</th>
                        <td><?= htmlspecialchars($quotation['quotation_valid_upto_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><?= htmlspecialchars($quotation['status_name']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        
        

    </div>
</div>



<!-- Materials Details Section -->
<h2 class="section-title d-flex align-items-center">
        Materials Details
        <div class="form-check form-switch ms-3 no-print">
        <input class="form-check-input" type="checkbox" id="toggleGrouping">
    </div>
        
    </h2>
    <table class="table table-bordered">
        <thead>
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
                <th>Remark</th>
            </tr>
        </thead>
        <tbody id="materialsTableBody">
            <?php 
            $totalBasic = 0;
            $totalHsn = 0;
            $totalGrand = 0;
            foreach ($materials as $material): 
                $totalBasic += $material['total'];
                $totalHsn += $material['hsn_total'];
                $totalGrand += $material['grand_total'];
            ?>
                <tr>
                    <td><?= htmlspecialchars($material['material_type_name']); ?></td>
                    <td><?= htmlspecialchars($material['material_make_name']); ?></td>
                    <td><?= htmlspecialchars($material['material_name']); ?></td>
                    <td><?= htmlspecialchars($material['quantity']); ?></td>
                    <td><?= htmlspecialchars($material['unit']); ?></td>
                    <td><?= htmlspecialchars(number_format($material['price'], 2)); ?></td>
                    <td><?= htmlspecialchars(number_format($material['total'], 2)); ?></td>
                    <td><?= htmlspecialchars($material['hsn_code']); ?></td>
                    <td><?= htmlspecialchars($material['hsn_percentage']); ?></td>
                    <td><?= htmlspecialchars(number_format($material['hsn_total'], 2)); ?></td>
                    <td><?= htmlspecialchars(number_format($material['grand_total'], 2)); ?></td>
                    <td><?= htmlspecialchars($material['master_quotation_materials_remark'] ?? 'N/A'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class='table-success'>
                <th colspan="6" class="text-end">Total</th>
                <th><?= htmlspecialchars(number_format($totalBasic, 2)); ?></th>
                <th></th>
                <th></th>
                <th><?= htmlspecialchars(number_format($totalHsn, 2)); ?></th>
                <th><?= htmlspecialchars(number_format($totalGrand, 2)); ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>




    <!-- Terms and Conditions Section -->
<div class="mt-5">
    <h2 class="section-title">Terms and Conditions</h2>
    <div class="card info-card">
        <div class="card-body">
            <ul>
                <li><strong>Payment Conditions:</strong> <?= htmlspecialchars($quotation['payment_conditions'] ?? 'N/A'); ?></li>
                <li><strong>Delivery Conditions:</strong> <?= htmlspecialchars($quotation['delivery_conditions'] ?? 'N/A'); ?></li>
                <li><strong>Other Conditions:</strong> <?= htmlspecialchars($quotation['other_conditions'] ?? 'N/A'); ?></li>
                <li><strong>Additional Terms:</strong> <?= nl2br(htmlspecialchars($quotation['terms_conditions'] ?? 'No additional terms and conditions provided.')); ?></li>
            </ul>
        </div>
    </div>
</div>

<!-- Footer Section -->
<table style="width: 100%; border-collapse: collapse; margin-top: 30px;">
    <tr>
        <td style="text-align: center; padding: 10px;"><strong>Prepared By</strong></td>
        <td style="text-align: center; padding: 10px;"><strong>Approved By : (<?= htmlspecialchars(trim(string: $quotation['company_name']));?>)</strong></td>
        <td style="text-align: center; padding: 10px;"><strong>Customer Sign :(<?= htmlspecialchars($quotation['customer_name']); ?>)</strong></td>
    </tr>
    <tr>
        <td style="text-align: center; padding: 20px;">________________________</td>
        <td style="text-align: center; padding: 20px;">________________________</td>
        <td style="text-align: center; padding: 20px;">________________________</td>
    </tr>
</table>

<div class="no-print text-center mb-4">
        <button onclick="window.print();" class="btn btn-primary">Print Quotation</button>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        let isGrouped = false;

        // Toggle button click handler
        $('#toggleGrouping').on('click', function () {
            isGrouped = !isGrouped;

            // Update button text
            $(this).text(isGrouped ? 'Ungroup Materials' : 'Group by Material Type');

            // Fetch grouped or ungrouped data
            $.ajax({
                url: 'fetch_materials_print.php',
                method: 'GET',
                data: { quotation_id: <?= $quotation_id; ?>, grouped: isGrouped },
                dataType: 'html',
                success: function (response) {
                    $('#materialsTableBody').html(response);
                },
                error: function (xhr, status, error) {
                    alert('An error occurred while fetching data.');
                    console.error(xhr.responseText);
                }
            });
        });
    });
</script>


<script>
    // send email ajax 
    document.getElementById('sendEmailButton').addEventListener('click', function () {
        const email = prompt("Enter the recipient's email address:");

        if (email) {
            fetch('send_quotation_email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    quotation_id: <?= $quotation_id; ?>,
                    token: "<?= htmlspecialchars($token); ?>",
                    email: email
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Email sent successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred.');
            });
        } else {
            alert('Email address is required.');
        }
    });
</script>

</body>
</html>
