<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if file and filename parameters are provided
if (!isset($_GET['file']) || !isset($_GET['filename'])) {
    die('Missing required parameters');
}

$file = $_GET['file'];
$filename = $_GET['filename'];

// Validate file path
if (!file_exists($file)) {
    die('File not found');
}

// Set headers for file download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Read and output file
readfile($file);

// Delete temporary file
unlink($file);
?> 