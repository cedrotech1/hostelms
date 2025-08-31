<?php
include('connection.php');
// include('./includes/auth.php');
// checkUserRole(['information_modifier']);

// Fetch current status and time from the database
$status = '';
$time = 0;
$result = $connection->query("SELECT status, time FROM system LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $status = $row['status'];
    $time = (int)$row['time'];
}

if (isset($_POST['update'])) {
    $status = $connection->real_escape_string($_POST['status']);
    $input_time = (float)$_POST['time']; // Changed to float to handle decimal inputs
    $unit = $connection->real_escape_string($_POST['time_unit']);
    
    // Convert to minutes for database storage
    $time_in_minutes = $unit === 'hours' ? $input_time * 60 : $input_time;
    $time_in_minutes = (int)round($time_in_minutes); // Round to nearest integer
    
    $query = "UPDATE system SET status='$status', time='$time_in_minutes'";
    $resultx = $connection->query($query);

    if ($resultx) {
        echo "<script>window.location.href='system.php'</script>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $connection->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UR-TIMETABLE | System Settings</title>
    <meta name="description" content="System settings management for UR Timetable">
    <meta name="keywords" content="timetable, system settings, management">

    <!-- Favicons -->
    <link href="assets/img/icon1.png" rel="icon">
    <link href="assets/img/icon1.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="assets/css/style.css" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f6f8;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 1.5rem;
        }
        .status-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }
        .form-select, .form-control {
            border-radius: 8px;
            padding: 0.75rem;
        }
        .btn-primary {
            background: #6366f1;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #4f46e5;
        }
        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem;
        }
        .input-group {
            align-items: center;
        }
        .input-group-text {
            border-radius: 8px 0 0 8px;
        }
        @media (max-width: 768px) {
            .card-header {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <?php
    include("./includes/header.php");
    include("./includes/menu.php");
    ?>

    <main id="main" class="main py-5">
        <section class="section dashboard">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-md-10">
                        <!-- System Status Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="bi bi-gear-fill me-2"></i>System Settings</h4>
                            </div>
                            <div class="card-body p-4">
                                <!-- Current Status Display -->
                                <div class="alert <?php 
                                    echo ($status == 'live' ? 'alert-success' : 
                                        ($status == 'mentainance' ? 'alert-warning' : 
                                        ($status == 'offline' ? 'alert-danger' : 
                                        ($status == 'closed' ? 'alert-secondary' : 'alert-info')))); 
                                ?>">
                                    <h5 class="mb-2">
                                        <i class="status-icon bi <?php 
                                            echo ($status == 'live' ? 'bi-check-circle-fill' : 
                                                ($status == 'mentainance' ? 'bi-tools' : 
                                                ($status == 'offline' ? 'bi-slash-circle' : 
                                                ($status == 'closed' ? 'bi-x-circle' : 'bi-code-slash')))); 
                                        ?>"></i>
                                        Current Status: <strong><?php echo ucfirst($status); ?></strong>
                                    </h5>
                                    <p class="mb-0">
                                        <?php
                                        switch ($status) {
                                            case 'live':
                                                echo "The system is fully operational and accessible to all users.";
                                                break;
                                            case 'mentainance':
                                                echo "The system is under maintenance. Access is restricted.";
                                                break;
                                            case 'offline':
                                                echo "The system is currently offline and inaccessible.";
                                                break;
                                            case 'closed':
                                                echo "The system is permanently closed.";
                                                break;
                                            case 'development':
                                                echo "The system is in development mode. Features may be unstable.";
                                                break;
                                        }
                                        ?>
                                    </p>
                                </div>

                                <!-- Update Form -->
                                <form action="" method="POST">
                                    <div class="mb-4">
                                        <label class="form-label fw-medium">Update System Status</label>
                                        <select class="form-select" name="status" required>
                                            <option value="live" <?php if($status=='live') echo 'selected'; ?>>Live (Fully operational)</option>
                                            <option value="mentainance" <?php if($status=='mentainance') echo 'selected'; ?>>Maintenance (Restricted access)</option>
                                            <option value="offline" <?php if($status=='offline') echo 'selected'; ?>>Offline (No access)</option>
                                            <option value="closed" <?php if($status=='closed') echo 'selected'; ?>>Closed (Permanently inactive)</option>
                                            <option value="development" <?php if($status=='development') echo 'selected'; ?>>Development (Unstable features)</option>
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-medium">Set Duration For Student to Provide Proof of Payment</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="timeInUnit" name="time" 
                                                   value="<?php echo round($time / 60, 2); ?>" min="0" step="0.1" required>
                                            <select class="form-select" id="timeUnit" name="time_unit" style="max-width: 150px;">
                                                <option value="hours" selected>Hours</option>
                                                <option value="minutes">Minutes</option>
                                            </select>
                                        </div>
                                        <small class="text-muted">Specify how long the system should remain in this mode.</small>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-medium">Readable Duration (Stored in Database)</label>
                                        <input type="text" class="form-control" id="timeDisplay" value="" disabled>
                                    </div>

                                    <button type="submit" name="update" class="btn btn-primary w-100">Update Settings</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include("./includes/footer.php"); ?>
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chart.js/chart.umd.js"></script>
    <script src="assets/vendor/echarts/echarts.min.js"></script>
    <script src="assets/vendor/quill/quill.min.js"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
    <script src="assets/vendor/tinymce/tinymce.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>
    <!-- Add jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        function convertToReadableDuration(value, unit) {
            const minutes = unit === 'hours' ? value * 60 : value;
            const days = Math.floor(minutes / 1440);
            const hours = Math.floor((minutes % 1440) / 60);
            const mins = Math.round(minutes % 60);
            return `${days} day(s), ${hours} hour(s), ${mins} minute(s)`;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const timeInput = document.getElementById('timeInUnit');
            const timeUnit = document.getElementById('timeUnit');
            const timeDisplay = document.getElementById('timeDisplay');
            const initialTime = <?php echo $time; ?>;
            
            // Set initial display
            timeDisplay.value = convertToReadableDuration(initialTime / 60, 'hours');

            // Update display when input or unit changes
            function updateDisplay() {
                const val = parseFloat(timeInput.value) || 0;
                const unit = timeUnit.value;
                timeDisplay.value = convertToReadableDuration(val, unit);
            }

            timeInput.addEventListener('input', updateDisplay);
            timeUnit.addEventListener('change', updateDisplay);
        });
    </script>
</body>
</html>