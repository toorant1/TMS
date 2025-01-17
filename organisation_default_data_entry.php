<?php
// Include the database connection
require_once 'database/db_connection.php';

// Ensure the session is started
if (!isset($_SESSION)) {
    session_start();
}

// Get the master_user_id from the session
$master_userid = $_SESSION['master_userid'] ?? null;

if ($master_userid) {
    try {
        // Insert default material types
        $check_sql_materials = "SELECT COUNT(*) as count FROM master_materials_type WHERE master_user_id = ?";
        $check_stmt_materials = $conn->prepare($check_sql_materials);
        if (!$check_stmt_materials) {
            throw new Exception("Error preparing statement for material types: " . $conn->error);
        }
        $check_stmt_materials->bind_param("i", $master_userid);
        $check_stmt_materials->execute();
        $check_result_materials = $check_stmt_materials->get_result();
        $row_materials = $check_result_materials->fetch_assoc();

        if ($row_materials['count'] == 0) {
            $sql_materials = "INSERT INTO master_materials_type (material_type, master_user_id) VALUES (?, ?)";
            $stmt_materials = $conn->prepare($sql_materials);
            if (!$stmt_materials) {
                throw new Exception("Error preparing statement for inserting material types: " . $conn->error);
            }

            $default_materials = [
                ['material_type' => 'Service'],
                ['material_type' => 'Materials']
            ];

            foreach ($default_materials as $default) {
                $materialType = $default['material_type'];
                $stmt_materials->bind_param("si", $materialType, $master_userid);
                $stmt_materials->execute();
            }
            $stmt_materials->close();
        }
        $check_stmt_materials->close();

        // Insert default ticket types
        $check_sql_tickets = "SELECT COUNT(*) as count FROM master_tickets_types WHERE master_user_id = ?";
        $check_stmt_tickets = $conn->prepare($check_sql_tickets);
        if (!$check_stmt_tickets) {
            throw new Exception("Error preparing statement for ticket types: " . $conn->error);
        }
        $check_stmt_tickets->bind_param("i", $master_userid);
        $check_stmt_tickets->execute();
        $check_result_tickets = $check_stmt_tickets->get_result();
        $row_tickets = $check_result_tickets->fetch_assoc();

        if ($row_tickets['count'] == 0) {
            $sql_tickets = "INSERT INTO master_tickets_types (ticket_type, master_user_id, status) VALUES (?, ?, ?)";
            $stmt_tickets = $conn->prepare($sql_tickets);
            if (!$stmt_tickets) {
                throw new Exception("Error preparing statement for inserting ticket types: " . $conn->error);
            }

            $default_tickets = [
                ['ticket_type' => 'New Tickets', 'status' => 1],
                ['ticket_type' => 'AMC Services', 'status' => 1],
                ['ticket_type' => 'Non AMC Services', 'status' => 1]
            ];

            foreach ($default_tickets as $default) {
                $ticketType = $default['ticket_type'];
                $status = $default['status'];
                $stmt_tickets->bind_param("sii", $ticketType, $master_userid, $status);
                $stmt_tickets->execute();
            }
            $stmt_tickets->close();
        }
        $check_stmt_tickets->close();

        // Insert default makes
        $check_sql_makes = "SELECT COUNT(*) as count FROM master_materials_make WHERE master_user_id = ?";
        $check_stmt_makes = $conn->prepare($check_sql_makes);
        if (!$check_stmt_makes) {
            throw new Exception("Error preparing statement for makes: " . $conn->error);
        }
        $check_stmt_makes->bind_param("i", $master_userid);
        $check_stmt_makes->execute();
        $check_result_makes = $check_stmt_makes->get_result();
        $row_makes = $check_result_makes->fetch_assoc();

        if ($row_makes['count'] == 0) {
            $sql_makes = "INSERT INTO master_materials_make (make, master_user_id, status) VALUES (?, ?, ?)";
            $stmt_makes = $conn->prepare($sql_makes);
            if (!$stmt_makes) {
                throw new Exception("Error preparing statement for inserting makes: " . $conn->error);
            }

            $default_makes = [
                ['make' => 'Default Make 1', 'status' => 1],
                ['make' => 'Default Make 2', 'status' => 1],
                ['make' => 'Default Make 3', 'status' => 1]
            ];

            foreach ($default_makes as $default) {
                $make = $default['make'];
                $status_m = $default['status'];
                $stmt_makes->bind_param("sii", $make, $master_userid, $status_m);
                $stmt_makes->execute();
            }
            $stmt_makes->close();
        }
        $check_stmt_makes->close();
    } catch (Exception $e) {
        error_log("Error inserting default data: " . $e->getMessage());
    }
} else {
    error_log("master_userid is not set. Default data cannot be inserted.");
}

?>
