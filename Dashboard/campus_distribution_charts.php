<?php


// Get user's role and ID
$userRole = $_SESSION['role'];
$userId = $_SESSION['id'];

// Handle AJAX request for campus data
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');

    // Fetch user's campus
    $userCampus = null;
    $campusQuery = "SELECT campus FROM users WHERE id = ?";
    $stmt = $connection->prepare($campusQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $campusResult = $stmt->get_result();
    if ($campusResult && $row = $campusResult->fetch_assoc()) {
        $userCampus = $row['campus'];
    }
    $stmt->close();

    // Get campus-level statistics
    $campuses = [];
    $campusCondition = "";
    $params = [];
    if ($userRole === 'warefare') {
        $campusCondition = "WHERE c.id = ?";
        $params[] = $userCampus;
    } elseif (isset($_GET['campus_id']) && !empty($_GET['campus_id'])) {
        $campusId = (int)$_GET['campus_id'];
        $campusCondition = "WHERE c.id = ?";
        $params[] = $campusId;
    }

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
        SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) AS approved_applications,
        COUNT(DISTINCT CASE WHEN a.id IS NOT NULL THEN r.id END) AS occupied_rooms
    FROM campuses c
    LEFT JOIN hostels h ON h.campus_id = c.id
    LEFT JOIN rooms r ON r.hostel_id = h.id
    LEFT JOIN applications a ON a.room_id = r.id
    $campusCondition
    GROUP BY c.id, c.name
    ORDER BY c.name";

    $stmt = $connection->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param("i", ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['occupied_beds'] = $row['total_beds'] - $row['available_beds'];
            $row['occupancy_rate'] = $row['total_beds'] > 0 ?
                ($row['occupied_beds'] / $row['total_beds']) * 100 : 0;
            $row['available_rooms'] = $row['total_rooms'] - $row['occupied_rooms'];
            $campuses[] = $row;
        }
        echo json_encode($campuses, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    } else {
        error_log("Campus query failed: " . $connection->error);
        echo json_encode(['error' => 'Query failed']);
    }
    $stmt->close();
    $connection->close();
    exit();
}

// Fetch user's campus information
$userCampus = null;
$campusQuery = "SELECT u.campus, c.name as campus_name 
                FROM users u 
                LEFT JOIN campuses c ON u.campus = c.id 
                WHERE u.id = ?";
$stmt = $connection->prepare($campusQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$campusResult = $stmt->get_result();

if ($campusResult && $row = $campusResult->fetch_assoc()) {
    $userCampus = $row['campus'];
    $_SESSION['campus_name'] = $row['campus_name'];
}
$stmt->close();

// Initialize arrays
$campuses = [];
$allCampuses = [];

// Fetch all campuses for non-warefare users (for dropdown)
if ($userRole !== 'warefare') {
    $campusQuery = "SELECT id, name FROM campuses ORDER BY name";
    $campusResult = $connection->query($campusQuery);
    if ($campusResult) {
        while ($row = $campusResult->fetch_assoc()) {
            $allCampuses[] = $row;
        }
    } else {
        error_log("All campuses query failed: " . $connection->error);
    }
}

// Get initial campus-level statistics
$campusCondition = "";
$params = [];
if ($userRole === 'warefare') {
    $campusCondition = "WHERE c.id = ?";
    $params[] = $userCampus;
} elseif (isset($_GET['campus_id']) && !empty($_GET['campus_id'])) {
    $campusId = (int)$_GET['campus_id'];
    $campusCondition = "WHERE c.id = ?";
    $params[] = $campusId;
}

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
    SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) AS approved_applications,
    COUNT(DISTINCT CASE WHEN a.id IS NOT NULL THEN r.id END) AS occupied_rooms
FROM campuses c
LEFT JOIN hostels h ON h.campus_id = c.id
LEFT JOIN rooms r ON r.hostel_id = h.id
LEFT JOIN applications a ON a.room_id = r.id
$campusCondition
GROUP BY c.id, c.name
ORDER BY c.name";

$stmt = $connection->prepare($query);
if (!empty($params)) {
    $stmt->bind_param("i", ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['occupied_beds'] = $row['total_beds'] - $row['available_beds'];
        $row['occupancy_rate'] = $row['total_beds'] > 0 ?
            ($row['occupied_beds'] / $row['total_beds']) * 100 : 0;
        $row['available_rooms'] = $row['total_rooms'] - $row['occupied_rooms'];
        $campuses[] = $row;
    }
} else {
    error_log("Campus query failed: " . $connection->error);
}
$stmt->close();
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Distribution Charts - UR-HOSTELS</title>
    <!-- Bootstrap CSS -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
        }
        h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
    </style>
</head>
<body class="bg-light">
    <!-- <main id="main" class="main"> -->
        <section class="section dashboard">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Campus Distribution Charts</h2>
                  
                </div>

                <div class="row">
                    <!-- Campus Beds Distribution Chart -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Campus Beds Distribution</h5>
                                <div class="chart-container">
                                    <canvas id="campusBedsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Campus Rooms Distribution Chart -->
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Campus Rooms Distribution</h5>
                                <div class="chart-container">
                                    <canvas id="campusRoomsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Campus Occupancy Rates Chart -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Campus Occupancy Rates</h5>
                                <div class="chart-container">
                                    <canvas id="campusOccupancyRatesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <!-- </main> -->

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let campuses = <?php echo json_encode($campuses, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            console.log('Initial campuses:', campuses);
            console.log('campuses type:', typeof campuses);
            console.log('Is campuses an array?', Array.isArray(campuses));

            // Chart instances to allow destruction and recreation
            let bedsChart = null;
            let roomsChart = null;
            let ratesChart = null;

            // Function to update charts
            function updateCharts(campusesData) {
                // Destroy existing charts
                if (bedsChart) {
                    bedsChart.destroy();
                }
                if (roomsChart) {
                    roomsChart.destroy();
                }
                if (ratesChart) {
                    ratesChart.destroy();
                }

                // Campus Beds Distribution Chart (Stacked Bar)
                const bedsCanvas = document.getElementById('campusBedsChart');
                if (bedsCanvas) {
                    bedsCanvas.parentElement.innerHTML = '<canvas id="campusBedsChart"></canvas>';
                    const newBedsCanvas = document.getElementById('campusBedsChart');
                    if (Array.isArray(campusesData) && campusesData.length > 0) {
                        bedsChart = new Chart(newBedsCanvas, {
                            type: 'bar',
                            data: {
                                labels: campusesData.map(c => c.campus_name),
                                datasets: [
                                    {
                                        label: 'Occupied Beds',
                                        data: campusesData.map(c => c.occupied_beds || 0),
                                        backgroundColor: 'darkblue',
                                        borderColor: '#a71d2a',
                                        borderWidth: 1
                                    },
                                    {
                                        label: 'Available Beds',
                                        data: campusesData.map(c => c.available_beds || 0),
                                        backgroundColor: 'green',
                                        borderColor: '#1e7e34',
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    x: { stacked: true },
                                    y: {
                                        stacked: true,
                                        beginAtZero: true,
                                        title: { display: true, text: 'Number of Beds' }
                                    }
                                },
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return context.dataset.label + ': ' + context.raw + ' beds';
                                            }
                                        }
                                    },
                                    datalabels: {
                                        anchor: 'end',
                                        align: 'top',
                                        offset: 8,
                                        formatter: Math.round,
                                        font: { weight: 'bold', size: 12 },
                                        color: '#000',
                                        textShadow: '1px 1px 2px rgba(255, 255, 255, 0.8)'
                                    }
                                }
                            },
                            plugins: [ChartDataLabels]
                        });
                    } else {
                        newBedsCanvas.parentElement.innerHTML = '<div class="alert alert-info">No campus data available for beds distribution.</div>';
                    }
                }

                // Campus Rooms Distribution Chart (Stacked Bar)
                const roomsCanvas = document.getElementById('campusRoomsChart');
                if (roomsCanvas) {
                    roomsCanvas.parentElement.innerHTML = '<canvas id="campusRoomsChart"></canvas>';
                    const newRoomsCanvas = document.getElementById('campusRoomsChart');
                    if (Array.isArray(campusesData) && campusesData.length > 0) {
                        roomsChart = new Chart(newRoomsCanvas, {
                            type: 'bar',
                            data: {
                                labels: campusesData.map(c => c.campus_name),
                                datasets: [
                                    {
                                        label: 'Occupied Rooms',
                                        data: campusesData.map(c => c.occupied_rooms || 0),
                                        backgroundColor: 'darkblue',
                                        borderColor: '#a71d2a',
                                        borderWidth: 1
                                    },
                                    {
                                        label: 'Available Rooms',
                                        data: campusesData.map(c => c.available_rooms || 0),
                                        backgroundColor: 'green',
                                        borderColor: '#1e7e34',
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    x: { stacked: true },
                                    y: {
                                        stacked: true,
                                        beginAtZero: true,
                                        title: { display: true, text: 'Number of Rooms' }
                                    }
                                },
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return context.dataset.label + ': ' + context.raw + ' rooms';
                                            }
                                        }
                                    },
                                    datalabels: {
                                        anchor: 'end',
                                        align: 'top',
                                        offset: 8,
                                        formatter: Math.round,
                                        font: { weight: 'bold', size: 12 },
                                        color: '#000',
                                        textShadow: '1px 1px 2px rgba(255, 255, 255, 0.8)'
                                    }
                                }
                            },
                            plugins: [ChartDataLabels]
                        });
                    } else {
                        newRoomsCanvas.parentElement.innerHTML = '<div class="alert alert-info">No campus data available for rooms distribution.</div>';
                    }
                }

                // Campus Occupancy Rates Chart (Horizontal Bar with Color Coding)
                const ratesCanvas = document.getElementById('campusOccupancyRatesChart');
                if (ratesCanvas) {
                    ratesCanvas.parentElement.innerHTML = '<canvas id="campusOccupancyRatesChart"></canvas>';
                    const newRatesCanvas = document.getElementById('campusOccupancyRatesChart');
                    if (Array.isArray(campusesData) && campusesData.length > 0) {
                        ratesChart = new Chart(newRatesCanvas, {
                            type: 'bar',
                            data: {
                                labels: campusesData.map(c => c.campus_name),
                                datasets: [{
                                    label: 'Occupancy Rate (%)',
                                    data: campusesData.map(c => Math.round(c.occupancy_rate || 0)),
                                    backgroundColor: campusesData.map(c => {
                                        const rate = c.occupancy_rate || 0;
                                        return rate >= 90 ? 'darkblue' : (rate >= 70 ? 'green' : 'darkgreen');
                                    }),
                                    borderColor: campusesData.map(c => {
                                        const rate = c.occupancy_rate || 0;
                                        return rate >= 90 ? 'darkblue' : (rate >= 70 ? 'green' : 'darkgreen');
                                    }),
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        max: 100,
                                        title: { display: true, text: 'Occupancy Rate (%)' }
                                    }
                                },
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return context.dataset.label + ': ' + context.raw + '%';
                                            }
                                        }
                                    },
                                    datalabels: {
                                        anchor: 'end',
                                        align: 'end',
                                        offset: 8,
                                        formatter: value => value + '%',
                                        font: { weight: 'bold', size: 12 },
                                        color: '#000',
                                        textShadow: '1px 1px 2px rgba(255, 255, 255, 0.8)'
                                    }
                                }
                            },
                            plugins: [ChartDataLabels]
                        });
                    } else {
                        newRatesCanvas.parentElement.innerHTML = '<div class="alert alert-info">No campus data available for occupancy rates.</div>';
                    }
                }
            }

            // Initial chart render
            updateCharts(campuses);

            // Campus filter for non-warefare users
            const campusSelect = document.getElementById('campusSelect');
            if (campusSelect) {
                campusSelect.addEventListener('change', function() {
                    const campusId = this.value;
                    fetch(`campus_distribution_charts.php?ajax=1${campusId ? '&campus_id=' + campusId : ''}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.error) {
                                console.error('Error fetching data:', data.error);
                                updateCharts([]);
                            } else {
                                console.log('Fetched campuses:', data);
                                updateCharts(data);
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            updateCharts([]);
                        });
                });
            }
        });
    </script>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>