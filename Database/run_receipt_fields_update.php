<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hostel_1';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to database successfully.<br>";

// SQL commands to add new fields
$sql_commands = [
    "ALTER TABLE `applications` ADD `ReceptNumber` VARCHAR(100) NOT NULL AFTER `slep`",
    "ALTER TABLE `applications` ADD `Date_of_payment` VARCHAR(100) NOT NULL AFTER `ReceptNumber`"
];

// Execute each SQL command
foreach ($sql_commands as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Successfully executed: " . $sql . "<br>";
    } else {
        echo "Error executing: " . $sql . "<br>";
        echo "Error: " . $conn->error . "<br>";
    }
}

echo "<br>Database update completed!";

$conn->close();
?> 