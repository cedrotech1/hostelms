<?php
// session_start();
include('connection.php');
$userid=$_SESSION['id'];
$ok1 = mysqli_query($connection, "select * from users where id=$userid");
                  while ($row = mysqli_fetch_array($ok1)) {
                    $id = $row["id"];
                    $names = $row["names"];
                    $image = $row["image"];
                    $phone = $row["phone"];
                    $email = $row["email"];
                    $about = $row["about"];
                    $role = $row["role"];
                    $campus = $row["campus"];
                    
                }


// Check if user is logged in and campus is set
// if (!isset($campus)) {
//     die("Error: Campus ID not set. Please log in again.");
// }



// Verify if campus exists in database
// $verifyCampus = $connection->query("SELECT id FROM campuses WHERE id = '$campus'");
// if (!$verifyCampus || $verifyCampus->num_rows === 0) {
//     die("Error: Invalid campus ID. Please contact administrator.");
//     echo $campus;
// }

// Fetch dynamic indexes from the DB table (excel)
$indexResult = $connection->query("SELECT * FROM excel LIMIT 1");
if (!$indexResult) {
    die("Failed to fetch index mapping: " . $connection->error);
}

$indexRow = $indexResult->fetch_assoc();

$regnumberIndex = (int) $indexRow['regnumber'];
$campusIndex = (int) $indexRow['campus'];
$collegeIndex = (int) $indexRow['college'];
$sirnameIndex = (int) $indexRow['sirname'];
$lastnameIndex = (int) $indexRow['lastname'];
$schoolIndex = (int) $indexRow['school'];
$programIndex = (int) $indexRow['program'];
$yearofstudyIndex = (int) $indexRow['yearofstudy'];
$emailIndex = (int) $indexRow['email'];
$genderIndex = (int) $indexRow['gender'];
$nidIndex = (int) $indexRow['nid'];
// phone
$phoneIndex = (int) $indexRow['phone'];

// Get JSON data
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Loop through rows (assuming $data['data'] is the array of rows)
foreach ($data['data'] as $row) {
    // Ensure required fields are not empty
    if (!empty($row[$regnumberIndex]) && !empty($row[$sirnameIndex])) {
        $regnumber = $connection->real_escape_string($row[$regnumberIndex]);
        $campus1 = strtolower($connection->real_escape_string($row[$campusIndex]));
        $college = $connection->real_escape_string($row[$collegeIndex]);
        $sirname = $connection->real_escape_string($row[$sirnameIndex]);
        $lastname = $connection->real_escape_string($row[$lastnameIndex]);
        $names = "$sirname $lastname";
        $school = $connection->real_escape_string($row[$schoolIndex]);
        $program = $connection->real_escape_string($row[$programIndex]);
        $yearofstudy = $connection->real_escape_string($row[$yearofstudyIndex]);
        $email = $connection->real_escape_string($row[$emailIndex]);
        $gender = $connection->real_escape_string($row[$genderIndex]);

        // gender varidation if M or F OR Male or Female save lower case in db  and if M  make it male and if F make it female
        if ($gender === 'M') {
            $gender = 'male';
        } elseif ($gender === 'F') {
            $gender = 'female';
        } elseif ($gender === 'Male') {
            $gender = 'male';
        } elseif ($gender === 'Female') {
            $gender = 'female';
        }


        $nid = $connection->real_escape_string($row[$nidIndex]);
        $phone = $connection->real_escape_string($row[$phoneIndex]);


        // Ensure the phone number is treated as a string
        $phone = (string) $phone;

        // If number doesn't start with '0', add it
        if (isset($phone[0]) && $phone[0] !== '0') {
            $phone = '0' . $phone;
        }



        // Optional fields
        $token = ''; // You can generate one later
        $status = 'active';

        // check if campus is match with his campus
        $query = "SELECT name FROM campuses WHERE id = '$campus'";
        $result = $connection->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $campus_name = $row['name'];
            
            if ($campus1 == $campus_name) { 
                // Check if registration number already exists
                $checkQuery = "SELECT regnumber FROM info WHERE regnumber = '$regnumber'";
                $checkResult = $connection->query($checkQuery);
                
                if ($checkResult->num_rows > 0) {
                    echo "Registration number $regnumber already exists. Skipping...<br>";
                } else {
                    // Insert into database
                    $sql = "INSERT INTO info (regnumber, campus, college, names, school, program, yearofstudy, email, gender, nid,phone,token, status)
                    VALUES ('$regnumber', '$campus1', '$college', '$names', '$school', '$program', '$yearofstudy', '$email', '$gender', '$nid','$phone', '$token', '$status')";

                    if (!$connection->query($sql)) {
                        echo "SQL Error: " . $connection->error . "<br>";
                    } else {
                        echo "Data inserted for $names!<br>";
                    }
                }
            } else {
                echo "Campus mismatch for registration number $regnumber. Skipping...<br>";
            }
        } else {
            echo "Error: Campus not found for ID $campus. Skipping...<br>";
        }


    } else {
        echo "Skipped an incomplete row.<br>";
    }
}

$connection->close(); 
?>