<?php
include('connection.php');

include ('./includes/auth.php');
$userRole=$_SESSION['role'];



// Function to check if there's data in the system
function checkExistingData($connection) {
    $query = "SELECT COUNT(*) as count FROM info"; // Adjust table name if necessary
    $result = mysqli_query($connection, $query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0; // Return true if data exists, false otherwise
    } else {
        return false; // Handle the case where the query fails
    }
}

$existingData = checkExistingData($connection); // Check if data exists
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
          <li class="breadcrumb-item">data</li>
          <li class="breadcrumb-item active">upload</li>
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
             

              <?php if ($existingData=1) : ?><br/>
                <h5 class="card-title">UPLOAD HOSTELS INFORMATION FORM</h5>
                <br>
                <div class="col-md-12">
                <div class="form-floating">
                  <input class="form-control" type="file" id="dataFile" accept=".xls,.xlsx,.csv" />
                  <label for="floatingName">DATA</label>
                </div>
              </div>
              <br>
              <div class="text-center">
                <button type="submit" id="uploadButton" name="saveproduct" class="btn btn-primary" 
                  >Save Data</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
              </div>
              <?php endif; ?>

          
              <!-- <?php if ($existingData) : ?><br/>
              <div class="alert alert-warning" role="alert">
                Data already exists in the system.
              </div>
              <?php endif; ?> -->
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
                  <li>Save Excel files as CSV before uploading that is good practice</li>
                  <?php if ($userRole === 'warefare'): ?>
                  <li>You can only upload hostels for your assigned campus</li>
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
                      <td><?php echo $userRole === 'warefare' ? 'Your assigned campus name' : 'Any valid campus name'; ?></td>
                    </tr>
                    <tr>
                      <td>Hostel Name*</td>
                      <td>Name of the hostel</td>
                    </tr>
                    <tr>
                      <td>Room Code*</td>
                      <td>Unique room identifier (e.g., A101)</td>
                    </tr>
                    <tr>
                      <td>Number of Beds*</td>
                      <td>Total number of beds in the room</td>
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
                  <li>Check that campus name matches your assigned campus exactly</li>
                  <?php else: ?>
                  <li>Check that campus name exists in the system</li>
                  <?php endif; ?>
                  <li>Room codes must be unique within each hostel</li>
                  <li>Number of beds must be a positive integer</li>
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
    // Create workbook
    var wb = XLSX.utils.book_new();
    
    // Create worksheet with headers
    var ws_data = [
      ['Campus', 'Hostel Name', 'Room Code', 'Number of Beds'],
      ['Huye', 'Bengazi', 'A101', '4'],
      ['Huye', 'Bengazi', 'A102', '4'],
      ['Huye', 'Bengazi', 'B101', '2']
    ];
    
    var ws = XLSX.utils.aoa_to_sheet(ws_data);
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, "Hostel Template");
    
    // Generate and download file
    XLSX.writeFile(wb, "hostel_template.xlsx");
  }

  document.getElementById('uploadButton').addEventListener('click', function () {
      uploadFile();
  });

  function uploadFile() {
      var fileInput = document.getElementById('dataFile');
      var file = fileInput.files[0];
      var uploadButton = document.getElementById('uploadButton');

      if (!file) {
          alert("Please select a file.");
          return;
      }

      // Disable button and show loading state
      uploadButton.disabled = true;
      uploadButton.innerHTML = "Loading...";

      var fileExtension = file.name.split('.').pop().toLowerCase();
      if (fileExtension === 'xls' || fileExtension === 'xlsx') {
          readExcel(file);
      } else if (fileExtension === 'csv') {
          readCSV(file);
      } else {
          alert("Unsupported file format. Please upload an Excel or CSV file.");
          uploadButton.disabled = false;  // Re-enable button if error
          uploadButton.innerHTML = "Save Data";
      }
  }

  // Function to read Excel files
  function readExcel(file) {
      var reader = new FileReader();

      reader.onload = function (e) {
          var data = new Uint8Array(e.target.result);
          var workbook = XLSX.read(data, { type: 'array' });
          var firstSheet = workbook.Sheets[workbook.SheetNames[0]];
          
          // Convert to array with empty string for empty cells
          var excelRows = XLSX.utils.sheet_to_json(firstSheet, { 
              header: 1,
              defval: '',
              blankrows: false
          });
          
          // Filter out completely empty rows
          excelRows = excelRows.filter(row => row.some(cell => cell !== ''));
          
          // Send data to the server
          uploadToServer(excelRows);
      };

      reader.readAsArrayBuffer(file);
  }

  // Function to read CSV files
  function readCSV(file) {
      Papa.parse(file, {
          complete: function (results) {
              // Filter out completely empty rows
              var filteredData = results.data.filter(row => 
                  row.some(cell => cell !== '' && cell !== null)
              );
              
              // Send data to the server
              uploadToServer(filteredData);
          },
          skipEmptyLines: true,
          transform: function(value) {
              return value.trim();
          }
      });
  }

  // Function to upload data to the server
  function uploadToServer(dataRows) {
      fetch('welfare_upload_hostel_excel.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json'
          },
          body: JSON.stringify({ data: dataRows })
      })
      .then(response => {
          if (!response.ok) {
              throw new Error('Network response was not ok');
          }
          return response.json();
      })
      .then(response => {
          showResultsModal(response);
      })
      .catch(error => {
          console.error('Error:', error);
          showResultsModal({
              status: 'error',
              message: error.message || 'An error occurred while processing the file. Please try again.',
              data: {
                  errors: [error.message],
                  success: []
              }
          });
      })
      .finally(() => {
          // Re-enable the button after processing
          var uploadButton = document.getElementById('uploadButton');
          uploadButton.disabled = false;
          uploadButton.innerHTML = "Save Data";
      });
  }

  // Function to display results in a modal
  function showResultsModal(response) {
      // Create modal HTML
      var modalHtml = `
          <div class="modal fade" id="resultsModal" tabindex="-1">
              <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                      <div class="modal-header">
                          <h5 class="modal-title">Upload Results</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                          <div class="alert alert-${response.status === 'success' ? 'success' : 
                                               response.status === 'partial' ? 'warning' : 'danger'}">
                              ${response.message}
                          </div>
                          ${response.data && response.data.errors && response.data.errors.length > 0 ? `
                              <div class="mt-3">
                                  <h6>Errors:</h6>
                                  <ul class="list-group">
                                      ${response.data.errors.map(error => `
                                          <li class="list-group-item list-group-item-danger">${error}</li>
                                      `).join('')}
                                  </ul>
                              </div>
                          ` : ''}
                          ${response.data && response.data.success && response.data.success.length > 0 ? `
                              <div class="mt-3">
                                  <h6>Success:</h6>
                                  <ul class="list-group">
                                      ${response.data.success.map(success => `
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

      // Add modal to document
      document.body.insertAdjacentHTML('beforeend', modalHtml);

      // Show modal
      var modal = new bootstrap.Modal(document.getElementById('resultsModal'));
      modal.show();

      // Remove modal from DOM after it's hidden
      document.getElementById('resultsModal').addEventListener('hidden.bs.modal', function () {
          this.remove();
      });
  }
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
