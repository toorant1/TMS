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
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true); // Enable remote resources (images, CSS, fonts)

        // Add styles for proper formatting & narrow borders
        $formattedHtml = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                @page {
                    size: A4 portrait;
                    margin: 10mm 8mm 10mm 8mm; /* Top, Right, Bottom, Left (Narrow Borders) */
                }
                body {
                    font-family: 'DejaVu Sans', sans-serif;
                    margin: 0;
                    padding: 5px;
                }
                .page-break {
                    page-break-before: always;
                }
            </style>
        </head>
        <body>
            $htmlContent
        </body>
        </html>";

        // Initialize Dompdf
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($formattedHtml);
        $dompdf->setPaper('A4', 'portrait'); // Set paper size to A4 portrait
        $dompdf->render();

        // Stream PDF to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="page.pdf"');
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
