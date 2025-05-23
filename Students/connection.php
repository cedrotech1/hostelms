<?php

// session_start();

// if(!isset($_SESSION['loggedin'])){
//     echo"<script>window.location.href='../login.php'</script>";

// }


// $connection=mysqli_connect('localhost','root','','ur-student-card');
$connection=mysqli_connect('localhost','root', '','hostel');
if($connection){
// echo'connected';
}


// function formatMoney($amount) {
//     // Check if the amount is not a number
//     if (!is_numeric($amount)) {
//         return "Invalid amount";
//     }

//     // Format the amount with two decimal places and commas for thousands separator
//     $formatted_amount = number_format($amount, 2, '.', ',');

//     // Return the formatted amount with a currency symbol
//     return  $formatted_amount. ' Rwf';
// }

// // Example usage
// $amount = 1234567.89;
//  formatMoney($amount); // Output: $1,234,567.89


?>