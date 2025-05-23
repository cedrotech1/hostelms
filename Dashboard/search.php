<?php
include 'connection.php';
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

        .filter-group {
            margin-bottom: 15px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading-spinner {
            width: 3rem;
            height: 3rem;
        }

        .export-btn {
            margin-left: 10px;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        .status-pending {
            background-color: #ffc107;
            color: #000;
        }

        .status-paid {
            background-color: #28a745;
            color: #fff;
        }

        .status-slep {
            background-color: #0d6efd;
            color: #fff;
        }

        .search-type-tabs {
            margin-bottom: 20px;
        }

        .search-type-tabs .nav-link {
            color: #495057;
        }

        .search-type-tabs .nav-link.active {
            font-weight: bold;
            color: #0d6efd;
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
            <h1>Advanced Search</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">Search</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <!-- Search Type Tabs -->
                    <ul class="nav nav-tabs search-type-tabs" id="searchTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="student-tab" data-bs-toggle="tab" href="#student" role="tab">
                                Student Search
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="hostel-tab" data-bs-toggle="tab" href="#hostel" role="tab">
                                Hostel Search
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="room-tab" data-bs-toggle="tab" href="#room" role="tab">
                                Room Search
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="hostel-members-tab" data-bs-toggle="tab" href="#hostel-members" role="tab">
                                Hostel Members
                            </a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="searchTabsContent">
                        <!-- Student Search Tab -->
                        <div class="tab-pane fade show active" id="student" role="tabpanel">
                            <div class="search-container">
                                <form id="studentSearchForm">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="regNumber">Registration Number</label>
                                                <input type="text" class="form-control" id="regNumber" name="regNumber">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="studentName">Student Name</label>
                                                <input type="text" class="form-control" id="studentName" name="studentName">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="studentCampus">Campus</label>
                                                <select class="form-control" id="studentCampus" name="studentCampus">
                                                    <option value="">All Campuses</option>
                                                    <?php
                                                    $query = "SELECT DISTINCT campus FROM info ORDER BY campus";
                                                    $result = $connection->query($query);
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<option value='" . htmlspecialchars($row['campus']) . "'>" . htmlspecialchars($row['campus']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="applicationStatus">Application Status</label>
                                                <select class="form-control" id="applicationStatus" name="applicationStatus">
                                                    <option value="">All Status</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="paid">Paid</option>
                                                    <!-- <option value="slep">SLEP</option> -->
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="gender">Gender</label>
                                                <select class="form-control" id="gender" name="gender">
                                                    <option value="">All</option>
                                                    <option value="male">Male</option>
                                                    <option value="female">Female</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="yearOfStudy">Year of Study</label>
                                                <select class="form-control" id="yearOfStudy" name="yearOfStudy">
                                                    <option value="">All Years</option>
                                                    <?php for($i = 1; $i <= 6; $i++): ?>
                                                        <option value="<?php echo $i; ?>">Year <?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">Search</button>
                                            <button type="button" class="btn btn-success export-btn" id="exportStudentResults">
                                                <i class="bi bi-file-excel"></i> Export to Excel
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div id="studentResultsTitle" class="mb-2"></div>
                            <div id="studentResults" class="result-container">
                                <!-- Results will be loaded here -->
                            </div>
                        </div>

                        <!-- Hostel Search Tab -->
                        <div class="tab-pane fade" id="hostel" role="tabpanel">
                            <div class="search-container">
                                <form id="hostelSearchForm">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="hostelName">Hostel Name</label>
                                                <input type="text" class="form-control" id="hostelName" name="hostelName">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="hostelCampus">Campus</label>
                                                <select class="form-control" id="hostelCampus" name="hostelCampus">
                                                    <option value="">All Campuses</option>
                                                    <?php
                                                    $query = "SELECT DISTINCT campus FROM info ORDER BY campus";
                                                    $result = $connection->query($query);
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<option value='" . htmlspecialchars($row['campus']) . "'>" . htmlspecialchars($row['campus']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="roomStatus">Room Status</label>
                                                <select class="form-control" id="roomStatus" name="roomStatus">
                                                    <option value="">All Status</option>
                                                    <option value="available">Available</option>
                                                    <option value="occupied">Occupied</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">Search</button>
                                            <button type="button" class="btn btn-success export-btn" id="exportHostelResults">
                                                <i class="bi bi-file-excel"></i> Export to Excel
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div id="hostelResultsTitle" class="mb-2"></div>
                            <div id="hostelResults" class="result-container">
                                <!-- Results will be loaded here -->
                            </div>
                        </div>

                        <!-- Room Search Tab -->
                        <div class="tab-pane fade" id="room" role="tabpanel">
                            <div class="search-container">
                                <form id="roomSearchForm">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="roomNumber">Room Number</label>
                                                <input type="text" class="form-control" id="roomNumber" name="roomNumber">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="roomHostel">Hostel</label>
                                                <select class="form-control" id="roomHostel" name="roomHostel">
                                                    <option value="">All Hostels</option>
                                                    <?php
                                                    $query = "SELECT DISTINCT name FROM hostels ORDER BY name";
                                                    $result = $connection->query($query);
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<option value='" . htmlspecialchars($row['name']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="roomCapacity">Capacity</label>
                                                <select class="form-control" id="roomCapacity" name="roomCapacity">
                                                    <option value="">All</option>
                                                    <?php for($i = 1; $i <= 4; $i++): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> Beds</option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="roomAvailability">Availability</label>
                                                <select class="form-control" id="roomAvailability" name="roomAvailability">
                                                    <option value="">All</option>
                                                    <option value="available">Available</option>
                                                    <option value="full">Full</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">Search</button>
                                            <button type="button" class="btn btn-success export-btn" id="exportRoomResults">
                                                <i class="bi bi-file-excel"></i> Export to Excel
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div id="roomResultsTitle" class="mb-2"></div>
                            <div id="roomResults" class="result-container">
                                <!-- Results will be loaded here -->
                            </div>
                        </div>

                        <!-- Hostel Members Tab -->
                        <div class="tab-pane fade" id="hostel-members" role="tabpanel">
                            <div class="search-container">
                                <form id="hostelMembersSearchForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="membersCampus">Campus</label>
                                                <select class="form-control" id="membersCampus" name="campus">
                                                    <option value="">All Campuses</option>
                                                    <?php
                                                    $query = "SELECT DISTINCT campus FROM info ORDER BY campus";
                                                    $result = $connection->query($query);
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<option value='" . htmlspecialchars($row['campus']) . "'>" . htmlspecialchars($row['campus']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="membersHostel">Hostel</label>
                                                <select class="form-control" id="membersHostel" name="hostel">
                                                    <option value="">All Hostels</option>
                                                    <?php
                                                    $query = "SELECT DISTINCT name FROM hostels ORDER BY name";
                                                    $result = $connection->query($query);
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<option value='" . htmlspecialchars($row['name']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">Search</button>
                                            <button type="button" class="btn btn-success export-btn" id="exportHostelMembersResults">
                                                <i class="bi bi-file-excel"></i> Export to Excel
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div id="hostelMembersResultsTitle" class="mb-2"></div>
                            <div id="hostelMembersResults" class="result-container">
                                <!-- Results will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Loading Spinner -->
    <div class="loading" id="loadingSpinner">
        <div class="spinner-border loading-spinner text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
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

    <!-- Add SheetJS library before closing body tag -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>

    <!-- Custom Search JS -->
    <script>
    $(document).ready(function () {
        // Function to build a summary of search fields
        function buildSearchSummary(formId) {
            const form = $(formId)[0];
            let summary = [];
            $(form).serializeArray().forEach(field => {
                if (field.value && field.value !== '') {
                    let label = $(form).find(`[name='${field.name}']`).closest('.form-group').find('label').text().replace(':', '');
                    summary.push(label + ': ' + field.value);
                }
            });
            return summary.length ? 'Results for ' + summary.join(', ') : 'All Results';
        }

        // Function to handle search form submission
        function handleSearch(formId, searchType) {
            $(formId).on('submit', function (e) {
                e.preventDefault();
                
                // Show loading state
                $('#' + searchType + 'Results').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                $('#' + searchType + 'ResultsTitle').html(''); // Clear previous title

                // Get form data
                var formData = $(this).serialize();

                // Make AJAX request
                $.ajax({
                    url: 'search_' + searchType + 's.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            // Build and show dynamic title
                            const summary = buildSearchSummary(formId);
                            $('#' + searchType + 'ResultsTitle').html('<h5>' + summary + '</h5>');
                            $('#' + searchType + 'Results').html(response.html);
                            window[searchType + 'Data'] = response.data;
                        } else {
                            $('#' + searchType + 'Results').html('<div class="alert alert-danger">Error: ' + (response.error || 'An unknown error occurred') + '</div>');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX Error:', { xhr: xhr, status: status, error: error });
                        var errorMessage = 'An error occurred while processing your request.';
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.error) {
                                errorMessage = response.error;
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                        }
                        $('#' + searchType + 'Results').html('<div class="alert alert-danger">' + errorMessage + '</div>');
                    }
                });
            });
        }

        // Initialize search handlers
        handleSearch('#studentSearchForm', 'student');
        handleSearch('#hostelSearchForm', 'hostel');
        handleSearch('#roomSearchForm', 'room');

        // Unified Excel export function for all panels
        function exportToExcel(type) {
            const data = window[type + 'Data'];
            if (!data || data.length === 0) {
                alert('No data to export. Please perform a search first.');
                return;
            }

            let keys = [], headers = [], filename = '';
            switch(type) {
                case 'student':
                    keys = ['regnumber', 'names', 'campus', 'college', 'school', 'program', 'yearofstudy', 'gender', 'email', 'phone', 'application_status', 'room_code', 'hostel_name', 'application_date'];
                    headers = ['Reg Number', 'Name', 'Campus', 'College', 'School', 'Program', 'Year', 'Gender', 'Email', 'Phone', 'Application Status', 'Room', 'Hostel', 'Application Date'];
                    filename = 'student_search_results.xlsx';
                    break;
                case 'hostel':
                    keys = ['name', 'campus_name', 'total_rooms', 'total_beds', 'available_beds', 'occupancy_rate', 'total_applications', 'pending_applications', 'paid_applications', 'slep_applications'];
                    headers = ['Hostel Name', 'Campus', 'Total Rooms', 'Total Beds', 'Available Beds', 'Occupancy Rate', 'Total Applications', 'Pending', 'Paid', 'SLEP'];
                    filename = 'hostel_search_results.xlsx';
                    break;
                case 'room':
                    keys = ['room_code', 'hostel_name', 'campus_name', 'capacity', 'available_beds', 'occupancy_rate', 'total_applications', 'pending_applications', 'paid_applications', 'occupants', 'status'];
                    headers = ['Room Code', 'Hostel', 'Campus', 'Capacity', 'Available Beds', 'Occupancy Rate', 'Total Applications', 'Pending', 'Paid', 'Occupants', 'Status'];
                    filename = 'room_search_results.xlsx';
                    break;
                case 'hostelMembers':
                    keys = ['hostel_name', 'room_code', 'applicant_name', 'regnumber'];
                    headers = ['Hostel', 'Room', 'Applicant Name', 'Reg Number'];
                    filename = 'hostel_members_results.xlsx';
                    break;
            }

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
            XLSX.utils.book_append_sheet(wb, ws, "Results");
            
            // Write file
            XLSX.writeFile(wb, filename);
        }

        // Bind export buttons to unified export function
        $('#exportStudentResults').on('click', function() { exportToExcel('student'); });
        $('#exportHostelResults').on('click', function() { exportToExcel('hostel'); });
        $('#exportRoomResults').on('click', function() { exportToExcel('room'); });
        $('#exportHostelMembersResults').on('click', function() { exportToExcel('hostelMembers'); });

        // Hostel Members search handler
        $('#hostelMembersSearchForm').on('submit', function(e) {
            e.preventDefault();
            $('#hostelMembersResults').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            $('#hostelMembersResultsTitle').html('');
            var formData = $(this).serialize();
            $.ajax({
                url: 'search_hostel_members.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const summary = buildSearchSummary('#hostelMembersSearchForm');
                        $('#hostelMembersResultsTitle').html('<h5>' + summary + '</h5>');
                        $('#hostelMembersResults').html(response.html);
                        window['hostelMembersData'] = response.data;
                    } else {
                        $('#hostelMembersResults').html('<div class="alert alert-danger">Error: ' + (response.error || 'An unknown error occurred') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#hostelMembersResults').html('<div class="alert alert-danger">Error: ' + error + '</div>');
                }
            });
        });
    });
</script>

</body>
</html> 