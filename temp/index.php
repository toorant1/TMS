<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convert Page to PDF</title>
    <script>
        function convertToPDF() {
            // Get the current page's HTML
            const pageContent = document.documentElement.outerHTML;

            // Send the HTML to the API
            fetch('convert_page_to_pdf.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        html: pageContent
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Download the generated PDF
                        const link = document.createElement('a');
                        link.href = data.file;
                        link.download = 'page.pdf';
                        link.click();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>
</head>

<body>
    <h1>Welcome to the PDF Converter</h1>
    <p>This is a demo page that will be converted into a PDF when you press the button.</p>
    <button onclick="convertToPDF()">Convert This Page to PDF</button>
</body>

</html>