<?php
require_once 'connection.php';

// Fetch campuses
$campusQuery = "SELECT id, name FROM campuses";
$campusResult = mysqli_query($connection, $campusQuery);

$campusData = [];

while ($campus = mysqli_fetch_assoc($campusResult)) {
    $campusId = $campus['id'];

    // Fetch hostels in campus
    $hostelQuery = "SELECT id, name FROM hostels WHERE campus_id = $campusId";
    $hostelResult = mysqli_query($connection, $hostelQuery);
    $hostels = [];
    $totalBeds = 0;
    $occupiedBeds = 0;
    $pendingBeds = 0;
    $remainingBeds = 0;

    while ($hostel = mysqli_fetch_assoc($hostelResult)) {
        $hostelId = $hostel['id'];

        // Fetch rooms
        $roomQuery = "SELECT id, number_of_beds FROM rooms WHERE hostel_id = $hostelId";
        $roomResult = mysqli_query($connection, $roomQuery);
        foreach ($roomResult as $room) {
            $totalBeds += (int)$room['number_of_beds'];

            // Applications for room
            $appQuery = "SELECT status FROM applications WHERE room_id = ".$room['id'];
            $appResult = mysqli_query($connection, $appQuery);
            foreach ($appResult as $app) {
                if ($app['status'] == 'paid' || $app['status'] == 'approved') $occupiedBeds++;
                if ($app['status'] == 'pending') $pendingBeds++;
            }
        }
    }

    $remainingBeds = $totalBeds - $occupiedBeds;

    $campusData[] = [
        'id' => $campusId,
        'name' => $campus['name'],
        'totalBeds' => $totalBeds,
        'occupiedBeds' => $occupiedBeds,
        'pendingBeds' => $pendingBeds,
        'remainingBeds' => $remainingBeds
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Campus Bed & Application Statistics</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<style>
body { background: #f8f9fa; }
.chart-container { position: relative; }
.title-text {
    background-color: rgb(23, 45, 80);
    padding: 10px;
    color: white !important;
    border-radius: 10px;
}
</style>
</head>
<body class="p-4">

<div class="container">
    <h2 class="mb-4 title-text">Campus Bed & Application Statistics</h2>

    <!-- Campus Summary Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Campus</th>
                    <th>Total Beds</th>
                    <th>Occupied (Paid/Approved)</th>
                    <th>Pending</th>
                    <th>Remaining</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campusData as $campus): ?>
                    <tr>
                        <td><?= htmlspecialchars($campus['name']) ?></td>
                        <td><?= $campus['totalBeds'] ?></td>
                        <td><?= $campus['occupiedBeds'] ?></td>
                        <td><?= $campus['pendingBeds'] ?></td>
                        <td><?= $campus['remainingBeds'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Campus Filter -->
    <div class="mb-3 d-flex align-items-center">
        <label for="campusFilter" class="form-label me-2"><b>Filter by Campus:</b></label>
        <select class="form-select w-auto" id="campusFilter">
            <option value="all">All Campuses</option>
            <?php foreach ($campusData as $campus): ?>
                <option value="<?= $campus['id'] ?>"><?= strtoupper(htmlspecialchars($campus['name'])) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-primary ms-3" id="downloadChart">Download Chart as PDF</button>
        <button class="btn btn-outline-success ms-2" id="downloadImage">Download Chart as Image</button>
    </div>

    <!-- Chart -->
    <div class="chart-container">
        <canvas id="bedChart"></canvas>
    </div>
</div>

<script>
const campusData = <?= json_encode($campusData) ?>;

// Helper: calculate percentage
function calculatePercent(value, total) {
    return total ? ((value / total) * 100).toFixed(1) + '%' : '0%';
}

// Filter out datasets with zero values
function filterZeros(data) {
    return data.map(c => c > 0 ? c : null);
}

const ctx = document.getElementById('bedChart').getContext('2d');

let bedChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: campusData.map(c => c.name),
        datasets: [
            { label: 'Total Beds', data: filterZeros(campusData.map(c => c.totalBeds)), backgroundColor: 'rgba(54, 162, 235, 0.6)' },
            { label: 'Occupied', data: filterZeros(campusData.map(c => c.occupiedBeds)), backgroundColor: 'rgba(255, 99, 132, 0.6)' },
            { label: 'Pending', data: filterZeros(campusData.map(c => c.pendingBeds)), backgroundColor: 'rgba(255, 206, 86, 0.6)' },
            { label: 'Remaining', data: filterZeros(campusData.map(c => c.remainingBeds)), backgroundColor: 'rgba(75, 192, 192, 0.6)' },
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: 'Campus Bed & Application Overview' },
            datalabels: {
                color: '#000',
                anchor: 'end',
                align: 'end',
                font: { weight: 'bold' },
                formatter: (value, context) => {
                    if(value === null) return '';
                    const total = campusData[context.dataIndex].totalBeds;
                    const percentage = calculatePercent(value, total);
                    return `${value} (${percentage})`;
                }
            }
        },
        scales: { y: { beginAtZero: true } }
    },
    plugins: [ChartDataLabels]
});

// Campus filter
document.getElementById('campusFilter').addEventListener('change', function() {
    const val = this.value;
    let filtered = val === 'all' ? campusData : campusData.filter(c => c.id == val);

    bedChart.data.labels = filtered.map(c => c.name);
    bedChart.data.datasets[0].data = filterZeros(filtered.map(c => c.totalBeds));
    bedChart.data.datasets[1].data = filterZeros(filtered.map(c => c.occupiedBeds));
    bedChart.data.datasets[2].data = filterZeros(filtered.map(c => c.pendingBeds));
    bedChart.data.datasets[3].data = filterZeros(filtered.map(c => c.remainingBeds));
    bedChart.update();
});

// Download Chart as PDF
document.getElementById('downloadChart').addEventListener('click', function() {
    html2canvas(document.querySelector('#bedChart')).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('landscape');
        pdf.addImage(imgData, 'PNG', 10, 10, 280, 150);
        pdf.save('campus_statistics.pdf');
    });
});

// Download Chart as Image
document.getElementById('downloadImage').addEventListener('click', function() {
    const link = document.createElement('a');
    link.href = bedChart.toBase64Image();
    link.download = 'campus_statistics.png';
    link.click();
});
</script>

</body>
</html>
