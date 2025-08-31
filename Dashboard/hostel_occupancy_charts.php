<?php


// Get user's role and ID
$userRole = $_SESSION['role'];
$userId = $_SESSION['id'];

// Handle AJAX request for hostel data
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

    // Get hostel-level statistics
    $hostels = [];
    $hostelCondition = "";
    $params = [];
    if ($userRole === 'warefare') {
        $hostelCondition = "WHERE h.campus_id = ?";
        $params[] = $userCampus;
    } elseif (isset($_GET['campus_id']) && !empty($_GET['campus_id'])) {
        $campusId = (int)$_GET['campus_id'];
        $hostelCondition = "WHERE h.campus_id = ?";
        $params[] = $campusId;
    }

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
        SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) AS approved_applications
    FROM hostels h
    LEFT JOIN campuses c ON h.campus_id = c.id
    LEFT JOIN rooms r ON r.hostel_id = h.id
    LEFT JOIN applications a ON a.room_id = r.id
    $hostelCondition
    GROUP BY h.id, h.name, c.name
    ORDER BY c.name, h.name";

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
            $hostels[] = $row;
        }
        echo json_encode($hostels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    } else {
        error_log("Hostel query failed: " . $connection->error);
        echo json_encode(['error' => 'Query failed']);
    }
    // $stmt->close();
    // $connection->close();
    // exit();
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
$hostels = [];
$campuses = [];

// Fetch all campuses for non-warefare users (for dropdown)
if ($userRole !== 'warefare') {
    $campusQuery = "SELECT id, name FROM campuses ORDER BY name";
    $campusResult = $connection->query($campusQuery);
    if ($campusResult) {
        while ($row = $campusResult->fetch_assoc()) {
            $campuses[] = $row;
        }
    } else {
        error_log("Campus query failed: " . $connection->error);
    }
}

// Get initial hostel-level statistics
$hostelCondition = "";
$params = [];
if ($userRole === 'warefare') {
    $hostelCondition = "WHERE h.campus_id = ?";
    $params[] = $userCampus;
} elseif (isset($_GET['campus_id']) && !empty($_GET['campus_id'])) {
    $campusId = (int)$_GET['campus_id'];
    $hostelCondition = "WHERE h.campus_id = ?";
    $params[] = $campusId;
}

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
    SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) AS approved_applications
FROM hostels h
LEFT JOIN campuses c ON h.campus_id = c.id
LEFT JOIN rooms r ON r.hostel_id = h.id
LEFT JOIN applications a ON a.room_id = r.id
$hostelCondition
GROUP BY h.id, h.name, c.name
ORDER BY c.name, h.name";

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
        $hostels[] = $row;
    }
} else {
    error_log("Hostel query failed: " . $connection->error);
}
// $stmt->close();
// $connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Occupancy Charts - UR-HOSTELS</title>
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
    </style>
</head>
<body class="bg-light">
    <!-- <main id="main" class="main"> -->
        <section class="section dashboard">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Hostel Occupancy Charts</h2>
                  
                </div>

                <div class="row">
                    <!-- Hostel Beds Occupancy Numbers Chart -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Hostel Beds Occupancy Numbers</h5>
                                <div class="chart-container">
                                    <canvas id="hostelOccupancyNumbersChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hostel Beds Occupancy Rates Chart -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Hostel Beds Occupancy Rates</h5>
                                <div class="chart-container">
                                    <canvas id="hostelOccupancyRatesChart"></canvas>
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
            let hostels = <?php echo json_encode($hostels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            console.log('Initial hostels:', hostels);
            console.log('hostels type:', typeof hostels);
            console.log('Is hostels an array?', Array.isArray(hostels));

            // Chart instances to allow destruction and recreation
            let numbersChart = null;
            let ratesChart = null;

            // Function to update charts
            function updateCharts(hostelsData) {
                // Destroy existing charts
                if (numbersChart) {
                    numbersChart.destroy();
                }
                if (ratesChart) {
                    ratesChart.destroy();
                }

                // Hostel Beds Occupancy Numbers Chart (Stacked Bar)
                const numbersCanvas = document.getElementById('hostelOccupancyNumbersChart');
                if (numbersCanvas) {
                    // Clear previous content
                    numbersCanvas.parentElement.innerHTML = '<canvas id="hostelOccupancyNumbersChart"></canvas>';
                    const newNumbersCanvas = document.getElementById('hostelOccupancyNumbersChart');
                    if (Array.isArray(hostelsData) && hostelsData.length > 0) {
                        numbersChart = new Chart(newNumbersCanvas, {
                            type: 'bar',
                            data: {
                                labels: hostelsData.map(h => h.hostel_name + ' (' + h.campus_name + ')'),
                                datasets: [
                                    {
                                        label: 'Occupied Beds',
                                        data: hostelsData.map(h => h.occupied_beds || 0),
                                        backgroundColor: '#dc3545',
                                        borderColor: '#a71d2a',
                                        borderWidth: 1
                                    },
                                    {
                                        label: 'Available Beds',
                                        data: hostelsData.map(h => h.available_beds || 0),
                                        backgroundColor: '#28a745',
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
                        newNumbersCanvas.parentElement.innerHTML = '<div class="alert alert-info">No hostel data available for occupancy numbers.</div>';
                    }
                }

                // Hostel Beds Occupancy Rates Chart (Horizontal Bar with Color Coding)
                const ratesCanvas = document.getElementById('hostelOccupancyRatesChart');
                if (ratesCanvas) {
                    // Clear previous content
                    ratesCanvas.parentElement.innerHTML = '<canvas id="hostelOccupancyRatesChart"></canvas>';
                    const newRatesCanvas = document.getElementById('hostelOccupancyRatesChart');
                    if (Array.isArray(hostelsData) && hostelsData.length > 0) {
                        ratesChart = new Chart(newRatesCanvas, {
                            type: 'bar',
                            data: {
                                labels: hostelsData.map(h => h.hostel_name + ' (' + h.campus_name + ')'),
                                datasets: [{
                                    label: 'Occupancy Rate (%)',
                                    data: hostelsData.map(h => Math.round(h.occupancy_rate || 0)),
                                    backgroundColor: hostelsData.map(h => {
                                        const rate = h.occupancy_rate || 0;
                                        return rate >= 90 ? '#dc3545' : (rate >= 70 ? '#ffc107' : '#28a745');
                                    }),
                                    borderColor: hostelsData.map(h => {
                                        const rate = h.occupancy_rate || 0;
                                        return rate >= 90 ? '#a71d2a' : (rate >= 70 ? '#d39e00' : '#1e7e34');
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
                        newRatesCanvas.parentElement.innerHTML = '<div class="alert alert-info">No hostel data available for occupancy rates.</div>';
                    }
                }
            }

            // Initial chart render
            updateCharts(hostels);

            // Campus filter for non-warefare users
            const campusSelect = document.getElementById('campusSelect');
            if (campusSelect) {
                campusSelect.addEventListener('change', function() {
                    const campusId = this.value;
                    fetch(`hostel_occupancy_charts.php?ajax=1${campusId ? '&campus_id=' + campusId : ''}`)
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
                                console.log('Fetched hostels:', data);
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