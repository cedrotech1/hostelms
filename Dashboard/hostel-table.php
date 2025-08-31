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


?>

<!DOCTYPE html>
<html>
<head>
    <title>Hostel Rooms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
</head>
<body class="p-4">

<div class="container">

    

    <h2 class="mb-4">Hostel Rooms</h2>
    <table id="hostelTable" class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Campus</th>
                <th>Hostel Name</th>
                <th>Hostel Block</th>
                <th>Room Code</th>
                <th>Number of Beds</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rows as $row) { 
                $hostel_name = $row['hostel_name'];
                $block = "";
                if (strpos($hostel_name, "-") !== false) {
                    $parts = explode("-", $hostel_name, 2);
                    $hostel_name = trim($parts[0]);
                    $block = trim($parts[1]);
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($row['campus_name']) ?></td>
                <td><?= htmlspecialchars($hostel_name) ?></td>
                <td><?= htmlspecialchars($block) ?></td>
                <td><?= htmlspecialchars($row['room_code']) ?></td>
                <td><?= htmlspecialchars($row['number_of_beds']) ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- JS libraries -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    $('#hostelTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Export Excel',
                title: 'Hostel_Rooms',
                exportOptions: { modifier: { page: 'all' } }
            }
        ],
        pageLength: 10
    });
});
</script>

</body> 
</html> 