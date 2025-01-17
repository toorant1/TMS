<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];
$account_id = isset($_GET['account_id']) ? (int)$_GET['account_id'] : 0;
$token = isset($_GET['token']) ? filter_var($_GET['token'], FILTER_SANITIZE_STRING) : '';

if ($account_id <= 0 || empty($token)) {
    die("Invalid Account ID or Token.");
}

// Query to fetch account details
$query_account = "
    SELECT account_name, address, city, state, pincode, country
    FROM account
    WHERE id = ?  AND master_user_id = ? 
";
//AND token = ?


$stmt_account = $conn->prepare($query_account);

//$stmt_account->bind_param("isi", $account_id, $token, $master_userid);
$stmt_account->bind_param("ii", $account_id, $master_userid);
$stmt_account->execute();
$result_account = $stmt_account->get_result();

if ($result_account->num_rows === 0) {
    die("Account not found.");
}

$account = $result_account->fetch_assoc();

// Query to fetch tickets for the account
$query_tickets = "
    SELECT 
        mt.ticket_id AS `Ticket ID`,
        DATE(mt.ticket_date) AS `Ticket Date`,
        IFNULL(mtt.ticket_type, 'N/A') AS `Ticket Type`,
        IFNULL(mp.priority, 'N/A') AS `Priority`,
        IFNULL(ms.status_name, 'N/A') AS `Status`,
        mt.problem_statement AS `Problem Statement`
    FROM master_tickets mt
    LEFT JOIN master_tickets_types mtt ON mt.ticket_type_id = mtt.id
    LEFT JOIN master_tickets_priority mp ON mt.ticket_priority_id = mp.id
    LEFT JOIN master_tickets_status ms ON mt.ticket_status_id = ms.id
    WHERE mt.master_user_id = ? AND mt.account_id = ?
    ORDER BY mt.ticket_date DESC
";

$stmt_tickets = $conn->prepare($query_tickets);
$stmt_tickets->bind_param("ii", $master_userid, $account_id);
$stmt_tickets->execute();
$result_tickets = $stmt_tickets->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service History for <?= htmlspecialchars($account['account_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center mb-4"><?= htmlspecialchars($account['account_name']); ?></h1>
    <p class="text-center">
        <?= htmlspecialchars($account['address']); ?>, <?= htmlspecialchars($account['city']); ?>, 
        <?= htmlspecialchars($account['state']); ?>, <?= htmlspecialchars($account['country']); ?> - <?= htmlspecialchars($account['pincode']); ?>
    </p>
    <h5 class="text-center mb-4">Service History</h5>

    <?php if ($result_tickets->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Ticket ID</th>
                        <th>Ticket Date</th>
                        <th>Ticket Type</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Problem Statement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result_tickets->fetch_assoc()): ?>
                        <tr>
                        <td>
                <a href="ticket_operation.php?ticket_id=<?= urlencode($row['Ticket ID']); ?>&token=<?= urlencode($token); ?>" 
                   class="text-primary">
                    <?= htmlspecialchars($row['Ticket ID']); ?>
                </a>
            </td>
                            <td><?= htmlspecialchars($row['Ticket Date']); ?></td>
                            <td><?= htmlspecialchars($row['Ticket Type']); ?></td>
                            <td><?= htmlspecialchars($row['Priority']); ?></td>
                            <td><?= htmlspecialchars($row['Status']); ?></td>
                            <td><?= htmlspecialchars($row['Problem Statement']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            No tickets found for this account.
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
