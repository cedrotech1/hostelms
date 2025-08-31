<?php
$reg = $_SESSION['student_regnumber'] ?? null;
if (!$reg) {
    echo "<div class='alert alert-warning'>Student not logged in.</div>";
    exit;
}

$stmt = $connection->prepare("
    SELECT i.campus, i.college, i.names, i.school, i.program, i.yearofstudy,
           i.regnumber, r.room_code, h.name AS hostel_name
    FROM info i
    INNER JOIN applications a ON i.regnumber = a.regnumber
    INNER JOIN rooms r ON a.room_id = r.id
    INNER JOIN hostels h ON r.hostel_id = h.id
    WHERE i.regnumber = ?
");
$stmt->bind_param("s", $reg);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Map college codes to full names
    $collegeNames = [
        'CASS'  => 'COLLEGE OF ARTS AND SOCIAL SCIENCES',
        'CBE'   => 'COLLEGE OF BUSINESS AND ECONOMICS',
        'CST'   => 'COLLEGE OF SCIENCE AND TECHNOLOGY',
        'CAVEM' => 'COLLEGE OF AGRICULTURE, ANIMAL SCIENCES, AND VETERINARY MEDICINE',
        'CMHS'  => 'COLLEGE OF MEDICINE AND HEALTH SCIENCES',
        'CE'    => 'COLLEGE OF EDUCATION'
    ];
    $colFull = $collegeNames[$row['college']] ?? $row['college'];
    ?>

    <div class="col-xxl-4 col-md-6"
         data-bs-toggle="modal" data-bs-target="#basicModal"
         data-campus="<?= htmlspecialchars($row['campus']) ?>"
         data-college="<?= htmlspecialchars($row['college']) ?>"
         data-names="<?= htmlspecialchars($row['names']) ?>"
         data-school="<?= htmlspecialchars($row['school']) ?>"
         data-program="<?= htmlspecialchars($row['program']) ?>"
         data-yearofstudy="<?= htmlspecialchars($row['yearofstudy']) ?>"
         data-regnumber="<?= htmlspecialchars($row['regnumber']) ?>"
         data-roomcode="<?= htmlspecialchars($row['room_code']) ?>"
         data-hostelname="<?= htmlspecialchars($row['hostel_name']) ?>">
        <div class="card info-card"
             style="background: url('./lox.png') no-repeat center center/cover; border:1px solid gray;">
            <div class="card-body ps-1" style="color:black;">
                <div class="row" style="padding-top:0.3cm;">
                    <div class="col-5">
                        <img src="./ur-logo.png" alt="Logo" style="height:2.3cm;width:7cm;">
                    </div>
                    <div class="col-7">
                        <h6 style="padding-top:0.6cm; text-transform:uppercase; text-align:right; font-size:28px; color:black;">
                            <b><?= htmlspecialchars($row['campus']) ?> Campus</b>
                        </h6>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h4 style="text-align:center; text-transform:uppercase; font-weight:bold; font-size:16px; font-family:Arial Narrow;">
                            <b><?= $colFull ?></b>
                        </h4>
                    </div>
                </div>
                <div class="row p-1">
                    <div class="col-9">
                        <h5 style="font-size:20px;text-align:center; font-family:Arial; margin-top:0.2cm;">
                            <b class="formatted-underline">STUDENT HOSTEL CARD</b>
                        </h5>

                        <p><strong>Names:</strong> <?= htmlspecialchars($row['names']) ?></p>
                        <p><strong>School:</strong> <?= htmlspecialchars($row['school']) ?></p>
                        <p><strong>Program:</strong> <?= htmlspecialchars($row['program']) ?></p>
                        <p><strong>Year of Study:</strong> <?= htmlspecialchars($row['yearofstudy']) ?></p>
                        <p><strong>Room Code:</strong> <?= htmlspecialchars($row['room_code']) ?></p>
                        <p><strong>Hostel:</strong> <?= htmlspecialchars($row['hostel_name']) ?></p>
                    </div>
                    <div class="col-3" style="margin-left:-0.3cm; display:flex; align-items:center; justify-content:center;">
                        <div style="height:3cm; width:3cm; border:1px solid black; display:flex; align-items:center; justify-content:center;">
                            <span><b><?= htmlspecialchars($row['regnumber']) ?></b></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
} else {
    echo "<div class='alert alert-warning'>No record found for this student.</div>";
}
$stmt->close();
?>
