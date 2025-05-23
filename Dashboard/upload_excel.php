<?php
include('connection.php');

// Fetch dynamic indexes from the DB table (excel)
$indexResult = $connection->query("SELECT * FROM excel LIMIT 1");
if (!$indexResult) {
    die("Failed to fetch index mapping: " . $connection->error);
}

$indexRow = $indexResult->fetch_assoc();

$regnumberIndex   = (int)$indexRow['regnumber'];
$campusIndex      = (int)$indexRow['campus'];
$collegeIndex     = (int)$indexRow['college'];
$sirnameIndex     = (int)$indexRow['sirname'];
$lastnameIndex    = (int)$indexRow['lastname'];
$schoolIndex      = (int)$indexRow['school'];
$programIndex     = (int)$indexRow['program'];
$yearofstudyIndex = (int)$indexRow['yearofstudy'];
$emailIndex       = (int)$indexRow['email'];
$genderIndex      = (int)$indexRow['gender'];
$nidIndex         = (int)$indexRow['nid'];
// phone
$phoneIndex     = (int)$indexRow['phone'];

// Get JSON data
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Loop through rows (assuming $data['data'] is the array of rows)
foreach ($data['data'] as $row) {
    // Ensure required fields are not empty
    if (!empty($row[$regnumberIndex]) && !empty($row[$sirnameIndex])) {
        $regnumber   = $connection->real_escape_string($row[$regnumberIndex]);
        $campus      = $connection->real_escape_string($row[$campusIndex]);
        $college     = $connection->real_escape_string($row[$collegeIndex]);
        $sirname     = $connection->real_escape_string($row[$sirnameIndex]);
        $lastname    = $connection->real_escape_string($row[$lastnameIndex]);
        $names       = "$sirname $lastname";
        $school      = $connection->real_escape_string($row[$schoolIndex]);
        $program     = $connection->real_escape_string($row[$programIndex]);
        $yearofstudy = $connection->real_escape_string($row[$yearofstudyIndex]);
        $email       = $connection->real_escape_string($row[$emailIndex]);
        $gender      = $connection->real_escape_string($row[$genderIndex]);

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
        
        




        $nid         = $connection->real_escape_string($row[$nidIndex]);
        $phone       = $connection->real_escape_string($row[$phoneIndex]);

 
        // Ensure the phone number is treated as a string
        $phone = (string) $phone;
        
        // If number doesn't start with '0', add it
        if (isset($phone[0]) && $phone[0] !== '0') {
            $phone = '0' . $phone;
        }
        


        // Optional fields
        $token  = ''; // You can generate one later
        $status = 'active';

        // Insert into database
        $sql = "INSERT INTO info (regnumber, campus, college, names, school, program, yearofstudy, email, gender, nid,phone,token, status)
                VALUES ('$regnumber', '$campus', '$college', '$names', '$school', '$program', '$yearofstudy', '$email', '$gender', '$nid','$phone', '$token', '$status')";

        if (!$connection->query($sql)) {
            echo "SQL Error: " . $connection->error . "<br>";
        } else {
            echo "Data inserted for $names!<br>";
        }
    } else {
        echo "Skipped an incomplete row.<br>";
    }
}

$connection->close();
?>
