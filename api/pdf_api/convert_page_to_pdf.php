<?php
require '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['html'])) {
        $htmlContent = $_POST['html'];

        // Set up Dompdf options
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans'); // Default font

        // Initialize Dompdf
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait'); // Paper size
        $dompdf->render();

        // Send PDF output to the browser for display
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="page.pdf"'); // Inline for opening in a tab
        echo $dompdf->output();
    } else {
        throw new Exception('Invalid request. Please provide HTML content.');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
