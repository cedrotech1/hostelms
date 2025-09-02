<?php
require_once 'connection.php';

// Fetch campus stats
$query = "
    SELECT 
        c.id AS campus_id,
        c.name AS campus_name,
        COUNT(DISTINCT h.id) AS total_hostels,
        COUNT(DISTINCT r.id) AS total_rooms,
        COALESCE(SUM(r.number_of_beds), 0) AS total_beds
    FROM campuses c
    LEFT JOIN hostels h ON h.campus_id = c.id
    LEFT JOIN rooms r ON r.hostel_id = h.id
    GROUP BY c.id, c.name
";

$result = mysqli_query($connection, $query);

$campusStats = [];
while ($row = mysqli_fetch_assoc($result)) {
    $campusStats[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Campus Statistics</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom"></script>
  <!-- <script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.9.2/dist/html2pdf.bundle.min.js"></script> -->
   <style>
    .accordion-button {
        font-weight: bold;
    }
    .title-text {
            background-color: rgb(23, 45, 80);
            padding: 10px;
            color: white !important;
            border-radius: 10px;
        }
   </style>
</head>
<body class="bg-light">
<div class="container py-4">

  <h2  class="mb-4 title-text">Campus Hostels Statistics</h2>

  <!-- Statistics Cards -->
  <div class="accordion mb-4" id="campusAccordion">
    <?php foreach ($campusStats as $index => $campus): ?>
      <div class="accordion-item">
        <h2 class="accordion-header" id="heading<?= $index ?>">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
              <?= strtoupper(htmlspecialchars($campus['campus_name']))  ?>
          </button>
        </h2>
        <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#campusAccordion">
          <div class="accordion-body">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="card shadow-sm text-center">
                  <div class="card-body">
                    <h6>Total Hostels</h6>
                    <h4><?= $campus['total_hostels'] ?></h4>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card shadow-sm text-center">
                  <div class="card-body">
                    <h6>Total Rooms</h6>
                    <h4><?= $campus['total_rooms'] ?></h4>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card shadow-sm text-center">
                  <div class="card-body">
                    <h6>Total Beds</h6>
                    <h4><?= $campus['total_beds'] ?></h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Chart -->
  <div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Campus Comparison Chart</span>
      <div>
        <select id="campusFilter" class="form-select form-select-sm d-inline-block w-auto">
          <option value="all">All Campuses</option>
          <?php foreach ($campusStats as $campus): ?>
            <option value="<?= $campus['campus_name'] ?>"><?= $campus['campus_name'] ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-outline-primary" onclick="downloadChart('png')">Download PNG</button>
        <!-- <button class="btn btn-sm b    tn-outline-secondary" onclick="downloadChart('pdf')">Download PDF</button> -->
      </div>
    </div>
    <div class="card-body">
      <canvas id="campusChart" height="100"></canvas>
    </div>
  </div>

</div>

<script>
const campusData = <?php echo json_encode($campusStats); ?>;

const ctx = document.getElementById('campusChart').getContext('2d');
let chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: campusData.map(c => c.campus_name),
        datasets: [
            { label: 'Hostels', data: campusData.map(c => c.total_hostels), backgroundColor: '#0d6efd' },
            { label: 'Rooms', data: campusData.map(c => c.total_rooms), backgroundColor: '#198754' },
            { label: 'Beds', data: campusData.map(c => c.total_beds), backgroundColor: '#ffc107' }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            datalabels: { anchor: 'end', align: 'top' }
        }
    },
    plugins: [ChartDataLabels]
});

// Campus Filter
document.getElementById('campusFilter').addEventListener('change', function() {
    const value = this.value;
    if (value === 'all') {
        chart.data.labels = campusData.map(c => c.campus_name);
        chart.data.datasets[0].data = campusData.map(c => c.total_hostels);
        chart.data.datasets[1].data = campusData.map(c => c.total_rooms);
        chart.data.datasets[2].data = campusData.map(c => c.total_beds);
    } else {
        const selected = campusData.find(c => c.campus_name === value);
        chart.data.labels = [selected.campus_name];
        chart.data.datasets[0].data = [selected.total_hostels];
        chart.data.datasets[1].data = [selected.total_rooms];
        chart.data.datasets[2].data = [selected.total_beds];
    }
    chart.update();
});

// Download Chart
function downloadChart(type) {
    if (type === 'png') {
        const link = document.createElement('a');
        link.href = chart.toBase64Image();
        link.download = 'campus_chart.png';
        link.click();
    } else if (type === 'pdf') {
        const element = document.getElementById('campusChart');
        html2pdf().from(element).save('campus_chart.pdf');
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
