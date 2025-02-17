<?php
session_start();
require_once '../database/db_connection.php';

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date("Y-m-01", strtotime("-3 months"));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date("Y-m-d");

// Fetch Purchase Orders with proper joins
$sql = "SELECT po.id, po.po_number, po.token, acc.account_name AS supplier, 
               mc.company_name AS company, po.po_date, 
               COALESCE(SUM(pom.grand_total), 0) AS total_amount,
               pos.status_name AS status, po.po_status
        FROM purchase_orders po
        LEFT JOIN account acc ON po.supplier_id = acc.id
        LEFT JOIN master_company mc ON po.company_name_id = mc.id
        LEFT JOIN purchase_order_materials pom ON po.id = pom.po_id
        LEFT JOIN purchase_order_status pos ON po.po_status = pos.id
        WHERE po.master_user_id = ? 
        AND po.po_date BETWEEN ? AND ?
        AND po.po_status != 1";  // Exclude POs with status = 1

if (!empty($search_query)) {
    $sql .= " AND (po.po_number LIKE ? OR acc.account_name LIKE ? OR mc.company_name LIKE ?)";
}

$sql .= " GROUP BY po.id ORDER BY po.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    $stmt->bind_param("isssss", $master_userid, $from_date, $to_date, $search_param, $search_param, $search_param);
} else {
    $stmt->bind_param("iss", $master_userid, $from_date, $to_date);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="po_styles.css"> <!-- Common Stylesheet -->
</head>

<body>

    <?php include('../headers/header.php'); ?>
    <div class="d-flex">
        <?php include('sidebar.php'); ?>
        <div class="content w-100">
            <div class="container">
                <h1 class="text-center mb-4">Purchase Orders Dashboard</h1>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>PO Number</th>
                                <th>Purchase Date</th>
                                <th>Company</th>
                                <th>Supplier</th>
                                <th>Total Amount (₹)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="poTableBody">
    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td>
                <a href="po_analysis.php?token=<?= htmlspecialchars($row['token']) ?>" class="fw-bold">
                    <?= htmlspecialchars($row['po_number']) ?>
                </a>
            </td>
            <td><?= date("d-M-Y", strtotime($row['po_date'])) ?></td>
            <td><?= htmlspecialchars($row['company']) ?></td>
            <td><?= htmlspecialchars($row['supplier']) ?></td>
            <td class="text-end">₹<?= number_format($row['total_amount'], 2) ?></td>

            <!-- Status Column (No Actions) -->
            <td class="status-badge">
                <span class="badge 
                    <?= $row['po_status'] == 2 ? 'bg-warning' : 
                        ($row['po_status'] == 3 ? 'bg-success' :
                        ($row['po_status'] == 4 ? 'bg-danger' : 
                        ($row['po_status'] == 5 ? 'bg-secondary' : ''))) ?>">
                    <?= htmlspecialchars($row['status']) ?>
                </span>
            </td>
        </tr>
    <?php } ?>
</tbody>


                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>

$(document).ready(function () {
    // Function to update PO status dynamically
    function updatePOStatus(poToken, statusId, button) {
        $.ajax({
            url: "update_po_status.php",
            type: "POST",
            data: { token: poToken, status_id: statusId },
            dataType: "json",
            success: function (response) {
                if (response.status === "success") {
                    let newStatus = "";
                    let badgeClass = "";
                    let dropdownHTML = "";

                    // Define status labels & dropdown actions
                    switch (statusId) {
                        case 2:
                            newStatus = "Under Approval";
                            badgeClass = "bg-warning";
                            dropdownHTML = `
                                <li><a class="dropdown-item approve-po" href="#" data-token="${poToken}">Approve</a></li>
                                <li><a class="dropdown-item reject-po" href="#" data-token="${poToken}">Reject</a></li>
                                <li><a class="dropdown-item cancel-po" href="#" data-token="${poToken}">Cancel</a></li>
                            `;
                            break;
                        case 3:
                            newStatus = "Approved";
                            badgeClass = "bg-success";
                            dropdownHTML = `<li><a class="dropdown-item cancel-po" href="#" data-token="${poToken}">Cancel</a></li>`;
                            break;
                        case 4:
                            newStatus = "Rejected";
                            badgeClass = "bg-danger";
                            break;
                        case 5:
                            newStatus = "Cancelled";
                            badgeClass = "bg-secondary";
                            break;
                    }

                    // Find the closest row of the clicked button
                    let row = button.closest("tr");

                    // Update the status column with new badge
                    row.find(".status-badge").html(`<span class="badge ${badgeClass}">${newStatus}</span>`);

                    // Update the dropdown menu with new actions
                    row.find(".po-actions .dropdown-menu").html(dropdownHTML);

                    // Show success message
                    alert(response.message);
                } else {
                    alert("Error updating status. Please try again.");
                }
            },
            error: function (xhr) {
                console.error("AJAX Error:", xhr.responseText);
                alert("AJAX error. Please try again.");
            }
        });
    }

    // Attach event handlers dynamically for status updates
    $(document).on("click", ".approve-po", function (e) {
        e.preventDefault();
        let poToken = $(this).data("token");
        updatePOStatus(poToken, 3, $(this));  // Status 3 = Approved
    });

    $(document).on("click", ".reject-po", function (e) {
        e.preventDefault();
        let poToken = $(this).data("token");
        updatePOStatus(poToken, 4, $(this));  // Status 4 = Rejected
    });

    $(document).on("click", ".send-approval", function (e) {
        e.preventDefault();
        let poToken = $(this).data("token");
        updatePOStatus(poToken, 2, $(this));  // Status 2 = Under Approval
    });

    $(document).on("click", ".cancel-po", function (e) {
        e.preventDefault();
        let poToken = $(this).data("token");
        updatePOStatus(poToken, 5, $(this));  // Status 5 = Cancelled
    });
});

    </script>
</body>

</html>
