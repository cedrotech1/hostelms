<?php
include('connection.php');

// Set timezone
date_default_timezone_set('Africa/Kigali'); // Set to Rwanda timezone

function getTimeAgo($datetime) {
    // Assume input datetime is already in 'Africa/Kigali' timezone
    $dt = new DateTime($datetime, new DateTimeZone('Africa/Kigali'));
    $now = new DateTime('now', new DateTimeZone('Africa/Kigali'));

    $timestamp = $dt->getTimestamp();
    $nowTimestamp = $now->getTimestamp();

    $diff = $nowTimestamp - $timestamp;

    if ($diff < 0) {
        $diff = abs($diff);
        $suffix = 'from now';
    } else {
        $suffix = 'ago';
    }

    if ($diff < 1) {
        return 'just now';
    }

    // Define time units in seconds
    $intervals = array(
        31536000 => 'year',
        2592000  => 'month',
        604800   => 'week',
        86400    => 'day',
        3600     => 'hour',
        60       => 'minute',
        1        => 'second'
    );

    foreach ($intervals as $seconds => $label) {
        $interval = floor($diff / $seconds);
        if ($interval >= 1) {
            $plural = $interval > 1 ? 's' : '';
            return $interval . ' ' . $label . $plural . ' ' . $suffix;
        }
    }

    return 'just now';
}



$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
$status = isset($_GET['status']) ? mysqli_real_escape_string($connection, $_GET['status']) : '';
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$sort_by = isset($_GET['sort_by']) ? mysqli_real_escape_string($connection, $_GET['sort_by']) : 'updated_at';
$sort_order = isset($_GET['sort_order']) ? mysqli_real_escape_string($connection, $_GET['sort_order']) : 'ASC';

$offset = ($page - 1) * $per_page;

$id =$_SESSION['id'];
$sql = "SELECT * FROM users WHERE id = $id";
$result = mysqli_query($connection, $sql);
$row = mysqli_fetch_assoc($result);
$mycampus = $row['campus'];
$role=$row['role'];


// Build the search condition
$search_condition = '';
if (!empty($search)) {
    $search_condition = " AND (
        i.regnumber LIKE '%$search%' OR 
        i.names LIKE '%$search%' OR 
        i.email LIKE '%$search%' OR 
        r.room_code LIKE '%$search%' OR
        h.name LIKE '%$search%' OR
        i.campus LIKE '%$search%' OR
        i.college LIKE '%$search%' OR
        i.school LIKE '%$search%' OR
        i.program LIKE '%$search%'
    )";
}

// Build the status condition
$status_condition = '';
if (!empty($status)) {
    $status_condition = " AND a.status = '$status'";
}

// Add campus condition
$campus_condition = "AND c.id = $mycampus";

// Validate and sanitize sort parameters
$valid_sort_columns = [
    'regnumber' => 'i.regnumber',
    'name' => 'i.names',
    'room' => 'r.room_code',
    'hostel' => 'h.name',
    'status' => 'a.status',
    'campus' => 'i.campus',
    'college' => 'i.college',
    'school' => 'i.school',
    'program' => 'i.program',
    'created_at' => 'a.created_at',
    'updated_at' => 'a.updated_at'
];

$sort_column = isset($valid_sort_columns[$sort_by]) ? $valid_sort_columns[$sort_by] : 'a.updated_at';
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Get applications with pagination, search, and sorting
$query = "SELECT 
            a.*, 
            i.names, 
            i.gender, 
            i.yearofstudy, 
            i.email, 
            i.phone,
            i.campus,
            i.college,
            i.school,
            i.program,
            r.room_code,
            h.name as hostel_name
          FROM applications a
          JOIN info i ON i.regnumber = a.regnumber
          JOIN rooms r ON r.id = a.room_id
          JOIN hostels h ON h.id = r.hostel_id
          JOIN campuses c ON c.id = h.campus_id
          WHERE 1=1 $search_condition $status_condition $campus_condition
          ORDER BY $sort_column $sort_order
          LIMIT $offset, $per_page";

$result = mysqli_query($connection, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo '<table class="table table-hover table-striped">
            <thead class="table-light">
                <tr>
                    <th><a href="#" onclick="sortApplications(\'regnumber\')" class="text-dark">Reg Number <i class="bi bi-arrow-down-up"></i></a></th>
                    <th><a href="#" onclick="sortApplications(\'name\')" class="text-dark">Student Name <i class="bi bi-arrow-down-up"></i></a></th>
                    <th><a href="#" onclick="sortApplications(\'room\')" class="text-dark">Room Details <i class="bi bi-arrow-down-up"></i></a></th>
                    <th><a href="#" onclick="sortApplications(\'status\')" class="text-dark">Status <i class="bi bi-arrow-down-up"></i></a></th>
                    <th>Payment Proof</th>
                    <th><a href="#" onclick="sortApplications(\'created_at\')" class="text-dark">Application Date <i class="bi bi-arrow-down-up"></i></a></th>
                    <th><a href="#" onclick="sortApplications(\'updated_at\')" class="text-dark">Last Updated <i class="bi bi-arrow-down-up"></i></a></th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>';
    
    while ($app = mysqli_fetch_assoc($result)) {
        // Debug output
        error_log("Application ID: " . $app['id']);
        error_log("Updated At: " . $app['updated_at']);
        
        // Set status class based on status
        $status_class = '';
        switch($app['status']) {
            case 'pending': 
                $status_class = 'status-pending';
                break;
            case 'paid':
                $status_class = 'status-paid';
                break;
            case 'approved':
                $status_class = 'status-approved';
                break;
            default:
                $status_class = 'status-' . $app['status'];
        }
        
        $slep_path = !empty($app['slep']) ? '../Students/uploads/receipts/' . $app['slep'] : '';
        $time_ago = getTimeAgo($app['updated_at']);
        
        echo '<tr>
                <td>' . htmlspecialchars($app['regnumber']) . '</td>
                <td>' . htmlspecialchars($app['names']) . '</td>
                <td>' . htmlspecialchars($app['room_code']) . ' (' . htmlspecialchars($app['hostel_name']) . ')</td>
                <td><span class="status-badge ' . $status_class . '">' . ucfirst($app['status']) . '</span></td>
                <td>';
        
        if (!empty($slep_path)) {
            echo '<button class="btn btn-sm btn-info" onclick="showSlepImage(\'' . $slep_path . '\')">
                    <i class="bi bi-image"></i> Receipt
                  </button>';
        } else {
            echo '<span class="text-muted">No Receipt</span>';
        }
        
        echo '</td>
                <td>' . date('M d, Y', strtotime($app['created_at'])) . '</td>
                <td><small class="text-muted">' . $time_ago . '</small></td>
                <td class="text-center">
                    <div class="action-buttons">';
        
        // View details button for all applications
        echo '<button class="btn btn-sm btn-info" onclick="viewApplicationDetails(' . $app['id'] . ')">
                <i class="bi bi-eye"></i> View
              </button>';
        
        // Approve/Reject buttons based on status
        if ($app['status'] === 'paid') {
            echo '<button class="btn btn-sm btn-success" onclick="updateApplicationStatus(' . $app['id'] . ', \'approve\')">
                     <i class="bi bi-check-circle"></i>
                     Approve
                  </button>';
            echo '<button class="btn btn-sm btn-danger" onclick="updateApplicationStatus(' . $app['id'] . ', \'reject\')">
                    <i class="bi bi-x-circle"></i> Reject
                  </button>';
        }
        
        echo '</div>
                </td>
            </tr>';
    }
    
    echo '</tbody></table>';
} else {
    echo '<div class="alert alert-info">No applications found.</div>';
}
?> 