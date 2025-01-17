<?php
// Include database connection
require_once '../database/db_connection.php'; // Update with your DB connection file
require_once '../vendor/autoload.php'; // Include DomPDF autoload file

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();

// Check if the user is logged in
if (!isset($_SESSION['master_userid'])) {
    header("Location: ../index.php"); // Redirect to login if not logged in
    exit;
}

// Use the session variable for master_userid
$master_userid = $_SESSION['master_userid'];

// Ensure the company_id and token are provided in the URL
if (isset($_GET['company_id']) && isset($_GET['token'])) {
    $company_id = $_GET['company_id'];
    $token = $_GET['token'];

    // Fetch company details from the database
    $query = "SELECT company_name, address, district, city, state, pincode, country, phone, mobile, email, website, currency, pan, gst, tds, msme, other1, other2, other3, bank, branch, ifsc, account_no 
              FROM master_company WHERE id = ? AND token = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $company_id, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the company exists
    if ($result->num_rows === 0) {
        die("Company not found or invalid token.");
    }

    $company = $result->fetch_assoc();
} else {
    die("Company ID or token not provided.");
}

// User-friendly labels for the fields
$labels = [
    "company_name" => "Company Name",
    "address" => "Address",
    "district" => "District",
    "city" => "City",
    "state" => "State",
    "country" => "Country",
    "pincode" => "Pincode",
    "phone" => "Phone",
    "mobile" => "Mobile",
    "email" => "Email",
    "website" => "Website",
    "currency" => "Currency",
    "pan" => "PAN",
    "gst" => "GST",
    "tds" => "TDS",
    "msme" => "MSME",
    "other1" => "Additional Info 1",
    "other2" => "Additional Info 2",
    "other3" => "Additional Info 3",
    "bank" => "Bank Name",
    "branch" => "Branch Name",
    "ifsc" => "IFSC Code",
    "account_no" => "Account Number"
];

// Generate PDF content
$html = '<h1 style="text-align:center;">Company Details</h1>';
$html .= '<table border="1" style="width:100%; border-collapse: collapse; font-family: Arial, sans-serif;">';

foreach ($labels as $field => $label) {
    $value = isset($company[$field]) ? htmlspecialchars($company[$field]) : "N/A";
    $html .= "<tr>
                <td style='padding: 10px; font-weight: bold; background-color: #f2f2f2;'>$label</td>
                <td style='padding: 10px;'>$value</td>
              </tr>";
}

$html .= '</table>';

// Configure DomPDF
$options = new Options();
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output the PDF directly to the browser
$dompdf->stream("Company_Details_" . preg_replace('/[^a-zA-Z0-9]/', '_', $company['company_name']) . ".pdf", ["Attachment" => false]);

// Close database connection
$conn->close();
?>
