<?php
include('../connection.php');
$hostel_id = intval($_GET['hostel_id']);
$rooms = [];
$sql = "SELECT id, room_code FROM rooms WHERE hostel_id = ? AND status = 'reserved' AND remain > 0";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $hostel_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}
header('Content-Type: application/json');
echo json_encode($rooms); 