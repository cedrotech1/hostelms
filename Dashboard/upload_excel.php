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
$phoneIndex       = (int)$indexRow['phone'];

// Get JSON data
$input = file_get_contents("php://input");
$data = json_decode($input, true);

foreach ($data['data'] as $row) {
    // Ensure required fields are not empty
    if (!empty($row[$regnumberIndex]) && !empty($row[$sirnameIndex])) {
        $regnumberRaw = $row[$regnumberIndex];
        $regnumber = strtolower(trim($connection->real_escape_string($regnumberRaw)));

        // Check if regnumber already exists (case-insensitive)
        $check = $connection->query("SELECT id FROM info WHERE LOWER(TRIM(regnumber)) = '$regnumber'");
        if ($check && $check->num_rows > 0) {
            echo "Skipped duplicate regnumber $regnumber.<br>";
            continue;
        }

        $campusInput = strtolower(trim($connection->real_escape_string($row[$campusIndex])));

        // Check if campus name exists (case-insensitive match)
        $campusResult = $connection->query("SELECT id FROM campuses WHERE LOWER(TRIM(name)) = '$campusInput'");
        if (!$campusResult || $campusResult->num_rows === 0) {
            echo "Campus '$campusInput' does not exist. Skipping...<br>";
            continue;
        }

        // Continue preparing data
        $college     = $connection->real_escape_string($row[$collegeIndex]);
        $sirname     = $connection->real_escape_string($row[$sirnameIndex]);
        $lastname    = $connection->real_escape_string($row[$lastnameIndex]);
        $names       = "$sirname $lastname";
        $school      = $connection->real_escape_string($row[$schoolIndex]);
        $program     = $connection->real_escape_string($row[$programIndex]);
        $yearofstudy = $connection->real_escape_string($row[$yearofstudyIndex]);
        $email       = $connection->real_escape_string($row[$emailIndex]);
        $genderRaw   = strtolower(trim($connection->real_escape_string($row[$genderIndex])));

        // Normalize gender
        $gender = ($genderRaw === 'm' || $genderRaw === 'male') ? 'male' :
                  (($genderRaw === 'f' || $genderRaw === 'female') ? 'female' : '');

        $nid         = $connection->real_escape_string($row[$nidIndex]);
        $phone       = preg_replace('/\D/', '', $row[$phoneIndex]); // keep digits only
        if (isset($phone[0]) && $phone[0] !== '0') {
            $phone = '0' . $phone;
        }

        // Optional fields
        $token  = ''; // You can generate or set token later
        $status = 'active';

        // Insert into database
        $sql = "INSERT INTO info (
                    regnumber, campus, college, names, school, program,
                    yearofstudy, email, gender, nid, phone, token, status
                ) VALUES (
                    '$regnumber', '$campusInput', '$college', '$names', '$school',
                    '$program', '$yearofstudy', '$email', '$gender', '$nid', '$phone', '$token', '$status'
                )";

        if (!$connection->query($sql)) {
            echo "SQL Error for $regnumber: " . $connection->error . "<br>";
        } else {
            echo "Inserted: $names ($regnumber)<br>";
        }
    } else {
        echo "Skipped row: Missing required fields.<br>";
    }
}

$connection->close();
?>
