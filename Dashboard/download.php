<?php

session_start();

if(!isset($_SESSION['loggedin'])){
    echo"<script>window.location.href='../login.php'</script>";

}

include ('./includes/auth.php');
checkUserRole(['information_modifier']);

if (isset($_POST['export'])) {
    $host = "localhost";
    $username = "root";
    $password = ""; // Update if necessary
    $database = "hostel";
    $port = 3306;
    $backupFile = __DIR__ . "/backup_" . date("Y-m-d_H-i-s") . ".sql";
    $mysqldumpPath = "C:\\HUYE APP\\mysql\\bin\\mysqldump"; // Adjust path

    $command = "\"$mysqldumpPath\" --user=$username --password=$password --host=$host --port=$port $database > \"$backupFile\"";

    exec($command . " 2>&1", $output, $result);

    if ($result === 0 && file_exists($backupFile)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($backupFile) . '"');
        header('Content-Length: ' . filesize($backupFile));
        readfile($backupFile);
        unlink($backupFile);
        exit;
    } else {
        echo "Database backup failed.<br>";
        echo "Command executed: $command<br>";
        echo "Error code: $result<br>";
        echo "Output:<br><pre>" . implode("\n", $output) . "</pre>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./icon1.png" rel="icon">
    <link href="./icon1.png" rel="apple-touch-icon">
    <title>Export Database</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
        }
        .container {
            text-align: center;
            background: white;
            padding: 20px 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-6">
                <img src="./ur-logo.png" alt="Logo" style="height:2.3cm;width:7cm;float:left;"> 
         </div>
            <div class="col-6">
            <h3>Export Database</h3>
            </div>
        </div>
    
       
        <p>Click the button below to download the database backup as an SQL file.</p>
        <form method="post">
            <button type="submit" name="export">Download Database</button>
        </form>
    </div>
</body>
</html>
