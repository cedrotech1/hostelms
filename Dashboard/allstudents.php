<?php
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>UR-HOSTELS</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="assets/img/icon1.png" rel="icon">
    <link href="assets/img/icon1.png" rel="apple-touch-icon">

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
                                <h5 class="card-title">LIST OF ALL STUDENTS</h5>

                                <div class="col-md-12 table-responsive">
                                    <table class="table datatable table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <b>#</b>
                                                </th>

                                                <th><b>Reg Number</b></th>
                                                <th><b>Campus</b></th>
                                                <!-- <th><b>College</b></th> -->
                                                <th><b>Names</b></th>
                                                <!-- <th><b>School</b></th> -->
                                                <!-- <th><b>Program</b></th> -->
                                                <th><b>Year</b></th>
                                                <th><b>Phone</b></th>
                                                <th><b>NID</b></th>
                                                

                                             
                                                <!-- <th><b>Modify</b></th> -->



                                            </tr>
                                        </thead>
                                        <tbody>



                                            <?php
                                            // role of the user
                                            $role = $_SESSION['role'];
                                            $userid=$_SESSION['id'];
                                            $ok1 = mysqli_query($connection, "select * from users where id=$userid");
                                                              while ($row = mysqli_fetch_array($ok1)) {
                                                                $id = $row["id"];
                                                            
                                                                $campus = $row["campus"];
                                                                
                                                            }
                                            
                                            $user_campus_id =$campus;
                                            // select campus name from the campus table
                                            $ok2 = mysqli_query($connection, "select * from campuses where id=$user_campus_id");
                                            while ($row = mysqli_fetch_array($ok2)) {
                                                $campus_name = $row["name"];
                                            }

                                            if($role == 'information_modifier'){
                                                $ok = mysqli_query($connection, "SELECT *                                               
                                            FROM info");
                                            }else{
                                                $ok = mysqli_query($connection, "SELECT *                                               
                                            FROM info WHERE campus = '$campus_name'");
                                            }
                                            $i = 0;
                                            while ($row = mysqli_fetch_array($ok)) {
                                                $i++;
                                                ?>

                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $row['regnumber']; ?></td>
                                                    <td><?php echo $row['campus']; ?></td>
                                                    <!-- <td><?php //echo $row['college']; ?></td> -->
                                                    <td><?php echo $row['names']; ?></td>
                                                    <!-- <td><?php //echo $row['school']; ?></td> -->
                                                    <!-- <td><?php //echo $row['program']; ?></td> -->
                                                    <td><?php echo $row['yearofstudy']; ?></td>
                                                    <td><?php echo $row['phone']; ?></td>
                                                    <td><?php echo $row['nid']; ?></td>
                                                   

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