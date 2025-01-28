function convertToPDF() {
    const pageContent = document.documentElement.outerHTML;

    fetch('../api/pdf_api/convert_page_to_pdf.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({ html: pageContent }),
    })
        .then(response => {
            if (response.ok) {
                return response.blob(); // Get the response as a Blob
            }
            throw new Error('Failed to generate PDF');
        })
        .then(blob => {
            const pdfUrl = URL.createObjectURL(blob); // Create a Blob URL
            window.open(pdfUrl, '_blank'); // Open in a new tab or window
        })
        .catch(error => console.error('Error:', error));
}
