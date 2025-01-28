<?php
require_once '../database/db_connection.php'; // Update with your DB connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['headers']) && !empty($_POST['chunk'])) {
        $headers = explode(',', $_POST['headers']);
        $chunk = explode("\n", $_POST['chunk']);

        // Validate headers
        if ($headers !== ['city', 'pincode', 'district', 'state']) {
            echo "Invalid headers. Expected: city, pincode, district, state.";
            exit;
        }

        $insertedRows = 0;
        $skippedRows = 0;

        foreach ($chunk as $line) {
            $data = str_getcsv($line);
            if (count($data) !== 4) {
                continue; // Skip invalid rows
            }

            list($city, $pincode, $district, $state) = array_map('trim', $data);

            // Check for duplicates
            $checkQuery = "SELECT COUNT(*) AS count FROM locations WHERE city = ? AND pincode = ? AND district = ? AND state = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param('ssss', $city, $pincode, $district, $state);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['count'] == 0) {
                // Insert new row
                $insertQuery = "INSERT INTO locations (city, pincode, district, state) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param('ssss', $city, $pincode, $district, $state);
                if ($stmt->execute()) {
                    $insertedRows++;
                }
            } else {
                $skippedRows++;
            }
        }

        echo "Chunk processed: $insertedRows rows inserted, $skippedRows duplicates skipped.";
    } else {
        echo "Invalid data received.";
    }
} else {
    echo "Invalid request.";
}
?>
