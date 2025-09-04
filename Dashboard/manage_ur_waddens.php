<?php
include 'connection.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userID = $_SESSION['id'];

// For headquarter user, we don't need to check campus
$is_hq = true; // Force HQ mode for this page
$campus_filter = ''; // No campus filter for HQ user

// Get all campuses for the filter dropdown
$campuses_query = "SELECT * FROM campuses ORDER BY name";
$campuses_result = mysqli_query($connection, $campuses_query);

// Get campus name if applicable
$campus_name = 'All Campuses'; // Default for HQ

// Handle form submissions for updating wadden
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_wadden'])) {
    $wadden_id = mysqli_real_escape_string($connection, $_POST['wadden_id']);
    $names = mysqli_real_escape_string($connection, $_POST['names']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $hostels = isset($_POST['hostels']) ? $_POST['hostels'] : [];
    
    // Fetch wadden's current campus (cannot change campus)
    $wadden_campus_query = "SELECT campus FROM users WHERE id = $wadden_id AND role='wadden'";
    $wadden_campus_result = mysqli_query($connection, $wadden_campus_query);
    $wadden_campus = mysqli_fetch_assoc($wadden_campus_result)['campus'];
    
    // Check if email already exists (excluding current wadden)
    $check_email = "SELECT id FROM users WHERE email = '$email' AND id != $wadden_id";
    $email_result = mysqli_query($connection, $check_email);
    
    if (mysqli_num_rows($email_result) > 0) {
        echo "<script>alert('Email already exists! Please use a different email address.');</script>";
    } else {
        // Update wadden (keep the same campus)
        $query = "UPDATE users SET names='$names', email='$email', phone='$phone' 
                  WHERE id=$wadden_id AND role='wadden'";
        // For campus user, add filter
        if (!$is_hq) {
            $query .= " AND campus='$user_campus'";
        }
        
        if (mysqli_query($connection, $query)) {
            // Validate hostels belong to wadden's campus
            $valid_hostels = [];
            if (!empty($hostels)) {
                foreach ($hostels as $hostel_id) {
                    $hostel_check = "SELECT campus_id FROM hostels WHERE id = $hostel_id";
                    $hostel_result = mysqli_query($connection, $hostel_check);
                    if ($hostel_row = mysqli_fetch_assoc($hostel_result)) {
                        if ($hostel_row['campus_id'] == $wadden_campus) {
                            $valid_hostels[] = $hostel_id;
                        }
                    }
                }
            }
            
            if (count($valid_hostels) != count($hostels)) {
                echo "<script>alert('Some hostels do not belong to the wadden\\'s campus and were ignored.');</script>";
            }
            
            // Delete existing hostel assignments
            mysqli_query($connection, "DELETE FROM wadden_hostels WHERE wadden_id=$wadden_id");
            
            // Insert new valid hostel assignments
            if (!empty($valid_hostels)) {
                foreach ($valid_hostels as $hostel_id) {
                    $hostel_query = "INSERT INTO wadden_hostels (wadden_id, hostel_id) VALUES ($wadden_id, $hostel_id)";
                    mysqli_query($connection, $hostel_query);
                }
            }
            
            echo "<script>alert('Wadden updated successfully!'); window.location.href='manage_ur_waddens.php';</script>";
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
    
    // Delete wadden
    $delete_query = "DELETE FROM users WHERE id=$wadden_id AND role='wadden'";
    if (!$is_hq) {
        $delete_query .= " AND campus='$user_campus'";
    }
    if (mysqli_query($connection, $delete_query)) {
        echo "<script>alert('Wadden deleted successfully!'); window.location.href='manage_ur_waddens.php';</script>";
    } else {
        echo "<script>alert('Error deleting wadden: " . mysqli_error($connection) . "');</script>";
    }
}

// Handle activate/deactivate
if (isset($_GET['toggle_status'])) {
    $wadden_id = mysqli_real_escape_string($connection, $_GET['toggle_status']);
    $current_status_query = "SELECT active FROM users WHERE id=$wadden_id AND role='wadden'";
    if (!$is_hq) {
        $current_status_query .= " AND campus='$user_campus'";
    }
    $current_status = mysqli_fetch_assoc(mysqli_query($connection, $current_status_query))['active'];
    $new_status = $current_status ? 0 : 1;
    
    $update_status_query = "UPDATE users SET active=$new_status WHERE id=$wadden_id AND role='wadden'";
    if (!$is_hq) {
        $update_status_query .= " AND campus='$user_campus'";
    }
    if (mysqli_query($connection, $update_status_query)) {
        echo "<script>alert('Status updated successfully!'); window.location.href='manage_ur_waddens.php';</script>";
    } else {
        echo "<script>alert('Error updating status: " . mysqli_error($connection) . "');</script>";
    }
}

// Pagination settings
$limit = 10; // Number of waddens per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of waddens
$totalQuery = "SELECT COUNT(*) as total FROM users u WHERE role = 'wadden'$campus_filter";
$totalResult = mysqli_query($connection, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalWaddens = $totalRow['total'];
$totalPages = ceil($totalWaddens / $limit);

// Get waddens with their assigned hostels
$waddens_query = "SELECT u.*, c.name as campus_name, GROUP_CONCAT(h.name) as assigned_hostels, GROUP_CONCAT(h.id) as hostel_ids
                  FROM users u 
                  LEFT JOIN campuses c ON u.campus = c.id
                  LEFT JOIN wadden_hostels wh ON u.id = wh.wadden_id 
                  LEFT JOIN hostels h ON wh.hostel_id = h.id 
                  WHERE u.role = 'wadden'$campus_filter
                  GROUP BY u.id 
                  ORDER BY u.names 
                  LIMIT $limit OFFSET $offset";
$waddens_result = mysqli_query($connection, $waddens_query);

// Get all hostels for dropdown (all campuses for HQ)
$hostels_query = "SELECT h.*, c.name as campus_name 
                FROM hostels h
                JOIN campuses c ON h.campus_id = c.id 
                ORDER BY c.name, h.name";
$hostels_result = mysqli_query($connection, $hostels_query);
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
    
    <style>
        /* Loading indicator */
        #loadingIndicator {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }
        
        #loadingIndicator .spinner-border {
            width: 3rem;
            height: 3rem;
            margin-bottom: 1rem;
        }
        
        /* Error messages */
        .alert {
            margin-bottom: 1rem;
        }
        
        .alert .bi {
            font-size: 1.2em;
            vertical-align: middle;
            margin-right: 0.5rem;
        }
        
        /* Hostel tags */
        .hostel-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
        }
        
        /* Modal loading overlay */
        .modal-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1060;
        }
    </style>
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
        .campus-info { background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        
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
        <div class="campus-info d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0"><i class="bi bi-geo-alt"></i> Managing Waddens for: <strong><?php echo $campus_name; ?></strong></h6>
        </div>

        <!-- Waddens List Section -->
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Waddens List <?php echo $is_hq ? '(All Campuses)' : '(' . ucwords($campus_name) . ' Campus)'; ?></h5>
                            
                            <!-- Error Message Container -->
                            <div id="errorMessage" class="mb-3" style="display: none;">
                                <!-- Error messages will be inserted here by JavaScript -->
                            </div>
                            
                            <!-- Search and Filter -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Search by name or email...">
                                </div>
                                <div class="col-md-3">
                                    <select id="campusFilter" class="form-select">
                                        <option value="0">All Campuses</option>
                                        <?php 
                                        $campuses_query = "SELECT * FROM campuses ORDER BY name";
                                        $campuses_result = mysqli_query($connection, $campuses_query);
                                        while ($campus = mysqli_fetch_assoc($campuses_result)) {
                                            echo "<option value='" . $campus['id'] . "'>" . htmlspecialchars($campus['name']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <!-- <div class="col-md-3">
                                    <select id="statusFilter" class="form-select">
                                        <option value="">All Status</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div> -->
                                <div class="col-md-3">
                                    <button class="btn btn-primary w-100" onclick="loadWaddens()">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Loading Indicator -->
                            <div id="loadingIndicator" class="text-center py-4" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading waddens...</p>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="waddensTable">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <?php if ($is_hq) { ?><th>Campus</th><?php } ?>
                                            <th>Assigned Hostels</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="waddensTableBody">
                                        <!-- Waddens will be loaded here via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <div id="pagination" class="d-flex justify-content-center mt-4">
                                <!-- Pagination links will be loaded here via AJAX -->
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
                                <input type="text" class="form-control" id="edit_campus_name" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Assign Hostels</label>
                                <div class="row" id="edit_hostels_container">
                                    <?php 
                                    mysqli_data_seek($hostels_result, 0);
                                    while ($hostel = mysqli_fetch_assoc($hostels_result)) { ?>
                                        <div class="col-md-4 mb-2 hostel-option" data-campus="<?php echo $hostel['campus_id']; ?>">
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
        // Debounce function to limit how often a function is called
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Load waddens with filters and pagination
        function loadWaddens(page = 1) {
            const searchText = $('#searchInput').val();
            const statusFilter = $('#statusFilter').val();
            const campusFilter = $('#campusFilter').val();
            
            // Show loading indicator
            $('#loadingIndicator').show();
            $('#waddensTableBody').html('');
            
            // Prepare request data
            const requestData = {
                search: searchText,
                status: statusFilter,
                campus: campusFilter,
                page: page
            };
            
            // Remove empty parameters
            Object.keys(requestData).forEach(key => {
                if (requestData[key] === '' || requestData[key] === '0' || requestData[key] === null) {
                    delete requestData[key];
                }
            });
            
            // Update URL with filter parameters
            const params = new URLSearchParams();
            if (searchText) params.set('search', searchText);
            if (statusFilter) params.set('status', statusFilter);
            if (campusFilter && campusFilter !== '0') params.set('campus', campusFilter);
            if (page > 1) params.set('page', page);
            
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.pushState({}, '', newUrl);
            
            // Clear any previous error messages
            $('#errorMessage').hide().empty();
            
            // Make AJAX request
            $.ajax({
                url: 'fetch_waddens.php',
                type: 'GET',
                data: requestData,
                dataType: 'json',
                beforeSend: function(xhr) {
                    // Add any custom headers or processing before sending
                    console.log('Fetching waddens with params:', {
                        search: searchText,
                        status: statusFilter,
                        campus: campusFilter,
                        page: page
                    });
                },
                success: function(response) {
                    $('#loadingIndicator').hide();
                    console.log('Received response:', response);
                    
                    if (!response) {
                        showError('Invalid response from server');
                        return;
                    }
                    
                    if (response.error) {
                        showError(response.error);
                        return;
                    }
                    
                    if (!response.success) {
                        showError(response.message || 'Failed to load waddens');
                        return;
                    }
                    
                    // Populate table rows
                    if (response.waddens.length > 0) {
                        response.waddens.forEach(function(wadden) {
                            const statusBadge = wadden.active 
                                ? '<span class="badge bg-success">Active</span>'
                                : '<span class="badge bg-danger">Inactive</span>';
                                
                            const campusCell = $('#is_hq').length 
                                ? `<td>${wadden.campus_name || ''}</td>` 
                                : '';
                                
                            const hostelsHtml = wadden.assigned_hostels 
                                ? `<div class="hostel-tags">${wadden.assigned_hostels.split(',').map(h => 
                                    `<span class="badge bg-info text-dark me-1 mb-1">${h.trim()}</span>`
                                ).join('')}</div>`
                                : '<span class="text-muted">No hostels assigned</span>';
                            
                            const row = `
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="${wadden.image || 'assets/img/profile-img.jpg'}" 
                                                 alt="Profile" class="rounded-circle me-2" width="40" height="40">
                                            <div>
                                                <strong>${wadden.names}</strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>${wadden.email}</td>
                                    <td>${wadden.phone || '-'}</td>
                                    ${campusCell}
                                    <td>${hostelsHtml}</td>
                                    <td>${statusBadge}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="editWadden(${wadden.id})">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm ${wadden.active ? 'btn-warning' : 'btn-success'}" 
                                                    onclick="toggleStatus(${wadden.id}, ${wadden.active ? 0 : 1})">
                                                <i class="bi bi-power"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(${wadden.id}, '${wadden.names.replace(/'/g, "\\'")}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                            $('#waddensTableBody').append(row);
                        });
                    } else {
                        $('#waddensTableBody').html(`
                            <tr>
                                <td colspan="${$('#is_hq').length ? '7' : '6'}" class="text-center">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> No waddens found.
                                    </div>
                                </td>
                            </tr>
                        `);
                    }
                    
                    // Update pagination
                    updatePagination(response.currentPage, response.totalPages);
                },
                error: function(xhr, status, error) {
                    $('#loadingIndicator').hide();
                    
                    let errorMessage = 'Error loading waddens';
                    
                    try {
                        // Try to parse the response as JSON
                        const response = JSON.parse(xhr.responseText);
                        if (response && response.error) {
                            errorMessage = response.error;
                        } else if (xhr.responseText && xhr.responseText.includes('<!DOCTYPE')) {
                            // If we got an HTML error page
                            errorMessage = 'Server returned an error page. Please check the server logs.';
                            console.error('HTML Error Page:', xhr.responseText);
                        }
                    } catch (e) {
                        // If we can't parse as JSON, use the raw response
                        if (xhr.responseText) {
                            errorMessage = xhr.responseText;
                        }
                    }
                    
                    showError(errorMessage);
                    console.error('AJAX Error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                }
            });
        }
        
        // Update pagination links
        function updatePagination(currentPage, totalPages) {
            const pagination = $('#pagination');
            pagination.empty();
            
            if (totalPages <= 1) return;
            
            const ul = $('<ul class="pagination"></ul>');
            
            // Previous button
            const prevLi = $(`<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" ${currentPage === 1 ? 'tabindex="-1"' : ''}>Previous</a>
            </li>`);
            
            if (currentPage > 1) {
                prevLi.find('a').on('click', function(e) {
                    e.preventDefault();
                    loadWaddens(currentPage - 1);
                });
            }
            ul.append(prevLi);
            
            // Page numbers
            const maxPages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxPages / 2));
            let endPage = startPage + maxPages - 1;
            
            if (endPage > totalPages) {
                endPage = totalPages;
                startPage = Math.max(1, endPage - maxPages + 1);
            }
            
            if (startPage > 1) {
                ul.append(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
                if (startPage > 2) {
                    ul.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const li = $(`<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`);
                
                if (i !== currentPage) {
                    li.find('a').on('click', function(e) {
                        e.preventDefault();
                        loadWaddens(i);
                    });
                }
                
                ul.append(li);
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    ul.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
                }
                ul.append(`<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`);
            }
            
            // Next button
            const nextLi = $(`<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" ${currentPage === totalPages ? 'tabindex="-1"' : ''}>Next</a>
            </li>`);
            
            if (currentPage < totalPages) {
                nextLi.find('a').on('click', function(e) {
                    e.preventDefault();
                    loadWaddens(currentPage + 1);
                });
            }
            ul.append(nextLi);
            
            pagination.append(ul);
        }
        
        // Function to show error messages
        function showError(message) {
            const errorDiv = $('#errorMessage');
            errorDiv.html(`
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `).show();
        }
        
        // Initialize on document ready
        $(document).ready(function() {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            // Load initial data
            loadWaddens();
            
            // Handle filter changes with debounce
            $('#searchInput').on('input', debounce(function() {
                loadWaddens(1);
            }, 500));
            
            $('#statusFilter, #campusFilter').on('change', function() {
                loadWaddens(1);
            });
            
            // Initialize filters from URL parameters if present
            const urlParams = new URLSearchParams(window.location.search);
            const campusFilter = urlParams.get('campus');
            if (campusFilter) {
                $('#campusFilter').val(campusFilter);
            }
            
            // Add hidden element to check if HQ user
            if ($('.card-title:contains("All Campuses")').length) {
                $('#waddensTable').before('<span id="is_hq" style="display: none;"></span>');
            }
        });
        
        // Toggle wadden status
        function toggleStatus(waddenId, newStatus) {
            if (confirm(`Are you sure you want to ${newStatus ? 'activate' : 'deactivate'} this wadden?`)) {
                $.ajax({
                    url: 'update_wadden_status.php',
                    type: 'POST',
                    data: {
                        wadden_id: waddenId,
                        status: newStatus
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Reload the current page to reflect changes
                            const currentPage = $('.pagination .active a').data('page') || 1;
                            loadWaddens(currentPage);
                        } else {
                            alert(response.message || 'Failed to update wadden status');
                        }
                    },
                    error: function() {
                        alert('Error updating wadden status');
                    }
                });
            }
        }
        
        // Confirm wadden deletion
        function confirmDelete(waddenId, waddenName) {
            if (confirm(`Are you sure you want to delete the wadden "${waddenName}"? This action cannot be undone.`)) {
                $.ajax({
                    url: 'delete_wadden.php',
                    type: 'POST',
                    data: { wadden_id: waddenId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Reload the current page to reflect changes
                            const currentPage = $('.pagination .active a').data('page') || 1;
                            loadWaddens(currentPage);
                        } else {
                            alert(response.message || 'Failed to delete wadden');
                        }
                    },
                    error: function() {
                        alert('Error deleting wadden');
                    }
                });
            }
        }

        // Edit wadden functionality
        function editWadden(waddenId) {
            // Show loading state
            $('#editWaddenModal').modal('show');
            $('#editWaddenModal .modal-content').append('<div class="modal-loading"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            
            // Reset form and hide all hostels initially
            $('.hostel-option').hide();
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
                        $('#edit_campus_name').val(wadden.campus_name);
                        
                        // Show only hostels for wadden's campus
                        $('.hostel-option[data-campus="' + wadden.campus + '"]').show();
                        
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
                        
                        // Show modal
                        $('#editWaddenModal').modal('show');
                    } else {
                        alert('Error loading wadden data: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading wadden data. Please try again.');
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