<?php
include('connection.php');
$id=$_GET['id'];
if (!isset($id)) {
    echo "<script>window.location.href='system.php'</script>";
  }

            $ok=mysqli_query($connection,"delete from system_emails where id='$id'");
            if($ok){
                echo "<script>alert('deleted')</script>";
                echo "<script>window.location.href='system.php'</script>";
            }
       
// }



?>