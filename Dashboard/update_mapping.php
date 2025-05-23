<?php
include('connection.php');

// First, clear existing mapping
$clear_sql = "TRUNCATE TABLE excel_mapping";
$connection->query($clear_sql);

// Define the mapping values
$mappings = [
    ['field_name' => 'regnumber', 'column_index' => 1],
    ['field_name' => 'campus', 'column_index' => 2],
    ['field_name' => 'college', 'column_index' => 3],
    ['field_name' => 'names', 'column_index' => 4],
    ['field_name' => 'school', 'column_index' => 5],
    ['field_name' => 'program', 'column_index' => 6],
    ['field_name' => 'yearofstudy', 'column_index' => 7],
    ['field_name' => 'email', 'column_index' => 8],
    ['field_name' => 'gender', 'column_index' => 9],
    ['field_name' => 'nid', 'column_index' => 10]
];

// Insert the mappings
$sql = "INSERT INTO excel_mapping (field_name, column_index) VALUES (?, ?)";
$stmt = $connection->prepare($sql);

foreach ($mappings as $mapping) {
    $stmt->bind_param("si", $mapping['field_name'], $mapping['column_index']);
    $stmt->execute();
}

$stmt->close();

echo "Mapping values updated successfully!";

$connection->close();
?> 