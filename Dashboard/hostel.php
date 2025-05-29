<?php
include 'connection.php'; // if not already included

// session_start(); // Make sure session is started at the top of your file

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_attribute'])) {
    if (isset($_GET['hostel_id'])) {
        $hostel_id = (int) $_GET['hostel_id'];
        $key = mysqli_real_escape_string($connection, $_POST['attribute_key']);
        $value = mysqli_real_escape_string($connection, $_POST['attribute_value']);

        if (!empty($key) && !empty($value)) {
            $check = mysqli_query($connection, "
                SELECT * FROM hostel_attributes 
                WHERE hostel_id = '$hostel_id' 
                AND attribute_key = '$key' 
                AND attribute_value = '$value'
                LIMIT 1
            ");

            if (mysqli_num_rows($check) === 0) {
                mysqli_query($connection, "
                    INSERT INTO hostel_attributes (hostel_id, attribute_key, attribute_value)
                    VALUES ('$hostel_id', '$key', '$value')
                ");
                echo "<script/>alert('done')<script/>";
            } else {
                echo "<script/>alert('This attribute already exists.')<script/>";

            }
        } else {
            // $_SESSION['message'] = ['type' => 'danger', 'text' => ''];
            echo "<script/>alert('Key and value must not be empty.')<script/>";
        }

        header("Location: hostel.php?hostel_id=" . $_GET['hostel_id']);
        exit;
    }
}

// DELETE single attribute
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_attribute_id'])) {
    $delete_id = (int) $_POST['delete_attribute_id'];
    $hostel_id = (int) $_GET['hostel_id'];
    
    if (mysqli_query($connection, "DELETE FROM hostel_attributes WHERE id = '$delete_id'")) {
        // $_SESSION['message'] = ['type' => 'success', 'text' => 'Attribute deleted successfully.'];
    } else {
        // $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to delete attribute.'];
    }
    
    header("Location: hostel.php?hostel_id=" . $hostel_id);
    exit;
}

// DELETE all attributes for this hostel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all_attributes']) && isset($_GET['hostel_id'])) {
    $hostel_id = (int) $_GET['hostel_id'];
    
    if (mysqli_query($connection, "DELETE FROM hostel_attributes WHERE hostel_id = '$hostel_id'")) {
        // $_SESSION['message'] = ['type' => 'warning', 'text' => 'All attributes deleted successfully.'];
    } else {
        // $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to delete attributes.'];
    }
    
    header("Location: hostel.php?hostel_id=" . $hostel_id);
    exit;
}

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
                <div class="col-lg-12">
                    <div class="row">
                        <?php
                        if (isset($_GET['hostel_id'])) {
                            $hostel_id = (int) $_GET['hostel_id'];

                            // Fetch hostel and campus
                            $hostel_query = mysqli_query($connection, "
                        SELECT h.id AS hostel_id, h.name AS hostel_name, c.name AS campus_name
                        FROM hostels h
                        JOIN campuses c ON c.id = h.campus_id
                        WHERE h.id = '$hostel_id'
                        LIMIT 1
                    ");

                            if (mysqli_num_rows($hostel_query) > 0) {
                                $hostel = mysqli_fetch_assoc($hostel_query);

                                // Fetch attributes
                                $attributes_query = mysqli_query($connection, "
                            SELECT id, attribute_key, attribute_value 
                            FROM hostel_attributes 
                            WHERE hostel_id = '$hostel_id'
                        ");
                                ?>

                                <div class="col-md-12">
                                    <div class="card shadow">
                                                    <div class="card-body">
                                                        <h5 class="card-title">
                                                            Hostel: <strong
                                                                class="text-primary"><?php echo htmlspecialchars($hostel['hostel_name']); ?></strong>
                                                            |
                                                            Campus: <small
                                                                class="text-muted"><?php echo htmlspecialchars($hostel['campus_name']); ?></small>
                                                        </h5>

                                                        <h6><strong>Attributes:</strong></h6>
                                                        <div class="row">
                                                          
                                                                <?php if (mysqli_num_rows($attributes_query) > 0): ?>
                                                                    <?php while ($attr = mysqli_fetch_assoc($attributes_query)): ?>
                                                                        <div class="col-md-6 mb-2">
                                                                            <div class="list-group-item d-flex justify-content-between align-items-center p-1" style="background-color:whitesmoke; border: 1px solid lightgray;border-radius:5px">
                                                                                <div class="m-2">
                                                                                    <strong><?= htmlspecialchars($attr['attribute_key']) ?>:</strong>
                                                                                    <?= htmlspecialchars($attr['attribute_value']) ?>
                                                                                </div>
                                                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this attribute?')" class="d-inline">
                                                                                    <input type="hidden" name="delete_attribute_id" value="<?= $attr['id'] ?>">    
                                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                                        <i class="fas fa-trash"></i> Delete
                                                                                    </button>
                                                                                </form>
                                                                            </div>
                                                                        </div>
                                                                    <?php endwhile; ?>

                                                                    <div class="col-12 mt-3">
                                                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete ALL attributes for this hostel?')" class="mb-3">
                                                                            <input type="hidden" name="delete_all_attributes" value="1">
                                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                                <i class="fas fa-trash-alt"></i> Delete All Attributes
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="col-12">
                                                                        <p class="text-muted">No attributes yet.</p>
                                                                    </div>
                                                                <?php endif; ?>
                                                           
                                                        </div>

                                                        <?php
                                                        // Fetch unique values for each key from `info`
                                                        function getUniqueValues($connection, $column)
                                                        {
                                                            $safe_column = in_array($column, ['school', 'college', 'yearofstudy', 'gender']) ? $column : '';
                                                            if ($safe_column) {
                                                                $result = mysqli_query($connection, "SELECT DISTINCT `$safe_column` FROM info WHERE `$safe_column` IS NOT NULL AND `$safe_column` != ''");
                                                                $values = [];
                                                                while ($row = mysqli_fetch_assoc($result)) {
                                                                    $values[] = $row[$safe_column];
                                                                }
                                                                return $values;
                                                            }
                                                            return [];
                                                        }

                                                        // Default keys allowed
                                                        $allowed_keys = ['school', 'college', 'yearofstudy', 'gender'];
                                                        $selected_key = isset($_POST['attribute_key']) && in_array($_POST['attribute_key'], $allowed_keys) ? $_POST['attribute_key'] : '';
                                                        ?>
                                                        <form method="POST" class="mt-3">
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <select name="attribute_key" class="form-control" required
                                                                        onchange="this.form.submit()">
                                                                        <option value="">-- Select Attribute Key --</option>
                                                                        <?php foreach ($allowed_keys as $key): ?>
                                                                            <option value="<?= $key ?>" <?= $selected_key === $key ? 'selected' : '' ?>>
                                                                                <?= ucfirst($key) ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>

                                                                <div class="col-md-5">
                                                                    <?php if ($selected_key): ?>
                                                                        <select name="attribute_value" class="form-control" required>
                                                                            <option value="">-- Select <?= ucfirst($selected_key) ?> --</option>
                                                                            <?php
                                                                            $options = getUniqueValues($connection, $selected_key);
                                                                            foreach ($options as $option) {
                                                                                echo "<option value=\"" . htmlspecialchars($option) . "\">" . htmlspecialchars($option) . "</option>";
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                    <?php else: ?>
                                                                        <input type="text" name="attribute_value" class="form-control"
                                                                            placeholder="Select key first" readonly>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <div class="col-md-3">
                                                                    <button type="submit" name="add_attribute" class="btn btn-primary"
                                                                        <?= !$selected_key ? 'disabled' : '' ?>>Add Attribute</button>
                                                                </div>
                                                            </div>
                                                        </form>

                                                    </div>
                                    </div>
                                </div>

                                <?php
                            } else {
                                echo "<div class='alert alert-danger'>Hostel not found.</div>";
                            }
                        } else {
                            echo "<div class='alert alert-warning'>No hostel selected. Use ?hostel_id=ID in the URL.</div>";
                        }
                        ?>
                    </div>
                </div>
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

    <!-- Add this for displaying messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['message']['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

</body>

</html>