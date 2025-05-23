<?php
include('connection.php');

// Create the excel_mapping table
$sql = "CREATE TABLE IF NOT EXISTS excel_mapping (
    id INT PRIMARY KEY AUTO_INCREMENT,
    field_name VARCHAR(50) NOT NULL,
    column_index INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($connection->query($sql)) {
    echo "Table excel_mapping created successfully";
} else {
    echo "Error creating table: " . $connection->error;
}

$connection->close();
?> 