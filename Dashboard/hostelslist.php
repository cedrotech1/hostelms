<?php
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>UR-HUYE</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="./icon1.png" rel="icon">
    <link href="./icon1.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
        rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">


</head>

<body>

    <?php
    include("./includes/header.php");
    include("./includes/menu.php");
    ?>



    <main id="main" class="main">



        <section class="section dashboard">
            <div class="row">
                <!-- <div class="col-lg-1"></div> -->
                <!-- Left side columns -->
                <div class="col-lg-12">
                    <div class="row">

                        <div class="card">
                            <div class="card-body">
                                <br>
                                <h5 class="card-title">LIST OF ALL HOSTELS WITH ROOM AND APPLICANTS</h5>

                                <div class="col-md-12 table-responsive">
                                    <table class="table datatable table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th><b>#</b></th>
                                                <th><b>Campus Name</b></th>
                                                <th><b>Hostel Name</b></th>
                                                <th><b>Room Code</b></th>
                                                <th><b>No. of Beds</b></th>
                                                <th><b>Remaining</b></th>
                                                <!-- <th><b>Status</b></th> -->
                                                <th><b>Applicants</b></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $ok = mysqli_query(
                                                $connection,
                                                "SELECT 
                                    c.id AS campus_id,
                                    c.name AS campus_name,
                                    h.id AS hostel_id,
                                    h.name AS hostel_name,
                                    r.id AS room_id,
                                    r.room_code,
                                    r.number_of_beds,
                                    r.remain,
                                 
                                    GROUP_CONCAT(DISTINCT CONCAT(i.names, ' (', i.regnumber, ', ', i.gender, ', ', i.yearofstudy, ')') SEPARATOR ' | ') AS applicants
                                FROM 
                                    campuses c
                                JOIN 
                                    hostels h ON h.campus_id = c.id
                                JOIN 
                                    rooms r ON r.hostel_id = h.id
                                LEFT JOIN 
                                    applications a ON a.room_id = r.id
                                LEFT JOIN 
                                    info i ON i.regnumber = a.regnumber
                                GROUP BY 
                                    r.id
                                ORDER BY 
                                    c.name, h.name, r.room_code;"
                                            );

                                            $i = 0;
                                            while ($row = mysqli_fetch_array($ok)) {
                                                $i++;
                                                ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo htmlspecialchars($row['campus_name']); ?></td>
                                                    <td>
                                                        <a href="hostel.php?hostel_id=<?php echo htmlspecialchars($row['hostel_id']); ?>">
                                                              <?php echo htmlspecialchars($row['hostel_name']); ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['room_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['number_of_beds']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['remain']); ?></td>
                                                    <!-- <td><?php //echo htmlspecialchars($row['status']); ?></td> -->
                                                    <td style="white-space: pre-line;">
                                                        <?php echo nl2br(htmlspecialchars($row['applicants'] ?: 'No Applicants')); ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>

                    </div>
                </div><!-- End Left side columns -->


            </div>
        </section>

    </main><!-- End #main -->

    <!-- ======= Footer ======= -->

    <?php
    include("./includes/footer.php");
    ?>

    <!-- End Footer -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chart.js/chart.umd.js"></script>
    <script src="assets/vendor/echarts/echarts.min.js"></script>
    <script src="assets/vendor/quill/quill.min.js"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
    <script src="assets/vendor/tinymce/tinymce.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>

    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>



</body>

</html>