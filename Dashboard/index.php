<?php
include 'connection.php';

// Initialize statistics arrays
$stats = [
    'overall' => [
        'total_campuses' => 0,
        'total_hostels' => 0,
        'total_rooms' => 0,
        'total_beds' => 0,
        'occupied_beds' => 0,
        'available_beds' => 0,
        'total_applications' => 0,
        'pending_applications' => 0,
        'paid_applications' => 0,
        'applications_by_month' => [],
        'applications_by_status' => [],
        'room_status_distribution' => [],
        'gender_distribution' => [],
        'applications_by_campus' => [],
        'applications_by_hostel' => []
    ],
    'campuses' => [],
    'hostels' => [],
    'gender_stats' => [
        'male' => ['total' => 0, 'pending' => 0, 'paid' => 0],
        'female' => ['total' => 0, 'pending' => 0, 'paid' => 0]
    ]
];

try {
    // Get campus-level statistics
    $query = "SELECT 
                c.id AS campus_id,
                c.name AS campus_name,
                COUNT(DISTINCT h.id) AS total_hostels,
                COUNT(DISTINCT r.id) AS total_rooms,
                SUM(r.number_of_beds) AS total_beds,
                SUM(r.number_of_beds) - COUNT(a.id) AS available_beds,
                COUNT(a.id) AS total_applications,
                SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) AS pending_applications,
                SUM(CASE WHEN a.status = 'paid' THEN 1 ELSE 0 END) AS paid_applications,
                SUM(CASE WHEN a.slep = 1 THEN 1 ELSE 0 END) AS slep_applications
            FROM campuses c
            LEFT JOIN hostels h ON h.campus_id = c.id
            LEFT JOIN rooms r ON r.hostel_id = h.id
            LEFT JOIN applications a ON a.room_id = r.id
            GROUP BY c.id, c.name
            ORDER BY c.name;
            ";

    $result = $connection->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['occupied_beds'] = $row['total_beds'] - $row['available_beds'];
            $row['occupancy_rate'] = $row['total_beds'] > 0 ?
                ($row['occupied_beds'] / $row['total_beds']) * 100 : 0;
            $stats['campuses'][] = $row;

            // Update overall stats
            $stats['overall']['total_campuses']++;
            $stats['overall']['total_hostels'] += $row['total_hostels'];
            $stats['overall']['total_rooms'] += $row['total_rooms'];
            $stats['overall']['total_beds'] += $row['total_beds'];
            $stats['overall']['occupied_beds'] += $row['occupied_beds'];
            $stats['overall']['total_applications'] += $row['total_applications'];
            $stats['overall']['pending_applications'] += $row['pending_applications'];
            $stats['overall']['paid_applications'] += $row['paid_applications'];
        }
    }

    // Get hostel-level statistics
    $query = "SELECT 
    h.id AS hostel_id,
    h.name AS hostel_name,
    c.name AS campus_name,
    COUNT(DISTINCT r.id) AS total_rooms,
    SUM(r.number_of_beds) AS total_beds,
    SUM(r.number_of_beds) - COUNT(a.id) AS available_beds,
    COUNT(a.id) AS total_applications,
    SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) AS pending_applications,
    SUM(CASE WHEN a.status = 'paid' THEN 1 ELSE 0 END) AS paid_applications,
    SUM(CASE WHEN a.slep = 1 THEN 1 ELSE 0 END) AS slep_applications
FROM hostels h
LEFT JOIN campuses c ON h.campus_id = c.id
LEFT JOIN rooms r ON r.hostel_id = h.id
LEFT JOIN applications a ON a.room_id = r.id
GROUP BY h.id, h.name, c.name
ORDER BY c.name, h.name;
";

    $result = $connection->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['occupied_beds'] = $row['total_beds'] - $row['available_beds'];
            $row['occupancy_rate'] = $row['total_beds'] > 0 ?
                ($row['occupied_beds'] / $row['total_beds']) * 100 : 0;
            $stats['hostels'][] = $row;
        }
    }

    // Get application trends by month
    $query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
                SUM(CASE WHEN slep = 1 THEN 1 ELSE 0 END) as slep
             FROM applications
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month DESC
             LIMIT 12";

    $result = $connection->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['overall']['applications_by_month'][] = $row;
        }
    }

    // Get room status distribution
    $query = "SELECT 
                status,
                COUNT(*) as count
             FROM rooms
             GROUP BY status";

    $result = $connection->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['overall']['room_status_distribution'][] = $row;
        }
    }

    // Get gender distribution
    $query = "SELECT 
                CASE 
                    WHEN LOWER(i.gender) IN ('m', 'male') THEN 'male'
                    WHEN LOWER(i.gender) IN ('f', 'female') THEN 'female'
                    ELSE 'other'
                END as gender,
                COUNT(DISTINCT i.regnumber) as total,
                SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN a.status = 'paid' OR a.status = 'Paid' THEN 1 ELSE 0 END) as paid
             FROM info i
             LEFT JOIN applications a ON i.regnumber = a.regnumber
             GROUP BY 
                CASE 
                    WHEN LOWER(i.gender) IN ('m', 'male') THEN 'male'
                    WHEN LOWER(i.gender) IN ('f', 'female') THEN 'female'
                    ELSE 'other'
                END";

    $result = $connection->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $gender = strtolower($row['gender']);
            $stats['gender_stats'][$gender] = [
                'total' => $row['total'],
                'pending' => $row['pending'],
                'paid' => $row['paid']
            ];
        }
    }

    // Get applications by campus
    $query = "SELECT 
                c.name as campus_name,
                COUNT(*) as total,
                SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN a.status = 'paid' THEN 1 ELSE 0 END) as paid
             FROM applications a
             JOIN rooms r ON a.room_id = r.id
             JOIN hostels h ON r.hostel_id = h.id
             JOIN campuses c ON h.campus_id = c.id
             GROUP BY c.id, c.name
             ORDER BY total DESC";

    $result = $connection->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['overall']['applications_by_campus'][] = $row;
        }
    }

    // Calculate overall available beds
    $stats['overall']['available_beds'] = $stats['overall']['total_beds'] - $stats['overall']['occupied_beds'];

} catch (Exception $e) {
    error_log("Error getting statistics: " . $e->getMessage());
}
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
<title>Hostel Statistics Dashboard</title>
    <!-- Template Main CSS File -->
    <link href="assets/css/style.css" rel="stylesheet">
<!-- 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"> -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            transition: transform 0.2s;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.8;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }

        .nav-tabs .nav-link {
            color: #495057;
        }

        .nav-tabs .nav-link.active {
            font-weight: bold;
            color: #0d6efd;
        }

        .occupancy-rate {
            font-weight: bold;
        }

        .occupancy-rate.high {
            color: #dc3545;
        }

        .occupancy-rate.medium {
            color: #ffc107;
        }

        .occupancy-rate.low {
            color: #28a745;
        }

        .table th {
            background-color: #f8f9fa;
        }

        .trend-indicator {
            font-size: 0.8rem;
            margin-left: 5px;
        }

        .trend-up {
            color: #dc3545;
        }

        .trend-down {
            color: #28a745;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            padding-right: 40px;
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .insight-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 15px;
        }

        .insight-card.warning {
            border-left-color: #ffc107;
        }

        .insight-card.danger {
            border-left-color: #dc3545;
        }

        .insight-card.success {
            border-left-color: #28a745;
        }

        /* Skeleton Loading Styles */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }

        .skeleton-card {
            height: 100%;
            padding: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .skeleton-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-bottom: 15px;
        }

        .skeleton-text {
            height: 20px;
            margin-bottom: 10px;
        }

        .skeleton-text.small {
            width: 60%;
        }

        .skeleton-chart {
            height: 300px;
            margin-bottom: 20px;
        }

        .skeleton-table {
            height: 400px;
        }

        .skeleton-row {
            height: 40px;
            margin-bottom: 10px;
        }

        #loadingSkeleton {
            display: none;
        }

        .loading #loadingSkeleton {
            display: block;
        }

        .loading #mainContent {
            display: none;
        }
    </style>
</head>

<body class="bg-light loading">

    <?php
    include("./includes/header.php");
    include("./includes/menu.php");
    ?>

    <!-- Loading Skeleton -->
    <div id="loadingSkeleton">
        <main id="main" class="main">
            <section class="section dashboard">
                <div class="row">
                    <div class="container-fluid py-4">
                        <!-- Skeleton Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="skeleton skeleton-text" style="width: 200px;"></div>
                            <div class="skeleton skeleton-text" style="width: 150px;"></div>
                        </div>

                        <!-- Skeleton Stats Cards -->
                        <div class="row mb-4 g-4">
                            <?php for($i = 0; $i < 4; $i++): ?>
                            <div class="col-md-3">
                                <div class="skeleton-card">
                                    <div class="skeleton-icon"></div>
                                    <div class="skeleton-text"></div>
                                    <div class="skeleton-text small"></div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>

                        <!-- Skeleton Tabs -->
                        <div class="skeleton skeleton-text" style="width: 100%; height: 40px; margin-bottom: 20px;"></div>

                        <!-- Skeleton Content -->
                        <div class="row">
                            <?php for($i = 0; $i < 4; $i++): ?>
                            <div class="col-md-6 mb-4">
                                <div class="skeleton-card">
                                    <div class="skeleton-text" style="width: 200px; margin-bottom: 20px;"></div>
                                    <div class="skeleton-chart"></div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Main Content -->
    <div id="mainContent">
        <main id="main" class="main">

            <section class="section dashboard">
                <div class="row">
                    <div class="container-fluid py-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Hostel Statistics Dashboard</h2>
                            <button class="btn btn-primary" onclick="refreshStats()">
                                <i class="fas fa-sync-alt"></i> Refresh Statistics
                            </button>
                        </div>

                        <!-- Overall Statistics Cards -->
                        <div class="row mb-4 g-4">
                            <!-- Total Campuses -->
                            <div class="col-md-3">
                                <div class="card shadow-sm border-0 h-100 bg-primary text-white rounded-4">
                                    <div class="card-body text-center py-4">
                                        <div class="mb-3">
                                            <i class="fas fa-building fa-2x"></i>
                                        </div>
                                        <h4 class="mb-1 fw-bold">
                                            <?php echo number_format($stats['overall']['total_campuses']); ?></h4>
                                        <p class="mb-0 text-uppercase small">Total Campuses</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Hostels -->
                            <div class="col-md-3">
                                <div class="card shadow-sm border-0 h-100 bg-success text-white rounded-4">
                                    <div class="card-body text-center py-4">
                                        <div class="mb-3">
                                            <i class="fas fa-home fa-2x"></i>
                                        </div>
                                        <h4 class="mb-1 fw-bold">
                                            <?php echo number_format($stats['overall']['total_hostels']); ?></h4>
                                        <p class="mb-0 text-uppercase small">Total Hostels</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Applications -->
                            <div class="col-md-3">
                                <div class="card shadow-sm border-0 h-100 bg-info text-white rounded-4">
                                    <div class="card-body text-center py-4">
                                        <div class="mb-3">
                                            <i class="fas fa-file-alt fa-2x"></i>
                                        </div>
                                        <h4 class="mb-1 fw-bold">
                                            <?php echo number_format($stats['overall']['total_applications']); ?>
                                        </h4>
                                        <p class="mb-0 text-uppercase small">Total Applications</p>
                                        <small class="d-block mt-1">Pending:
                                            <?php echo number_format($stats['overall']['pending_applications']); ?></small>
                                    </div>
                                </div>
                            </div>

                            <!-- Available Beds -->
                            <div class="col-md-3">
                                <div class="card shadow-sm border-0 h-100 bg-warning text-white rounded-4">
                                    <div class="card-body text-center py-4">
                                        <div class="mb-3">
                                            <i class="fas fa-bed fa-2x"></i>
                                        </div>
                                        <h4 class="mb-1 fw-bold">
                                            <?php echo number_format($stats['overall']['available_beds']); ?></h4>
                                        <p class="mb-0 text-uppercase small">Available Beds</p>
                                        <small class="d-block mt-1">Total:
                                            <?php echo number_format($stats['overall']['total_beds']); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Navigation Tabs -->
                        <ul class="nav nav-tabs mb-4" id="statTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="overview-tab" data-bs-toggle="tab" href="#overview"
                                    role="tab">
                                    Overview
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="campuses-tab" data-bs-toggle="tab" href="#campuses" role="tab">
                                    Campus Details
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="hostels-tab" data-bs-toggle="tab" href="#hostels" role="tab">
                                    Hostel Details
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="search-tab" data-bs-toggle="tab" href="#search" role="tab">
                                    Dynamic Search
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="insights-tab" data-bs-toggle="tab" href="#insights" role="tab">
                                    Insights
                                </a>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="statTabsContent">
                            <!-- Overview Tab -->
                            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                                <div class="row">
                                    <!-- Application Trends Chart -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Application Trends</h5>
                                                <div class="chart-container">
                                                    <canvas id="applicationTrendsChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Campus Distribution -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Campus Distribution</h5>
                                                <div class="chart-container">
                                                    <canvas id="campusDistributionChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Application Status -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Application Status</h5>
                                                <div class="chart-container">
                                                    <canvas id="applicationStatusChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Campus Details Tab -->
                            <div class="tab-pane fade" id="campuses" role="tabpanel">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">Campus Statistics</h5>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Campus Name</th>
                                                        <th>Hostels</th>
                                                        <th>Rooms</th>
                                                        <th>Total Beds</th>
                                                        <th>Occupied</th>
                                                        <th>Available</th>
                                                        <th>Occupancy Rate</th>
                                                        <th>Applications</th>
                                                        <th>Pending</th>
                                                        <th>Paid</th>
                                                        <!-- <th>SLEP</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($stats['campuses'] as $campus): ?>
                                                        <tr>
                                                            <td>
                                                                <a href="campus_details.php?campus_id=<?php echo $campus['campus_id']; ?>">
                                                                    <?php echo htmlspecialchars($campus['campus_name']); ?>
                                                                </a>
                                                            </td>
                                                            <td><?php echo number_format($campus['total_hostels']); ?></td>
                                                            <td><?php echo number_format($campus['total_rooms']); ?></td>
                                                            <td><?php echo number_format($campus['total_beds']); ?></td>
                                                            <td><?php echo number_format($campus['occupied_beds']); ?></td>
                                                            <td><?php echo number_format($campus['available_beds']); ?></td>
                                                            <td>
                                                                <span class="occupancy-rate <?php
                                                                echo $campus['occupancy_rate'] >= 90 ? 'high' :
                                                                    ($campus['occupancy_rate'] >= 70 ? 'medium' : 'low');
                                                                ?>">
                                                                    <?php echo number_format($campus['occupancy_rate'], 1); ?>%
                                                                </span>
                                                            </td>
                                                            <td><?php echo number_format($campus['total_applications']); ?></td>
                                                            <td><?php echo number_format($campus['pending_applications']); ?>
                                                            </td>
                                                            <td><?php echo number_format($campus['paid_applications']); ?></td>
                                                            <!-- <td><?php //echo number_format($campus['slep_applications']); ?></td> -->
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hostel Details Tab -->
                            <div class="tab-pane fade" id="hostels" role="tabpanel">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">Hostel Statistics</h5>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Campus</th>
                                                        <th>Hostel Name</th>
                                                        <th>Rooms</th>
                                                        <th>Total Beds</th>
                                                        <th>Occupied</th>
                                                        <th>Available</th>
                                                        <th>Occupancy Rate</th>
                                                        <th>Applications</th>
                                                        <th>Pending</th>
                                                        <th>Paid</th>
                                                        <!-- <th>SLEP</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($stats['hostels'] as $hostel): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($hostel['campus_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($hostel['hostel_name']); ?></td>
                                                            <td><?php echo number_format($hostel['total_rooms']); ?></td>
                                                            <td><?php echo number_format($hostel['total_beds']); ?></td>
                                                            <td><?php echo number_format($hostel['occupied_beds']); ?></td>
                                                            <td><?php echo number_format($hostel['available_beds']); ?></td>
                                                            <td>
                                                                <span class="occupancy-rate <?php
                                                                echo $hostel['occupancy_rate'] >= 90 ? 'high' :
                                                                    ($hostel['occupancy_rate'] >= 70 ? 'medium' : 'low');
                                                                ?>">
                                                                    <?php echo number_format($hostel['occupancy_rate'], 1); ?>%
                                                                </span>
                                                            </td>
                                                            <td><?php echo number_format($hostel['total_applications']); ?></td>
                                                            <td><?php echo number_format($hostel['pending_applications']); ?>
                                                            </td>
                                                            <td><?php echo number_format($hostel['paid_applications']); ?></td>
                                                            <!-- <td><?php //echo number_format($hostel['slep_applications']); ?></td> -->
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamic Search Tab -->
                            <div class="tab-pane fade" id="search" role="tabpanel">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">Dynamic Hostel Search</h5>
                                        <div class="search-box">
                                            <input type="text" class="form-control" id="hostelSearch"
                                                placeholder="Search for a hostel...">
                                            <i class="fas fa-search"></i>
                                        </div>
                                        <div id="searchResults" class="mt-4">
                                            <!-- Results will be populated here -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Insights Tab -->
                            <div class="tab-pane fade" id="insights" role="tabpanel">
                                <div class="row">
                                    <!-- Application Trends Analysis -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Application Trends Analysis</h5>
                                                <div class="chart-container">
                                                    <canvas id="applicationTrendsAnalysisChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Campus Performance -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Campus Performance</h5>
                                                <div class="chart-container">
                                                    <canvas id="campusPerformanceChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Application Status Distribution -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Application Status Distribution</h5>
                                                <div class="chart-container">
                                                    <canvas id="applicationStatusDistributionChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        // Application Trends Chart
                        new Chart(document.getElementById('applicationTrendsChart'), {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode(array_column(array_reverse($stats['overall']['applications_by_month']), 'month')); ?>,
                                datasets: [{
                                    label: 'Total Applications',
                                    data: <?php echo json_encode(array_column(array_reverse($stats['overall']['applications_by_month']), 'total')); ?>,
                                    borderColor: '#0d6efd',
                                    tension: 0.1
                                }, {
                                    label: 'Pending',
                                    data: <?php echo json_encode(array_column(array_reverse($stats['overall']['applications_by_month']), 'pending')); ?>,
                                    borderColor: '#ffc107',
                                    tension: 0.1
                                }, {
                                    label: 'Paid',
                                    data: <?php echo json_encode(array_column(array_reverse($stats['overall']['applications_by_month']), 'paid')); ?>,
                                    borderColor: '#28a745',
                                    tension: 0.1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });

                        // Campus Distribution Chart
                        new Chart(document.getElementById('campusDistributionChart'), {
                            type: 'bar',
                            data: {
                                labels: <?php echo json_encode(array_column($stats['campuses'], 'campus_name')); ?>,
                                datasets: [{
                                    label: 'Total Beds',
                                    data: <?php echo json_encode(array_column($stats['campuses'], 'total_beds')); ?>,
                                    backgroundColor: '#0d6efd'
                                }, {
                                    label: 'Occupied Beds',
                                    data: <?php echo json_encode(array_column($stats['campuses'], 'occupied_beds')); ?>,
                                    backgroundColor: '#dc3545'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });

                        // Application Status Chart
                        new Chart(document.getElementById('applicationStatusChart'), {
                            type: 'doughnut',
                            data: {
                                labels: ['Pending', 'Paid', 'SLEP'],
                                datasets: [{
                                    data: [
                                        <?php echo $stats['overall']['pending_applications']; ?>,
                                        <?php echo $stats['overall']['paid_applications']; ?>,
                                        <?php
                                        $slep_total = 0;
                                        foreach ($stats['campuses'] as $campus) {
                                            $slep_total += $campus['slep_applications'];
                                        }
                                        echo $slep_total;
                                        ?>
                                    ],
                                    backgroundColor: ['#ffc107', '#28a745', '#0d6efd']
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false
                            }
                        });

                        // Application Status Distribution Chart
                        new Chart(document.getElementById('applicationStatusDistributionChart'), {
                            type: 'doughnut',
                            data: {
                                labels: ['Pending', 'Paid', 'SLEP'],
                                datasets: [{
                                    data: [
                                        <?php echo $stats['overall']['pending_applications']; ?>,
                                        <?php echo $stats['overall']['paid_applications']; ?>,
                                        <?php echo array_sum(array_column($stats['overall']['applications_by_month'], 'slep')); ?>
                                    ],
                                    backgroundColor: ['#ffc107', '#198754', '#dc3545']
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false
                            }
                        });

                        // Campus Performance Chart
                        new Chart(document.getElementById('campusPerformanceChart'), {
                            type: 'radar',
                            data: {
                                labels: ['Occupancy Rate', 'Application Success', 'SLEP Ratio'],
                                datasets: <?php
                                $datasets = [];
                                foreach ($stats['campuses'] as $campus) {
                                    $success_rate = $campus['total_applications'] > 0 ?
                                        ($campus['paid_applications'] / $campus['total_applications']) * 100 : 0;
                                    $slep_ratio = $campus['total_applications'] > 0 ?
                                        ($campus['slep_applications'] / $campus['total_applications']) * 100 : 0;

                                    $datasets[] = [
                                        'label' => $campus['campus_name'],
                                        'data' => [
                                            $campus['occupancy_rate'],
                                            $success_rate,
                                            $slep_ratio
                                        ],
                                        'backgroundColor' => 'rgba(13, 110, 253, 0.2)',
                                        'borderColor' => '#0d6efd',
                                        'pointBackgroundColor' => '#0d6efd'
                                    ];
                                }
                                echo json_encode($datasets);
                                ?>
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    r: {
                                        beginAtZero: true,
                                        max: 100
                                    }
                                }
                            }
                        });

                        // Application Trends Analysis Chart
                        new Chart(document.getElementById('applicationTrendsAnalysisChart'), {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode(array_column(array_reverse($stats['overall']['applications_by_month']), 'month')); ?>,
                                datasets: [{
                                    label: 'Total Applications',
                                    data: <?php echo json_encode(array_column(array_reverse($stats['overall']['applications_by_month']), 'total')); ?>,
                                    borderColor: '#0d6efd',
                                    tension: 0.1
                                }, {
                                    label: 'SLEP Applications',
                                    data: <?php echo json_encode(array_column(array_reverse($stats['overall']['applications_by_month']), 'slep')); ?>,
                                    borderColor: '#dc3545',
                                    tension: 0.1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });

                        // Dynamic Search Functionality
                        const hostelSearch = document.getElementById('hostelSearch');
                        const searchResults = document.getElementById('searchResults');
                        const hostels = <?php echo json_encode($stats['hostels']); ?>;

                        hostelSearch.addEventListener('input', function () {
                            const searchTerm = this.value.toLowerCase();
                            const filteredHostels = hostels.filter(hostel =>
                                hostel.hostel_name.toLowerCase().includes(searchTerm) ||
                                hostel.campus_name.toLowerCase().includes(searchTerm)
                            );

                            if (filteredHostels.length > 0) {
                                let html = '<div class="row">';
                                filteredHostels.forEach(hostel => {
                                    html += `
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">${hostel.hostel_name}</h5>
                                        <h6 class="card-subtitle mb-3 text-muted">${hostel.campus_name}</h6>
                                        <div class="row">
                                            <div class="col-6">
                                                <p class="mb-1"><strong>Rooms:</strong> ${hostel.total_rooms}</p>
                                                <p class="mb-1"><strong>Total Beds:</strong> ${hostel.total_beds}</p>
                                                <p class="mb-1"><strong>Available:</strong> ${hostel.available_beds}</p>
                                            </div>
                                            <div class="col-6">
                                                <p class="mb-1"><strong>Applications:</strong> ${hostel.total_applications}</p>
                                                <p class="mb-1"><strong>Pending:</strong> ${hostel.pending_applications}</p>
                                                <p class="mb-1"><strong>Paid:</strong> ${hostel.paid_applications}</p>
                                            </div>
                                        </div>
                                       <div class="progress mt-3" style="height: 25px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.2); border-radius: 8px;">
                                        <div class="progress-bar ${hostel.occupancy_rate >= 90 ? 'bg-danger' :
                                            (hostel.occupancy_rate >= 70 ? 'bg-warning text-dark' : 'bg-success')}" 
                                            role="progressbar" 
                                            style="width: ${hostel.occupancy_rate}%; font-weight: 500; font-size: 0.9rem;">
                                            ${hostel.occupancy_rate.toFixed(1)}% Occupancy
                                        </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        `;
                                });
                                html += '</div>';
                                searchResults.innerHTML = html;
                            } else {
                                searchResults.innerHTML =
                                 '<div class="alert alert-info">No hostels found matching your search.</div>';
                            }
                        });
                    });

                    function refreshStats() {
                        window.location.reload();
                    }
                </script>

                <!-- End Footer -->
                 

                <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
                        class="bi bi-arrow-up-short"></i></a>

                <!-- Vendor JS Files -->
                <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <script src="assets/vendor/chart.js/chart.umd.js"></script>
                <script src="assets/vendor/echarts/echarts.min.js"></script>
                <script src="assets/vendor/quill/quill.min.js"></script>
                <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
                <script src="assets/vendor/tinymce/tinymce.min.js"></script>
                <script src="assets/vendor/php-email-form/validate.js"></script>
                <script src="assets/js/main.js"></script>
                <!-- Template Main JS File -->
                <script>
                    // Initialize Bootstrap tabs
                    document.addEventListener('DOMContentLoaded', function() {
                        // Remove loading state when all charts are initialized
                        Promise.all([
                            // Wait for all charts to be initialized
                            new Promise(resolve => {
                                const charts = [
                                    'applicationTrendsChart',
                                    'campusDistributionChart',
                                    'applicationStatusChart',
                                    'applicationStatusDistributionChart',
                                    'campusPerformanceChart',
                                    'applicationTrendsAnalysisChart'
                                ];
                                
                                let loadedCharts = 0;
                                charts.forEach(chartId => {
                                    const canvas = document.getElementById(chartId);
                                    if (canvas) {
                                        const chart = Chart.getChart(canvas);
                                        if (chart) {
                                            loadedCharts++;
                                            if (loadedCharts === charts.length) {
                                                resolve();
                                            }
                                        }
                                    }
                                });
                            })
                        ]).then(() => {
                            // Remove loading class and show main content
                            document.body.classList.remove('loading');
                        });

                        // Get all tab elements
                        const tabElements = document.querySelectorAll('[data-bs-toggle="tab"]');
                        
                        // Initialize each tab
                        tabElements.forEach(tab => {
                            tab.addEventListener('click', function(e) {
                                e.preventDefault();
                                
                                // Remove active class from all tabs
                                tabElements.forEach(t => t.classList.remove('active'));
                                
                                // Add active class to clicked tab
                                this.classList.add('active');
                                
                                // Hide all tab panes
                                document.querySelectorAll('.tab-pane').forEach(pane => {
                                    pane.classList.remove('show', 'active');
                                });
                                
                                // Show the corresponding tab pane
                                const target = document.querySelector(this.getAttribute('href'));
                                if (target) {
                                    target.classList.add('show', 'active');
                                }
                            });
                        });

                        // Show the first tab by default
                        const firstTab = document.querySelector('[data-bs-toggle="tab"]');
                        if (firstTab) {
                            firstTab.click();
                        }
                    });
                </script>

            </div>
        </section>
    </main>
</body>

</html>