<?php
// Fetch ticket data from query parameters
$tickets = isset($_GET['data']) ? json_decode(urldecode($_GET['data']), true) : [];

if (empty($tickets)) {
    echo "<h3>No tickets available for this account.</h3>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Ticket Details</h2>
    <div class="row">
        <?php foreach ($tickets as $ticket): ?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($ticket['card_title']) ?></h5>
                        <p class="card-text">Status: <?= htmlspecialchars($ticket['card_status']) ?></p>
                        <p class="card-text">Ticket ID: <?= htmlspecialchars($ticket['card_details']['Ticket ID']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <a href="new_ticket.php" class="btn btn-secondary mt-4">Back to New Ticket</a>
</div>
</body>
</html>
