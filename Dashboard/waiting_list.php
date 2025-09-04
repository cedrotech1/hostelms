<?php
// session_start();

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'connection.php';
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get user's campus ID
$user_id = mysqli_real_escape_string($connection, $_SESSION['id']);
$query = "SELECT campus FROM users WHERE id = '$user_id'";
$result = mysqli_query($connection, $query);
if (!$result || mysqli_num_rows($result) == 0) {
    die("User not found.");
}
$user = mysqli_fetch_assoc($result);
$campus_id = mysqli_real_escape_string($connection, $user['campus']);

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search and filter parameters
$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, trim($_GET['search'])) : '';
$gender = isset($_GET['gender']) ? mysqli_real_escape_string($connection, trim($_GET['gender'])) : '';
$yearofstudy = isset($_GET['yearofstudy']) ? mysqli_real_escape_string($connection, trim($_GET['yearofstudy'])) : '';
$disability = isset($_GET['disability']) ? mysqli_real_escape_string($connection, trim($_GET['disability'])) : '';

// Build the query
$query = "
    SELECT w.regnumber, w.created_at, i.names, i.program, i.yearofstudy, i.gender, i.disability, 
           h.name AS hostel_name, r.room_code, c.name AS campus_name
    FROM waiting_list w
    JOIN info i ON w.regnumber = i.regnumber
    JOIN hostels h ON w.hostel_id = h.id
    JOIN rooms r ON w.room_id = r.id
    JOIN campuses c ON h.campus_id = c.id
    WHERE h.campus_id = '$campus_id'
";

// Add search conditions
if ($search) {
    $query .= " AND (i.regnumber LIKE '%$search%' OR i.names LIKE '%$search%' OR i.program LIKE '%$search%')";
}

// Add filter conditions
if ($gender) {
    $query .= " AND i.gender = '$gender'";
}
if ($yearofstudy) {
    $query .= " AND i.yearofstudy = '$yearofstudy'";
}
if ($disability) {
    $query .= " AND i.disability = '$disability'";
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM ($query) AS total";
$count_result = mysqli_query($connection, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch records for the current page
$query .= " LIMIT $offset, $records_per_page";
$result = mysqli_query($connection, $query);
if (!$result) {
    die("Query failed: " . mysqli_error($connection));
}
$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

// Fetch filter options
$genders = [];
$result = mysqli_query($connection, "SELECT DISTINCT gender FROM info WHERE gender IS NOT NULL");
while ($row = mysqli_fetch_assoc($result)) {
    $genders[] = $row['gender'];
}

$years = [];
$result = mysqli_query($connection, "SELECT DISTINCT yearofstudy FROM info WHERE yearofstudy IS NOT NULL");
while ($row = mysqli_fetch_assoc($result)) {
    $years[] = $row['yearofstudy'];
}

$disabilities = [];
$result = mysqli_query($connection, "SELECT DISTINCT disability FROM info WHERE disability IS NOT NULL");
while ($row = mysqli_fetch_assoc($result)) {
    $disabilities[] = $row['disability'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting List Students</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Waiting List Students</h1>

        <!-- Search and Filter Form -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by Reg. Number, Name, or Program" 
                           class="w-full p-2 border rounded">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <select name="gender" class="w-full p-2 border rounded">
                        <option value="">All Genders</option>
                        <?php foreach ($genders as $g): ?>
                            <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $gender === $g ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($g); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <select name="yearofstudy" class="w-full p-2 border rounded">
                        <option value="">All Years</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?php echo htmlspecialchars($y); ?>" <?php echo $yearofstudy === $y ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($y); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <select name="disability" class="w-full p-2 border rounded">
                        <option value="">All Disability Status</option>
                        <?php foreach ($disabilities as $d): ?>
                            <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $disability === $d ? 'selected' : ''; ?>>
                                <?php if($d == '1') echo 'Yes'; else echo 'No'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Filter</button>
            </form>
        </div>

        <!-- Students Table -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-3 text-left">Reg. Number</th>
                        <th class="p-3 text-left">Name</th>
                        <th class="p-3 text-left">Program</th>
                        <th class="p-3 text-left">Year</th>
                        <th class="p-3 text-left">Gender</th>
                        <th class="p-3 text-left">Disability</th>
                        <th class="p-3 text-left">Hostel</th>
                        <th class="p-3 text-left">Room</th>
                        <th class="p-3 text-left">Campus</th>
                        <th class="p-3 text-left">Applied On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="10" class="p-3 text-center">No students found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr class="border-t">
                                <td class="p-3"><?php echo htmlspecialchars($student['regnumber']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($student['names']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($student['program']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($student['yearofstudy']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($student['gender']); ?></td>
                                <td class="p-3"><?php if($student['disability'] == '1') echo 'Yes'; else echo 'No'; ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($student['hostel_name']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($student['room_code']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($student['campus_name']); ?></td>
                                <td class="p-3"><?php echo date('Y-m-d H:i', strtotime($student['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-4 flex justify-between items-center">
                <div>
                    Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> students
                </div>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender); ?>&yearofstudy=<?php echo urlencode($yearofstudy); ?>&disability=<?php echo urlencode($disability); ?>" 
                           class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender); ?>&yearofstudy=<?php echo urlencode($yearofstudy); ?>&disability=<?php echo urlencode($disability); ?>" 
                           class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Client-side search input handling (optional for instant feedback)
        document.querySelector('input[name="search"]').addEventListener('input', function() {
            this.value = this.value.trim();
        });
    </script>
</body>
</html>