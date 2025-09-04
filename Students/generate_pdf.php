<?php
// Include the main form content
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Hostel Application Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm 15mm;
            box-sizing: border-box;
        }
        .header {
            text-align: left;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 14pt;
            margin: 0 0 5px 0;
        }
        .header p {
            font-size: 9pt;
            margin: 2px 0;
        }
        .section {
            margin-bottom: 5px;
            page-break-inside: avoid;
        }
        .section h2 {
            font-size: 11pt;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin: 8px 0 5px 0;
        }
        .field {
            display: flex;
            margin-bottom: 2px;
            font-size: 9pt;
        }
        .field-label {
            width: 90px;
            min-width: 90px;
            font-weight: bold;
        }
        .field-value {
            flex: 1;
            border-bottom: 1px dashed #000;
            padding-bottom: 1px;
        }
        .signature {
            margin-top: 10px;
        }
        .signature p {
            font-size: 9pt;
            margin: 0 0 5px 0;
            line-height: 1.2;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            width: 150px;
            display: inline-block;
            margin-top: 5px;
        }
        .logo {
            width: 40px;
            height: 40px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if (file_exists('assets/img/icon1.png')): ?>
                <img src="assets/img/icon1.png" class="logo" alt="Logo">
            <?php endif; ?>
            <h1>Hostel Application Form</h1>
            <p>University of Rwanda</p>
            <p class="italic">To be submitted to the Hostel Warden</p>
        </div>

        <?php include 'form_content.php'; ?>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Include the TCPDF library if available, otherwise use a simple fallback
if (file_exists('tcpdf/tcpdf.php')) {
    require_once('tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Hostel Management System');
    $pdf->SetAuthor('University of Rwanda');
    $pdf->SetTitle('Hostel Application Form');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output('hostel_application.pdf', 'D');
} else {
    // Fallback to HTML download if TCPDF is not available
    header('Content-Type: text/html');
    echo $html;
}
?>
