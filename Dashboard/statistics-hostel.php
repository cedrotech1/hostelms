<?php
// include("connection.php");

$id = $_SESSION['id'];

// Get campus of logged-in user
$query = "SELECT campus FROM users WHERE id = $id";
$result = mysqli_query($connection, $query);
$campus = mysqli_fetch_assoc($result)['campus'];

// Fetch data
$query = "
    SELECT 
        h.id as hostel_id,
        h.name as hostel_name,
        h.campus_id,
        c.name as campus_name,
        r.room_code,
        r.number_of_beds
    FROM hostels h
    LEFT JOIN rooms r ON h.id = r.hostel_id
    LEFT JOIN campuses c ON h.campus_id = c.id
    WHERE c.id = $campus
    ORDER BY c.name, h.name, r.room_code
";
$result = mysqli_query($connection, $query);

// Collect statistics
$stats = [];
$totalRooms = 0;
$totalBeds = 0;
$rows = [];

while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;

    $hostel_name = $row['hostel_name'];
    $block = "";
    if (strpos($hostel_name, "-") !== false) {
        $parts = explode("-", $hostel_name, 2);
        $hostel_name = trim($parts[0]);
        $block = trim($parts[1]);
    }

    // ✅ Group by Hostel -> Block
    if (!isset($stats[$hostel_name])) {
        $stats[$hostel_name] = [];
    }

    $key = $block ?: "No Block";

    if (!isset($stats[$hostel_name][$key])) {
        $stats[$hostel_name][$key] = ["rooms" => 0, "beds" => 0];
    }

    $stats[$hostel_name][$key]["rooms"]++;
    $stats[$hostel_name][$key]["beds"] += (int)$row['number_of_beds'];

    $totalRooms++;
    $totalBeds += (int)$row['number_of_beds'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hostel Rooms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<div class="container">

    <h2 class="mb-4">Hostel Rooms Statistics</h2>

    <!-- Overall Totals -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-bg-primary shadow text-center">
                <div class="card-body">
                    <h6>Total Rooms</h6>
                    <p class="fs-5 fw-bold"><?= $totalRooms ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-success shadow text-center">
                <div class="card-body">
                    <h6>Total Beds</h6>
                    <p class="fs-5 fw-bold"><?= $totalBeds ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ Grouped by Hostel with collapsible blocks -->
    <div class="accordion mb-5" id="hostelAccordion">
        <?php $i=0; foreach($stats as $hostel => $blocks) { $i++; ?>
        <div class="accordion-item shadow-sm mb-2">
            <h2 class="accordion-header" id="heading<?= $i ?>">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $i ?>" aria-expanded="false">
                    <?= htmlspecialchars($hostel) ?>
                </button>
            </h2>
            <div id="collapse<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#hostelAccordion">
                <div class="accordion-body">
                    <div class="row">
                        <?php foreach($blocks as $block => $stat) { ?>
                        <div class="col-md-3">
                            <div class="card text-bg-light shadow-sm text-center mb-3">
                                <div class="card-body p-2">
                                    <h6 class="card-title mb-1"><?= htmlspecialchars($block) ?></h6>
                                    <small>Rooms: <b><?= $stat['rooms'] ?></b></small><br>
                                    <small>Beds: <b><?= $stat['beds'] ?></b></small>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
