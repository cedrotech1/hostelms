<?php
include('connection.php');
include('./includes/auth.php');
checkUserRole(['information_modifier']);

require_once '../../loadEnv.php';

// Load the .env file
$filePath = __DIR__ . '/../../.env'; // Corrected path
loadEnv($filePath);
include("../email_functions.php");





?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Add User</title>
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

  <script>
    // Function to generate a random password
    function generateRandomPassword(length = 12) {
      const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
      let password = "";
      for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
      }
      return password;
    }

    // Automatically set a random password in the hidden password input
    function setRandomPassword() {
      const passwordField = document.getElementById('password');
      const generatedPassword = generateRandomPassword();
      passwordField.value = generatedPassword;
    }

    // Call the function to set the password when the page loads
    window.onload = setRandomPassword;
  </script>



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
        <div class="col-lg-5">
          <div class="row">

            <div class="card">
              <div class="card-body">
                <h5 class="card-title">ADD USER FORM</h5>


                <form class="row g-3" action="add_user.php" method="post">
                  <div class="col-md-12">
                    <div class="form-floating">
                      <input type="text" class="form-control" id="floatingName" placeholder="Name" name="name" required>
                      <label for="floatingName">Name</label>
                    </div>
                  </div>

                  <div class="col-md-12">
                    <div class="form-floating">
                      <input type="email" class="form-control" id="floatingEmail" placeholder="Email" name="email"
                        required>
                      <label for="floatingEmail">Email</label>
                    </div>
                  </div>

                  <!-- Role Selection Dropdown -->
                  <div class="col-md-12">
                    <div class="form-floating">
                      <select class="form-select" id="floatingRole" name="role" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="cards_manager">Cards Manager</option>
                        <option value="information_modifier">Information Modifier</option>
                        <option value="admin">Admin</option>
                      </select>
                      <label for="floatingRole">Role</label>
                    </div>
                  </div>
                  <input type="hidden" id="password" name="password">


                  <div class="text-center">
                    <button type="submit" name="saveuser" class="btn btn-primary">Save User</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                  </div>
                </form>




              </div>
            </div>


          </div>
        </div><!-- End Left side columns -->


      </div>
    </section>

    <?php
    // Query to select users
    $query = "SELECT * FROM users WHERE role != 'admin'";
    $result = mysqli_query($connection, $query);

    // Check if any users were found
    if (mysqli_num_rows($result) > 0) {
      ?>
      <section class="section dashboard">
        <div class="row">
          <!-- <div class="col-lg-1"></div> -->
          <!-- Left side columns -->
          <div class="col-lg-12">
            <div class="row">

              <div class="card">
                <div class="card-body p-2">
                  <center>
                    <h5 class="card-title"> LIST OF ALL USERS</h5>
                  </center>
                </div>
              </div>


            </div>
          </div><!-- End Left side columns -->


        </div>
      </section>

      <?php

    }

    ?>



    <section class="section dashboard">
      <div class="row">
        <!-- Left side columns -->
        <div class="col-lg-12">
          <div class="row">
            <?php
            // Query to select users
            $query = "SELECT * FROM users WHERE role != 'admin'";
            $result = mysqli_query($connection, $query);

            // Check if any users were found
            if (mysqli_num_rows($result) > 0) {
              // Loop through each user
              while ($row = mysqli_fetch_assoc($result)) {
                // Get privileges for the current user
                $userId = $row['id'];
                
                ?>
                <div class="col-xxl-3 col-md-4">
                  <!-- User Card -->
                  <!-- <a href="one-user.php?id=<?php echo $userId; ?>"> -->
                  <div class="card info-card">
                    <div class="card-body">
                      <br>
                      <!-- Display user's image if available -->
                      <!-- Replace 'user_image_column_name' with the actual column name in your 'users' table that stores the image path -->
                      <img src="./<?php echo $row['image']; ?>" class="card-img-top">
                      <div class="card-body">
                        <!-- Display user's name -->
                        <div style="
    border: 1px solid #ddd; 
    border-radius: 8px; 
    padding: 2px; 
    margin: 2px 0; 
 
    background-color: #f9f9f9; 
 
    font-family: Arial, sans-serif;
">
                          <!-- Display user's name -->
                          <p style="font-size: 18px; font-weight: bold; margin-bottom: 8px; color: #333;">
                            Names: <?php echo $row['names']; ?>
                          </p>

                          <!-- Display user's email -->
                          <p style="font-size: 16px; margin-bottom: 8px; color: #555;">
                            Email: <?php echo $row['email']; ?>
                          </p>

                          <!-- Display user's role -->
                          <p style="font-size: 16px; color: #555;">
                            Role: <?php echo $row['role']; ?>
                          </p>
                          <p style="font-size: 16px; color: #555;">
                            Status: <?php if ($row['active']) {
                              echo "active";
                            } else {
                              echo "inactive";
                            }
                            ; ?>
                          </p>
                        </div>


                   
                        <div class="ps-1">
                          <div class="row" style='background-color:#f6f9ff;margin-top:0.2cm'>
                            <!-- Button to delete the user -->
                            <div class="col-8">
                              <a href="user-delete.php?userId=<?php echo $row['id']; ?>">
                                <button class="btn" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete">
                                  <i class="fas fa-trash text-danger"></i>
                                </button>
                              </a>
                            </div>
                            <!-- Button to edit the user's information -->

                            <!-- Button to view user details -->

                            <!-- Button to activate or deactivate the user -->
                            <div class="col-4">
                              <?php
                              // Check if the user is active or inactive and display the appropriate action button
                              if ($row['active'] == 1) {
                                // User is active, display the deactivate button
                                echo '<button class="btn " onclick="confirmDeactivation(' . $row['id'] . ', \'' . $row['names'] . '\')" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Deactivate">';
                                echo '<i class="fas fa-toggle-on text-primary"></i>';
                                echo '</button>';
                              } else {
                                // User is inactive, display the activate button
                                echo '<button class="btn" onclick="confirmActivation(' . $row['id'] . ', \'' . $row['names'] . '\')" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Activate">';
                                echo '<i class="fas fa-toggle-off text-success"></i>';
                                echo '</button>';
                              }
                              ?>
                            </div>
                          </div>

                          <script>
                            // Function to confirm deactivation
                            function confirmDeactivation(userId, userName) {
                              if (confirm('Are you sure you want to deactivate the user ' + userName + '?')) {
                                window.location.href = 'user-deactivate.php?userId=' + userId;
                              } else {
                                // Do nothing or handle cancellation
                              }
                            }

                            // Function to confirm activation
                            function confirmActivation(userId, userName) {
                              if (confirm('Are you sure you want to activate the user ' + userName + '?')) {
                                window.location.href = 'user-activate.php?userId=' + userId;
                              } else {
                                // Do nothing or handle cancellation
                              }
                            }
                          </script>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- </a> -->
                  <!-- End User Card -->
                </div>
                <?php
              }
            } else {
              // If no users found
              // echo '<p>No users found</p>';
            }
            ?>
          </div>
        </div>
      </div>
    </section>



  </main><!-- End #main -->





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

<?php
if (isset($_POST['saveuser'])) {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $role = $_POST['role'];
  $password = $_POST['password']; // Get the random password from the form

  if ($name != '' && $email != '' && $password != '') {
    // Hash the password for security using bcrypt algorithm
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert the user into the database with hashed password
    $query = "INSERT INTO users (names, email, role, password, image, active) VALUES ('$name', '$email', '$role', '$hashed_password', 'assets/img/av.png', '1')";
    $result = mysqli_query($connection, $query);

    if ($result) {

      sendWelcomeEmail($email, $name, $password);
      echo "<script>alert('User added successfully.')</script>";
      echo "<script>window.location.href='add_user.php'</script>";
    } else {
      echo "<script>alert('Error occurred while adding user.')</script>";
    }
  } else {
    echo "<script>alert('Please fill all fields.')</script>";
  }
}
?>