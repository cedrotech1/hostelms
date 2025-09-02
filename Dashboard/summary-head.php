<?php
// session_start();
// include('connection.php');

$id = $_SESSION['id'];

// Fetch all hostels and rooms with campus
$query = "
    SELECT 
        c.id AS campus_id,
        c.name AS campus_name,
        h.id AS hostel_id,
        h.name AS hostel_name,
        SUBSTRING_INDEX(h.name, '-', -1) AS block_name,
        r.room_code,
        r.number_of_beds
    FROM campuses c
    LEFT JOIN hostels h ON h.campus_id = c.id
    LEFT JOIN rooms r ON h.id = r.hostel_id
    ORDER BY c.name, h.name, block_name
";
$result = mysqli_query($connection, $query);

// Prepare summary grouped by campus
$campusSummary = [];
$overallRooms = 0;
$overallBeds = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $campusId = $row['campus_id'];
    $campusName = $row['campus_name'];
    $hostel = trim(explode("-", $row['hostel_name'])[0]);
    $block = $row['block_name'] ?: 'No Block';

    if (!isset($campusSummary[$campusId])) {
        $campusSummary[$campusId] = [
            'name' => $campusName,
            'hostels' => [],
            'campusBeds' => 0,
            'totalRows' => 0
        ];
    }

    if (!isset($campusSummary[$campusId]['hostels'][$hostel])) {
        $campusSummary[$campusId]['hostels'][$hostel] = [
            'blocks' => [],
            'hostelBeds' => 0
        ];
    }

    if (!isset($campusSummary[$campusId]['hostels'][$hostel]['blocks'][$block])) {
        $campusSummary[$campusId]['hostels'][$hostel]['blocks'][$block] = [
            'rooms' => 0,
            'beds' => 0
        ];
        $campusSummary[$campusId]['totalRows']++; // count row for rowspan
    }

    $beds = (int)$row['number_of_beds'];
    $campusSummary[$campusId]['hostels'][$hostel]['blocks'][$block]['rooms']++;
    $campusSummary[$campusId]['hostels'][$hostel]['blocks'][$block]['beds'] += $beds;

    $campusSummary[$campusId]['hostels'][$hostel]['hostelBeds'] += $beds;
    $campusSummary[$campusId]['campusBeds'] += $beds;

    $overallRooms++;
    $overallBeds += $beds;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hostel Summary</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body class="p-4">

<div class="container">
    <h2 class="mb-4">Hostel Summary</h2>

    <button class="btn btn-success mb-3" onclick="exportTableToExcel('summaryTable')">Export Summary to Excel</button>

    <table class="table table-bordered" id="summaryTable">
        <thead class="table-dark">
            <tr>
                <th>Campus</th>
                <th>Total Beds by Campus</th>
                <th>Hostel Name</th>
                <th>Block</th>
                <th>Number of Rooms</th>
                <th>Total Beds by Hostel</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($campusSummary as $campusId => $campus): ?>
                <?php 
                    $campusPrinted = false; 
                    foreach ($campus['hostels'] as $hostelName => $hostel):
                        $rowCount = count($hostel['blocks']);
                        $firstHostel = true;
                        foreach ($hostel['blocks'] as $blockName => $block):
                ?>
                    <tr>
                        <?php if (!$campusPrinted): ?>
                            <td rowspan="<?= $campus['totalRows'] ?>"><?= strtoupper(htmlspecialchars($campus['name'])) ?></td>
                            <td rowspan="<?= $campus['totalRows'] ?>"><?= $campus['campusBeds'] ?></td>
                            <?php $campusPrinted = true; ?>
                        <?php endif; ?>

                        <?php if ($firstHostel): ?>
                            <td rowspan="<?= $rowCount ?>"><?= htmlspecialchars($hostelName) ?></td>
                        <?php endif; ?>

                        <td><?= htmlspecialchars($blockName) ?></td>
                        <td><?= $block['rooms'] ?></td>

                        <?php if ($firstHostel): ?>
                            <td rowspan="<?= $rowCount ?>"><?= $hostel['hostelBeds'] ?></td>
                            <?php $firstHostel = false; ?>
                        <?php endif; ?>
                    </tr>
                <?php 
                        endforeach; 
                    endforeach; 
                endforeach; 
                ?>

            <!-- Overall Total -->
            <tr class="table-primary">
                <td colspan="4"><strong>Overall Total</strong></td>
                <td><strong><?= $overallRooms ?></strong></td>
                <td><strong><?= $overallBeds ?></strong></td>
            </tr>
        </tbody>
    </table>
</div>

<script>
function exportTableToExcel(tableID) {
    var table = document.getElementById(tableID);
    var wb = XLSX.utils.table_to_book(table, {sheet:"Summary"});
    XLSX.writeFile(wb, "hostel_summary.xlsx");
}
</script>
</body>
</html>
