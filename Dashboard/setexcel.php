<?php
include('connection.php');

// Handle update if form is submitted
if (isset($_POST['update'])) {
    $id = intval($_POST['update']);

    // Gather and trim all submitted values
    $fields_to_check = [
        'regnumber',
        'nid',
        'email',
        'campus',
        'college',
        'sirname',
        'lastname',
        'school',
        'program',
        'yearofstudy',
        'gender',
        'phone'
    ];

    foreach ($fields_to_check as $field) {
        $submitted_values = array_map('trim', $_POST[$field]);

        // Check if duplicates exist
        if (count($submitted_values) !== count(array_unique($submitted_values))) {
            echo "<p style='color:red;'>Duplicate values detected in '$field'. All $field values must be unique.</p>";
            echo "<script>alert('Duplicate values detected in '$field'. All $field values must be unique.');</script>";
            exit;
        }
    }

    // Proceed with the update for the specific row
    $regnumber = trim($_POST['regnumber'][$id]);
    $campus = trim($_POST['campus'][$id]);
    $college = trim($_POST['college'][$id]);
    $sirname = trim($_POST['sirname'][$id]);
    $lastname = trim($_POST['lastname'][$id]);
    $school = trim($_POST['school'][$id]);
    $program = trim($_POST['program'][$id]);
    $year = trim($_POST['yearofstudy'][$id]);
    $email = trim($_POST['email'][$id]);
    $gender = trim($_POST['gender'][$id]);
    $nid = trim($_POST['nid'][$id]);
    $phone = trim($_POST['phone'][$id]);


    // Prepare and execute the update
    $stmt = $connection->prepare("UPDATE excel SET 
        regnumber = ?, campus = ?, college = ?, sirname = ?, lastname = ?, school = ?, program = ?, yearofstudy = ?, email = ?, gender = ?, nid = ?, phone = ? 
        WHERE id = ?");

    $stmt->bind_param("sssssssissssi", $regnumber, $campus, $college, $sirname, $lastname, $school, $program, $year, $email, $gender, $nid, $phone, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Record updated successfully'); window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
        exit;
    } else {
        echo "<p style='color:red;'>Update failed: " . htmlspecialchars($stmt->error) . "</p>";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />

    <title>UR-HUYE</title>
    <meta content="" name="description" />
    <meta content="" name="keywords" />

    <link href="./icon1.png" rel="icon" />
    <link href="./icon1.png" rel="apple-touch-icon" />
    <link href="https://fonts.gstatic.com" rel="preconnect" />
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
        rel="stylesheet" />

    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" />
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet" />
    <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet" />
    <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet" />
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet" />
    <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
</head>

<body>

    <?php include("./includes/header.php"); ?>
    <?php   include("./includes/menu.php"); ?>

    <main id="main" class="main">
        <section class="section dashboard">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card p-3">
                        <h5 class="card-title">Excel Table Records</h5>
                        <?php
                        $result = $connection->query("SELECT * FROM excel");

                        if ($result && $result->num_rows > 0) {
                            echo "<form method='post' action=''>";
                            echo "<div class='table-responsive'>";
                            echo "<table class='table table-bordered table-striped'>";
                            echo "<thead class='table-light'><tr>
                                    <th>RegNumber</th><th>Campus</th><th>College</th><th>Sirname</th><th>Lastname</th>
                                    <th>School</th><th>Program</th><th>Year</th><th>Email</th><th>Gender</th><th>NID</th><th>Phone</th><th>Action</th>
                                  </tr></thead><tbody>";

                            while ($row = $result->fetch_assoc()) {
                                $id = $row['id'];
                                echo "<tr>
                                    <td><input type='number' name='regnumber[$id]' value='" . htmlspecialchars($row['regnumber']) . "' class='form-control'></td>
                                    <td><input type='number' name='campus[$id]' value='" . htmlspecialchars($row['campus']) . "' class='form-control'></td>
                                    <td><input type='number' name='college[$id]' value='" . htmlspecialchars($row['college']) . "' class='form-control'></td>
                                    <td><input type='number' name='sirname[$id]' value='" . htmlspecialchars($row['sirname']) . "' class='form-control'></td>
                                    <td><input type='number' name='lastname[$id]' value='" . htmlspecialchars($row['lastname']) . "' class='form-control'></td>
                                    <td><input type='number' name='school[$id]' value='" . htmlspecialchars($row['school']) . "' class='form-control'></td>
                                    <td><input type='number' name='program[$id]' value='" . htmlspecialchars($row['program']) . "' class='form-control'></td>
                                    <td><input type='number' name='yearofstudy[$id]' value='" . htmlspecialchars($row['yearofstudy']) . "' class='form-control'></td>
                                    <td><input type='number' name='email[$id]' value='" . htmlspecialchars($row['email']) . "' class='form-control'></td>
                                    <td><input type='number' name='gender[$id]' value='" . htmlspecialchars($row['gender']) . "' class='form-control'></td>
                                    <td><input type='number' name='nid[$id]' value='" . htmlspecialchars($row['nid']) . "' class='form-control'></td>
                                    <td><input type='number' name='phone[$id]' value='" . htmlspecialchars($row['phone']) . "' class='form-control'></td>
                                    <td><button type='submit' name='update' value='$id' class='btn btn-sm btn-primary'>Update</button></td>
                                  </tr>";
                            }

                            echo "</tbody></table>";
                            echo "</div>";
                            echo "</form>";
                        } else {
                            echo "<p class='text-danger'>No records found in the excel table.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>
    </main><!-- End #main -->

    <?php include("./includes/footer.php"); ?>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chart.js/chart.umd.js"></script>
    <script src="assets/vendor/echarts/echarts.min.js"></script>
    <script src="assets/vendor/quill/quill.min.js"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
    <script src="assets/vendor/tinymce/tinymce.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>
    <script src="assets/js/main.js"></script>

</body>

</html>
