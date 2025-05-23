<?php
if (!isset($_SESSION['student_id'])) {
    return;
}
?>
<div class="container mt-4">
<!-- Bootstrap CSS -->
<!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->

<?php include dirname(__DIR__) . '/includes/studentMenu.php'; ?>
<br>

<!-- Bootstrap JS -->
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> -->

<div class="student-info-card mb-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0 text-white"><i class="bi bi-person-circle text-white"></i> Student Information</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="student-info-item">
                        <label class="text-muted">Full Name:</label>
                        <p class="mb-0"><?php echo htmlspecialchars($_SESSION['student_name']); ?></p>
                    </div>
                    <div class="student-info-item">
                        <label class="text-muted">Registration Number:</label>
                        <p class="mb-0"><?php echo htmlspecialchars($_SESSION['student_regnumber']); ?></p>
                    </div>
                    <div class="student-info-item">
                        <label class="text-muted">Campus:</label>
                        <p class="mb-0"><?php echo htmlspecialchars($_SESSION['student_campus']); ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="student-info-item">
                        <label class="text-muted">College:</label>
                        <p class="mb-0"><?php echo htmlspecialchars($_SESSION['student_college']); ?></p>
                    </div>
                    <div class="student-info-item">
                        <label class="text-muted">Program:</label>
                        <p class="mb-0"><?php echo htmlspecialchars($_SESSION['student_program']); ?></p>
                    </div>
                    <div class="student-info-item">
                        <label class="text-muted">Year of Study:</label>
                        <p class="mb-0"><?php echo htmlspecialchars($_SESSION['student_year']); ?></p>
                    </div>
                    <!-- gender -->
                    <div class="student-info-item">
                        <label class="text-muted">Gender:</label>
                        <p class="mb-0"><?php echo htmlspecialchars($_SESSION['student_gender']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 
</div>