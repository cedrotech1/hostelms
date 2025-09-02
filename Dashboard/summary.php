<?php
// session_start();
// include('connection.php');

$id = $_SESSION['id'];

// Get campus of logged-in user
$query = "SELECT campus FROM users WHERE id = $id";
$result = mysqli_query($connection, $query);
$campus = mysqli_fetch_assoc($result)['campus'];

// Fetch hostel and room data
$query = "
    SELECT 
        h.id as hostel_id,
        h.name as hostel_name,
        SUBSTRING_INDEX(h.name, '-', -1) AS block_name,
        r.room_code,
        r.number_of_beds
    FROM hostels h
    LEFT JOIN rooms r ON h.id = r.hostel_id
    LEFT JOIN campuses c ON h.campus_id = c.id
    WHERE c.id = $campus
    ORDER BY h.name, block_name
";
$result = mysqli_query($connection, $query);

// Prepare summary statistics
$summary = [];
$hostelTotals = [];
$totalRooms = 0;
$totalBeds = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $hostel = trim(explode("-", $row['hostel_name'])[0]);
    $block = $row['block_name'] ?: 'No Block';
    
    if (!isset($summary[$hostel])) $summary[$hostel] = [];
    if (!isset($summary[$hostel][$block])) $summary[$hostel][$block] = ['rooms' => 0, 'beds' => 0];

    $summary[$hostel][$block]['rooms']++;
    $summary[$hostel][$block]['beds'] += (int)$row['number_of_beds'];

    if (!isset($hostelTotals[$hostel])) $hostelTotals[$hostel] = 0;
    $hostelTotals[$hostel] += (int)$row['number_of_beds'];

    $totalRooms++;
    $totalBeds += (int)$row['number_of_beds'];
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
                <th>Hostel Name</th>
                <th>Block</th>
                <th>Number of Rooms</th>
                <th>Total Beds by Hostel</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($summary as $hostel => $blocks): ?>
                <?php 
                $rowCount = count($blocks); // number of blocks for rowspan
                $first = true; 
                ?>
                <?php foreach ($blocks as $block => $data): ?>
                    <tr>
                        <?php if ($first): ?>
                            <td rowspan="<?= $rowCount ?>"><?= htmlspecialchars($hostel) ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($block) ?></td>
                        <td><?= $data['rooms'] ?></td>
                        <?php if ($first): ?>
                            <td rowspan="<?= $rowCount ?>"><?= $hostelTotals[$hostel] ?></td>
                            <?php $first = false; ?>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <!-- Overall statistics -->
            <tr class="table-primary">
                <td colspan="2"><strong>Overall Total</strong></td>
                <td><strong><?= $totalRooms ?></strong></td>
                <td><strong><?= $totalBeds ?></strong></td>
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