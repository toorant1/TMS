<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    echo "<div class='alert alert-danger text-center'>User not authenticated.</div>";
    exit;
}

if (!isset($_GET['account_id'])) {
    echo "<div class='alert alert-danger text-center'>Invalid customer request.</div>";
    exit;
}

$customer_id = urldecode($_GET['account_id']);
$master_userid = $_SESSION['master_userid'];

$query = "SELECT acc.account_name, mqd.entry_date, mc.company_name, mm.name AS material_name, 
                 mqd.vehicle, mqd.delivery_challan, mqd.gross_weight, mqd.tare_weight, mqd.net_weight, mqd.royalty_weight, 
                 mqd.royalty_name, mqd.royalty_pass_no, mqd.royalty_pass_count, mqd.ssp_no
          FROM master_quarry_dispatch_data mqd
          INNER JOIN master_company mc ON mqd.company_name_id = mc.id
          INNER JOIN account acc ON mqd.customer_name_id = acc.id
          INNER JOIN master_materials mm ON mqd.material_id = mm.id
          WHERE mqd.master_user_id = ? AND acc.id = ?
          ORDER BY mqd.entry_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $master_userid, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$customer_name = "Unknown";
if ($row = $result->fetch_assoc()) {
    $customer_name = $row['account_name'];
    $result->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dispatch Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .table th {
            background-color: #007bff;
            color: white;
            text-align: center;
        }

        .table tbody tr:hover {
            background-color: #f1f1f1;
        }

        .container {
            margin-top: 20px;
        }

        .header {
            background: linear-gradient(90deg, #4caf50, #2196f3);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .alert {
            margin-top: 10px;
        }

    </style>

</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Dispatch Records for <span class="fw-bold"> <?php echo htmlspecialchars($customer_name); ?> </span></h2>
        </div>

        <div class="card mb-4 shadow-sm border-0 mx-auto" style="max-width: 500px; padding-top: 30px;">
        <div class="card-header" style="background: linear-gradient(90deg, #6a11cb, #2575fc); color: white; text-align: center; padding: 15px;">

        <h5 class="mb-0 fw-bold">Filter Options</h5>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="groupBy" class="form-label fw-bold">Group By:</label>
            <select id="groupBy" class="form-select shadow-sm">
                <option value="">Select Group</option>
                <option value="company">Company</option>
                <option value="material">Material</option>
                <option value="vehicle">Vehicle No</option>
                <option value="entry_date">Date</option>
                <option value="royalty_name">Royalty</option>
            </select>
        </div>
        <div class="mb-6 d-flex align-items-end gap-3">
    <div class="flex-grow-1">
        <label for="fromDate" class="form-label fw-bold">From Date:</label>
        <input type="date" id="fromDate" class="form-control shadow-sm" value="<?php echo date('Y-m-01'); ?>">
    </div>
    <div class="flex-grow-1">
        <label for="toDate" class="form-label fw-bold">To Date:</label>
        <input type="date" id="toDate" class="form-control shadow-sm" value="<?php echo date('Y-m-t'); ?>">
    </div>
</div>

    </div>
</div>
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body d-flex justify-content-around align-items-center py-3">
    <button class="btn btn-primary shadow-sm px-4" id="generatePDF">Generate PDF</button>

        <button class="btn btn-secondary shadow-sm px-4" onclick="printPage()">Print Page</button>
        <button class="btn btn-success shadow-sm px-4">Email PDF</button>
        <button class="btn btn-info shadow-sm px-4 text-white">WhatsApp PDF</button>
    </div>
</div>

<script>
    $("#generatePDF").on("click", function () {
    let filters = {
        group_by: $("#groupBy").val() || "",
        from_date: $("#fromDate").val() || "",
        to_date: $("#toDate").val() || "",
        account_id: "<?php echo $customer_id; ?>",
    };

    let queryString = $.param(filters);

    // Redirect to the generate_pdf.php script with the filters
    window.location.href = "generate_pdf.php?" + queryString;
});

</script>


        <div class="table-responsive">
            <table class="table table-striped table-bordered mt-4">
                <thead>
                    <tr>
                        <th>Entry Date</th>
                        <th>Company</th>
                        <th>Material</th>
                        <th>Vehicle</th>
                        <th>Challan No</th>
                        <th>Gross Weight</th>
                        <th>Tare Weight</th>
                        <th>Net Weight</th>
                        <th style='color: black;'>Royalty Weight</th>
                        <th style='color: black;'>Royalty Name</th>
                        <th style='color: black;'>Royalty Pass No</th>
                        <th style='color: black;'>Royalty Pass Count</th>
                        <th style='color: black;'>SSP No</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                </tbody>
            </table>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            function fetchRecords() {

                let groupBy = $("#groupBy").val() || "Unknown Group";
                let fromDate = $("#fromDate").val() || "";
                let toDate = $("#toDate").val() || "";
                $.ajax({
                    url: "fetch_filtered_records.php",
                    type: "POST",
                    data: {
                        group_by: groupBy,
                        from_date: fromDate,
                        to_date: toDate,
                        account_id: "<?php echo $customer_id; ?>"
                    },
                    success: function(response) {
                        $("#tableBody").html(response);
                    }
                });
            }
            $("#searchBox, #groupBy, #fromDate, #toDate").on("change keyup", function() {
                fetchRecords();
            });
            fetchRecords();
        });
    </script>
</body>

</html>
<?php
$stmt->close();
$conn->close();
?>