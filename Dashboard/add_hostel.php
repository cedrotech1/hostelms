<?php
include('connection.php');

// Check user authentication and role
if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

$userRole = $_SESSION['role'];
$user_id = filter_var($_SESSION['id'], FILTER_VALIDATE_INT);
if ($user_id === false) {
    die('Error: Invalid user ID');
}

// Get user's campus using prepared statement
$campusQuery = $connection->prepare("SELECT c.id, c.name FROM campuses c INNER JOIN users u ON u.campus = c.id WHERE u.id = ?");
$campusQuery->bind_param("i", $user_id);
$campusQuery->execute();
$campusResult = $campusQuery->get_result();
$campusQuery->close();

if (!$campusResult || $campusResult->num_rows === 0) {
    die('Error: User is not associated with any campus');
}

$campus = $campusResult->fetch_assoc();
$campus_id = $campus['id'];
$campus_name = $campus['name'];

// Function to check if hostel data exists
function checkExistingData($connection, $campus_id, $userRole) {
    $query = $userRole === 'warefare'
        ? "SELECT COUNT(*) as count FROM hostels WHERE campus_id = ?"
        : "SELECT COUNT(*) as count FROM hostels";
    $stmt = $connection->prepare($query);
    
    if ($userRole === 'warefare') {
        $stmt->bind_param("i", $campus_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

$existingData = checkExistingData($connection, $campus_id, $userRole);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>UR-HOSTELS</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="assets/img/icon1.png" rel="icon">
    <link href="assets/img/icon1.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="assets/css/style.css" rel="stylesheet">

    <!-- XLSX and PapaParse libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>

    <style>
        .btn-primary {
            background-color: rgb(17, 37, 58) !important;
            border-color: rgb(14, 49, 83) !important;
            transition: all 0.3s ease !important;
        }
        .btn-primary:hover {
            background-color: rgb(14, 49, 83);
            border-color: rgb(11, 32, 55);
        }
    </style>
</head>

<body>

<?php  
include("./includes/header.php");
include("./includes/menu.php");
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Data</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item">Data</li>
                <li class="breadcrumb-item active">Upload</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
        <div class="row">
            <div class="col-lg-6">
                <div class="row">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <h5 class="card-title">UPLOAD HOSTELS INFORMATION FORM</h5>
                            <br>
                          
                                <div class="col-md-12">
                                    <div class="form-floating">
                                        <input class="form-control" type="file" id="dataFile" name="dataFile" accept=".xls,.xlsx,.csv" required />
                                        <label for="dataFile">Upload Excel/CSV File</label>
                                    </div>
                                </div>
                                <br>
                                <div class="text-center">
                                    <button type="button" id="uploadButton" class="btn btn-primary">
                                        <i class="bi bi-upload me-1"></i> Upload & Process File
                                    </button>
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-1"></i> Reset
                                    </button>
                                </div>
                        
                        </div>
                    </div>
                </div>
            </div><!-- End Left side columns -->

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Template & Instructions</h5>
                        
                        <!-- Download Template Section -->
                        <div class="mb-4">
                            <h6 class="fw-bold">Download Template</h6>
                            <p class="text-muted">Use our template to ensure your data is correctly structured or make sure header of each column is correct as in that template.</p>
                            <button onclick="downloadTemplate()" class="btn btn-primary">
                                <i class="bi bi-download me-1"></i> Download Template
                            </button>
                        </div>

                        <!-- Instructions Section -->
                        <div class="mb-4">
                            <h6 class="fw-bold">Instructions for Data Upload</h6>
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Important Notes:</h6>
                                <ol class="mb-0">
                                    <li>All fields marked with * are required</li>
                                    <li>File must be in Excel (.xlsx) or CSV format</li>
                                    <li>Do not modify the header row OR make sure header of each column is correct as in that template</li>
                                    <li>Save Excel files as CSV before uploading for best results</li>
                                    <li>Building Code must be unique for each building</li>
                                    <li>Gender must be either 'M' or 'F' (capital letters)</li>
                                    <li>Year must be a valid year of study (1-6)</li>
                                    <?php if ($userRole === 'warefare'): ?>
                                        <li>You can only upload hostels for your assigned campus (<?php echo htmlspecialchars($campus_name); ?>)</li>
                                    <?php else: ?>
                                        <li>You can upload hostels for any campus</li>
                                    <?php endif; ?>
                                </ol>
                            </div>
                        </div>

                        <!-- Required Fields -->
                        <div class="mb-4">
                            <h6 class="fw-bold">Required Fields</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Campus*</td>
                                            <td><?php echo $userRole === 'warefare' ? 'Your assigned campus name (' . htmlspecialchars($campus_name) . ')' : 'Any valid campus name'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Hostel Name*</td>
                                            <td>Name of the hostel (e.g., KIST)</td>
                                        </tr>
                                        <tr>
                                            <td>Hostel Block Name*</td>
                                            <td>Name of the hostel block (e.g., Block A)</td>
                                        </tr>
                                        <tr>
                                            <td>Building/Hostel Block Code*</td>
                                            <td>Unique identifier for the building/block (e.g., KIST-A)</td>
                                        </tr>
                                        <tr>
                                            <td>Room Code 1*</td>
                                            <td>Primary room identifier (e.g., 101)</td>
                                        </tr>
                                        <tr>
                                            <td>Room Code 2</td>
                                            <td>Secondary room identifier (if applicable)</td>
                                        </tr>
                                        <tr>
                                            <td>Number of Beds*</td>
                                            <td>Total number of beds in the room</td>
                                        </tr>
                                        <tr>
                                            <td>Gender*</td>
                                            <td>M or F (capital letters)</td>
                                        </tr>
                                        <tr>
                                            <td>Year*</td>
                                            <td>Year of study (1-6)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Common Issues -->
                        <div class="mb-4">
                            <h6 class="fw-bold">Common Issues & Solutions</h6>
                            <div class="alert alert-warning">
                                <ul class="mb-0">
                                    <li>Ensure all required fields are filled</li>
                                    <?php if ($userRole === 'warefare'): ?>
                                        <li>Check that campus name matches your assigned campus exactly (<?php echo htmlspecialchars($campus_name); ?>)</li>
                                    <?php else: ?>
                                        <li>Check that campus name exists in the system</li>
                                    <?php endif; ?>
                                    <li>Building Code must be unique and properly formatted</li>
                                    <li>Room codes must be unique within each hostel</li>
                                    <li>Number of beds must be a positive integer</li>
                                    <li>Gender must be exactly 'M' or 'F' (capital letters)</li>
                                    <li>Year must be a valid year of study (1-6)</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Support -->
                        <div>
                            <h6 class="fw-bold">Need Help?</h6>
                            <p class="text-muted">If you encounter any issues, please contact the system administrator or refer to the user manual.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main><!-- End #main -->

<script>
    // Function to download template
    function downloadTemplate() {
        var wb = XLSX.utils.book_new();
        var headers = [
            'Campus',
            'Hostel Name',
            'Hostel Block Name',
            'Building/Hostel Block Code',
            'Room Code 1',
            'Room Code 2',
            'Number of Beds',
            'Gender',
            'Year'
        ];
        
        var exampleData = [
            headers,
            <?php if ($userRole === 'warefare'): ?>
                ['<?php echo htmlspecialchars($campus_name); ?>', 'Bengazi', 'Block A', 'B001', 'A101', '', '4', 'M', '1'],
                ['<?php echo htmlspecialchars($campus_name); ?>', 'Bengazi', 'Block A', 'B001', 'A102', '', '4', 'M', '1'],
                ['<?php echo htmlspecialchars($campus_name); ?>', 'Bengazi', 'Block B', 'B002', 'B101', '', '2', 'F', '2']
            <?php else: ?>
                ['Huye', 'Bengazi', 'Block A', 'B001', 'A101', '', '4', 'M', '1'],
                ['Huye', 'Bengazi', 'Block A', 'B001', 'A102', '', '4', 'M', '1'],
                ['Huye', 'Bengazi', 'Block B', 'B002', 'B101', '', '2', 'F', '2']
            <?php endif; ?>
        ];
        
        var ws = XLSX.utils.aoa_to_sheet(exampleData);
        XLSX.utils.book_append_sheet(wb, ws, 'Hostel Template');
        XLSX.writeFile(wb, 'Hostel_Template.xlsx');
    }

    // Function to handle file upload
    function handleFileUpload(file) {
        if (!file) {
            alert("Please select a file.");
            return;
        }

        var fileExtension = file.name.split('.').pop().toLowerCase();
        if (fileExtension === 'xls' || fileExtension === 'xlsx') {
            processExcel(file);
        } else if (fileExtension === 'csv') {
            processCSV(file);
        } else {
            alert("Unsupported file format. Please upload an Excel (.xlsx, .xls) or CSV file.");
            toggleUploadButton(false);
        }
    }

    // Function to toggle upload button state
    function toggleUploadButton(processing) {
        var uploadButton = document.getElementById('uploadButton');
        if (uploadButton) {
            uploadButton.disabled = processing;
            uploadButton.innerHTML = processing 
                ? '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'
                : '<i class="bi bi-upload me-1"></i> Upload & Process File';
        }
    }

    // Function to process Excel files
    function processExcel(file) {
        var reader = new FileReader();
        
        reader.onload = function(e) {
            try {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, { type: 'array' });
                var worksheet = workbook.Sheets[workbook.SheetNames[0]];
                var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                
                if (jsonData.length < 2) {
                    throw new Error('The file is empty or has no data rows');
                }
                
                var expectedHeaders = [
                    'Campus',
                    'Hostel Name',
                    'Hostel Block Name',
                    'Building/Hostel Block Code',
                    'Room Code 1',
                    'Room Code 2',
                    'Number of Beds',
                    'Gender',
                    'Year'
                ];
                
                var fileHeaders = jsonData[0].map(h => h.toString().trim());
                var headersMatch = expectedHeaders.length === fileHeaders.length &&
                    expectedHeaders.every((header, index) => header === fileHeaders[index]);
                
                if (!headersMatch) {
                    throw new Error('Invalid file format. Please use the provided template.');
                }
                
                var validRows = 0;
                var errors = [];
                var dataToSend = [expectedHeaders];
                
                for (var i = 1; i < jsonData.length; i++) {
                    var row = jsonData[i];
                    if (!row || row.length === 0 || row.every(cell => !cell)) continue;
                    
                    var record = {
                        campus: (row[0] || '').toString().trim(),
                        hostel_name: (row[1] || '').toString().trim(),
                        hostel_block_name: (row[2] || '').toString().trim(),
                        building_code: (row[3] || '').toString().trim(),
                        room_code1: (row[4] || '').toString().trim(),
                        room_code2: (row[5] || '').toString().trim(),
                        number_of_beds: (row[6] || '').toString().trim(),
                        gender: (row[7] !== undefined ? row[7].toString().trim().toUpperCase() : ''),
                        year: (row[8] !== undefined ? row[8].toString().trim() : '')
                    };
                    
                    var validationErrors = [];
                    if (!record.campus) validationErrors.push('Campus is required');
                    if (!record.hostel_name) validationErrors.push('Hostel Name is required');
                    if (!record.building_code) validationErrors.push('Building/Hostel Block Code is required');
                    if (!record.room_code1) validationErrors.push('Room Code 1 is required');
                    if (!record.number_of_beds) validationErrors.push('Number of Beds is required');
                    if (!record.gender) {
                        validationErrors.push('Gender is required');
                    } else if (!['M', 'F'].includes(record.gender)) {
                        validationErrors.push('Gender must be either M or F');
                    }
                    if (!record.year) {
                        validationErrors.push('Year is required');
                    } else if (isNaN(record.year) || parseInt(record.year) < 1 || parseInt(record.year) > 6) {
                        validationErrors.push('Year must be a valid number between 1 and 6');
                    }
                    
                    if (validationErrors.length > 0) {
                        errors.push(`Row ${i + 1}: ${validationErrors.join('; ')}`);
                    } else {
                        dataToSend.push([
                            record.campus,
                            record.hostel_name,
                            record.hostel_block_name,
                            record.building_code,
                            record.room_code1,
                            record.room_code2,
                            parseInt(record.number_of_beds, 10),
                            record.gender,
                            parseInt(record.year, 10)
                        ]);
                        validRows++;
                    }
                }
                
                if (validRows > 0) {
                    uploadToServer(dataToSend);
                } else {
                    showResultsModal({
                        status: 'error',
                        message: 'No valid data found in the file',
                        data: { errors: errors.length > 0 ? errors : ['No valid data rows found'], success: [] }
                    });
                    toggleUploadButton(false);
                }
            } catch (error) {
                console.error('Error processing Excel file:', error);
                showResultsModal({
                    status: 'error',
                    message: 'Error processing the file: ' + error.message,
                    data: { errors: ['Please ensure the file is a valid Excel file and matches the template'], success: [] }
                });
                toggleUploadButton(false);
            }
        };
        
        reader.onerror = function(error) {
            console.error('File reading error:', error);
            showResultsModal({
                status: 'error',
                message: 'Error reading the file',
                data: { errors: ['Unable to read the file. Please try again.'], success: [] }
            });
            toggleUploadButton(false);
        };
        
        reader.readAsArrayBuffer(file);
    }

    // Function to process CSV files
    function processCSV(file) {
        Papa.parse(file, {
            complete: function(results) {
                var filteredData = results.data.filter(row => row.some(cell => cell !== '' && cell !== null));
                if (filteredData.length < 2) {
                    showResultsModal({
                        status: 'error',
                        message: 'The CSV file is empty or has no data rows',
                        data: { errors: ['No valid data rows found'], success: [] }
                    });
                    toggleUploadButton(false);
                    return;
                }
                
                var expectedHeaders = [
                    'Campus',
                    'Hostel Name',
                    'Hostel Block Name',
                    'Building/Hostel Block Code',
                    'Room Code 1',
                    'Room Code 2',
                    'Number of Beds',
                    'Gender',
                    'Year'
                ];
                
                var fileHeaders = filteredData[0].map(h => h.trim());
                var headersMatch = expectedHeaders.length === fileHeaders.length &&
                    expectedHeaders.every((header, index) => header === fileHeaders[index]);
                
                if (!headersMatch) {
                    showResultsModal({
                        status: 'error',
                        message: 'Invalid CSV file format',
                        data: { errors: ['Please use the provided template with correct headers'], success: [] }
                    });
                    toggleUploadButton(false);
                    return;
                }
                
                var validRows = 0;
                var errors = [];
                var dataToSend = [expectedHeaders];
                
                for (var i = 1; i < filteredData.length; i++) {
                    var row = filteredData[i];
                    if (!row || row.length === 0 || row.every(cell => !cell)) continue;
                    
                    var record = {
                        campus: (row[0] || '').toString().trim(),
                        hostel_name: (row[1] || '').toString().trim(),
                        hostel_block_name: (row[2] || '').toString().trim(),
                        building_code: (row[3] || '').toString().trim(),
                        room_code1: (row[4] || '').toString().trim(),
                        room_code2: (row[5] || '').toString().trim(),
                        number_of_beds: (row[6] || '').toString().trim(),
                        gender: (row[7] !== undefined ? row[7].toString().trim().toUpperCase() : ''),
                        year: (row[8] !== undefined ? row[8].toString().trim() : '')
                    };
                    
                    var validationErrors = [];
                    if (!record.campus) validationErrors.push('Campus is required');
                    if (!record.hostel_name) validationErrors.push('Hostel Name is required');
                    if (!record.building_code) validationErrors.push('Building/Hostel Block Code is required');
                    if (!record.room_code1) validationErrors.push('Room Code 1 is required');
                    if (!record.number_of_beds) validationErrors.push('Number of Beds is required');
                    if (!record.gender) {
                        validationErrors.push('Gender is required');
                    } else if (!['M', 'F'].includes(record.gender)) {
                        validationErrors.push('Gender must be either M or F');
                    }
                    if (!record.year) {
                        validationErrors.push('Year is required');
                    } else if (isNaN(record.year) || parseInt(record.year) < 1 || parseInt(record.year) > 6) {
                        validationErrors.push('Year must be a valid number between 1 and 6');
                    }
                    
                    if (validationErrors.length > 0) {
                        errors.push(`Row ${i + 1}: ${validationErrors.join('; ')}`);
                    } else {
                        dataToSend.push([
                            record.campus,
                            record.hostel_name,
                            record.hostel_block_name,
                            record.building_code,
                            record.room_code1,
                            record.room_code2,
                            parseInt(record.number_of_beds, 10),
                            record.gender,
                            parseInt(record.year, 10)
                        ]);
                        validRows++;
                    }
                }
                
                if (validRows > 0) {
                    uploadToServer(dataToSend);
                } else {
                    showResultsModal({
                        status: 'error',
                        message: 'No valid data found in the CSV file',
                        data: { errors: errors.length > 0 ? errors : ['No valid data rows found'], success: [] }
                    });
                    toggleUploadButton(false);
                }
            },
            skipEmptyLines: true
        });
    }

    // Function to upload data to the server
    function uploadToServer(dataRows) {
        fetch('welfare_upload_hostel_excel.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ data: dataRows })
        })
        .then(response => {
            return response.json().then(jsonResponse => ({
                status: response.status,
                json: jsonResponse
            }));
        })
        .then(({ status, json }) => {
            if (status >= 400) {
                // Handle error responses (e.g., 400 Bad Request) with valid JSON
                showResultsModal(json);
            } else {
                showResultsModal(json);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showResultsModal({
                status: 'error',
                message: 'Failed to communicate with the server: ' + error.message,
                data: {
                    errors: ['Unable to process the request. Please check your network connection and try again.'],
                    success: []
                }
            });
        })
        .finally(() => {
            toggleUploadButton(false);
        });
    }

    // Function to display results in a modal
    function showResultsModal(response) {
        // Sanitize response message to prevent XSS
        const escapeHtml = (unsafe) => {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        };

        // Prepare errors and successes
        const errors = response.data && response.data.errors ? response.data.errors.map(escapeHtml) : [];
        const successes = response.data && response.data.hostels_created ? response.data.hostels_created.map(escapeHtml) : [];

        var modalHtml = `
            <div class="modal fade" id="resultsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Upload Results</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-${response.status === 'success' ? 'success' : 'danger'}">
                                ${escapeHtml(response.message)}
                            </div>
                            ${errors.length > 0 ? `
                                <div class="mt-3">
                                    <h6>Errors:</h6>
                                    <ul class="list-group">
                                        ${errors.map(error => `
                                            <li class="list-group-item list-group-item-danger">${error}</li>
                                        `).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                            ${successes.length > 0 ? `
                                <div class="mt-3">
                                    <h6>Successes:</h6>
                                    <ul class="list-group">
                                        ${successes.map(success => `
                                            <li class="list-group-item list-group-item-success">${success}</li>
                                        `).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        var modal = new bootstrap.Modal(document.getElementById('resultsModal'));
        modal.show();
        document.getElementById('resultsModal').addEventListener('hidden.bs.modal', function () {
            this.remove();
        });
    }

    // Add event listener for file input
    document.addEventListener('DOMContentLoaded', function() {
        var fileInput = document.getElementById('dataFile');
        var uploadButton = document.getElementById('uploadButton');
        
        if (fileInput && uploadButton) {
            uploadButton.addEventListener('click', function(e) {
                e.preventDefault();
                var file = fileInput.files[0];
                if (!file) {
                    alert('Please select a file first');
                    return;
                }
                toggleUploadButton(true);
                handleFileUpload(file);
            });
        }
    });
</script>

<?php  
include("./includes/footer.php");
?>

<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<!-- Vendor JS Files -->
<script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/chart.js/chart.umd.js"></script>
<script src="assets/vendor/echarts/echarts.min.js"></script>
<script src="assets/vendor/quill/quill.min.js"></script>
<script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
<script src="assets/vendor/tinymce/tinymce.min.js"></script>
<script src="assets/vendor/php-email-form/validate.js"></script>

<!-- Template Main JS File -->
<script src="assets/js/main.js"></script>

</body>
</html> 