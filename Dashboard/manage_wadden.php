<?php
include 'connection.php';

$userID = $_SESSION['id'];

$query = "SELECT campus FROM users WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$user_campus = '';
if ($row = $result->fetch_assoc()) {
    $user_campus = $row['campus'];
}

// Check if user is logged in and has campus assigned
if (!isset($user_campus)) {
    echo "<script>alert('No campus assigned. Please contact administrator.'); window.location.href='index.php';</script>";
    exit();
}

// Handle form submissions for updating wadden
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_wadden'])) {
    $wadden_id = mysqli_real_escape_string($connection, $_POST['wadden_id']);
    $names = mysqli_real_escape_string($connection, $_POST['names']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $hostels = isset($_POST['hostels']) ? $_POST['hostels'] : [];
    
    // Check if email already exists (excluding current wadden)
    $check_email = "SELECT id FROM users WHERE email = '$email' AND id != $wadden_id";
    $email_result = mysqli_query($connection, $check_email);
    
    if (mysqli_num_rows($email_result) > 0) {
        echo "<script>alert('Email already exists! Please use a different email address.');</script>";
    } else {
        // Update wadden (keep the same campus)
        $query = "UPDATE users SET names='$names', email='$email', phone='$phone' 
                  WHERE id=$wadden_id AND role='wadden' AND campus='$user_campus'";
        
        if (mysqli_query($connection, $query)) {
            // Delete existing hostel assignments
            mysqli_query($connection, "DELETE FROM wadden_hostels WHERE wadden_id=$wadden_id");
            
            // Insert new hostel assignments
            if (!empty($hostels)) {
                foreach ($hostels as $hostel_id) {
                    $hostel_query = "INSERT INTO wadden_hostels (wadden_id, hostel_id) VALUES ($wadden_id, $hostel_id)";
                    mysqli_query($connection, $hostel_query);
                }
            }
            
            echo "<script>alert('Wadden updated successfully!'); window.location.href='manage_wadden.php';</script>";
        } else {
            echo "<script>alert('Error updating wadden: " . mysqli_error($connection) . "');</script>";
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $wadden_id = mysqli_real_escape_string($connection, $_GET['delete']);
    
    // Delete hostel assignments first
    mysqli_query($connection, "DELETE FROM wadden_hostels WHERE wadden_id=$wadden_id");
    
    // Delete wadden (only if belongs to user's campus)
    if (mysqli_query($connection, "DELETE FROM users WHERE id=$wadden_id AND role='wadden' AND campus='$user_campus'")) {
        echo "<script>alert('Wadden deleted successfully!'); window.location.href='manage_wadden.php';</script>";
    } else {
        echo "<script>alert('Error deleting wadden: " . mysqli_error($connection) . "');</script>";
    }
}

// Handle activate/deactivate
if (isset($_GET['toggle_status'])) {
    $wadden_id = mysqli_real_escape_string($connection, $_GET['toggle_status']);
    $current_status = mysqli_fetch_assoc(mysqli_query($connection, "SELECT active FROM users WHERE id=$wadden_id AND campus='$user_campus'"))['active'];
    $new_status = $current_status ? 0 : 1;
    
    if (mysqli_query($connection, "UPDATE users SET active=$new_status WHERE id=$wadden_id AND role='wadden' AND campus='$user_campus'")) {
        echo "<script>alert('Status updated successfully!'); window.location.href='manage_wadden.php';</script>";
    } else {
        echo "<script>alert('Error updating status: " . mysqli_error($connection) . "');</script>";
    }
}

// Pagination settings
$limit = 10; // Number of waddens per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of waddens
$totalQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'wadden' AND campus = '$user_campus'";
$totalResult = mysqli_query($connection, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalWaddens = $totalRow['total'];
$totalPages = ceil($totalWaddens / $limit);

// Get waddens with their assigned hostels (only for user's campus)
$waddens_query = "SELECT u.*, GROUP_CONCAT(h.name) as assigned_hostels, GROUP_CONCAT(h.id) as hostel_ids
                  FROM users u 
                  LEFT JOIN wadden_hostels wh ON u.id = wh.wadden_id 
                  LEFT JOIN hostels h ON wh.hostel_id = h.id 
                  WHERE u.role = 'wadden' AND u.campus = '$user_campus'
                  GROUP BY u.id 
                  ORDER BY u.names 
                  LIMIT $limit OFFSET $offset";
$waddens_result = mysqli_query($connection, $waddens_query);

// Get all hostels for dropdown (only from user's campus)
$hostels_query = "SELECT * FROM hostels WHERE campus_id = '$user_campus' ORDER BY name";
$hostels_result = mysqli_query($connection, $hostels_query);

// Get campus name for display
$campus_name_query = "SELECT name FROM campuses WHERE id = '$user_campus'";
$campus_name_result = mysqli_query($connection, $campus_name_query);
$campus_name = mysqli_fetch_assoc($campus_name_result)['name'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
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

    <!-- Additional CSS -->
    <style>
        .hostel-checkbox { margin-right: 10px; }
        .wadden-card { transition: transform 0.2s; }
        .wadden-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .status-badge { font-size: 0.8em; }
        .hostel-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 5px; }
        .hostel-tag { background: #e9ecef; padding: 2px 8px; border-radius: 12px; font-size: 0.75em; color: #495057; }
        
        /* Modal loading overlay */
        .modal-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1050;
        }
        
        .modal-loading .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        .campus-info { background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination a {
            margin: 0 5px;
            padding: 8px 16px;
            border: 1px solid #1e40af;
            color: #1e40af;
            border-radius: 4px;
            text-decoration: none;
        }
        .pagination a.active {
            background-color: #1e40af;
            color: white;
        }
        .pagination a:hover:not(.active) {
            background-color: #e6f0fa;
        }
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

    <title>UR-HOSTELS</title>
</head>

<body>
    <?php
    include("./includes/header.php");
    include("./includes/menu.php");
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Hostel Wardens</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">Manage Hostel Wardens</li>
                </ol>
            </nav>
        </div>

        <!-- Campus Info -->
        <div class="campus-info">
            <h6><i class="bi bi-geo-alt"></i> Managing Waddens for Campus: <strong><?php echo ucwords($campus_name); ?></strong></h6>
        </div>

        <!-- Waddens List Section -->
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Waddens List (<?php echo ucwords($campus_name); ?> Campus)</h5>
                            
                            <!-- Search and Filter -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Search waddens...">
                                </div>
                                <div class="col-md-3">
                                    <select id="statusFilter" class="form-select">
                                        <option value="">All Status</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="waddensTable">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Assigned Hostels</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($waddens_result) > 0) { 
                                            while ($wadden = mysqli_fetch_assoc($waddens_result)) { ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo $wadden['image']; ?>" alt="Profile" class="rounded-circle me-2" width="40" height="40">
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($wadden['names']); ?></strong>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($wadden['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($wadden['phone']); ?></td>
                                                    <td>
                                                        <?php if ($wadden['assigned_hostels']) { ?>
                                                            <div class="hostel-tags">
                                                                <?php 
                                                                $hostels_array = explode(',', $wadden['assigned_hostels']);
                                                                foreach ($hostels_array as $hostel) { ?>
                                                                    <span class="hostel-tag"><?php echo htmlspecialchars(trim($hostel)); ?></span>
                                                                <?php } ?>
                                                            </div>
                                                        <?php } else { ?>
                                                            <span class="text-muted">No hostels assigned</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($wadden['active']) { ?>
                                                            <span class="badge bg-success status-badge">Active</span>
                                                        <?php } else { ?>
                                                            <span class="badge bg-danger status-badge">Inactive</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                    onclick="editWadden(<?php echo $wadden['id']; ?>)">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <a href="?toggle_status=<?php echo $wadden['id']; ?>" 
                                                               class="btn btn-sm btn-outline-<?php echo $wadden['active'] ? 'warning' : 'success'; ?>"
                                                               onclick="return confirm('Are you sure you want to <?php echo $wadden['active'] ? 'deactivate' : 'activate'; ?> this wadden?')">
                                                                <i class="bi bi-<?php echo $wadden['active'] ? 'pause' : 'play'; ?>"></i>
                                                            </a>
                                                            <a href="?delete=<?php echo $wadden['id']; ?>" 
                                                               class="btn btn-sm btn-outline-danger"
                                                               onclick="return confirm('Are you sure you want to delete this wadden? This action cannot be undone.')">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } else { ?>
                                            <tr>
                                                <td colspan="6" class="text-center">
                                                    <div class="alert alert-info">
                                                        <i class="bi bi-info-circle"></i> No waddens found for your campus.
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination -->
                            <div class="pagination">
                                <?php
                                if ($totalPages > 1) {
                                    for ($i = 1; $i <= $totalPages; $i++) {
                                        echo "<a href='manage_wadden.php?page=$i' class='" . ($i == $page ? 'active' : '') . "'>$i</a>";
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Edit Wadden Modal -->
    <div class="modal fade" id="editWaddenModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Wadden</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editWaddenForm">
                    <div class="modal-body">
                        <input type="hidden" name="wadden_id" id="edit_wadden_id">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_names" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="edit_names" name="names" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="edit_phone" name="phone" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Campus</label>
                                <input type="text" class="form-control" value="<?php echo ucwords($campus_name); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Assign Hostels (<?php echo ucwords($campus_name); ?> Campus)</label>
                                <div class="row" id="edit_hostels_container">
                                    <?php 
                                    mysqli_data_seek($hostels_result, 0);
                                    while ($hostel = mysqli_fetch_assoc($hostels_result)) { ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input edit-hostel-checkbox" type="checkbox" 
                                                       name="hostels[]" value="<?php echo $hostel['id']; ?>" 
                                                       id="edit_hostel_<?php echo $hostel['id']; ?>">
                                                <label class="form-check-label" for="edit_hostel_<?php echo $hostel['id']; ?>">
                                                    <?php echo htmlspecialchars($hostel['name']); ?> (<?php echo htmlspecialchars($hostel['building_code']); ?>)
                                                </label>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_wadden" class="btn btn-primary">Update Wadden</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chart.js/chart.umd.js"></script>
    <script src="assets/vendor/echarts/echarts.min.js"></script>
    <script src="assets/vendor/quill/quill.min.js"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
    <script src="assets/vendor/tinymce/tinymce.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>

    <!-- Custom JavaScript -->
    <script>
        // Search and filter functionality
        $(document).ready(function() {
            // Search functionality
            $('#searchInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#waddensTable tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Status filter
            $('#statusFilter').on('change', function() {
                var value = $(this).val();
                $('#waddensTable tbody tr').each(function() {
                    var status = $(this).find('.status-badge').text().toLowerCase();
                    if (value === '' || (value === '1' && status === 'active') || (value === '0' && status === 'inactive')) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });

        // Edit wadden functionality
        function editWadden(waddenId) {
            // Show loading state
            $('#editWaddenModal').modal('show');
            $('#editWaddenModal .modal-content').append('<div class="modal-loading"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            
            // Reset form and hide all hostels initially
            $('.edit-hostel-checkbox').prop('checked', false);
            
            $.ajax({
                url: 'get_wadden_data.php',
                type: 'POST',
                data: { wadden_id: waddenId },
                dataType: 'json',
                success: function(response) {
                    // Remove loading state
                    $('.modal-loading').remove();
                    
                    if (response.status === 'success') {
                        var wadden = response.data;
                        console.log('Wadden data:', wadden); // Debug log
                        
                        // Populate form fields
                        $('#edit_wadden_id').val(wadden.id);
                        $('#edit_names').val(wadden.names);
                        $('#edit_email').val(wadden.email);
                        $('#edit_phone').val(wadden.phone);
                        
                        // Check the checkboxes for assigned hostels
                        if (wadden.hostels && wadden.hostels.length > 0) {
                            console.log('Processing hostels:', wadden.hostels); // Debug log
                            
                            // First, uncheck all checkboxes
                            $('.edit-hostel-checkbox').prop('checked', false);
                            
                            // Then check the ones that should be checked
                            wadden.hostels.forEach(function(hostel) {
                                var checkbox = $('#edit_hostel_' + hostel.id);
                                if (checkbox.length) {
                                    checkbox.prop('checked', hostel.selected === true || hostel.selected === '1');
                                    console.log('Setting checkbox for hostel ' + hostel.id + ' to ' + (hostel.selected ? 'checked' : 'unchecked'));
                                } else {
                                    console.warn('Checkbox not found for hostel ID:', hostel.id);
                                }
                            });
                            
                            // If no hostels are selected, check if we have hostel_ids to work with
                            if (wadden.hostel_ids) {
                                var hostelIds = wadden.hostel_ids.split(',');
                                console.log('Using hostel_ids:', hostelIds);
                                
                                hostelIds.forEach(function(id) {
                                    var checkbox = $('#edit_hostel_' + id.trim());
                                    if (checkbox.length) {
                                        checkbox.prop('checked', true);
                                        console.log('Set checkbox for hostel ' + id + ' to checked (from hostel_ids)');
                                    } else {
                                        console.warn('Checkbox not found for hostel ID from hostel_ids:', id);
                                    }
                                });
                            }
                        }
                        
                        // Show modal (already shown, but in case it was hidden)
                        $('#editWaddenModal').modal('show');
                    } else {
                        alert('Error loading wadden data: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    $('.modal-loading').remove();
                    console.error('AJAX Error:', status, error);
                    alert('Error loading wadden data. Please check console for details.');
                }
            });
        }

        // Form validation
        $('form').on('submit', function() {
            var hostels = $('input[name="hostels[]"]:checked').length;
            if (hostels === 0) {
                if (!confirm('No hostels are selected. Are you sure you want to continue?')) {
                    return false;
                }
            }
        });
    </script>

</body>
</html>