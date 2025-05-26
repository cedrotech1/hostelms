<?php
include('connection.php');

include ('./includes/auth.php');
checkUserRole(['information_modifier']);


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

  <title>UR-HUYE-HOSTELS</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="./icon1.png" rel="icon">
  <link href="./icon1.png" rel="apple-touch-icon">

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
          var excelRows = XLSX.utils.sheet_to_json(firstSheet, { header: 1 }).slice(1); // Skip first row (headers)
          
          // Send data to the server
          uploadToServer(excelRows);
      };

      reader.readAsArrayBuffer(file);
  }

  // Function to read CSV files
  function readCSV(file) {
      Papa.parse(file, {
          complete: function (results) {
              var csvRows = results.data.slice(1); // Skip first row (headers)
              
              // Send data to the server
              uploadToServer(csvRows);
          }
      });
  }

  // Function to upload data to the server
  function uploadToServer(dataRows) {
      console.log(dataRows);  // Debugging

      fetch('upload_hostel_excel.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json'
          },
          body: JSON.stringify({ data: dataRows })
      })
      .then(response => response.text())
      .then(responseText => {
          console.log(responseText);
          alert("DONE");
          // window.location.href = "upload_hostel_excel.php";
      })
      .catch(error => {
          console.error('Error:', error);
      })
      .finally(() => {
          // Re-enable the button after processing
          var uploadButton = document.getElementById('uploadButton');
          uploadButton.disabled = false;
          uploadButton.innerHTML = "Save Data";
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
