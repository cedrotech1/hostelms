<?php
include('connection.php');

// include ('./includes/auth.php');
// checkUserRole(['information_modifier']);


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
      <h1>Student Data</h1>
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
             
    
                <h5 class="card-title">UPLOAD STUDENT INFORMATION FORM</h5>
                <br>
                <div class="col-md-12">
                <div class="form-floating">
                  <input class="form-control" type="file" id="dataFile" accept=".xls,.xlsx,.csv" />
                  <label for="floatingName">STUDENT DATA</label>
                </div>
              </div>
              <br>
              <div class="text-center">
                <button type="submit" id="uploadButton" name="saveproduct" class="btn btn-primary" 
                  >Save Data</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
              </div>
              

          
              <!-- <?php if ($existingData) : ?><br/>
              <div class="alert alert-warning" role="alert">
                Data already exists in the system.
              </div>
              <?php endif; ?> -->
            </div>
          </div>
        </div>
      </div><!-- End Left side columns -->
      <!-- template for data and file upload -->
      <div class="col-lg-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Template & Instructions</h5>
            
            <!-- Download Template Section -->
            <div class="mb-4">
              <h6 class="fw-bold">Download Template</h6>
              <p class="text-muted">Use our template to ensure your data is correctly structured or make sure header of each column is correct as in that template </p>
              <button onclick="downloadTemplate('xlsx')" class="btn btn-primary mb-1">
                <i class="bi bi-download me-1"></i> Download Excel Template
              </button>
              <button onclick="downloadTemplate('csv')" class="btn btn-secondary mb-1">
                <i class="bi bi-download me-1"></i> Download CSV Template
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
                  <!-- <li>Maximum file size: 5MB</li> -->
                  <li>Do not modify the header row OR make sure header of each column is correct as in that template</li>
                  <li>Save Excel files as CSV before uploading that is good plactice </li>
               
                </ol>
              </div>
            </div>

            <!-- Field Requirements -->
            <div class="mb-4">
              <h6 class="fw-bold">Field Requirements</h6>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Field</th>
                      <th>Requirements</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>Registration Number*</td>
                      <td>Must be unique for each student</td>
                    </tr>
                    <tr>
                      <td>Campus*</td>
                      <td>Must match existing campus name exactly</td>
                    </tr>
                    <tr>
                      <td>College*</td>
                      <td>Name of the college</td>
                    </tr>
                    <tr>
                      <td>Sirname*</td>
                      <td>Student's first name</td>
                    </tr>
                    <tr>
                      <td>Lastname*</td>
                      <td>Student's last name</td>
                    </tr>
                    <tr>
                      <td>School*</td>
                      <td>Name of the school</td>
                    </tr>
                    <tr>
                      <td>Program*</td>
                      <td>Name of the program</td>
                    </tr>
                    <tr>
                      <td>Intake*</td>
                      <td>Year of intake (e.g., 2023, 2024)</td>
                    </tr>
                    <tr>
                      <td>Disability*</td>
                      <td>Disability status (0 for No disability, 1 for Has disability)</td>
                    </tr>
                    <tr>
                      <td>Year of Study*</td>
                      <td>Current year of study (1-6)</td>
                    </tr>
                    <tr>
                      <td>Email*</td>
                      <td>Valid email address</td>
                    </tr>
                    <tr>
                      <td>Gender*</td>
                      <td>Male or Female</td>
                    </tr>
                    <tr>
                      <td>NID*</td>
                      <td>National ID number</td>
                    </tr>
                    <tr>
                      <td>Phone*</td>
                      <td>Phone number (10 digits)</td>
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
                  <li>Check that campus names match exactly</li>
                  <li>Registration numbers must be unique - no two students can have the same registration number</li>
                  <li>Email addresses must be unique and in valid format</li>
                  <li>Phone numbers should be 10 digits starting with 0</li>
                  <li>Gender should be "Male" or "Female" (case insensitive)</li>
                  <li>Year of study must be between 1 and 6</li>
                  <li>Disability must be "0" (No disability) or "1" (Has disability)</li>
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
      console.log('=== UPLOAD DEBUG START ===');
      console.log('Total rows to upload:', dataRows.length);
      console.log('First row (headers):', dataRows[0]);
      console.log('Second row (first data):', dataRows[1]);
      
      fetch('welfare_upload_student_excel.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json'
          },
          body: JSON.stringify({ data: dataRows })
      })
      .then(response => {
          console.log('Response status:', response.status);
          console.log('Response ok:', response.ok);
          if (!response.ok) {
              throw new Error('Network response was not ok');
          }
          return response.json();
      })
      .then(response => {
          console.log('Server response:', response);
          console.log('Response status:', response.status);
          console.log('Response message:', response.message);
          if (response.data && response.data.errors) {
              console.log('Validation errors:', response.data.errors);
          }
          // Always show the modal, regardless of status
          showResultsModal(response);
      })
      .catch(error => {
          console.error('Fetch error:', error);
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
          console.log('=== UPLOAD DEBUG END ===');
          // Re-enable the button after processing
          var uploadButton = document.getElementById('uploadButton');
          uploadButton.disabled = false;
          uploadButton.innerHTML = "Save Data";
      });
  }

  // Function to display results in a modal
  function showResultsModal(response) {
      // Ensure response.data exists with default values
      if (!response.data) {
          response.data = { errors: [], success: [] };
      }
      if (!response.data.errors) {
          response.data.errors = [];
      }
      if (!response.data.success) {
          response.data.success = [];
      }

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
                          ${response.data.errors && response.data.errors.length > 0 ? `
                              <div class="mt-3">
                                  <h6>Errors:</h6>
                                  <div class="table-responsive">
                                      <table class="table table-sm table-bordered">
                                          <thead class="table-light">
                                              <tr>
                                                  <th>Row</th>
                                                  <th>Error</th>
                                              </tr>
                                          </thead>
                                          <tbody>
                                              ${response.data.errors.map(error => {
                                                  const match = error.match(/Row (\d+): (.*)/);
                                                  return `
                                                      <tr>
                                                          <td>${match ? match[1] : 'N/A'}</td>
                                                          <td>${match ? match[2] : error}</td>
                                                      </tr>
                                                  `;
                                              }).join('')}
                                          </tbody>
                                      </table>
                                  </div>
                              </div>
                          ` : ''}
                          ${response.data.success && response.data.success.length > 0 ? `
                              <div class="mt-3">
                                  <h6>Successful Uploads:</h6>
                                  <div class="table-responsive">
                                      <table class="table table-sm table-bordered">
                                          <thead class="table-light">
                                              <tr>
                                                  <th>Row</th>
                                                  <th>Details</th>
                                              </tr>
                                          </thead>
                                          <tbody>
                                              ${response.data.success.map(success => {
                                                  const match = success.match(/Row (\d+): (.*)/);
                                                  return `
                                                      <tr>
                                                          <td>${match ? match[1] : 'N/A'}</td>
                                                          <td>${match ? match[2] : success}</td>
                                                      </tr>
                                                  `;
                                              }).join('')}
                                          </tbody>
                                      </table>
                                  </div>
                              </div>
                          ` : ''}
                      </div>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                          ${response.status === 'success' ? `
                              <button type="button" class="btn btn-primary" onclick="window.location.reload()">Refresh Page</button>
                          ` : ''}
                      </div>
                  </div>
              </div>
          </div>
      `;

      // Add modal to body
      document.body.insertAdjacentHTML('beforeend', modalHtml);

      // Show modal
      var modalElement = document.getElementById('resultsModal');
      if (modalElement) {
          var modal = new bootstrap.Modal(modalElement);
          modal.show();

          // Remove modal from DOM after it's hidden
          modalElement.addEventListener('hidden.bs.modal', function () {
              this.remove();
          });
      }
  }

  function downloadTemplate(format = 'xlsx') {
    // Define headers
    const headers = [
        'regnumber',
        'campus',
        'college',
        'sirname',
        'lastname',
        'school',
        'program',
        'intake',
        'disability',
        'yearofstudy',
        'email',
        'gender',
        'nid',
        'phone'
    ];

    // Example data sources
    const campuses = ['huye', 'gikondo', 'remera'];
    const colleges = ['CASS', 'CBE', 'CAVM', 'CST', 'CMHS', 'CE'];
    const programs = [
      'Computer Science', 'Economics', 'Agribusiness', 'Nursing',
      'Civil Engineering', 'Education', 'Veterinary Science',
      'Accounting', 'Medicine', 'Journalism'
    ];
    const genders = ['M', 'F'];
    const intakes = ['May-2022', 'Dec-2022', 'May-2023', 'Dec-2023'];
    const collegeSchoolMap = {
      CASS: ['School of Journalism', 'School of Law', 'School of Social Sciences'],
      CBE: ['School of Economics', 'School of Business', 'School of Finance'],
      CAVM: ['School of Agriculture', 'School of Animal Sciences', 'School of Veterinary Medicine'],
      CST: ['School of Engineering', 'School of ICT', 'School of Architecture'],
      CMHS: ['School of Medicine', 'School of Nursing', 'School of Dentistry'],
      CE: ['School of Education', 'School of Inclusive Education', 'School of Distance Learning']
    };
    const firstNames = ['John', 'Jane', 'Alice', 'Bob', 'Emily', 'David', 'Grace', 'James', 'Lucy', 'Michael'];
    const lastNames = ['Doe', 'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Wilson', 'Anderson'];

    function generateID(index) {
      return '202310' + (index + 1).toString().padStart(2, '0');
    }
    function generateNID(index) {
      return '1234567890' + (100000 + index).toString().padStart(6, '0');
    }
    function generatePhone(index) {
      return '0781' + (300000 + index).toString().padStart(6, '0').slice(0, 6);
    }
    function getRandomWithBias(trueProbability = 0.08) {
      return Math.random() < trueProbability ? 1 : 0;
    }

    // Generate 50 example rows
    const exampleRows = [];
    for (let i = 0; i < 50; i++) {
      const regnumber = generateID(i);
      const campus = campuses[i % campuses.length];
      const college = colleges[i % colleges.length];
      const schoolList = collegeSchoolMap[college];
      const school = schoolList[i % schoolList.length];
      const program = programs[i % programs.length];
      const intake = intakes[i % intakes.length];
      const yearofstudy = ((i % 6) + 1).toString();
      const sirname = firstNames[i % firstNames.length];
      const lastname = lastNames[i % lastNames.length];
      const email = `${sirname.toLowerCase()}.${lastname.toLowerCase()}${i + 1}@gmail.com`;
      const gender = genders[i % genders.length];
      const nid = generateNID(i);
      const phone = generatePhone(i);
      const disability = getRandomWithBias(0.08);
      exampleRows.push([
        regnumber, campus, college, sirname, lastname,
        school, program, intake, disability, yearofstudy,
        email, gender, nid, phone
      ]);
    }

    const wsData = [headers, ...exampleRows];

    if (format === 'xlsx') {
        // Create worksheet and workbook
        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Template");

        // Write workbook and trigger download
        const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
        const blob = new Blob([wbout], { type: "application/octet-stream" });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.href = url;
        link.download = 'student_data_template.xlsx';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    } else {
        // Fallback to CSV
        function escapeCSV(value) {
            if (typeof value !== 'string') value = String(value);
            if (value.includes(',') || value.includes('"') || value.includes('\n')) {
                return `"${value.replace(/"/g, '""')}"`;
            }
            return value;
        }
        let csvContent = headers.map(escapeCSV).join(',') + '\n';
        exampleRows.forEach(row => {
            csvContent += row.map(escapeCSV).join(',') + '\n';
        });
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.href = url;
        link.download = 'student_data_template.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }
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