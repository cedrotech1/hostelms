<?php
// This file contains the form content that will be included in both HTML and PDF versions
// Data is passed from the parent template
?>

<div class="section">
    <h2>Student Information</h2>
    <div class="field">
        <div class="field-label">Full Names:</div>
        <div class="field-value"><?php echo $escaped_data['names'] ?? ''; ?></div>
    </div>
    <div class="field">
        <div class="field-label">Registration Number:</div>
        <div class="field-value"><?php echo $regnumber; ?></div>
    </div>
    <div class="field">
        <div class="field-label">National ID:</div>
        <div class="field-value"><?php echo $escaped_data['nid'] ?? ''; ?></div>
    </div>
    <div class="field">
        <div class="field-label">Gender:</div>
        <div class="field-value"><?php echo $escaped_data['gender'] ?? ''; ?></div>
    </div>
    <div class="field">
        <div class="field-label">Email:</div>
        <div class="field-value"><?php echo $escaped_data['email'] ?? ''; ?></div>
    </div>
    <div class="field">
        <div class="field-label">Phone Number:</div>
        <div class="field-value"><?php echo $escaped_data['phone'] ?? ''; ?></div>
    </div>
</div>

<div class="section">
    <h2>Academic Information</h2>
    <div class="field">
        <div class="field-label">Campus:</div>
        <div class="field-value"><?php echo $escaped_data['campus_name'] ?? ''; ?></div>
    </div>
    <div class="field">
        <div class="field-label">College:</div>
        <div class="field-value"><?php echo $escaped_data['college'] ?? ''; ?></div>
    </div>
    <div class="field">
        <div class="field-label">School:</div>
        <div class="field-value"><?php echo $escaped_data['school'] ?? ''; ?></div>
    </div>
    <div class="field">
        <div class="field-label">Program:</div>
        <div class="field-value"><?php echo $escaped_data['program'] ?? ''; ?></div>
    </div>
    <div class="field">
        <div class="field-label">Intake:</div>
        <div class="field-value"><?php echo $escaped_data['intake'] ?? ''; ?></div>
    </div>
    <div class="field">
        <div class="field-label">Year of Study:</div>
        <div class="field-value"><?php echo $escaped_data['yearofstudy'] ?? ''; ?></div>
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
        <div class="field-value"><?php echo htmlspecialchars($data['kin_name'] ?? 'Not provided'); ?></div>
    </div>
    <div class="field">
        <div class="field-label">Phone Number:</div>
        <div class="field-value"><?php echo htmlspecialchars($data['kin_phone'] ?? 'Not provided'); ?></div>
    </div>
</div>

<div class="section">
    <h2>Hostel Information</h2>
    <div class="field">
        <div class="field-label">Hostel Name:</div>
        <div class="field-value"><?php echo $escaped_data['hostel_name'] ?? ''; ?></div>
    </div>
    <div class="field">
        <div class="field-label">Building Code:</div>
        <div class="field-value"><?php echo $escaped_data['building_code'] ?? ''; ?></div>
    </div>
    <div class="field">
        <div class="field-label">Room Code:</div>
        <div class="field-value"><?php echo $escaped_data['room_code'] ?? ''; ?></div>
    </div>
</div>

<div class="section">
    <h2>Application Information</h2>
    <div class="field">
        <div class="field-label">Receipt Number:</div>
        <div class="field-value"><?php echo $escaped_data['ReceptNumber'] ?? ''; ?></div>
    </div>
    <div class="field">
        <div class="field-label">Date of Payment:</div>
        <div class="field-value"><?php echo $escaped_data['Date_of_payment'] ?? ''; ?></div>
    </div>
    <div class="field">
        <div class="field-label">Application Status:</div>
        <div class="field-value"><?php echo $escaped_data['status'] ?? ''; ?></div>
    </div>
</div>

<div class="section signature">
    <h2>Declaration and Signature</h2>
    <p>I, <?php echo $escaped_data['names'] ?? ''; ?>, hereby confirm that the information provided in this form is accurate and complete to 
    the best of my knowledge. I understand that this form must be signed and submitted to the Hostel Warden. 
    I also understand that providing wrong information may result in losing my right to a hostel room without a refund of money paid.</p>
    
    <div style="margin-top: 15px;">
        <div style="display: inline-block; width: 200px; margin-right: 20px;">
            <div>Signature:</div>
            <div class="signature-line"></div>
        </div>
        <div style="display: inline-block; width: 150px;">
            <div>Date:</div>
            <div class="signature-line"></div>
        </div>
    </div>
</div>
