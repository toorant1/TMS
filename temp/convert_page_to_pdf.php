<?php
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json');

try {
    // Capture the current page's HTML via POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['html'])) {
        $htmlContent = $_POST['html'];

        // Set up Dompdf options
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans'); // Default font for PDF

        // Initialize Dompdf
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait'); // Paper size and orientation
        $dompdf->render();

        // Save PDF to server or return it for download
        $fileName = 'generated_pdf_' . time() . '.pdf';
        file_put_contents($fileName, $dompdf->output());

        // Respond with success and file details
        echo json_encode([
            'success' => true,
            'message' => 'PDF generated successfully!',
            'file' => $fileName,
        ]);
    } else {
        throw new Exception('Invalid request. Please provide HTML content.');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
