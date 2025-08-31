<?php
include('connection.php');

// Generate Excel template if requested
if (isset($_GET['download_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="blacklist_template.xlsx"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    
    // Write header
    fputcsv($output, array('Registration Number', 'Names', 'Reason'));
    
    // Write example data
    fputcsv($output, array('2023001', 'John Doe', 'Late payment'));
    fputcsv($output, array('2023002', 'Jane Smith', 'Violation of rules'));
    fputcsv($output, array('2023003', 'Bob Johnson', 'Damage to property'));
    
    fclose($output);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['blacklist'])) {
    $file = $_FILES['blacklist']['tmp_name'];

    if (($handle = fopen($file, "r")) !== false) {
        // Skip header
        fgetcsv($handle);

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $regnumber = trim($data[0]);
            $names = trim($data[1]);
            $reason = trim($data[2]);

            if (!empty($regnumber) && !empty($names) && !empty($reason)) {
                $stmt = $connection->prepare("INSERT INTO blacklist (regnumber, names, reason) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $regnumber, $names, $reason);
                $stmt->execute();
            }
        }

        fclose($handle);
        echo "<script>alert('Blacklist uploaded successfully.');</script>";
    } else {
        echo "<script>alert('Failed to open the file.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Add User</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/icon1.png" rel="icon">
  <link href="assets/img/icon1.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link
    href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
    rel="stylesheet">

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

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

  <style>
    ul li {
      list-style: none;
    }
  </style>
</head>
<body>  

<?php
  include("./includes/header.php");
  include("./includes/menu.php");
  ?>


  <main id="main" class="main">
  <section class="section dashboard">
    <div class="row">
      <div class="col-lg-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Blacklist Management</h5>
            
            <div class="alert alert-info mb-3">
              <h6>How to Use:</h6>
              <ol>
                <li>Click the "Download Template" button to get the CSV template</li>
                <li>Fill in the template with student information (Registration Number, Names, Reason)</li>
                <li>Click "Upload" to add students to the blacklist</li>
                <a href="templates/Blacklist_Management.csv" download class="btn btn-primary btn-sm">
    <i class="fas fa-download"></i> Download Template
</a>

              </ol>
              <p><strong>Note:</strong> Ensure all required fields are filled before uploading.</p>
            </div>

           
            <form id="clearDataForm" action="blacklist.php" method="post" enctype="multipart/form-data">
              <div class="mb-3">
                <label for="blacklistFile" class="form-label">Select CSV File</label>
                <input type="file" class="form-control" id="blacklistFile" name="blacklist" accept=".csv" required>
                <div class="form-text">Only CSV files are allowed</div>
              </div>
              <input type="submit" value="Upload" class="btn btn-primary w-100">
            </form>

            <div class="mt-3">
              <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                File should contain three columns: Registration Number, Names, and Reason
              </small>
            </div>

            <script>
              document.getElementById("clearDataForm").addEventListener("submit", function(event) {
                var confirmClear = confirm("Are you sure you want to upload blacklist data? This will add new entries to the blacklist.");
                if (!confirmClear) {
                  event.preventDefault();
                }
              });
            </script>
          </div>
        </div>
      </div>

      <!-- Blacklist Entries Display -->
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Current Blacklist Entries</h5>

            <!-- Search Form -->
            <form class="mb-4">
              <div class="row">
                <div class="col-md-8">
                  <div class="input-group">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search by Registration Number..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                      <i class="fas fa-search"></i> Search
                    </button>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="text-end">
                    <select class="form-select" name="limit" onchange="this.form.submit()">
                      <option value="10" <?php echo isset($_GET['limit']) && $_GET['limit'] == 10 ? 'selected' : ''; ?>>10 per page</option>
                      <option value="25" <?php echo isset($_GET['limit']) && $_GET['limit'] == 25 ? 'selected' : ''; ?>>25 per page</option>
                      <option value="50" <?php echo isset($_GET['limit']) && $_GET['limit'] == 50 ? 'selected' : ''; ?>>50 per page</option>
                      <option value="100" <?php echo isset($_GET['limit']) && $_GET['limit'] == 100 ? 'selected' : ''; ?>>100 per page</option>
                    </select>
                  </div>
                </div>
              </div>
            </form>

            <?php
            // Pagination variables
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            // Search query
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM blacklist";
            if (!empty($search)) {
                $countQuery .= " WHERE regnumber LIKE '%" . $connection->real_escape_string($search) . "%'";
            }
            $countResult = $connection->query($countQuery);
            $total = $countResult->fetch_assoc()['total'];
            $totalPages = ceil($total / $limit);
            
            // Get records
            $query = "SELECT * FROM blacklist";
            if (!empty($search)) {
                $query .= " WHERE regnumber LIKE '%" . $connection->real_escape_string($search) . "%'";
            }
            $query .= " ORDER BY regnumber LIMIT $limit OFFSET $offset";
            $result = $connection->query($query);

            if ($result && $result->num_rows > 0) {
                echo "<div class='table-responsive'>";
                echo "<table class='table table-striped'>";
                echo "<thead><tr>";
                echo "<th>Registration Number</th>";
                echo "<th>Names</th>";
                echo "<th>Reason</th>";
                echo "<th>Actions</th>";
                echo "</tr></thead><tbody>";
                
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['regnumber']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['names']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['reason']) . "</td>";
                    echo "<td>";
                    echo "<button class='btn btn-danger btn-sm' onclick='confirmDelete(" . $row['id'] . ")'>Remove</button>";
                    echo "</td></tr>";
                }
                echo "</tbody></table></div>";

                // Pagination
                echo "<nav class='mt-4' aria-label='Page navigation'>";
                echo "<ul class='pagination justify-content-center'>";

                // Previous page
                if ($page > 1) {
                    echo "<li class='page-item'>";
                    echo "<a class='page-link' href='?page=" . ($page - 1) . "&limit=$limit" . (!empty($search) ? "&search=" . urlencode($search) : "") . "' aria-label='Previous'>";
                    echo "<span aria-hidden='true'>&laquo;</span>";
                    echo "</a></li>";
                }

                // Page numbers
                for ($i = 1; $i <= $totalPages; $i++) {
                    if ($i == $page) {
                        echo "<li class='page-item active'><span class='page-link'>$i</span></li>";
                    } else {
                        echo "<li class='page-item'>";
                        echo "<a class='page-link' href='?page=$i&limit=$limit" . (!empty($search) ? "&search=" . urlencode($search) : "") . "'>$i</a>";
                        echo "</li>";
                    }
                }

                // Next page
                if ($page < $totalPages) {
                    echo "<li class='page-item'>";
                    echo "<a class='page-link' href='?page=" . ($page + 1) . "&limit=$limit" . (!empty($search) ? "&search=" . urlencode($search) : "") . "' aria-label='Next'>";
                    echo "<span aria-hidden='true'>&raquo;</span>";
                    echo "</a></li>";
                }

                echo "</ul></nav>";
                
                // Show total records
                echo "<div class='text-center mt-3'>Showing " . (($page - 1) * $limit + 1) . " to " . 
                      min($page * $limit, $total) . " of $total entries</div>";
            } else {
                echo "<div class='alert alert-info'>No blacklist entries found.</div>";
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to remove this student from the blacklist?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
function confirmDelete(id) {
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
    
    document.getElementById('confirmDeleteBtn').onclick = function() {
        window.location.href = 'delete_blacklist.php?id=' + id;
    };
}
</script>

<?php include("./includes/footer.php"); ?>

<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

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
