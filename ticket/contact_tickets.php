<?php
require_once '../database/db_connection.php';
session_start();

if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];
$contact_id = isset($_GET['contact_id']) ? (int)$_GET['contact_id'] : 0;

if ($contact_id <= 0) {
    die("Invalid Contact ID.");
}

// Query to fetch contact details
$query_contact = "
    SELECT name, designation, mobile1, mobile2, email
    FROM contacts
    WHERE id = ?
";

$stmt_contact = $conn->prepare($query_contact);

if ($stmt_contact === false) {
    die("Error in preparing contact query: " . $conn->error);
}

// Bind only the `contact_id` since `master_user_id` might not exist in the `contacts` table
$stmt_contact->bind_param("i", $contact_id);
$stmt_contact->execute();
$result_contact = $stmt_contact->get_result();

if ($result_contact->num_rows === 0) {
    die("Contact not found.");
}

$contact = $result_contact->fetch_assoc();

// Query to fetch tickets for the contact
$query_tickets = "
    SELECT 
        ticket_id AS `Ticket ID`,
        DATE(ticket_date) AS `Ticket Date`,
        problem_statement AS `Problem Statement`,
        ticket_token AS `token`
    FROM master_tickets
    WHERE master_user_id = ? AND contact_id = ?
    ORDER BY ticket_date DESC
";

$stmt_tickets = $conn->prepare($query_tickets);

if ($stmt_tickets === false) {
    die("Error in preparing tickets query: " . $conn->error);
}

$stmt_tickets->bind_param("ii", $master_userid, $contact_id);
$stmt_tickets->execute();
$result_tickets = $stmt_tickets->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets for <?= htmlspecialchars($contact['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center mb-4">Tickets for <?= htmlspecialchars($contact['name']); ?></h1>
    <p class="text-center">
        <strong><?= htmlspecialchars($contact['designation']); ?></strong><br>
        Mobile: <?= htmlspecialchars($contact['mobile1']); ?> / <?= htmlspecialchars($contact['mobile2']); ?><br>
        Email: <?= htmlspecialchars($contact['email']); ?>
    </p>
    <h5 class="text-center mb-4">Ticket History</h5>

    <?php if ($result_tickets->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Ticket ID</th>
                        <th>Ticket Date</th>
                        <th>Problem Statement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result_tickets->fetch_assoc()): ?>
                        <tr>




                            <td>
                            <a href="ticket_operation.php?ticket_id=<?= urlencode($row['Ticket ID']); ?>&token=<?= urlencode($row['token']); ?>" 
                   class="text-primary">
                    <?= htmlspecialchars($row['Ticket ID']); ?>
                </a>
                            <td><?= htmlspecialchars($row['Ticket Date']); ?></td>
                            <td><?= htmlspecialchars($row['Problem Statement']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            No tickets found for this contact.
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
