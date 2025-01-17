<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Zip File</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include('../headers/header.php'); ?>
    <div class="container mt-5 ">
    <h1 class="text-center">Upload Files</h1>
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="text-center mb-0">Upload Zip File</h4>
            </div>
            <div class="card-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <!-- File Name -->
                    <div class="mb-3">
                        <label for="file_name" class="form-label">File Name</label>
                        <input type="text" class="form-control" id="file_name" name="file_name" placeholder="Enter file name" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <!-- Upload Method Selection with Input Fields in the Same Row -->
                    <div class="mb-3">
                        <label class="form-label">Choose Upload Method</label>
                        <div class="d-flex align-items-center gap-3">
                            <!-- Radio Buttons -->
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="upload_option" id="google_link_option" value="google_link" checked>
                                <label class="form-check-label" for="google_link_option">Google Drive Link</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="upload_option" id="file_upload_option" value="file_upload">
                                <label class="form-check-label" for="file_upload_option">Upload Zip File (Max Size = 10 MB)</label>
                            </div>

                            <!-- Google Drive File Link -->
                            <div id="googleLinkContainer" class="flex-grow-1">
                                <input type="text" class="form-control" id="google_drive_link" name="google_drive_link" placeholder="Google Drive Link">
                            </div>

                            <!-- Select Zip File -->
                            <div id="fileUploadContainer" class="flex-grow-1" style="display: none;">
                                <input type="file" class="form-control" id="zip_file" name="zip_file" accept=".zip">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning d-none" id="validationError">
                        Please provide either a Google Drive link or a zip file to upload.
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary w-100">Upload</button>
                    <button type="button" class="btn btn-secondary w-100" onclick="goBack()">Back</button>

                </form>

                <!-- Progress Bar -->
                <div class="progress mt-3" style="height: 25px; display: none;" id="progressContainer">
                    <div class="progress-bar" id="progressBar" role="progressbar" style="width: 0%;">0%</div>
                </div>

                <!-- Result Section -->
                <div id="result" class="mt-3"></div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Function to navigate back to the previous page
    function goBack() {
        window.history.back();
    }
</script>
    <script>
        const googleLinkOption = document.getElementById('google_link_option');
        const fileUploadOption = document.getElementById('file_upload_option');
        const googleLinkContainer = document.getElementById('googleLinkContainer');
        const fileUploadContainer = document.getElementById('fileUploadContainer');

        // Toggle visibility of input fields based on selected option
        googleLinkOption.addEventListener('change', () => {
            if (googleLinkOption.checked) {
                googleLinkContainer.style.display = 'block';
                fileUploadContainer.style.display = 'none';
                document.getElementById('google_drive_link').required = true;
                document.getElementById('zip_file').required = false;
            }
        });

        fileUploadOption.addEventListener('change', () => {
            if (fileUploadOption.checked) {
                googleLinkContainer.style.display = 'none';
                fileUploadContainer.style.display = 'block';
                document.getElementById('google_drive_link').required = false;
                document.getElementById('zip_file').required = true;
            }
        });

        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const fileInput = document.getElementById('zip_file');
            const googleDriveLink = document.getElementById('google_drive_link').value.trim();
            const file = fileInput.files[0];
            const validationError = document.getElementById('validationError');
            const resultDiv = document.getElementById('result');
            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('progressBar');

            validationError.classList.add('d-none');
            resultDiv.innerHTML = '';

            // Validation: Ensure either Google Drive link or file is provided
            if (googleLinkOption.checked && !googleDriveLink) {
                validationError.classList.remove('d-none');
                validationError.innerText = 'Please provide a Google Drive link.';
                return;
            }

            if (fileUploadOption.checked && !file) {
                validationError.classList.remove('d-none');
                validationError.innerText = 'Please upload a zip file.';
                return;
            }

            // Validation: File type and size (if file is uploaded)
            if (file && fileUploadOption.checked) {
                if (file.type !== 'application/zip' && file.name.split('.').pop() !== 'zip') {
                    resultDiv.innerHTML = `<div class="alert alert-danger">Only .zip files are allowed.</div>`;
                    return;
                }

                if (file.size > 10 * 1024 * 1024) {
                    resultDiv.innerHTML = `<div class="alert alert-danger">File size must not exceed 10 MB.</div>`;
                    return;
                }
            }

            // Show progress bar
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            progressBar.innerText = '0%';

            const formData = new FormData(this);

            const xhr = new XMLHttpRequest();

            // Progress handler
            xhr.upload.onprogress = function(event) {
                if (event.lengthComputable) {
                    const percentComplete = Math.round((event.loaded / event.total) * 100);
                    progressBar.style.width = percentComplete + '%';
                    progressBar.innerText = percentComplete + '%';
                }
            };

            // Load handler
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        progressBar.style.width = '100%';
                        progressBar.innerText = '100%';
                        resultDiv.innerHTML = `<div class="alert alert-success">${response.message}</div>`;
                    } else {
                        progressBar.style.width = '0%';
                        progressBar.innerText = '0%';
                        resultDiv.innerHTML = `<div class="alert alert-danger">${response.message}</div>`;
                    }
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">An unexpected error occurred.</div>`;
                }
            };

            // Error handler
            xhr.onerror = function() {
                resultDiv.innerHTML = `<div class="alert alert-danger">An unexpected error occurred.</div>`;
            };

            // Configure and send the request
            xhr.open('POST', 'upload_file.php', true);
            xhr.send(formData);
        });
    </script>
</body>

</html>