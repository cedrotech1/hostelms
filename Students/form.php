<?php
session_start();
include 'connection.php';

// Handle PDF generation if requested
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Include the print template which will handle its own data fetching
    include 'print_template.php';
    exit();
}

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
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .container {
            width: 100%;
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 5mm 15mm 10mm 15mm;
            box-sizing: border-box;
            background: #fff;
            position: relative;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            text-align: left;
            margin: 0 0 8px 0;
            padding: 0;
        }
        .header h1 {
            font-size: 13pt;
            margin: 0 0 3px 0;
            line-height: 1.1;
        }
        .header p {
            font-size: 9pt;
            margin: 2px 0;
            line-height: 1.1;
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
            margin-bottom: 5px;
            page-break-inside: avoid;
        }
        .section h2 {
            font-size: 11pt;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin: 2px 0 3px 0;
        }
        .field {
            display: flex;
            margin-bottom: 2px;
            font-size: 8.5pt;
        }
        .field-label {
            width: 90px;
            min-width: 90px;
            font-weight: bold;
            font-size: 8pt;
        }
        .field-value {
            flex: 1;
            border-bottom: 1px dashed #000;
            padding-bottom: 0;
            font-size: 8pt;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.1;
            padding-top: 1px;
        }
        .signature {
            margin-top: 5px;
            font-size: 8.5pt;
        }
        .signature p {
            font-size: 8pt;
            margin: 0 0 2px 0;
            line-height: 1.1;
        }
        .signature-line {
            border-bottom: 1px dashed #000;
            width: 150px;
            margin-top: 3px;
            display: inline-block;
        }
        .download-btn {
            text-align: center;
            margin: 10px 0 0 0;
            position: fixed;
            bottom: 15px;
            right: 15px;
            z-index: 1000;
        }
        .download-btn .download-link {
            display: inline-block;
            padding: 12px 24px !important;
            background: #007bff !important;
            color: #fff !important;
            border: none !important;
            border-radius: 4px !important;
            font-size: 14px !important;
            font-weight: bold !important;
            text-decoration: none !important;
            cursor: pointer !important;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
            transition: all 0.3s ease !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }
        
        .download-btn .download-link:hover,
        .download-btn .download-link:active {
            background: #0056b3 !important;
            transform: translateY(-1px);
        }

        .download-btn button:active {
            transform: scale(0.98);
            background: #0056b3;
        }
        .download-btn button:hover {
            background: #0056b3;
        }
        @media print {
            body {
                padding: 0 !important;
                margin: 0 !important;
            }
            .container {
                box-shadow: none;
                padding: 0 10mm !important;
                margin: 0 !important;
                width: 100% !important;
                height: 100% !important;
                max-height: 297mm !important;
                font-size: 8pt;
            }
            .download-btn {
                display: none !important;
            }
            @page {
                margin: 0 !important;
                padding: 0 !important;
                size: A4 portrait;
            }
            .section {
                margin-bottom: 3px;
            }
            .field {
                margin-bottom: 1px;
            }
        }
        @media (max-width: 768px) {
            .container {
                padding: 5mm 8mm !important;
                width: 100% !important;
                height: auto !important;
                min-height: 100vh !important;
                box-shadow: none;
            }
            .download-btn {
                position: fixed;
                bottom: 10px;
                right: 10px;
            }
            .download-btn button {
                padding: 8px 16px;
                font-size: 14px;
                width: auto;
                border-radius: 20px;
            }
        }
        .header img {
            width: 40px;
            height: 40px;
            margin: 0 0 3px 0;
        }
    </style>
    <script>
        function downloadPDF(event) {
            if (event) {
                event.preventDefault();
            }
            
            const button = event ? event.target : document.querySelector('.download-btn a');
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = 'Generating PDF...';
            button.style.pointerEvents = 'none';
            
            // Open in a new tab to avoid iframe issues
            const newWindow = window.open(button.href, '_blank');
            
            // Reset button after a short delay
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.pointerEvents = 'auto';
            }, 2000);
            
            // Prevent default link behavior
            return false;
        }
        
        // Add event listeners when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const button = document.querySelector('.download-btn button');
            if (button) {
                button.addEventListener('click', downloadPDF);
                if ('ontouchstart' in window) {
                    button.addEventListener('touchstart', downloadPDF);
                }
            }
        });
    </script>
</head>
<body>
    <div class="container" id="form-content">
        <!-- <div class="photo-placeholder">Profile Picture<br>(3cm x 3cm)</div> -->
        <div class="header">
            <!-- logo -->
        
            <img src="assets/img/icon1.png" alt="Logo" style="width: 50px; height: 50px;">
            <h1>Hostel Application Form</h1>
            <p>University of Rwanda</p>
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
      
    </div>
    <div class="download-btn">
        <a href="?download=pdf" class="download-link" target="_blank">Download PDF</a>
    </div>
</body>
</html>