<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php");
    exit;
}

$master_userid = $_SESSION['master_userid'];

// Get the search term if provided
$search_term = isset($_GET['search']) ? '%' . trim($_GET['search']) . '%' : '%';

// Fetch records from the database with file size
$query = "
    SELECT 
        id, 
        file_name, 
        description, 
        upload_date, 
        upload_token, 
        file_link,
        LENGTH(uploaded_file) AS file_size
    FROM zip_file_storage 
    WHERE master_user_id = ? AND status = 1 
    AND (file_name LIKE ? OR description LIKE ?)
    ORDER BY upload_date DESC
";

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param("iss", $master_userid, $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();
$files = [];
$total_size = 0;

while ($row = $result->fetch_assoc()) {
    $files[] = $row;
    $total_size += $row['file_size'];
}

$stmt->close();

// Convert total size to MB
$total_size_mb = round($total_size / (1024 * 1024), 2);

// Check if total size exceeds 50 MB
$disable_upload = $total_size_mb >= 10;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploaded Files</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include('../headers/header.php'); ?>

    <div class="container mt-5">
        <h1 class="text-center">Uploaded Files</h1>

        <!-- Search Bar -->
        <div class="mt-4 mb-3">
            <form method="get" class="d-flex">
                <input type="text" class="form-control me-2" name="search" placeholder="Search by file name or description" value="<?= htmlspecialchars(isset($_GET['search']) ? $_GET['search'] : ''); ?>">
                <button type="submit" class="btn btn-primary me-2">Search</button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>';">Reset</button>
            </form>
        </div>

        <!-- Uploaded Files Table -->
        <div class="mt-4">
            <?php if (count($files) > 0): ?>
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>File Name</th>
                            <th>Description</th>
                            <th>File Size</th>
                            <th>Upload Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $index => $file): ?>
                            <tr>
                                <td><?= $index + 1; ?></td>
                                <td><?= htmlspecialchars($file['file_name']); ?></td>
                                <td><?= htmlspecialchars($file['description']); ?></td>
                                <td>
                                    <?= isset($file['file_size']) ? round($file['file_size'] / 1024, 2) . ' KB' : 'N/A'; ?>
                                </td>
                                <td><?= htmlspecialchars(date('d-M-Y H:i:s', strtotime($file['upload_date']))); ?></td>
                                <td>
                                    <?php if (!empty($file['file_link'])): ?>
                                        <!-- If the file is a Google Drive link -->
                                        <a href="#"
                                            onclick="copyToClipboard('<?= htmlspecialchars($file['file_link']); ?>')"
                                            class="btn btn-secondary btn-sm">Copy Link</a>
                                    <?php else: ?>
                                        <!-- If the file is a zip uploaded to the server -->
                                        <a href="download.php?file_id=<?= urlencode($file['id']); ?>&token=<?= urlencode($file['upload_token']); ?>"
                                            class="btn btn-primary btn-sm">Download File</a>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-danger btn-sm" onclick="deleteFile(<?= $file['id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center">No files found for the given search criteria.</p>
            <?php endif; ?>
        </div>

        <!-- Total Size -->
        <div class="mt-3 text-center">
            <p>Total Size: <?= $total_size_mb; ?> MB</p>
            <p>Total Storage Capacity : 10 MB</p>
            
            <a href="file_management.php" class="btn btn-success <?= $disable_upload ? 'disabled' : ''; ?>">Upload New File</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(link) {
            const tempInput = document.createElement('input');
            tempInput.value = link;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            alert('Link copied to clipboard!');
        }

        function deleteFile(fileId) {
    if (confirm('Are you sure you want to delete this file?')) {
        fetch('delete_file.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: fileId }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('File deleted successfully.');
                location.reload(); // Refresh the page to reflect changes
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('An unexpected error occurred.');
            console.error('Error:', error);
        });
    }
}
    </script>
</body>

</html>
