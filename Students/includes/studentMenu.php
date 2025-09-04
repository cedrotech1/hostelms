<?php
// Query to select system status
$query = "SELECT * FROM system";
$result = mysqli_query($connection, $query);
$row = mysqli_fetch_assoc($result);
$status = $row['status'] ?? null;
$allow_message = $row['allow_message'] ?? null;

if ($status != "live") {
    header("Location: ../status.php");
    exit(); // Stop further script execution
}


?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm" style="border-radius: 10px; margin-bottom: 20px;">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
      <!-- <img src="../../assets/img/ur.png" alt="Logo" width="32" height="32" class="me-2"> -->
      Student Portal
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
  
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="index.php">
            <i class="bi bi-house-door me-1"></i> Home
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="view_my_claims.php">
            <i class="bi bi-clipboard-data me-1"></i> My Claims
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="view_my_card.php">
            <i class="bi bi-card-heading me-1"></i> Hostel Card
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="form.php">
            <i class="bi bi-card-heading me-1"></i> Hostel Form
          </a>
        </li>
        <!--  Disability Proof -->
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="upload_disability_proof.php">
            <i class="bi bi-card-heading me-1"></i> Upload Disability Proof
          </a>
        </li>
        <!-- Add more nav items as needed -->
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="btn btn-danger d-flex align-items-center px-3 fw-semibold" href="./logout.php" role="button">
            <i class="bi bi-box-arrow-right me-1"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<!-- Bootstrap Icons CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">