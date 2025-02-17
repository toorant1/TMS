<?php
session_start();
require_once '../database/db_connection.php';

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

$master_userid = $_SESSION['master_userid'];
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Default date range: From (3 months ago, 1st day) to Today
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date("Y-m-01", strtotime("-3 months"));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date("Y-m-d");

// Fetch Purchase Orders with search filter and date range
$sql = "SELECT po.id, po.po_number, po.token, acc.account_name AS supplier, 
               mc.company_name AS company, po.po_date, 
               COALESCE(SUM(pom.grand_total), 0) AS total_amount,
               pos.status_name AS status
        FROM purchase_orders po
        LEFT JOIN account acc ON po.supplier_id = acc.id
        LEFT JOIN master_company mc ON po.company_name_id = mc.id
        LEFT JOIN purchase_order_materials pom ON po.id = pom.po_id
        INNER JOIN purchase_order_status pos ON po.po_status = pos.id
        WHERE po.master_user_id = ? 
        AND po.po_date BETWEEN ? AND ?";


// Apply search filter
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

// Fetch Total Purchase Order Value per Company with date range
$sql_company_summary = "SELECT mc.company_name, COALESCE(SUM(pom.grand_total), 0) AS total_value
                        FROM purchase_orders po
                        LEFT JOIN master_company mc ON po.company_name_id = mc.id
                        LEFT JOIN purchase_order_materials pom ON po.id = pom.po_id
                        WHERE po.master_user_id = ? 
                        AND po.po_date BETWEEN ? AND ?
                        ORDER BY po.id DESC";
$stmt_company = $conn->prepare($sql_company_summary);
$stmt_company->bind_param("iss", $master_userid, $from_date, $to_date);
$stmt_company->execute();
$company_result = $stmt_company->get_result();


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            width: 200px;
            min-height: 100vh;
            background: #343a40;
            color: white;
            position: fixed;
            padding-top: 20px;
        }

        .sidebar a {
            color: white;
            padding: 10px;
            display: block;
            text-decoration: none;
        }

        .sidebar a:hover {
            background: #495057;
        }

        .content {
            margin-left: 200px;
            padding: 50px;
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
            <div class="container">
                <h1 class="text-center mb-4">Purchase Orders Dashboard</h1>




                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-funnel"></i> Filter Purchase Orders</h6>
                        <button type="button" id="resetFilters" class="btn btn-light btn-sm"><i class="bi bi-arrow-clockwise"></i> Reset</button>
                    </div>
                    <div class="card-body">
                        <form id="searchForm" class="row g-3">
                            <!-- Search Input -->
                            <div class="col-md-4">
                                <input type="text" id="search" name="search" class="form-control" placeholder="Search PO Number, Supplier, or Company">
                            </div>

                            <!-- From Date -->
                            <div class="col-md-3">
                                <input type="date" id="from_date" name="from_date" class="form-control">
                            </div>

                            <!-- To Date -->
                            <div class="col-md-3">
                                <input type="date" id="to_date" name="to_date" class="form-control">
                            </div>

                            <!-- Search & Add PO Button -->
                            <div class="col-md-2 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
                                <a href="po_new.php" class="btn btn-success ms-2"><i class="bi bi-plus-circle"></i> Add PO</a>
                            </div>
                        </form>
                    </div>
                    <!-- Stylish Group Toggle Radio Buttons -->
                    <div class="d-flex justify-content-end align-items-center gap-2 mb-3">
                        <label class="fw-bold text-dark">Group View Mode:</label>
                        <div class="btn-group" role="group" id="groupToggle">
                            <input type="radio" class="btn-check" name="groupBy" id="groupAll" value="all" autocomplete="off" checked>
                            <label class="btn btn-outline-primary" for="groupAll">All</label>

                            <input type="radio" class="btn-check" name="groupBy" id="groupCompany" value="company" autocomplete="off">
                            <label class="btn btn-outline-primary" for="groupCompany">Company</label>

                            <input type="radio" class="btn-check" name="groupBy" id="groupSupplier" value="supplier" autocomplete="off">
                            <label class="btn btn-outline-primary" for="groupSupplier">Supplier</label>
                        </div>
                    </div>

                </div>

                <!-- Table to display purchase orders -->
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
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="poTableBody">
                            <tr>
                                <td colspan="6" class="text-center text-muted">Enter search criteria and press Filter</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script>
                $(document).ready(function() {
                    function setDefaultDates() {
                        let today = new Date().toISOString().split("T")[0];
                        let threeMonthsAgo = new Date();
                        threeMonthsAgo.setMonth(threeMonthsAgo.getMonth() - 3);
                        let firstDayOfThreeMonthsAgo = new Date(threeMonthsAgo.getFullYear(), threeMonthsAgo.getMonth(), 1).toISOString().split("T")[0];

                        $("#from_date").val(firstDayOfThreeMonthsAgo);
                        $("#to_date").val(today);
                    }

                    // Apply default values on page load
                    setDefaultDates();

                    // Function to fetch purchase order data dynamically
                    function fetchPOData() {
                        let search = $("#search").val();
                        let from_date = $("#from_date").val();
                        let to_date = $("#to_date").val();
                        let groupBy = $("input[name='groupBy']:checked").val(); // Get selected toggle value

                        $.ajax({
                            url: "fetch_po_data.php",
                            type: "GET",
                            data: {
                                search,
                                from_date,
                                to_date,
                                groupBy
                            },
                            dataType: "json",
                            beforeSend: function() {
                                $("#poTableBody").html("<tr><td colspan='7' class='text-center'>🔄 Loading...</td></tr>");
                            },
                            success: function(response) {
                                if (response.status === "success") {
                                    let poHTML = "";
                                    let currentGroup = "";

                                    if (response.data.length > 0) {
                                        response.data.forEach(po => {
                                            if (groupBy === "company" && currentGroup !== po.company) {
                                                currentGroup = po.company;
                                                poHTML += `<tr class="table-primary"><td colspan="7"><strong>Company: ${po.company}</strong></td></tr>`;
                                            }
                                            if (groupBy === "supplier" && currentGroup !== po.supplier) {
                                                currentGroup = po.supplier;
                                                poHTML += `<tr class="table-success"><td colspan="7"><strong>Supplier: ${po.supplier}</strong></td></tr>`;
                                            }

                                            let actionDropdown = `
    <div class="dropdown">
        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            Actions
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item bg-info text-white" href="export_po.php?token=${po.token}"><i class="bi bi-file-earmark-excel"></i> Download BOQ</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item bg-warning text-dark" href="export_po_pdf.php?token=${po.token}"><i class="bi bi-file-earmark-pdf"></i> Download PO (PDF)</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item bg-success text-white send-approval" href="#" data-token="${po.token}"><i class="bi bi-check-circle"></i> Send for Approval</a></li>
        </ul>
    </div>
`;


                                            poHTML += `<tr>
                            <td><a href="po_details.php?token=${po.token}" class="text-decoration-none fw-bold">${po.po_number}</a></td>
                            <td>${po.po_date}</td>
                            <td>${po.company}</td>
                            <td>${po.supplier}</td>
                            <td class="text-end">₹${po.total_amount}</td>
                            <td>${getStatusBadge(po.status)}</td>

                          <td>${actionDropdown}</td>

                            
                        </tr>`;
                                        });
                                    } else {
                                        poHTML = `<tr><td colspan='7' class='text-center text-muted'>No Purchase Orders Found</td></tr>`;
                                    }

                                    $("#poTableBody").html(poHTML);

                                    // Attach event listener to the "Send for Approval" buttons
                                    $(".send-approval").click(function() {
                                        let poToken = $(this).data("token");
                                        sendForApproval(poToken, $(this));
                                    });
                                } else {
                                    alert("Error fetching data.");
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error("AJAX Error: ", xhr.responseText);
                                alert("AJAX error. Please try again.");
                            }
                        });
                    }

                    // Function to update status when clicking "Send for Approval"

                    // Function to send PO for approval// Function to send PO for approval
                    function sendForApproval(poToken, button) {
                        $.ajax({
                            url: "update_po_status.php", // The PHP script to update PO status
                            type: "POST",
                            data: {
                                token: poToken,
                                status_id: 2
                            }, // Status ID 2 = "Under Approval"
                            dataType: "json",
                            beforeSend: function() {
                                button.prop("disabled", true).html('<i class="bi bi-hourglass-split"></i> Processing...');
                            },
                            success: function(response) {
                                if (response.status === "success") {
                                    // Refresh table data after successful update
                                    fetchPOData();
                                } else {
                                    alert(response.message);
                                    button.prop("disabled", false).text("Send for Approval");
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error("AJAX Error: ", xhr.responseText);
                                alert("Error: Unable to process request.");
                                button.prop("disabled", false).text("Send for Approval");
                            }
                        });
                    }


                    // Fetch data on form submission
                    $("#searchForm").submit(function(e) {
                        e.preventDefault();
                        fetchPOData();
                    });

                    // Reset Filters and Reload Default Data
                    $("#resetFilters").click(function() {
                        $("#search").val(""); // Clear search field
                        setDefaultDates(); // Reset date range
                        $("#groupAll").prop("checked", true); // Reset to "All"
                        fetchPOData(); // Fetch data again
                    });

                    // Toggle radio button event for grouping
                    $("input[name='groupBy']").change(function() {
                        fetchPOData();
                    });

                    // Fetch data when the page loads
                    fetchPOData();
                });

                // Function to dynamically assign colors based on status
function getStatusBadge(status) {
    let statusClass = "bg-dark"; // Default color

    switch (status.toLowerCase()) {
        case "pending":
            statusClass = "bg-warning text-dark"; // Yellow for Pending
            break;
        case "under approval":
            statusClass = "bg-primary"; // Blue for Under Approval
            break;
        case "approved":
            statusClass = "bg-success"; // Green for Approved
            break;
        case "rejected":
            statusClass = "bg-danger"; // Red for Rejected
            break;
        case "cancelled":
            statusClass = "bg-secondary"; // Grey for Cancelled
            break;
        case "revision required":
            statusClass = "bg-info"; // Light Blue for Revision
            break;
    }

    return `<span class="badge ${statusClass} fs-7 p-2">${status}</span>`; // Bigger Font Size (fs-6)
}


            </script>


</body>

</html>