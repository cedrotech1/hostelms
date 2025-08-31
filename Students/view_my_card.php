<?php
session_start();
include 'connection.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: logout.php");
    exit();
}

$student_regnumber = $_SESSION['student_regnumber'];

// Handle claim deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_claim'])) {
    $claim_id = mysqli_real_escape_string($connection, $_POST['claim_id']);
    
    // Verify the claim belongs to this student
    $verify_query = "SELECT id FROM claiming WHERE id = ? AND regnumber = ?";
    $stmt = $connection->prepare($verify_query);
    $stmt->bind_param("is", $claim_id, $student_regnumber);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Delete the claim
        $delete_query = "DELETE FROM claiming WHERE id = ? AND regnumber = ?";
        $stmt = $connection->prepare($delete_query);
        $stmt->bind_param("is", $claim_id, $student_regnumber);
        
        if ($stmt->execute()) {
            echo "<script>alert('Claim deleted successfully!'); window.location.href='view_my_claims.php';</script>";
        } else {
            echo "<script>alert('Error deleting claim.');</script>";
        }
    } else {
        echo "<script>alert('Claim not found or you do not have permission to delete it.');</script>";
    }
}

// Handle claim edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_claim'])) {
    $claim_id = mysqli_real_escape_string($connection, $_POST['claim_id']);
    $edit_category = mysqli_real_escape_string($connection, $_POST['edit_category']);
    $edit_message = mysqli_real_escape_string($connection, $_POST['edit_message']);
    $update_query = "UPDATE claiming SET category = ?, message = ?, updated_at = NOW() WHERE id = ? AND regnumber = ? AND status = 'pending'";
    $stmt = $connection->prepare($update_query);
    $stmt->bind_param("ssis", $edit_category, $edit_message, $claim_id, $student_regnumber);
    if ($stmt->execute()) {
        echo "<script>alert('Claim updated successfully!'); window.location.href='view_my_claims.php';</script>";
    } else {
        echo "<script>alert('Error updating claim.');</script>";
    }
}

// Get student's claims with replier's name
$claims_query = "SELECT c.*, r.room_code, h.name as hostel_name, u.names as replier_name 
                 FROM claiming c
                 JOIN rooms r ON c.room_id = r.id
                 JOIN hostels h ON r.hostel_id = h.id
                 LEFT JOIN users u ON c.repliedby = u.id
                 WHERE c.regnumber = ?
                 ORDER BY c.created_at DESC";

$stmt = $connection->prepare($claims_query);
$stmt->bind_param("s", $student_regnumber);
$stmt->execute();
$claims_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Claims - UR-HOSTELS</title>
    <link href="../icon1.png" rel="icon">
    <link href="../icon1.png" rel="apple-touch-icon">
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        body{
            background-color:#f6f9ff;
        }
        .claim-card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 4px 16px rgba(30, 60, 114, 0.08);
            margin-bottom: 20px;
            transition: transform 0.2s;
            overflow: hidden;
            position: relative;
        }

        .claim-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1e3c72, #2a5298, #667eea);
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        .claim-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(30, 60, 114, 0.12);
        }

        .claim-header {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .claim-body {
            padding: 1.5rem;
        }

        .claim-footer {
            padding: 1rem;
            background: #f8f9fa;
            border-top: 1px solid #f0f0f0;
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 5px;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #842029;
        }

        .reply-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 3px;
            margin-top: 1rem;
            border-left: 4px solid #1e3c72;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .btn-action {
            padding: 0.4rem 1rem;
            border-radius: 4px;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>

<body>
<div class="container mt-4">
<?php include("./includes/studentMenu.php"); ?>

    <main id="main" class="main">
        <div class="row">
            <div class="col-lg-12">
                <?php
                include("./hostel_includes/components/card.php");

                ?>
                
            </div>
        </div>
        

      
    </main>

    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html> 