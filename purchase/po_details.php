<?php
session_start();
require_once '../database/db_connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Validate token from URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("<div class='alert alert-danger text-center'>❌ Invalid Access: Token is required.</div>");
}

$po_token = $_GET['token'];

// Fetch Purchase Order details securely using the token
$sql = "SELECT po.id, po.po_number, po.po_date, po.quotation_no, 
               po.inco_term, po.payment_term, po.general_terms, po.delivery_terms, 
               acc.account_name AS supplier, mc.company_name AS company, 
               COALESCE(SUM(pom.grand_total), 0) AS total_amount,
               po.po_status,  -- ✅ Fetch PO status
               pos.status_name AS status  -- ✅ Fetch status name from purchase_order_status
        FROM purchase_orders po
        LEFT JOIN purchase_order_status pos ON po.po_status = pos.id  -- ✅ Proper LEFT JOIN
        LEFT JOIN account acc ON po.supplier_id = acc.id
        LEFT JOIN master_company mc ON po.company_name_id = mc.id
        LEFT JOIN purchase_order_materials pom ON po.id = pom.po_id
        WHERE po.token = ? AND po.master_user_id = ?
        GROUP BY po.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $po_token, $master_userid);
$stmt->execute();
$result = $stmt->get_result();

// Check if PO exists
if ($result->num_rows === 0) {
    die("<div class='alert alert-danger text-center'>❌ Purchase Order Not Found or Unauthorized Access.</div>");
}
$po = $result->fetch_assoc();

// Fetch Purchase Order Materials
$sql_materials = "
    SELECT 
        pom.material_id, 
        mm.name AS material_name,
        mmu.unit_name AS unit,
        mm.hsn_code AS hsn_sac,
        pom.make, 
        pom.quantity, 
        pom.unit_price, 
        pom.total, 
        pom.gst_percentage, 
        pom.gst_total, 
        pom.grand_total,
        pom.material_description, 
        pom.special_remark
    FROM purchase_order_materials pom
    INNER JOIN master_materials mm ON pom.material_id = mm.id
    LEFT JOIN master_materials_unit mmu ON pom.unit = mmu.id  -- ✅ Fix this Join!
    WHERE pom.po_id = ?
";

$stmt_mat = $conn->prepare($sql_materials);
$stmt_mat->bind_param("i", $po['id']);
$stmt_mat->execute();
$materials_result = $stmt_mat->get_result();


$sql_price_analysis = "
    SELECT pom.material_id, mm.name AS material_name, 
           acc.account_name AS supplier, 
           GROUP_CONCAT(DISTINCT contacts.name ORDER BY contacts.id ASC SEPARATOR ', ') AS contact_names,
           GROUP_CONCAT(DISTINCT contacts.mobile1 ORDER BY contacts.id ASC SEPARATOR ', ') AS contact_mobile1,
           GROUP_CONCAT(DISTINCT contacts.mobile2 ORDER BY contacts.id ASC SEPARATOR ', ') AS contact_mobile2,
           pom.unit_price, po.po_number, po.po_date, po.id, po.token, 
           mc.company_name as company
    FROM purchase_order_materials pom
    INNER JOIN purchase_orders po ON pom.po_id = po.id
    INNER JOIN account acc ON po.supplier_id = acc.id
    INNER JOIN master_materials mm ON pom.material_id = mm.id
    INNER JOIN master_company mc ON po.company_name_id = mc.id
    LEFT JOIN contacts ON contacts.account_id = acc.id  -- ✅ Fetch Multiple Supplier Contacts
    WHERE pom.material_id IN (
        SELECT material_id FROM purchase_order_materials WHERE po_id = ?
    )
    GROUP BY pom.material_id, po.id  -- ✅ Group to Prevent Duplicate Rows
    ORDER BY po.id  desc 
    LIMIT 11";



$stmt_price = $conn->prepare($sql_price_analysis);
$stmt_price->bind_param("i", $po['id']);
$stmt_price->execute();
$price_result = $stmt_price->get_result();

// Group by Material ID for display
$price_analysis = [];
while ($row = $price_result->fetch_assoc()) {
    $price_analysis[$row['material_id']][] = $row;
}


// Fetch Purchase Order Approval Remarks History
$sql_remarks = "SELECT por.remark, por.status, por.created_at, 
                       mu.name AS approver_name
                FROM purchase_order_remarks por
                LEFT JOIN master_users mu ON por.approver_id = mu.id
                WHERE por.po_token = ?
                ORDER BY por.created_at DESC";  // Order by latest first

$stmt_remarks = $conn->prepare($sql_remarks);
$stmt_remarks->bind_param("s", $po_token);
$stmt_remarks->execute();
$remarks_result = $stmt_remarks->get_result();



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order Details - <?= htmlspecialchars($po['po_number']); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="po_styles.css"> <!-- Common Stylesheet -->

    <style>
        .content {
            padding: 50px;
        }

        .card-header {
            font-size: 1.1rem;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <?php include('../headers/header.php'); ?>

    <div class="d-flex">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?>

        <!-- Main Content -->
        <div class="content w-100">
            <div class="container mt-4">
                <h4 class="text-center mb-3">Purchase Order Details</h4>
                

                <!-- PO Details Card -->
                <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-file-earmark-text"></i> Purchase Order Information
                        </div>
                        <div>
                            <span class="badge fs-5 p-3 
                <?= isset($po['po_status']) ?
                    ($po['po_status'] == 2 ? 'bg-warning' : ($po['po_status'] == 3 ? 'bg-success' : ($po['po_status'] == 4 ? 'bg-danger' : ($po['po_status'] == 5 ? 'bg-secondary' : 'bg-dark'))))
                    : 'bg-dark' ?>">
                                PO Status : <?= isset($po['po_status']) ? htmlspecialchars($po['status']) : "Unknown Status" ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><i class="bi bi-hash"></i> PO Number:</strong> <?= htmlspecialchars($po['po_number']); ?></p>
                                <p><strong><i class="bi bi-buildings"></i> Company:</strong> <?= htmlspecialchars($po['company']); ?></p>
                                <p><strong><i class="bi bi-person-circle"></i> Supplier:</strong> <?= htmlspecialchars($po['supplier']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="bi bi-calendar-event"></i> PO Date:</strong> <?= date("d-M-Y", strtotime($po['po_date'])); ?></p>
                                <p><strong><i class="bi bi-receipt"></i> Quotation No:</strong> <?= htmlspecialchars($po['quotation_no'] ?? "N/A"); ?></p>
                                <p><strong><i class="bi bi-currency-exchange"></i> Total Amount (₹):</strong>
                                    <span class="fw-bold text-success"><?= number_format($po['total_amount'], 2); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Materials Ordered Card -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-box-seam"></i> Materials Ordered
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Material Name</th>
                                        <th>Make</th>
                                        <th>HSN/SAC</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                        <th>Unit Price (₹)</th>
                                        <th>Total (₹)</th>
                                        <th>GST%</th>
                                        <th>GST Total (₹)</th>
                                        <th>Grand Total (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_amount = 0;
                                    $total_gst_total = 0;
                                    $total_grand_total = 0;

                                    if ($materials_result->num_rows > 0):
                                        while ($mat = $materials_result->fetch_assoc()):
                                            $total_amount += $mat['total'];
                                            $total_gst_total += $mat['gst_total'];
                                            $total_grand_total += $mat['grand_total'];
                                    ?>
                                            <tr>
                                                <td class="text-start">
                                                    <strong><?= htmlspecialchars($mat['material_name']); ?></strong><br>

                                                    <?php if (!empty($mat['material_description'])): ?>
                                                        Description: <?= htmlspecialchars($mat['material_description']); ?><br>
                                                    <?php endif; ?>

                                                    <?php if (!empty($mat['special_remark'])): ?>
                                                        Special Remark: <?= htmlspecialchars($mat['special_remark']); ?>
                                                    <?php endif; ?>
                                                </td>

                                                <td><?= htmlspecialchars($mat['make'] ?? "N/A"); ?></td>
                                                <td><?= htmlspecialchars($mat['hsn_sac'] ?? "N/A"); ?></td>
                                                <td class="text-center"><?= number_format($mat['quantity'], 2); ?></td>
                                                <td><?= htmlspecialchars($mat['unit'] ?? "N/A"); ?></td>
                                                <td class="text-end"><?= number_format($mat['unit_price'], 2); ?></td>
                                                <td class="text-end"><?= number_format($mat['total'], 2); ?></td>
                                                <td class="text-end"><?= number_format($mat['gst_percentage'], 2); ?>%</td>
                                                <td class="text-end"><?= number_format($mat['gst_total'], 2); ?></td>
                                                <td class="text-end fw-bold"><?= number_format($mat['grand_total'], 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                        <!-- Grand Total Row -->
                                        <tr class="table-warning">
                                            <td colspan="6" class="text-end fw-bold">Total (₹):</td>
                                            <td class="text-end fw-bold"><?= number_format($total_amount, 2); ?></td>
                                            <td class="text-end fw-bold"></td>
                                            <td class="text-end fw-bold"><?= number_format($total_gst_total, 2); ?></td>
                                            <td class="text-end fw-bold text-success"><?= number_format($total_grand_total, 2); ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No materials linked to this PO</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>

                  <!-- Material Price Analysis Card -->
                  <div class="card shadow-sm mt-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-graph-up"></i> Material Price Analysis (Max Last 10 POs)</span>
                        <button class="btn btn-light btn-sm" id="toggleAccordion"><i class="bi bi-arrows-expand"></i> Expand All</button>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="priceAnalysisAccordion">
                            <?php if (!empty($price_analysis)): ?>
                                <?php foreach ($price_analysis as $material_id => $prices): ?>
                                    <?php
                                    // Calculate Min, Max, and Average price
                                    $minPrice = min(array_column($prices, 'unit_price'));
                                    $maxPrice = max(array_column($prices, 'unit_price'));
                                    $avgPrice = array_sum(array_column($prices, 'unit_price')) / count($prices);
                                    ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?= $material_id ?>">
                                            <button class="accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $material_id ?>">
                                                <span class="text-start flex-grow-1">
                                                    <strong> <?= htmlspecialchars($prices[0]['material_name']) ?> </strong>
                                                </span>
                                                <span class="d-flex gap-2">
                                                    <span class="badge bg-secondary fs-6 p-1 rounded-pill"> Min: ₹<?= number_format($minPrice, 2) ?> </span>
                                                    <span class="badge bg-success fs-6 p-1 rounded-pill"> Max: ₹<?= number_format($maxPrice, 2) ?> </span>
                                                    <span class="badge bg-primary fs-6 p-1 rounded-pill"> Avg: ₹<?= number_format($avgPrice, 2) ?> </span>
                                                </span>
                                            </button>
                                        </h2>

                                        <div id="collapse<?= $material_id ?>" class="accordion-collapse collapse" data-bs-parent="#priceAnalysisAccordion">
                                            <div class="accordion-body">
                                                <!-- Price Comparison Progress Bar -->
                                                <div class="progress mb-3">
                                                    <div class="progress-bar bg-secondary" role="progressbar" style="width: <?= ($minPrice / $maxPrice) * 100 ?>%;" title="Min: ₹<?= number_format($minPrice, 2) ?>">
                                                        ₹<?= number_format($minPrice, 2) ?>
                                                    </div>

                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= ($maxPrice / $maxPrice) * 100 ?>%;" title="Max: ₹<?= number_format($maxPrice, 2) ?>">
                                                        ₹<?= number_format($maxPrice, 2) ?>
                                                    </div>
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= ($avgPrice / $maxPrice) * 100 ?>%;" title="Avg: ₹<?= number_format($avgPrice, 2) ?>">
                                                        ₹<?= number_format($avgPrice, 2) ?>
                                                    </div>
                                                </div>

                                                <table class="table table-sm table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>PO Number</th>
                                                            <th>PO Date</th>
                                                            <th>Company</th>
                                                            <th>Supplier</th>
                                                            <th>Unit Price (₹)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($prices as $price): ?>
                                                            <tr class="<?= ($price['token'] == $po_token) ? 'table-info fw-bold' : '' ?>">
                                                                <td class="text-start">
                                                                    <?php if ($price['token'] == $po_token): ?>
                                                                        <span class="text-primary"><?= htmlspecialchars($price['po_number']) ?> (Current PO Price)</span>
                                                                    <?php else: ?>
                                                                        <a href="po_details.php?token=<?= htmlspecialchars($price['token']) ?>&id=<?= htmlspecialchars($po['id']) ?>" target="_blank">
                                                                            <?= htmlspecialchars($price['po_number']) ?>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?= date("d-M-Y", strtotime($price['po_date'])) ?></td>
                                                                <td class="text-start"><?= htmlspecialchars($price['company']) ?></td>
                                                                <!-- ✅ Supplier Name + Multiple Contact Details Inside Single Row -->
                                                                <td class="text-start">
                                                                    <strong><?= htmlspecialchars($price['supplier']) ?></strong><br>

                                                                    <?php
                                                                    $contactNames = explode(", ", $price['contact_names'] ?? '');
                                                                    $mobile1List = explode(", ", $price['contact_mobile1'] ?? '');
                                                                    $mobile2List = explode(", ", $price['contact_mobile2'] ?? '');

                                                                    foreach ($contactNames as $index => $contactName):
                                                                        if (!empty($contactName)): ?>
                                                                            <i class="bi bi-person"></i> <?= htmlspecialchars($contactName) ?><br>
                                                                        <?php endif;
                                                                        if (!empty($mobile1List[$index])): ?>
                                                                            <i class="bi bi-telephone"></i> <?= htmlspecialchars($mobile1List[$index]) ?>
                                                                        <?php endif;
                                                                        if (!empty($mobile2List[$index])): ?>
                                                                            - <?= htmlspecialchars($mobile2List[$index]) ?><br>
                                                                    <?php endif;
                                                                    endforeach; ?>
                                                                </td>
                                                                <td class="text-end"><?= number_format($price['unit_price'], 2) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center">No historical data available for this PO's materials.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>



                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const toggleButton = document.getElementById("toggleAccordion");
                        const accordionItems = document.querySelectorAll(".accordion-collapse");
                        let expanded = false;

                        toggleButton.addEventListener("click", function() {
                            expanded = !expanded;
                            accordionItems.forEach(item => {
                                if (expanded) {
                                    new bootstrap.Collapse(item, {
                                        show: true
                                    });
                                } else {
                                    new bootstrap.Collapse(item, {
                                        hide: true
                                    });
                                }
                            });

                            // Update button text and icon
                            toggleButton.innerHTML = expanded ?
                                '<i class="bi bi-arrows-collapse"></i> Collapse All' :
                                '<i class="bi bi-arrows-expand"></i> Expand All';
                        });
                    });
                </script>


<!-- Purchase Order Remarks History -->
<div class="card shadow-sm mt-4">
    <div class="card-header bg-warning text-white">
        <i class="bi bi-clock-history"></i> Purchase Order Remarks History
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Approver</th>
                        <th>Remark</th>
                        <th>Status</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($remarks_result->num_rows > 0): ?>
                        <?php while ($remark = $remarks_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($remark['approver_name'] ?? 'Unknown'); ?></td>
                                <td><?= htmlspecialchars($remark['remark']); ?></td>
                                <td>
                                    <span class="badge 
                                        <?= ($remark['status'] == 3) ? 'bg-success' : 
                                            (($remark['status'] == 4) ? 'bg-danger' : 
                                            (($remark['status'] == 5) ? 'bg-secondary' : 
                                            (($remark['status'] == 6) ? 'bg-warning' : 'bg-dark'))); ?>">
                                        <?= ($remark['status'] == 3) ? 'Approved' :
                                            (($remark['status'] == 4) ? 'Rejected' :
                                            (($remark['status'] == 5) ? 'Cancelled' :
                                            (($remark['status'] == 6) ? 'Revision Required' : 'Unknown'))); ?>
                                    </span>
                                </td>
                                <td><?= date("d-M-Y H:i:s", strtotime($remark['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No remarks found for this PO.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


                <!-- Back to Dashboard Button -->
                <div class="mt-4">
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>