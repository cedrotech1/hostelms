<?php
// session_start();
include 'connection.php';

$regnumber = $_SESSION['student_regnumber'];

// Query to fetch student data
$query = "
    SELECT 
        i.*,
        c.name AS campus_name,
        h.name AS hostel_name, h.building_code, h.othernames,
        r.room_code,
        a.ReceptNumber, a.Date_of_payment, a.status
    FROM info i
    LEFT JOIN campuses c ON i.campus = c.id
    LEFT JOIN applications a ON i.regnumber = a.regnumber
    LEFT JOIN rooms r ON a.room_id = r.id
    LEFT JOIN hostels h ON r.hostel_id = h.id
    WHERE i.regnumber = ? AND a.status IN ('paid', 'approved')";

$stmt = $connection->prepare($query);
$stmt->bind_param("s", $regnumber);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

// Handle null values
$disability = empty($data['disability']) ? 'None' : $data['disability'];
$othernames = empty($data['othernames']) ? 'None' : $data['othernames'];

// Escape data for HTML output
$escaped_data = array_map('htmlspecialchars', $data);
$escaped_data['disability'] = htmlspecialchars($disability);
$escaped_data['othernames'] = htmlspecialchars($othernames);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hostel Application Form</title>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4;
            margin: 10mm;
            
            /* Remove headers and footers */
            @top-center {
                content: '';
                display: none;
            }
            @bottom-center {
                content: '';
                display: none;
            }
            @top-right {
                content: '';
                display: none;
            }
            @bottom-right {
                content: '';
                display: none;
            }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 10px;
        }
        .header {
            text-align: left;
            margin-bottom: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0 0 5px 0;
        }
        .header p {
            margin: 2px 0;
            font-size: 11px;
        }
        .section {
            margin-bottom: 8px;
            page-break-inside: avoid;
        }
        .section h2 {
            font-size: 12px;
            border-bottom: 1px solid #000;
            margin: 10px 0 5px 0;
            padding-bottom: 2px;
        }
        .field {
            display: flex;
            margin-bottom: 3px;
        }
        .field-label {
            width: 100px;
            font-weight: bold;
            flex-shrink: 0;
        }
        .field-value {
            border-bottom: 1px dashed #000;
            flex-grow: 1;
            padding-bottom: 1px;
        }
        .signature {
            margin-top: 15px;
        }
        .signature p {
            margin: 5px 0;
            font-size: 10px;
            line-height: 1.2;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            width: 150px;
            display: inline-block;
            margin-top: 15px;
        }
        .logo {
            width: 40px;
            height: 40px;
            margin-bottom: 5px;
        }
        @media print {
            /* Remove URL and page numbers */
            @page {
                margin: 0;
                size: A4;
            }
            
            body {
                margin: 1.6cm;
            }
            
            /* Hide print button when printing */
            .no-print, .print-button {
                display: none !important;
            }
            
            /* Ensure proper page breaks */
            .section {
                page-break-inside: avoid;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .print-button {
                display: none !important;
            }
        }
    </style>
    <script>
        // Auto-print and close after printing
        window.onload = function() {
            // Set a small delay to ensure all content is loaded
            setTimeout(function() {
                // Open print dialog
                var beforePrint = function() {
                    console.log('Functionality to run before printing.');
                };
                var afterPrint = function() {
                    // Close the window after printing is done or cancelled
                    setTimeout(function() {
                        window.close();
                    }, 100);
                };

                // Add event listeners for older browsers
                if (window.matchMedia) {
                    var mediaQueryList = window.matchMedia('print');
                    mediaQueryList.addListener(function(mql) {
                        if (!mql.matches) {
                            afterPrint();
                        }
                    });
                }

                // For newer browsers
                window.onbeforeprint = beforePrint;
                window.onafterprint = afterPrint;

                // Trigger print
                window.print();
                
                // Fallback in case onafterprint doesn't work
                setTimeout(function() {
                    window.close();
                }, 1000);
                
            }, 500);
        };
        
        // Handle manual print button
        function printDocument() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if (file_exists('assets/img/icon1.png')): ?>
                <img src="assets/img/icon1.png" class="logo" alt="Logo">
            <?php endif; ?>
            <h1>Hostel Application Form</h1>
            <p>University of Rwanda</p>
            <p>To be submitted to the Hostel Warden</p>
        </div>

        <?php include 'form_content.php'; ?>
        
        <div class="no-print" style="text-align: center; margin: 20px 0;">
            <button onclick="printDocument()" style="padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
                Print Form
            </button>
            <p style="font-size: 12px; margin-top: 10px; color: #666;">
                If the print dialog doesn't open automatically, click the button above.
            </p>
        </div>
    </div>
</body>
</html>
