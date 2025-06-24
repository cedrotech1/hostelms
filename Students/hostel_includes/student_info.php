<?php
if (!isset($_SESSION['student_id'])) {
    return;
}

// Get loading state from parent
$isLoading = isset($isLoading) ? $isLoading : false;
?>
<div class="container mt-4">
<!-- Bootstrap CSS -->
<!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->

<?php include dirname(__DIR__) . '/includes/studentMenu.php'; ?>
<br>

<!-- Bootstrap JS -->
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> -->

<style>
    /* Compact Card Style for 3-column layout, horizontal info */
    .ur-student-info-frame {
        background: rgba(255, 255, 255, 0.97);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(30, 60, 114, 0.08);
        padding: 0.7rem 0.7rem 0.7rem 0.7rem;
        width: 100%;
        max-width: 340px;
        margin: 0.7rem auto 0.7rem auto;
        position: relative;
        overflow: hidden;
    }
    .ur-student-info-frame::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #1e3c72, #2a5298, #667eea);
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
    }
    .ur-student-info-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.7rem;
    }
    .ur-student-info-header .profile-icon {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #e3f0ff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #1e3c72;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }
    .ur-student-info-header h4 {
        margin: 0;
        font-weight: 700;
        font-size: 1rem;
        color: #1e3c72;
        letter-spacing: 0.3px;
    }
    .ur-student-info-list {
        width: 100%;
    }
    .ur-student-info-item {
        margin-bottom: 0.2rem;
        /* padding-bottom: 0.2rem; */
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.97rem;
    }
    .ur-student-info-item:last-child {
        border-bottom: none;
    }
    .ur-student-info-item label {
        font-weight: 600;
        color: #1e3c72;
        margin-bottom: 0;
        display: block;
        font-size: 0.97rem;
        margin-right: 0.5rem;
        white-space: nowrap;
    }
    .ur-student-info-item p {
        margin: 0;
        font-size: 0.97rem;
        color: #222;
        text-align: right;
        flex: 1 1 auto;
        margin-left: 0.5rem;
        word-break: break-word;
    }
    @media (max-width: 900px) {
        .ur-student-info-frame {
            max-width: 98vw;
        }
    }
</style>

<?php if ($isLoading): ?>
    <!-- Skeleton Loading Structure -->
    <div class="student-info-card mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="skeleton skeleton-title w-50"></div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <?php for($i = 0; $i < 3; $i++): ?>
                        <div class="student-info-item mb-3">
                            <div class="skeleton skeleton-label"></div>
                            <div class="skeleton skeleton-value"></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="col-md-6">
                        <?php for($i = 0; $i < 4; $i++): ?>
                        <div class="student-info-item mb-3">
                            <div class="skeleton skeleton-label"></div>
                            <div class="skeleton skeleton-value"></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Actual Content -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-4 mb-3">
                <div class="ur-student-info-frame">
                    <div class="ur-student-info-header">
                        <div class="profile-icon">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <h4>Personal</h4>
                    </div>
                    <div class="ur-student-info-list">
                        <div class="ur-student-info-item">
                            <label>Full Name:</label>
                            <p><?php echo htmlspecialchars($_SESSION['student_name']); ?></p>
                        </div>
                        <div class="ur-student-info-item">
                            <label>Gender:</label>
                            <p><?php echo htmlspecialchars($_SESSION['student_gender']); ?></p>
                        </div>
                        <div class="ur-student-info-item">
                            <label>Disability:</label>
                            <p><?php 
                                if (isset($_SESSION['student_disability'])) {
                                    if(htmlspecialchars($_SESSION['student_disability'])){
                                        echo "yes";
                                    }else{
                                        echo "no"; 
                                    };
                                } else {
                                    echo '<span style="color: red;">Session variable not set</span>';
                                }
                            ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
                <div class="ur-student-info-frame">
                    <div class="ur-student-info-header">
                        <div class="profile-icon">
                            <i class="bi bi-mortarboard"></i>
                        </div>
                        <h4>Academic</h4>
                    </div>
                    <div class="ur-student-info-list">
                        <div class="ur-student-info-item">
                            <label>Reg. No.:</label>
                            <p><?php echo htmlspecialchars($_SESSION['student_regnumber']); ?></p>
                        </div>
                        <div class="ur-student-info-item">
                            <label>Year:</label>
                            <p><?php echo htmlspecialchars($_SESSION['student_year']); ?></p>
                        </div>
                        <div class="ur-student-info-item">
                            <label>Intake:</label>
                            <p><?php 
                                if (isset($_SESSION['student_intake'])) {
                                    echo htmlspecialchars($_SESSION['student_intake']);
                                } else {
                                    echo '<span style="color: red;">Session variable not set</span>';
                                }
                            ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-3">
                <div class="ur-student-info-frame">
                    <div class="ur-student-info-header">
                        <div class="profile-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <h4>Institution</h4>
                    </div>
                    <div class="ur-student-info-list">
                        <div class="ur-student-info-item">
                            <label>Campus:</label>
                            <p><?php echo htmlspecialchars($_SESSION['student_campus']); ?></p>
                        </div>
                        <div class="ur-student-info-item">
                            <label>College:</label>
                            <p><?php echo htmlspecialchars($_SESSION['student_college']); ?></p>
                        </div>
                        <div class="ur-student-info-item">
                            <label>Program:</label>
                            <p><?php echo htmlspecialchars($_SESSION['student_program']); ?></p>
                        </div>
                        <div class="ur-student-info-item">
                            <label>School:</label>
                            <p><?php echo htmlspecialchars($_SESSION['student_school']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>