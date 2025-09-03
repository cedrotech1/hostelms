<?php
include('connection.php');
// session_start();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $status = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
        $slep = mysqli_real_escape_string($connection, $_POST['slep'] ?? '');
        
        // Start transaction
        mysqli_begin_transaction($connection);
        
        try {
            // Update application status
            $query = "UPDATE applications SET status = '$status', slep = '$slep', updated_at = NOW() WHERE id = $id";
            if (!mysqli_query($connection, $query)) {
                throw new Exception('Failed to update application');
            }
            
            // If status is approved, update room's remaining beds
            if ($status === 'approved') {
                $query = "UPDATE rooms r
                          JOIN applications a ON a.room_id = r.id
                          SET r.remain = r.remain - 1
                          WHERE a.id = $id";
                
                if (!mysqli_query($connection, $query)) {
                    throw new Exception('Failed to update room beds');
                }
            }
            
            // If status is rejected and was previously approved, increment remaining beds
            if ($status === 'rejected') {
                $query = "UPDATE rooms r
                          JOIN applications a ON a.room_id = r.id
                          SET r.remain = r.remain + 1
                          WHERE a.id = $id AND a.status = 'approved'";
                
                if (!mysqli_query($connection, $query)) {
                    throw new Exception('Failed to update room beds');
                }
            }
            
            mysqli_commit($connection);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
            exit;
        } catch (Exception $e) {
            mysqli_rollback($connection);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>UR-HOSTELS</title>
    
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
    
    <style>
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-approved { background-color: #28a745; color: #fff; }
        .status-rejected { background-color: #dc3545; color: #fff; }
        
        /* Add table styles */
        .table {
            width: 100%;
            table-layout: fixed;
        }
        
        .table th, .table td {
            padding: 0.75rem;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .table th a {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Responsive table */
        @media (max-width: 992px) {
            .table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
        
        .slep-image {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .action-buttons {
            display: none; /* Hide action buttons in table */
        }
        
        .btn-approve {
            background-color: #28a745;
            color: white;
            padding: 0.5rem 1.5rem;
        }
        
        .btn-reject {
            background-color: #dc3545;
            color: white;
            padding: 0.5rem 1.5rem;
        }
        
        .btn-approve:hover {
            background-color: #218838;
            color: white;
        }
        
        .btn-reject:hover {
            background-color: #c82333;
            color: white;
        }
        
        .modal-fullscreen {
            max-width: 85%;
            margin: 0 auto;
        }
        
        .slep-modal .modal-body {
            padding: 2rem;
            text-align: center;
        }
        .status-paid{
            background-color: #28a745;
            color: #fff;
        }
        
        .slep-modal .modal-content {
            background-color: #f8f9fa;
        }
        
        .slep-modal .modal-header {
            background-color: #fff;
            border-bottom: 2px solid #dee2e6;
        }
        
        .slep-modal .modal-footer {
            background-color: #fff;
            border-top: 2px solid #dee2e6;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .card-header {
            padding: 0.75rem 1rem;
        }
        .table th {
            width: 40%;
            background-color: #f8f9fa;
        }
        #receipt-container {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
        }
        #detail-receipt {
            cursor: pointer;
            transition: transform 0.2s;
        }
        #detail-receipt:hover {
            transform: scale(1.02);
        }
        .receipt-section {
            height: 80vh;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .receipt-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        #receipt-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            overflow: hidden;
        }
        #detail-receipt {
            max-height: 75vh;
            max-width: 100%;
            object-fit: contain;
        }
        .receipt-actions {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.5rem;
        }
        .details-section {
            padding: 2rem;
            background-color: white;
        }
        .btn-view-proof {
            background-color: #0d6efd;
            color: white;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .btn-view-proof:hover {
            background-color: #0b5ed7;
            color: white;
        }
        
        /* Filter buttons styles */
        .filter-btn {
            min-width: 100px;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }
        
        .filter-btn.active {
            color: #fff !important;
        }
        
        .filter-btn[data-status=""]:not(.active) {
            border-color: #6c757d;
            color: #6c757d;
        }
        
        .filter-btn[data-status=""]:hover,
        .filter-btn[data-status=""].active {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        
        .filter-btn[data-status="pending"]:not(.active) {
            border-color: #ffc107;
            color: #ffc107;
        }
        
        .filter-btn[data-status="pending"]:hover,
        .filter-btn[data-status="pending"].active {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }
        
        .filter-btn[data-status="approved"]:not(.active) {
            border-color: #198754;
            color: #198754;
        }
        
        .filter-btn[data-status="approved"]:hover,
        .filter-btn[data-status="approved"].active {
            background-color: #198754;
            border-color: #198754;
        }
        
        .filter-btn[data-status="paid"]:not(.active) {
            border-color: #0dcaf0;
            color: #0dcaf0;
        }
        
        .filter-btn[data-status="paid"]:hover,
        .filter-btn[data-status="paid"].active {
            background-color: #0dcaf0;
            border-color: #0dcaf0;
        }
        
        .btn-group {
            border-radius: 0.375rem;
            overflow: hidden;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
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
            <h1>Manage Applications</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">Applications</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Application List</h5>
                                    <div class="d-flex gap-2">
                                        <input type="text" id="searchInput" class="form-control" placeholder="Search applicants" style="width:100%;">
                                        <select id="statusFilter" class="form-select">
                                            <option value="">All Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="paid">Paid</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <div class="btn-group" role="group" aria-label="Status filters">
                                        <button type="button" class="btn btn-outline-secondary filter-btn active" data-status="">All</button>
                                        <button type="button" class="btn btn-outline-warning filter-btn" data-status="pending">Pending Payment</button> 
                                      
                                        <button type="button" class="btn btn-outline-info filter-btn" data-status="paid">Paid Applications</button>
                                        <button type="button" class="btn btn-outline-success filter-btn" data-status="approved">Approved Applications</button>
                                      
                                    </div>
                                </div>
                            </div>
                            <br>

                            <div id="applicationsList">
                                <!-- Applications will be loaded here -->
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div id="paginationInfo"></div>
                                <div id="paginationControls" class="d-flex gap-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Application Details Modal -->
    <div class="modal fade" id="applicationDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <!-- Receipt Section (will be shown/hidden based on receipt availability) -->
                    <div id="receipt-section" class="receipt-section" style="display: none;">
                        <div class="receipt-container">
                            <div id="receipt-container" class="text-center">
                                <img id="detail-receipt" class="img-fluid" src="" alt="Receipt Document">
                            </div>
                            <div class="receipt-actions">
                                <button id="viewFullScreenBtn" class="btn btn-primary">
                                    <i class="bi bi-arrows-fullscreen"></i> View Full Screen
                                </button>
                                <button id="downloadReceiptBtn" class="btn btn-secondary">
                                    <i class="bi bi-download"></i> Download
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Details Section -->
                    <div class="details-section">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Student Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <tr><th>Registration Number:</th><td id="detail-regnumber"></td></tr>
                                            <tr><th>Name:</th><td id="detail-name"></td></tr>
                                            <tr><th>Gender:</th><td id="detail-gender"></td></tr>
                                            <tr><th>Year of Study:</th><td id="detail-year"></td></tr>
                                            <tr><th>Email:</th><td id="detail-email"></td></tr>
                                            <tr><th>Phone:</th><td id="detail-phone"></td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Academic Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <tr><th>Campus:</th><td id="detail-campus"></td></tr>
                                            <tr><th>College:</th><td id="detail-college"></td></tr>
                                            <tr><th>School:</th><td id="detail-school"></td></tr>
                                            <tr><th>Program:</th><td id="detail-program"></td></tr>
                                        </table>
                                    </div>
                                </div>
                                <div class="card mt-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Room Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <tr><th>Room Code:</th><td id="detail-room"></td></tr>
                                            <tr><th>Hostel:</th><td id="detail-hostel"></td></tr>
                                            <tr><th>Status:</th><td id="detail-status"></td></tr>
                                            <tr><th>Applied Date:</th><td id="detail-date"></td></tr>
                                            <tr><th>Receipt Number:</th><td id="detail-receipt-number"></td></tr>
                                            <tr><th>Date of Payment:</th><td id="detail-payment-date"></td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <div id="action-buttons" class="d-flex gap-2">
                        <button type="button" class="btn btn-approve" onclick="updateApplicationStatus(currentApplicationId, 'approve')">
                            <i class="bi bi-check-circle"></i> Approve
                        </button>
                        <button type="button" class="btn btn-reject" onclick="updateApplicationStatus(currentApplicationId, 'reject')">
                            <i class="bi bi-x-circle"></i> Reject
                        </button>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt PDF Modal -->
    <div class="modal fade slep-modal" id="slepModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Receipt Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="d-flex gap-2 mb-2 p-3 border-bottom">
                        <button id="viewReceiptButton" class="btn btn-sm btn-primary">
                            <i class="bi bi-arrows-fullscreen"></i> Full Screen
                        </button>
                        <button id="downloadReceiptButton" class="btn btn-sm btn-secondary">
                            <i class="bi bi-download"></i> Download
                        </button>
                    </div>
                    <div class="ratio ratio-16x9">
                        <iframe id="pdfViewer" class="w-100" style="height: 70vh; border: none;"></iframe>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include("./includes/footer.php"); ?>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
    
    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>

    <script>
        // Define sortApplications in the global scope
        window.sortApplications = function(column) {
            if (currentSortBy === column) {
                // Toggle sort order if clicking the same column
                currentSortOrder = currentSortOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                // Set new column and default to descending order
                currentSortBy = column;
                currentSortOrder = 'DESC';
            }
            currentPage = 1; // Reset to first page when sorting
            loadApplications();
        };

        let currentPage = 1;
        let currentSearch = '';
        let currentStatus = '';
        let currentSortBy = 'updated_at';
        let currentSortOrder = 'ASC';
        let perPage = 10;
        let currentApplicationId = null;
        let updateInterval = null; // Variable to hold the interval timer
        const updateIntervalTime = 1000; // Update interval in milliseconds (e.g., 30 seconds)

        // Load applications
        function loadApplications() {
            const url = `get_applications.php?page=${currentPage}&search=${currentSearch}&status=${currentStatus}&sort_by=${currentSortBy}&sort_order=${currentSortOrder}&per_page=${perPage}`;
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('applicationsList').innerHTML = html;
                    updatePagination();
                    updateSortIndicators();
                })
                .catch(error => {
                    console.error('Error loading applications:', error);
                    document.getElementById('applicationsList').innerHTML = 
                        '<div class="alert alert-danger">Error loading applications. Please try again.</div>';
                });
        }

        // Function to start the periodic updates
        function startPeriodicUpdates() {
            // Clear any existing interval before starting a new one
            if (updateInterval) {
                clearInterval(updateInterval);
            }
            updateInterval = setInterval(loadApplications, updateIntervalTime);
            console.log(`Started periodic updates every ${updateIntervalTime / 1000} seconds.`);
        }

        // Function to stop the periodic updates (e.g., when the modal is open)
        function stopPeriodicUpdates() {
            if (updateInterval) {
                clearInterval(updateInterval);
                updateInterval = null;
                console.log('Stopped periodic updates.');
            }
        }

        // Update sort indicators
        function updateSortIndicators() {
            const headers = document.querySelectorAll('th a');
            headers.forEach(header => {
                const column = header.getAttribute('onclick').match(/'([^']+)'/)[1];
                const icon = header.querySelector('i');
                if (column === currentSortBy) {
                    icon.className = currentSortOrder === 'ASC' ? 'bi bi-arrow-up' : 'bi bi-arrow-down';
                } else {
                    icon.className = 'bi bi-arrow-down-up';
                }
            });
        }

        // Update pagination
        function updatePagination() {
            const url = `get_applications_count.php?search=${currentSearch}&status=${currentStatus}`;
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const total = data.total;
                    const totalPages = Math.ceil(total / perPage);
                    
                    // Update pagination info
                    const start = (currentPage - 1) * perPage + 1;
                    const end = Math.min(currentPage * perPage, total);
                    document.getElementById('paginationInfo').innerHTML = 
                        `Showing ${start}-${end} of ${total} applications`;
                    
                    // Update pagination controls
                    let controls = '';
                    if (totalPages > 1) {
                        controls += `
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="changePage(${currentPage - 1})"
                                    ${currentPage === 1 ? 'disabled' : ''}>
                                Previous
                            </button>`;
                        
                        for (let i = 1; i <= totalPages; i++) {
                            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                                controls += `
                                    <button class="btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline-primary'}"
                                            onclick="changePage(${i})">
                                        ${i}
                                    </button>`;
                            } else if (i === currentPage - 3 || i === currentPage + 3) {
                                controls += `<span class="btn btn-sm btn-outline-primary disabled">...</span>`;
                            }
                        }
                        
                        controls += `
                            <button class="btn btn-sm btn-outline-primary"
                                    onclick="changePage(${currentPage + 1})"
                                    ${currentPage === totalPages ? 'disabled' : ''}>
                                Next
                            </button>`;
                    }
                    document.getElementById('paginationControls').innerHTML = controls;
                })
                .catch(error => {
                    console.error('Error updating pagination:', error);
                    document.getElementById('paginationInfo').innerHTML = 'Error loading pagination';
                    document.getElementById('paginationControls').innerHTML = '';
                });
        }

        // Change page
        function changePage(page) {
            currentPage = page;
            loadApplications();
        }

        // Search applications
        // Debounce function to limit how often a function is called
        function debounce(func, delay) {
            let timeoutId;
            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    func.apply(this, args);
                }, delay);
            };
        }

        // Debounced search function
        const debouncedSearchApplications = debounce(() => {
            currentSearch = document.getElementById('searchInput').value;
            currentPage = 1; // Reset to first page on new search
            loadApplications();
        }, 300); // 300ms delay

        function searchApplications() {
            // This function is now just a wrapper to call the debounced version
            debouncedSearchApplications();
        }

        // Filter by status using the new button-based UI
        function filterByStatus(status) {
            // Update active button state
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.status === status) {
                    btn.classList.add('active');
                }
            });
            
            // Update current status and reload applications
            currentStatus = status;
            currentPage = 1;
            loadApplications();
        }
        
        // Add event listeners for filter buttons
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers for filter buttons
            document.querySelectorAll('.filter-btn').forEach(button => {
                button.addEventListener('click', function() {
                    filterByStatus(this.dataset.status);
                });
            });
            
            // Initialize the first load with the active filter
            const activeFilter = document.querySelector('.filter-btn.active');
            if (activeFilter) {
                currentStatus = activeFilter.dataset.status || '';
            }
        });

        // Show SLEP image
        function showSlepImage(slepPath) {
            console.log('showSlepImage called with path:', slepPath);
            if (!slepPath) {
                alert('No SLEP document available');
                return;
            }
            const modal = new bootstrap.Modal(document.getElementById('slepModal'));
            const slepImage = document.getElementById('slepImage');
            slepImage.src = slepPath;
            slepImage.onerror = function() {
                alert('Error loading SLEP image. Please try again.');
                modal.hide();
            };
            
            // Add click handler for the view in new tab button
            document.getElementById('viewReceiptButton').onclick = function() {
                window.open(slepPath, '_blank');
            };

            // Add click handler for the download button
            document.getElementById('downloadReceiptButton').onclick = function() {
                const link = document.createElement('a');
                link.href = slepPath;
                // Extract filename from path or use default name
                const filename = slepPath.split('/').pop() || 'receipt.pdf';
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            };
            
            modal.show();
        }

        // View application details
        function viewApplicationDetails(id) {
            // Stop periodic updates when modal is open to avoid conflicts
            stopPeriodicUpdates();

            currentApplicationId = id;
            fetch(`get_application_details.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const app = data.application;
                        document.getElementById('detail-regnumber').textContent = app.regnumber;
                        document.getElementById('detail-name').textContent = app.names;
                        document.getElementById('detail-gender').textContent = app.gender;
                        document.getElementById('detail-year').textContent = app.yearofstudy;
                        document.getElementById('detail-email').textContent = app.email;
                        document.getElementById('detail-phone').textContent = app.phone;
                        document.getElementById('detail-campus').textContent = app.campus;
                        document.getElementById('detail-college').textContent = app.college;
                        document.getElementById('detail-school').textContent = app.school;
                        document.getElementById('detail-program').textContent = app.program;
                        document.getElementById('detail-room').textContent = app.room_code;
                        document.getElementById('detail-hostel').textContent = app.hostel_name;
                        document.getElementById('detail-status').innerHTML = 
                            `<span class="status-badge status-${app.status}">${app.status}</span>`;
                        document.getElementById('detail-date').textContent = 
                            new Date(app.created_at).toLocaleDateString();
                            
                        // Display receipt number and payment date if they exist
                        const receiptNumberElement = document.getElementById('detail-receipt-number');
                        const paymentDateElement = document.getElementById('detail-payment-date');
                        
                        if (app.ReceptNumber) {
                            receiptNumberElement.textContent = app.ReceptNumber;
                            receiptNumberElement.closest('tr').style.display = '';
                        } else {
                            receiptNumberElement.closest('tr').style.display = 'none';
                        }
                        
                        if (app.Date_of_payment) {
                            paymentDateElement.textContent = app.Date_of_payment;
                            paymentDateElement.closest('tr').style.display = '';
                        } else {
                            paymentDateElement.closest('tr').style.display = 'none';
                        }
                        
                        // Handle receipt display
                        const receiptSection = document.getElementById('receipt-section');
                        const receiptContainer = document.getElementById('receipt-container');
                        const receiptImg = document.getElementById('detail-receipt');
                        
                        if (app.slep && app.slep.trim() !== '') {
                            // Show receipt section
                            receiptSection.style.display = 'flex';
                            
                            // Ensure the path is absolute and includes the uploads directory
                            let receiptPath = app.slep;
                            if (!receiptPath.startsWith('http')) {
                                receiptPath = '../Students/uploads/receipts/' + receiptPath;
                            }
                            
                            console.log('Full receipt path:', receiptPath); // Debug log
                            
                            // Clear previous content
                            receiptContainer.innerHTML = '';
                            
                            // Create iframe for PDF preview
                            const iframe = document.createElement('iframe');
                            iframe.className = 'w-100';
                            iframe.style.height = '500px';
                            iframe.style.border = '1px solid #dee2e6';
                            iframe.style.borderRadius = '0.25rem';
                            iframe.src = receiptPath + '#toolbar=0&navpanes=0&scrollbar=0';
                            
                            // Add error handling
                            iframe.onerror = function() {
                                receiptContainer.innerHTML = `
                                    <div class="alert alert-warning">
                                        Failed to load PDF document. 
                                        <br>Path: ${receiptPath}
                                        <br>Please check if the file exists and is accessible.
                                    </div>`;
                            };
                            
                            // Add iframe to container
                            receiptContainer.appendChild(iframe);
                            
                            // Set up full screen view button
                            const viewFullScreenBtn = document.getElementById('viewFullScreenBtn');
                            if (viewFullScreenBtn) {
                                viewFullScreenBtn.onclick = function() {
                                    window.open(receiptPath, '_blank');
                                };
                            }
                            
                            // Set up download button
                            const downloadReceiptBtn = document.getElementById('downloadReceiptBtn');
                            if (downloadReceiptBtn) {
                                downloadReceiptBtn.onclick = function() {
                                    const link = document.createElement('a');
                                    link.href = receiptPath;
                                    link.download = 'receipt_' + app.regnumber + '.pdf';
                                    document.body.appendChild(link);
                                    link.click();
                                    document.body.removeChild(link);
                                };
                            }
                            
                        } else {
                            receiptContainer.innerHTML = '<div class="alert alert-info">No receipt document uploaded</div>';
                        }

                        // Show/hide action buttons based on status
                        const actionButtons = document.getElementById('action-buttons');
                        if (app.status === 'pending') {
                            actionButtons.style.display = 'flex';
                        } else {
                            actionButtons.style.display = 'none';
                        }
                        
                        const modal = new bootstrap.Modal(document.getElementById('applicationDetailsModal'));
                        modal.show();
                        
                        // Add event listener for modal close to resume updates
                        document.getElementById('applicationDetailsModal').addEventListener('hidden.bs.modal', startPeriodicUpdates, { once: true });

                    } else {
                        showAlert(data.message || 'Error loading application details', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error loading application details', 'danger');
                });
        }

        // Update application status
        function updateApplicationStatus(id, status) {
            if (!id || !status) {
                console.error('Invalid application ID or status');
                return;
            }

            const formData = new FormData();
            formData.append('action', status);
            formData.append('id', id);

            fetch('update_application.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert(`Application ${status === 'approve' ? 'approved' : 'rejected'} successfully!`);
                    
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('applicationDetailsModal'));
                    modal.hide();
                    
                    // Reload the applications list (this will also be triggered by the periodic update soon after)
                    // loadApplications(); // Optional: Can rely solely on the periodic update after modal closes
                } else {
                    showAlert(data.message || `Error ${status === 'approve' ? 'approving' : 'rejecting'} application`, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert(`Error ${status === 'approve' ? 'approving' : 'rejecting'} application`, 'danger');
            });
        }

        // Show alert message
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            // Auto dismiss after 3 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        // Event listeners
        document.getElementById('searchInput').addEventListener('input', searchApplications);
        document.getElementById('statusFilter').addEventListener('change', filterByStatus);

        // Check for success/error messages in URL
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                showAlert('Application status updated successfully');
            } else if (urlParams.has('error')) {
                showAlert(decodeURIComponent(urlParams.get('error')), 'danger');
            }
        });

        // Initial load and start periodic updates
        loadApplications();
        startPeriodicUpdates();
    </script>
</body>
</html>