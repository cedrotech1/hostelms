<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers for JSON response
header('Content-Type: application/json');

// Include required files
include('connection.php');
require_once __DIR__ . '/../email_functions.php';

// Function to generate a strong password
function generateStrongPassword($length = 16) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+<>?';
    $password = '';
    $maxIndex = strlen($chars) - 1;

    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $maxIndex)];
    }
    return $password;
}

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'An unknown error occurred.'
];

try {
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // Get and validate input data
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8') : '';
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone']), ENT_QUOTES, 'UTF-8') : '';
    $role = isset($_POST['role']) ? htmlspecialchars(trim($_POST['role']), ENT_QUOTES, 'UTF-8') : '';
    $campus = isset($_POST['campus']) ? htmlspecialchars(trim($_POST['campus']), ENT_QUOTES, 'UTF-8') : null;

    // Validate required fields
    if (empty($name) || empty($email) || empty($role)) {
        throw new Exception('Please fill in all required fields.');
    }

    // Generate a strong password
    $password = generateStrongPassword(16);
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Prepare the SQL query
    $query = "INSERT INTO users (names, email, phone, role, password, image, active, campus) 
              VALUES (?, ?, ?, ?, ?, 'assets/img/av.png', 1, ?)";
    
    $stmt = mysqli_prepare($connection, $query);
    
    if ($stmt === false) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($connection));
    }

    // Bind parameters
    mysqli_stmt_bind_param($stmt, 'ssssss', $name, $email, $phone, $role, $hashed_password, $campus);
    
    // Execute the query
    $result = mysqli_stmt_execute($stmt);
    
    if ($result) {
        // Send welcome email
        $emailSent = sendWelcomeEmail($email, $name, $password);
        
        $response = [
            'status' => 'success',
            'message' => 'User added successfully. ' . 
                        ($emailSent ? 'Welcome email sent.' : 'Failed to send welcome email.')
        ];
    } else {
        throw new Exception('Failed to add user: ' . mysqli_error($connection));
    }
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Close database connection if needed
if (isset($connection)) {
    mysqli_close($connection);
}

// Return JSON response
echo json_encode($response);
