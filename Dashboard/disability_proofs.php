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

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['proof_id'])) {
    $proof_id = mysqli_real_escape_string($connection, $_POST['proof_id']);
    $action = $_POST['action'];
    $user_id = mysqli_real_escape_string($connection, $_SESSION['id']);
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $query = "UPDATE proof_of_disability SET status = '$status', approved_by = '$user_id' WHERE id = '$proof_id'";
        mysqli_query($connection, $query);
        // Redirect to refresh the page
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);
        exit();
    }
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
$status = isset($_GET['status']) ? mysqli_real_escape_string($connection, trim($_GET['status'])) : '';

// Build the query
$query = "
    SELECT p.id, p.regnumber, p.file, p.created_at, p.status, p.approved_by,
           i.names, i.program, i.yearofstudy, i.gender, c.name AS campus_name
    FROM proof_of_disability p
    JOIN info i ON p.regnumber = i.regnumber
    JOIN campuses c ON i.campus = c.name
    WHERE c.id = '$campus_id'
";

// Add search conditions
if ($search) {
    $query .= " AND (p.regnumber LIKE '%$search%' OR i.names LIKE '%$search%' OR i.program LIKE '%$search%')";
}

// Add filter conditions
if ($gender) {
    $query .= " AND i.gender = '$gender'";
}
if ($yearofstudy) {
    $query .= " AND i.yearofstudy = '$yearofstudy'";
}
if ($status) {
    $query .= " AND p.status = '$status'";
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM ($query) AS total";
$count_result = mysqli_query($connection, $count_query);
if (!$count_result) {
    die("Count query failed: " . mysqli_error($connection));
}
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch records for the current page
$query .= " LIMIT $offset, $records_per_page";
$result = mysqli_query($connection, $query);
if (!$result) {
    die("Query failed: " . mysqli_error($connection));
}
$proofs = [];
while ($row = mysqli_fetch_assoc($result)) {
    $proofs[] = $row;
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

$statuses = ['pending', 'approved', 'rejected'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proof of Disability</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Proof of Disability</h1>

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
                    <select name="status" class="w-full p-2 border rounded">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $status === $s ? 'selected' : ''; ?>>
                                <?php echo ucfirst(htmlspecialchars($s)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Filter</button>
            </form>
        </div>

        <!-- Proofs Table -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-3 text-left">Reg. Number</th>
                        <th class="p-3 text-left">Name</th>
                        <th class="p-3 text-left">Program</th>
                        <th class="p-3 text-left">Year</th>
                        <th class="p-3 text-left">Gender</th>
                        <th class="p-3 text-left">Campus</th>
                        <th class="p-3 text-left">Uploaded On</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proofs)): ?>
                        <tr>
                            <td colspan="9" class="p-3 text-center">No proofs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($proofs as $proof): ?>
                            <tr class="border-t">
                                <td class="p-3"><?php echo htmlspecialchars($proof['regnumber']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($proof['names']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($proof['program']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($proof['yearofstudy']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($proof['gender']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($proof['campus_name']); ?></td>
                                <td class="p-3"><?php echo date('Y-m-d H:i', strtotime($proof['created_at'])); ?></td>
                                <td class="p-3"><?php echo ucfirst(htmlspecialchars($proof['status'])); ?></td>
                                <td class="p-3">
                                    <button onclick="openModal('<?php echo htmlspecialchars($proof['file']); ?>', <?php echo $proof['id']; ?>)" 
                                            class="bg-green-500 text-white p-1 rounded hover:bg-green-600">View</button>
                                    <?php if ($proof['status'] === 'pending'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="proof_id" value="<?php echo $proof['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="bg-blue-500 text-white p-1 rounded hover:bg-blue-600">Approve</button>
                                        </form>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="proof_id" value="<?php echo $proof['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="bg-red-500 text-white p-1 rounded hover:bg-red-600">Reject</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
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
                    Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> proofs
                </div>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender); ?>&yearofstudy=<?php echo urlencode($yearofstudy); ?>&status=<?php echo urlencode($status); ?>" 
                           class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender); ?>&yearofstudy=<?php echo urlencode($yearofstudy); ?>&status=<?php echo urlencode($status); ?>" 
                           class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal for PDF View -->
    <div id="pdfModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-4 rounded-lg w-3/4 h-3/4 max-w-4xl max-h-screen overflow-auto">
            <div class="flex justify-between mb-2">
                <h2 class="text-xl font-bold">View PDF</h2>
                <button onclick="closeModal()" class="text-red-500">Close</button>
            </div>
            <iframe id="pdfFrame" class="w-full h-96" frameborder="0"></iframe>
            <div class="mt-4 flex gap-4">
                <a id="downloadLink" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Download</a>
                <a id="newTabLink" target="_blank" class="bg-green-500 text-white p-2 rounded hover:bg-green-600">View in New Tab</a>
            </div>
        </div>
    </div>

    <script>
        function openModal(filePath, proofId) {
            const modal = document.getElementById('pdfModal');
            const frame = document.getElementById('pdfFrame');
            const downloadLink = document.getElementById('downloadLink');
            const newTabLink = document.getElementById('newTabLink');

            // Ensure file path is relative to the Students/ folder
            const fullPath = filePath.startsWith('../Students/') ? filePath : '../Students/' + filePath;
            frame.src = fullPath;
            downloadLink.href = fullPath;
            downloadLink.download = filePath.split('/').pop();
            newTabLink.href = fullPath;

            modal.classList.remove('hidden');
        }

        function closeModal() {
            const modal = document.getElementById('pdfModal');
            const frame = document.getElementById('pdfFrame');
            frame.src = '';
            modal.classList.add('hidden');
        }
    </script>
</body>
</html>