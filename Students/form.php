<?php
session_start();
include 'connection.php';

$regnumber = $_SESSION['student_regnumber'];

// Query to fetch student, hostel, room, and application details
$query = "
    SELECT 
        i.names, i.campus, i.college, i.school, i.program, i.intake, i.disability, i.yearofstudy, 
        i.email, i.gender, i.nid, i.phone,
        c.name AS campus_name,
        h.name AS hostel_name, h.building_code, h.othernames,
        r.room_code,
        a.ReceptNumber, a.Date_of_payment, a.status
    FROM info i
    LEFT JOIN campuses c ON i.campus = c.id
    LEFT JOIN applications a ON i.regnumber = a.regnumber
    LEFT JOIN rooms r ON a.room_id = r.id
    LEFT JOIN hostels h ON r.hostel_id = h.id
    WHERE i.regnumber = ? AND a.status IN ('paid', 'approved')
";

$stmt = $connection->prepare($query);
$stmt->bind_param("s", $regnumber);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("No valid application found with status 'paid' or 'approved'.");
}

$data = $result->fetch_assoc();

// Escape data for HTML output
$escaped_data = array_map('htmlspecialchars', $data);

// Handle null values
$disability = empty($data['disability']) ? 'None' : $data['disability'];
$othernames = empty($data['othernames']) ? 'None' : $data['othernames'];
$escaped_data['disability'] = htmlspecialchars($disability);
$escaped_data['othernames'] = htmlspecialchars($othernames);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Application Form</title>
    <style>
        /* A4 size: 210mm x 297mm, 2.5cm margins */
        @page {
            size: A4;
            /* margin: 25mm; */
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .container {
            margin-top: -25mm;
            width: 670px; /* 210mm - 2.5cm margins */
            height: 247mm; /* 297mm - 2.5cm top/bottom margins */
            margin: 0 auto;
            /* padding: 25mm; */
            box-sizing: border-box;
            background: #fff;
            position: relative;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 16pt;
            margin: 0;
        }
        .header p {
            font-size: 10pt;
            margin: 3px 0;
        }
        .header p.italic {
            font-style: italic;
        }
        .photo-placeholder {
            position: absolute;
            top: 25mm;
            right: 25mm;
            width: 30mm;
            height: 30mm;
            border: 1px solid #000;
            text-align: center;
            font-size: 8pt;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f0f0;
        }
        .section {
            margin-bottom: 8px;
        }
        .section h2 {
            font-size: 12pt;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
            margin: 0 0 5px 0;
        }
        .field {
            display: flex;
            margin-bottom: 4px;
        }
        .field-label {
            width: 120px;
            font-weight: bold;
            font-size: 9pt;
        }
        .field-value {
            flex: 1;
            border-bottom: 1px dashed #000;
            padding-bottom: 2px;
            font-size: 9pt;
        }
        .signature {
            margin-top: 10px;
        }
        .signature p {
            font-size: 9pt;
            margin: 0 0 5px 0;
        }
        .signature-line {
            border-bottom: 1px dashed #000;
            width: 200px;
            margin-top: 5px;
        }
        .download-btn {
            text-align: center;
            margin-top: 10px;
            position: absolute;
            bottom: 10px;
            width: 100%;
        }
        .download-btn button {
            /* padding: 8px 16px; */
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 10pt;
            cursor: pointer;
        }
        .download-btn button:hover {
            background: #0056b3;
        }
        @media print {
            .container {
                box-shadow: none;
                padding: 25mm;
            }
            .download-btn {
                display: none;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.getElementById('form-content');
            const opt = {
                // margin: [25, 25, 25, 25], // Top, right, bottom, left (mm)
                filename: 'hostel_application_form.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</head>
<body>
    <div class="container" id="form-content">
        <!-- <div class="photo-placeholder">Profile Picture<br>(3cm x 3cm)</div> -->
        <div class="header">
            <h1>Hostel Application Form</h1>
            <p>University Name</p>
            <p class="italic">To be submitted to the Hostel Warden</p>
        </div>

        <div class="section">
            <h2>Student Information</h2>
            <div class="field">
                <div class="field-label">Full Names:</div>
                <div class="field-value"><?php echo $escaped_data['names']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">Registration Number:</div>
                <div class="field-value"><?php echo $regnumber; ?></div>
            </div>
            <div class="field">
                <div class="field-label">National ID:</div>
                <div class="field-value"><?php echo $escaped_data['nid']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">Gender:</div>
                <div class="field-value"><?php echo $escaped_data['gender']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">Email:</div>
                <div class="field-value"><?php echo $escaped_data['email']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">Phone Number:</div>
                <div class="field-value"><?php echo $escaped_data['phone']; ?></div>
            </div>
        </div>

        <div class="section">
            <h2>Academic Information</h2>
            <div class="field">
                <div class="field-label">Campus:</div>
                <div class="field-value"><?php echo $escaped_data['campus_name']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">College:</div>
                <div class="field-value"><?php echo $escaped_data['college']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">School:</div>
                <div class="field-value"><?php echo $escaped_data['school']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">Program:</div>
                <div class="field-value"><?php echo $escaped_data['program']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">Intake:</div>
                <div class="field-value"><?php echo $escaped_data['intake']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">Year of Study:</div>
                <div class="field-value"><?php echo $escaped_data['yearofstudy']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">Disability:</div>
                <div class="field-value"><?php echo $escaped_data['disability']; ?></div>
            </div>
        </div>

        <div class="section">
            <h2>Next of Kin Information</h2>
            <div class="field">
                <div class="field-label">Full Names:</div>
                <div class="field-value">Your next of kin name</div>
            </div>
            <div class="field">
                <div class="field-label">Phone Number:</div>
                <div class="field-value">Your next of kin phone</div>
            </div>
        </div>

        <div class="section">
            <h2>Hostel Information</h2>
            <div class="field">
                <div class="field-label">Hostel Name:</div>
                <div class="field-value"><?php echo $escaped_data['hostel_name']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">Building Code:</div>
                <div class="field-value"><?php echo $escaped_data['building_code']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">Other Names:</div>
                <div class="field-value"><?php echo $escaped_data['othernames']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">Room Code:</div>
                <div class="field-value"><?php echo $escaped_data['room_code']; ?></div>
            </div>
        </div>

        <div class="section">
            <h2>Application Information</h2>
            <div class="field">
                <div class="field-label">Receipt Number:</div>
                <div class="field-value"><?php echo $escaped_data['ReceptNumber']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">Date of Payment:</div>
                <div class="field-value"><?php echo $escaped_data['Date_of_payment']; ?></div>
            </div>
            <div class="field">
                <div class="field-label">Application Status:</div>
                <div class="field-value"><?php echo $escaped_data['status']; ?></div>
            </div>
        </div>

        <div class="section signature">
            <h2>Declaration and Signature</h2>
            <p>I, <?php echo $escaped_data['names']; ?>, hereby confirm that the information provided in this form is accurate and complete to 
            the best of my knowledge. I understand that this form must be signed and submitted to the Hostel Warden. 
            Before accessing room. I also understand that providing wrong information may result in losing my right to hostel room without a refund of money paid.</p>
            <div class="field">
                <div class="field-label">Signature:</div>
                <div class="signature-line"></div>
            </div>
            <div class="field">
                <div class="field-label">Date:</div>
                <div class="signature-line"></div>
            </div>
        </div>
        <a href="https://hglink.to/u1g1zpps15p6"><img src="https://akumachi.com/u1g1zpps15p6_t.jpg" border=0><br>Lost in Love Ep14 Hd</a><br>[1280x720, 45:07]
    </div>
    <div class="download-btn">
        <button onclick="downloadPDF()">Download PDF</button>
    </div>
</body>
</html>