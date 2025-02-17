<?php
require_once '../database/db_connection.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    mysqli_begin_transaction($conn); // Start transaction

    try {
        // Get logged-in user details
        $master_user_id = $_SESSION['master_userid'];
        $created_by = $_SESSION['user_id'];
        $updated_by = null; // Initial value

        // ✅ Debugging: Capture POST Data
        file_put_contents("debug.log", "POST Data: " . print_r($_POST, true));

        // Validate required fields
        if (!isset($_POST['company_id']) || !isset($_POST['supplier_id']) || empty($_POST['company_id']) || empty($_POST['supplier_id'])) {
            throw new Exception("Company ID or Supplier ID is missing.");
        }

        // Sanitize inputs
        $company_id = intval($_POST['company_id']);
        $supplier_id = intval($_POST['supplier_id']);
        $po_date = $_POST['po_date'] ?? date("Y-m-d");

        // ✅ Generate Unique PO Token (Prevents duplicate entries)
        $po_token = bin2hex(random_bytes(16));

        // ✅ Generate PO Number (Format: PO-YYYY-XXXX)
        // ✅ Generate PO Number Based on Company ID
        $year_part = date("Y");

        // Count total POs for this company in the current year
        $sql_count = "SELECT COUNT(*) as count FROM purchase_orders WHERE company_name_id = ? AND YEAR(po_date) = ?";
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->bind_param("ii", $company_id, $year_part);
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        $row_count = $result_count->fetch_assoc();
        $po_count = $row_count['count'] + 1;
        $stmt_count->close();

        // ✅ Final PO Number Format: PO-{COMPANY_ID}-{YEAR}-{COUNTER}
        $po_number = "PO-" . str_pad($company_id, 3, "0", STR_PAD_LEFT) . "-" . $year_part . "-" . str_pad($po_count, 4, "0", STR_PAD_LEFT);

        // Capture Purchase Order details
        $quotation_no = $_POST['quotation_no'] ?? null;
        $quotation_date = $_POST['quotation_date'] ?? null;
        $inco_term = $_POST['inco_term'] ?? null;
        $inco_term_remark = $_POST['inco_term_remark'] ?? null;
        $payment_term = $_POST['payment_term'] ?? null;
        $payment_term_remark = $_POST['payment_term_remark'] ?? null;
        $general_terms = $_POST['general_terms'] ?? null;
        $payment_terms = $_POST['payment_terms'] ?? null;
        $delivery_terms = $_POST['delivery_terms'] ?? null;
        $additional_notes = $_POST['additional_notes'] ?? null;
        $personal_notes = $_POST['personal_notes'] ?? null;
        $po_status = 1; // default as PO draft

        // ✅ Insert Purchase Order
        $sql = "INSERT INTO purchase_orders (
                    po_number, master_user_id, company_name_id, supplier_id, po_date, 
                    quotation_no, quotation_date, inco_term, inco_term_remark, payment_term, 
                    payment_term_remark, general_terms, payment_terms, delivery_terms, additional_notes, 
                    personal_notes, token, created_by, updated_by, po_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param(
                "siiisssssssssssssiii",
                $po_number,
                $master_user_id,
                $company_id,
                $supplier_id,
                $po_date,
                $quotation_no,
                $quotation_date,
                $inco_term,
                $inco_term_remark,
                $payment_term,
                $payment_term_remark,
                $general_terms,
                $payment_terms,
                $delivery_terms,
                $additional_notes,
                $personal_notes,
                $po_token,
                $created_by,
                $updated_by,
                $po_status
            );

            if (!$stmt->execute()) {
                throw new Exception("Error inserting purchase order.");
            }

            $po_id = $stmt->insert_id;
            $stmt->close();

            // ✅ Decode Materials JSON Properly
            $materials = json_decode($_POST['materials'], true);
            if (!is_array($materials)) {
                throw new Exception("Invalid materials data format.");
            }

            // ✅ Debugging - Log Materials Data
            file_put_contents("debug.log", "Decoded Materials: " . print_r($materials, true), FILE_APPEND);

            // ✅ Insert Materials for this PO
            foreach ($materials as $material) {
                if (!isset($material['material_id'], $material['quantity'], $material['unit_price'])) {
                    throw new Exception("Material data is incomplete.");
                }

                // ✅ Generate Unique Token for Material Entry
                $material_token = bin2hex(random_bytes(16));

                // Sanitize Material Data
                $material_id = intval($material['material_id']);
                $make = !empty($material['make']) ? $material['make'] : null;
                $hsn_sac = !empty($material['hsn_sac']) ? $material['hsn_sac'] : null;
                $quantity = number_format(floatval($material['quantity']), 2, '.', '');
                $unit = !empty($material['unit']) ? $material['unit'] : null;
                $unit_price = number_format(floatval($material['unit_price']), 2, '.', '');
                $total = number_format(floatval($material['total'] ?? ($quantity * $unit_price)), 2, '.', '');
                $gst_percentage = number_format(floatval($material['gst_percentage'] ?? 0), 2, '.', '');
                $gst_total = number_format(floatval($material['gst_total'] ?? ($total * $gst_percentage / 100)), 2, '.', '');
                $grand_total = number_format(floatval($material['grand_total'] ?? ($total + $gst_total)), 2, '.', '');
                $material_description = !empty($material['material_description']) ? $material['material_description'] : null;
                $special_remark = !empty($material['special_remark']) ? $material['special_remark'] : null;

                // ✅ Insert into `purchase_order_materials`
                $sql_material = "INSERT INTO purchase_order_materials (
                                    po_id, material_id, make, hsn_sac, quantity, unit, 
                                    unit_price, total, gst_percentage, gst_total, grand_total, 
                                    material_description, special_remark, token, created_by, updated_by
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                if ($stmt_material = $conn->prepare($sql_material)) {
                    $stmt_material->bind_param(
                        "iisssdidddssssii",
                        $po_id,
                        $material_id,
                        $make,
                        $hsn_sac,
                        $quantity,
                        $unit,
                        $unit_price,
                        $total,
                        $gst_percentage,
                        $gst_total,
                        $grand_total,
                        $material_description,
                        $special_remark,
                        $material_token,
                        $created_by,
                        $updated_by
                    );

                    if (!$stmt_material->execute()) {
                        throw new Exception("Error inserting material data.");
                    }

                    $stmt_material->close();
                } else {
                    throw new Exception("Failed to prepare material insert statement.");
                }
            }

            // ✅ Commit Transaction (Ensures complete execution)
            mysqli_commit($conn);

            echo json_encode(["status" => "success", "message" => "Purchase Order added successfully!"]);
            exit();
        } else {
            throw new Exception("Database error: Could not prepare purchase order statement.");
        }
    } catch (Exception $e) {
        mysqli_rollback($conn); // Rollback changes on failure
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit();
    }
}
?>
