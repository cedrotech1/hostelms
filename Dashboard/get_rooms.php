<?php
include('connection.php');

$hostel_id = isset($_GET['hostel_id']) ? (int)$_GET['hostel_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

if ($hostel_id <= 0) {
    echo '<tr><td colspan="4" class="text-center">Invalid hostel ID</td></tr>';
    exit;
}

// Calculate offset
$offset = ($page - 1) * $per_page;

// Build the query
$query = "SELECT * FROM rooms WHERE hostel_id = $hostel_id";
if (!empty($search)) {
    $query .= " AND room_code LIKE '%$search%'";
}
$query .= " ORDER BY CAST(SUBSTRING(room_code, 2) AS UNSIGNED) LIMIT $offset, $per_page";

$result = mysqli_query($connection, $query);

if (!$result) {
    echo '<tr><td colspan="4" class="text-center">Error fetching rooms: ' . mysqli_error($connection) . '</td></tr>';
    exit;
}

if (mysqli_num_rows($result) === 0) {
    echo '<tr><td colspan="4" class="text-center">No rooms found</td></tr>';
    exit;
}

// Output table header
echo '<table class="table table-hover table-striped">
        <thead class="table-light">
            <tr>
                <th>Room Code</th>
                <th>Number of Beds</th>
                <th>Remaining Beds</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>';

while ($room = mysqli_fetch_assoc($result)) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($room['room_code']) . '</td>';
    echo '<td>' . $room['number_of_beds'] . '</td>';
    echo '<td>' . $room['remain'] . '</td>';
    echo '<td class="text-center">';
    echo '<div class="btn-group" role="group">';
    echo '<button class="btn btn-sm btn-primary" onclick="editRoom(' . $room['id'] . ', \'' . htmlspecialchars($room['room_code']) . '\', ' . $room['number_of_beds'] . ', ' . $room['hostel_id'] . ')">';
    echo '<i class="bi bi-pencil-square"></i>';
    echo '</button>';
    echo '<button class="btn btn-sm btn-danger" onclick="deleteRoom(' . $room['id'] . ')">';
    echo '<i class="bi bi-trash"></i>';
    echo '</button>';
    echo '</div>';
    echo '</td>';
    echo '</tr>';
}

echo '</tbody></table>';
?> 