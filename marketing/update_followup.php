<?php
require_once '../database/db_connection.php';

header('Content-Type: application/json');

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Retrieve POST data
$progressStatement = isset($_POST['progress_statement']) ? trim($_POST['progress_statement']) : '';
$progressStatus = isset($_POST['progress_status']) ? intval($_POST['progress_status']) : 0;
$followupDatetime = isset($_POST['followup_datetime']) ? trim($_POST['followup_datetime']) : null;
$futureFollowup = isset($_POST['future_followup']) ? 1 : 0;

// Validate required fields
if (empty($progressStatement) || !$progressStatus) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data. All fields are required.']);
    exit;
}

// Begin transaction to ensure data integrity
$conn->begin_transaction();

try {
    // Select the most recent follow-up record
    $selectRecentQuery = "
        SELECT id, marketing_id 
        FROM master_marketing_followups 
        ORDER BY progress_date DESC 
        LIMIT 1";

    $result = $conn->query($selectRecentQuery);
    if ($result && $result->num_rows > 0) {
        $recentFollowup = $result->fetch_assoc();
        $followupId = $recentFollowup['id'];
        $marketingId = $recentFollowup['marketing_id'];

        // Update the most recent follow-up record
        $updateQuery = "UPDATE master_marketing_followups 
                        SET progress_statement = ?, 
                            current_marketing_status = ?, 
                            followup_datetime = ?, 
                            future_followup_required = ? 
                        WHERE id = ?";

        $stmt = $conn->prepare($updateQuery);
        if ($stmt) {
            $stmt->bind_param('sissi', $progressStatement, $progressStatus, $followupDatetime, $futureFollowup, $followupId);
            if (!$stmt->execute()) {
                throw new Exception('Database Error: Failed to update follow-up record.');
            }
            $stmt->close();
        } else {
            throw new Exception('Query Preparation Failed: Failed to prepare update query.');
        }

        // Update the master_marketing.marketing_id_status field
        $updateMarketingStatusQuery = "UPDATE master_marketing SET marketing_id_status = ? WHERE id = ?";
        $stmtUpdateMarketingStatus = $conn->prepare($updateMarketingStatusQuery);
        if ($stmtUpdateMarketingStatus) {
            $stmtUpdateMarketingStatus->bind_param('ii', $progressStatus, $marketingId);
            if (!$stmtUpdateMarketingStatus->execute()) {
                throw new Exception('Database Error: Failed to update marketing status.');
            }
            $stmtUpdateMarketingStatus->close();
        } else {
            throw new Exception('Query Preparation Failed: Failed to prepare update marketing status query.');
        }
    } else {
        throw new Exception('No follow-up records found to update.');
    }

    // Commit the transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Follow-up updated successfully.']);
} catch (Exception $e) {
    // Rollback the transaction on failure
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
