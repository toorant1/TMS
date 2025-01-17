<?php
require_once '../database/db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['followup_id']) ? intval($_POST['followup_id']) : 0;
    $progressStatement = isset($_POST['progress_statement']) ? trim($_POST['progress_statement']) : '';
    $progressStatus = isset($_POST['progress_status']) ? intval($_POST['progress_status']) : 0;
    $followupDatetime = isset($_POST['followup_datetime']) ? trim($_POST['followup_datetime']) : null;
    $futureFollowup = isset($_POST['future_followup']) ? 1 : 0;

    if ($id && !empty($progressStatement) && $progressStatus) {
        $query = "UPDATE master_marketing_followups 
                  SET progress_statement = ?, 
                      current_marketing_status = ?, 
                      followup_datetime = ?, 
                      future_followup_required = ?
                  WHERE id = ?";
                  
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param('sissi', $progressStatement, $progressStatus, $followupDatetime, $futureFollowup, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Follow-up updated successfully.']);
            } else {
                echo json_encode(['error' => 'Failed to update follow-up: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 'Query preparation failed: ' . $conn->error]);
        }
    } else {
        echo json_encode(['error' => 'Invalid input data.']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}

$conn->close();
?>
