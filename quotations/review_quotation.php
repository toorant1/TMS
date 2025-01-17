<?php
// Decode the JSON data passed in the query string
if (!isset($_GET['data'])) {
    die('No data provided.');
}

$data = json_decode(urldecode($_GET['data']), true);

if (!$data) {
    die('Invalid data format.');
}

// Extract data
$internalID = $data['internalID']; // Extract the internal ID
$companyID = $data['companyID'];
$quotationDate = $data['quotationDate'];
$quotationValidDate = $data['quotationValidDate'];
$statusID = $data['statusID'];
$termsConditions = $data['termsConditions'];
$materials = $data['materials'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Quotation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1 class="text-center mb-4">Review Quotation</h1>

    <!-- Quotation Details -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Quotation Details</div>
        <div class="card-body">
            <p><strong>Internal ID:</strong> <?= htmlspecialchars($internalID) ?></p>
            <p><strong>Company ID:</strong> <?= htmlspecialchars($companyID) ?></p>
            <p><strong>Quotation Date:</strong> <?= htmlspecialchars($quotationDate) ?></p>
            <p><strong>Valid Upto:</strong> <?= htmlspecialchars($quotationValidDate) ?></p>
            <p><strong>Status ID:</strong> <?= htmlspecialchars($statusID) ?></p>
        </div>
    </div>

    <!-- Terms and Conditions -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">Terms and Conditions</div>
        <div class="card-body">
            <p><strong>Payment Conditions:</strong> <?= htmlspecialchars($termsConditions['paymentConditions']) ?></p>
            <p><strong>Delivery Conditions:</strong> <?= htmlspecialchars($termsConditions['deliveryConditions']) ?></p>
            <p><strong>Other Conditions:</strong> <?= htmlspecialchars($termsConditions['otherConditions']) ?></p>
            <p><strong>Internal Remarks:</strong> <?= htmlspecialchars($termsConditions['internalRemarkConditions']) ?></p>
        </div>
    </div>

    <!-- Materials -->
    <table class="table table-bordered">
    <thead>
        <tr>
            <th>Material ID</th>
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
    <tbody>
        <?php foreach ($materials as $material): ?>
            <tr>
                <td><?= htmlspecialchars($material['materialID']) ?></td>
                <td><?= htmlspecialchars($material['materialType']) ?></td>
                <td><?= htmlspecialchars($material['make']) ?></td>
                <td><?= htmlspecialchars($material['materialName']) ?></td>
                <td><?= htmlspecialchars($material['quantity']) ?></td>
                <td><?= htmlspecialchars($material['unitName']) ?></td>
                <td><?= htmlspecialchars($material['price']) ?></td>
                <td><?= htmlspecialchars($material['basicTotal']) ?></td>
                <td><?= htmlspecialchars($material['hsnCode']) ?></td>
                <td><?= htmlspecialchars($material['hsnPercentage']) ?></td>
                <td><?= htmlspecialchars($material['hsnTotal']) ?></td>
                <td><?= htmlspecialchars($material['grandTotal']) ?></td>
                <td><?= htmlspecialchars($material['materialRemark'] ?? 'N/A') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

        </div>
    </div>

    <!-- Action Buttons -->
    <div class="text-center">
        <button class="btn btn-secondary" onclick="window.history.back()">Back</button>
        <button class="btn btn-primary" onclick="submitQuotation()">Submit Quotation</button>
    </div>
</div>

<script>
function submitQuotation() {
    // Submit the data to the server for database storage
    const data = <?= json_encode($data) ?>;
    fetch('save_quotation_to_db.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Quotation saved successfully!');
            window.location.href = 'quotation_list.php'; // Redirect to the list page
        } else {
            alert('Error saving quotation: ' + result.error);
        }
    })
    .catch(error => alert('Error submitting quotation.'));
}
</script>
</body>
</html>
