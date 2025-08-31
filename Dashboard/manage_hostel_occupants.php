<?php
include 'connection.php';

// Get wadden's assigned hostels
$wadden_id = $_SESSION['id'];

// Check if user is a wadden
$check_role = "SELECT role FROM users WHERE id = ?";
$stmt = $connection->prepare($check_role);
$stmt->bind_param("i", $wadden_id);
$stmt->execute();
$role_result = $stmt->get_result();
$user_role = $role_result->fetch_assoc()['role'];

if ($user_role !== 'wadden') {
    echo "<script>alert('Access denied. Only waddens can access this page.'); window.location.href='index.php';</script>";
    exit();
}

// Get wadden's assigned hostels
$hostels_query = "SELECT h.id, h.name, h.building_code, h.gender, h.year, h.college, h.school
                  FROM hostels h
                  JOIN wadden_hostels wh ON h.id = wh.hostel_id
                  WHERE wh.wadden_id = ?
                  ORDER BY h.name";
$stmt = $connection->prepare($hostels_query);
$stmt->bind_param("i", $wadden_id);
$stmt->execute();
$assigned_hostels = $stmt->get_result();

// Get campus name for display
$campus_query = "SELECT c.name FROM campuses c 
                 JOIN users u ON c.id = u.campus 
                 WHERE u.id = ?";
$stmt = $connection->prepare($campus_query);
$stmt->bind_param("i", $wadden_id);
$stmt->execute();
$campus_result = $stmt->get_result();
$campus_name = $campus_result->fetch_assoc()['name'];

// Handle search/filter
$selected_hostel = isset($_GET['hostel']) ? $_GET['hostel'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$room_filter = isset($_GET['room']) ? $_GET['room'] : '';
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
        .search-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .result-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .hostel-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }

        .hostel-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .hostel-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            border-radius: 8px 8px 0 0;
        }

        .hostel-body {
            padding: 15px;
        }

        .occupant-row {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }

        .occupant-row:last-child {
            border-bottom: none;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75em;
        }

        .status-approved {
            background-color: #28a745;
            color: #fff;
        }

        .status-pending {
            background-color: #ffc107;
            color: #000;
        }

        .status-paid {
            background-color: #17a2b8;
            color: #fff;
        }

        .campus-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
        }

        .export-btn {
            margin-left: 10px;
        }

        .hostel-stat-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }

        .hostel-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .stat-item {
            padding: 5px;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
        }

        .hostel-stat-card .card-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .hostel-stat-card hr {
            margin: 10px 0;
            opacity: 0.3;
        }

        .status-full {
            background-color: #dc3545;
            color: #fff;
        }

        .status-empty {
            background-color: #ffc107;
            color: #000;
        }

        .status-partial {
            background-color: #17a2b8;
            color: #fff;
        }

        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd;
            font-weight: 600;
        }

        .tab-content {
            padding-top: 20px;
        }
    </style>
  
    <title>UR-HOSTELS - Manage Hostel Occupants</title>
</head>

<body>
    <?php
    include("./includes/header.php");
    include("./includes/menu.php");
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Manage Hostel Occupants</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">Hostel Occupants</li>
                </ol>
            </nav>
        </div>

        <!-- Campus Info -->
        <div class="campus-info">
            <h6><i class="bi bi-geo-alt"></i> Managing Hostel Occupants for Campus: <strong><?php echo $campus_name; ?></strong></h6>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Total Hostels</h6>
                            <div class="stats-number"><?php echo $assigned_hostels->num_rows; ?></div>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-building" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Total Occupants</h6>
                            <div class="stats-number" id="totalOccupants">-</div>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-people" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Available Beds</h6>
                            <div class="stats-number" id="availableBeds">-</div>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-bed" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Occupancy Rate</h6>
                            <div class="stats-number" id="occupancyRate">-</div>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-percent" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hostel Statistics Cards -->
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Hostel Statistics</h5>
                            <div id="hostelStatsContainer" class="row">
                                <!-- Hostel stats will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Search and Filter Section -->
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="search-container">
                        <form method="GET" id="occupantsSearchForm">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="hostel">Hostel</label>
                                        <select class="form-control" id="hostel" name="hostel">
                                            <!-- <option value="">All Hostels</option> -->
                                            <?php 
                                            mysqli_data_seek($assigned_hostels, 0);
                                            while ($hostel = $assigned_hostels->fetch_assoc()) { ?>
                                                <option value="<?php echo $hostel['id']; ?>" <?php echo ($selected_hostel == $hostel['id']) ? 'selected' : ''; ?>>
                                                    <?php echo $hostel['name']; ?> (<?php echo $hostel['building_code']; ?>)
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="room">Room Code</label>
                                        <input type="text" class="form-control" id="room" name="room" value="<?php echo htmlspecialchars($room_filter); ?>" placeholder="e.g., A101">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="room_status">Room Status</label>
                                        <select class="form-control" id="room_status" name="room_status">
                                            <option value="">All Rooms</option>
                                            <option value="full">Full Rooms</option>
                                            <option value="empty">Empty Rooms</option>
                                            <option value="partial">Partially Occupied</option>
                                            <option value="has_occupants">Has Occupants</option>
                                            <option value="no_occupants">No Occupants</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="search">Search Student</label>
                                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Name or Reg Number">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-search"></i> Search
                                            </button>
                                            <button type="button" class="btn btn-success export-btn" id="exportResults">
                                                <i class="bi bi-file-excel"></i> Export
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tabs Section -->
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Tabs -->
                            <ul class="nav nav-tabs nav-tabs-bordered" id="mainTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="occupants-tab" data-bs-toggle="tab" data-bs-target="#occupants-panel" type="button" role="tab" aria-controls="occupants-panel" aria-selected="true">
                                        <i class="bi bi-people"></i> Occupants
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="rooms-tab" data-bs-toggle="tab" data-bs-target="#rooms-panel" type="button" role="tab" aria-controls="rooms-panel" aria-selected="false">
                                        <i class="bi bi-door-open"></i> Rooms
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content pt-2" id="mainTabsContent">
                                <!-- Occupants Panel -->
                                <div class="tab-pane fade show active" id="occupants-panel" role="tabpanel" aria-labelledby="occupants-tab">
                                    <div id="occupantsResults">
                                        <!-- Occupants results will be loaded here -->
                                        <div class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2">Loading hostel occupants...</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rooms Panel -->
                                <div class="tab-pane fade" id="rooms-panel" role="tabpanel" aria-labelledby="rooms-tab">
                                    <div id="roomsResults">
                                        <!-- Rooms results will be loaded here -->
                                        <div class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2">Loading rooms...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Loading Spinner -->
    <!-- <div class="loading" id="loadingSpinner">
        <div class="spinner-border loading-spinner text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div> -->

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

    <!-- Add SheetJS library -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        $(document).ready(function() {
            // Load initial data
            loadOccupants();

            // Handle form submission
            $('#occupantsSearchForm').on('submit', function(e) {
                e.preventDefault();
                if ($('#occupants-tab').hasClass('active')) {
                    loadOccupants();
                } else {
                    loadRooms();
                }
            });

            // Handle tab switching
            $('#rooms-tab').on('click', function() {
                loadRooms();
            });

            $('#occupants-tab').on('click', function() {
                loadOccupants();
            });

            // Handle export
            $('#exportResults').on('click', function() {
                if ($('#occupants-tab').hasClass('active')) {
                    exportOccupantsToExcel();
                } else {
                    exportRoomsToExcel();
                }
            });

            function loadOccupants() {
                $('#occupantsResults').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

                var formData = $('#occupantsSearchForm').serialize();

                $.ajax({
                    url: 'get_wadden_occupants.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#occupantsResults').html(response.html);
                            
                            // Update statistics
                            if (response.stats) {
                                $('#totalOccupants').text(response.stats.total_occupants || 0);
                                $('#availableBeds').text(response.stats.available_beds || 0);
                                $('#occupancyRate').text((response.stats.occupancy_rate || 0) + '%');
                            }
                            
                            // Update hostel statistics
                            if (response.hostel_stats) {
                                displayHostelStats(response.hostel_stats);
                            }
                            
                            // Store data for export
                            window.occupantsData = response.data;
                        } else {
                            $('#occupantsResults').html('<div class="alert alert-danger">Error: ' + (response.error || 'An unknown error occurred') + '</div>');
                        }
                    },
                    error: function() {
                        $('#occupantsResults').html('<div class="alert alert-danger">Error loading data. Please try again.</div>');
                    }
                });
            }

            function loadRooms() {
                $('#roomsResults').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

                var formData = $('#occupantsSearchForm').serialize();

                $.ajax({
                    url: 'get_wadden_rooms.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#roomsResults').html(response.html);
                            
                            // Store data for export
                            window.roomsData = response.data;
                        } else {
                            $('#roomsResults').html('<div class="alert alert-danger">Error: ' + (response.error || 'An unknown error occurred') + '</div>');
                        }
                    },
                    error: function() {
                        $('#roomsResults').html('<div class="alert alert-danger">Error loading data. Please try again.</div>');
                    }
                });
            }

            function displayHostelStats(hostelStats) {
                var html = '';
                
                if (hostelStats.length === 0) {
                    html = '<div class="col-12"><div class="alert alert-info">No hostel statistics available.</div></div>';
                } else {
                    hostelStats.forEach(function(hostel) {
                        var occupancyColor = 'success';
                        if (hostel.occupancy_rate < 50) {
                            occupancyColor = 'warning';
                        } else if (hostel.occupancy_rate > 90) {
                            occupancyColor = 'danger';
                        }
                        
                        html += '<div class="col-md-6 col-lg-4 mb-3">';
                        html += '<div class="card hostel-stat-card">';
                        html += '<div class="card-body">';
                        html += '<h6 class="card-title"><i class="bi bi-building"></i> ' + hostel.hostel_name + '</h6>';
                        html += '<p class="text-muted small">(' + hostel.building_code + ')</p>';
                        
                        html += '<div class="row text-center">';
                        html += '<div class="col-4">';
                        html += '<div class="stat-item">';
                        html += '<div class="stat-number">' + hostel.total_rooms + '</div>';
                        html += '<div class="stat-label">Rooms</div>';
                        html += '</div>';
                        html += '</div>';
                        
                        html += '<div class="col-4">';
                        html += '<div class="stat-item">';
                        html += '<div class="stat-number">' + hostel.total_beds + '</div>';
                        html += '<div class="stat-label">Beds</div>';
                        html += '</div>';
                        html += '</div>';
                        
                        html += '<div class="col-4">';
                        html += '<div class="stat-item">';
                        html += '<div class="stat-number text-' + occupancyColor + '">' + hostel.occupancy_rate + '%</div>';
                        html += '<div class="stat-label">Occupancy</div>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                        
                        html += '<hr>';
                        
                        html += '<div class="row text-center">';
                        html += '<div class="col-4">';
                        html += '<div class="stat-item">';
                        html += '<div class="stat-number text-success">' + hostel.occupied_beds + '</div>';
                        html += '<div class="stat-label small">Occupied</div>';
                        html += '</div>';
                        html += '</div>';
                        
                        html += '<div class="col-4">';
                        html += '<div class="stat-item">';
                        html += '<div class="stat-number text-primary">' + hostel.available_beds + '</div>';
                        html += '<div class="stat-label small">Available</div>';
                        html += '</div>';
                        html += '</div>';
                        
                        html += '<div class="col-4">';
                        html += '<div class="stat-item">';
                        html += '<div class="stat-number text-info">' + hostel.partial_rooms + '</div>';
                        html += '<div class="stat-label small">Partial</div>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                        
                        html += '<hr>';
                        
                        html += '<div class="row text-center">';
                        html += '<div class="col-6">';
                        html += '<div class="stat-item">';
                        html += '<div class="stat-number text-danger">' + hostel.full_rooms + '</div>';
                        html += '<div class="stat-label small">Full Rooms</div>';
                        html += '</div>';
                        html += '</div>';
                        
                        html += '<div class="col-6">';
                        html += '<div class="stat-item">';
                        html += '<div class="stat-number text-warning">' + hostel.empty_rooms + '</div>';
                        html += '<div class="stat-label small">Empty Rooms</div>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                        
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                    });
                }
                
                $('#hostelStatsContainer').html(html);
            }

            function exportOccupantsToExcel() {
                const data = window.occupantsData;
                if (!data || data.length === 0) {
                    alert('No data to export. Please perform a search first.');
                    return;
                }

                const keys = ['regnumber', 'names', 'hostel_name', 'room_code', 'campus', 'college', 'school', 'yearofstudy', 'phone', 'gender', 'application_status'];
                const headers = ['Reg Number', 'Name', 'Hostel', 'Room', 'Campus', 'College', 'School', 'Year', 'Phone', 'Gender', 'Status'];

                // Create worksheet with headers
                const ws = XLSX.utils.aoa_to_sheet([headers]);
                
                // Add data rows
                const dataRows = data.map(row => {
                    return keys.map(key => row[key] || '');
                });
                
                // Add data starting from row 1 (after headers)
                XLSX.utils.sheet_add_aoa(ws, dataRows, { origin: 1 });

                // Create workbook and add worksheet
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Hostel Occupants');

                // Generate filename with timestamp
                const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
                const filename = `hostel_occupants_${timestamp}.xlsx`;

                // Save the file
                XLSX.writeFile(wb, filename);
            }

            function exportRoomsToExcel() {
                const data = window.roomsData;
                if (!data || data.length === 0) {
                    alert('No data to export. Please perform a search first.');
                    return;
                }

                const keys = ['hostel_name', 'building_code', 'room_code', 'number_of_beds', 'occupied_beds', 'remain', 'occupancy_rate', 'occupant_count', 'occupant_names'];
                const headers = ['Hostel', 'Building Code', 'Room Code', 'Capacity', 'Occupied', 'Available', 'Occupancy Rate', 'Occupant Count', 'Occupant Names'];

                // Create worksheet with headers
                const ws = XLSX.utils.aoa_to_sheet([headers]);
                
                // Add data rows
                const dataRows = data.map(row => {
                    return keys.map(key => row[key] || '');
                });
                
                // Add data starting from row 1 (after headers)
                XLSX.utils.sheet_add_aoa(ws, dataRows, { origin: 1 });

                // Create workbook and add worksheet
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Hostel Rooms');

                // Generate filename with timestamp
                const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
                const filename = `hostel_rooms_${timestamp}.xlsx`;

                // Save the file
                XLSX.writeFile(wb, filename);
            }
        });
    </script>

</body>
</html> 