<?php
// session_start();
include 'connection.php';

// Enable error logging for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// Get the logged-in warefare user's ID and campus
$warefare_id = $_SESSION['id'];
$campus_query = "SELECT campus FROM users WHERE id = ? AND role = 'warefare'";
$stmt = $connection->prepare($campus_query);
if (!$stmt) {
    error_log("Campus query prepare failed: " . $connection->error);
    echo "<script>alert('Error fetching user campus: " . mysqli_error($connection) . "');</script>";
    exit;
}
$stmt->bind_param("i", $warefare_id);
$stmt->execute();
$campus_result = $stmt->get_result();
if ($campus_result->num_rows === 0) {
    error_log("No campus found for warefare user ID: $warefare_id");
    echo "<script>alert('User not found or invalid role.');</script>";
    exit;
}
$campus = $campus_result->fetch_assoc()['campus'];
if (!$campus) {
    error_log("Warefare user ID: $warefare_id has no campus assigned");
    echo "<script>alert('No campus assigned to this user.');</script>";
    exit;
}

// --- 1. Fetch all hostels on the user's campus ---
$hostels = [];
$hostel_query = "SELECT id, name, building_code FROM hostels WHERE campus_id = ? ORDER BY name";
$stmt = $connection->prepare($hostel_query);
$stmt->bind_param("i", $campus);
$stmt->execute();
$hostel_result = $stmt->get_result();
while ($row = $hostel_result->fetch_assoc()) {
    $hostels[] = $row;
}

// --- 2. Get filter/search values from GET/POST ---
$selected_hostel = isset($_GET['hostel']) ? intval($_GET['hostel']) : (isset($hostels[0]['id']) ? $hostels[0]['id'] : 0);
$selected_room = isset($_GET['room']) ? intval($_GET['room']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// --- 3. Fetch rooms for selected hostel ---
$rooms = [];
if ($selected_hostel) {
    $room_query = "SELECT id, room_code FROM rooms WHERE hostel_id = ? ORDER BY room_code";
    $stmt = $connection->prepare($room_query);
    $stmt->bind_param("i", $selected_hostel);
    $stmt->execute();
    $room_result = $stmt->get_result();
    while ($row = $room_result->fetch_assoc()) {
        $rooms[] = $row;
    }
}

// --- 4. Build claims query with filters/search/pagination ---
$where = "WHERE h.campus_id = ?";
$params = [$campus];
$types = "i";

if ($selected_hostel) {
    $where .= " AND h.id = ?";
    $params[] = $selected_hostel;
    $types .= "i";
}
if ($selected_room) {
    $where .= " AND r.id = ?";
    $params[] = $selected_room;
    $types .= "i";
}
if ($status_filter && in_array($status_filter, ['pending', 'approved', 'rejected'])) {
    $where .= " AND c.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
if ($search) {
    $where .= " AND (i.names LIKE ? OR c.regnumber LIKE ? OR c.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}
if ($category_filter && $category_filter !== '') {
    $where .= " AND c.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

// Show archived or not
$show_archived = isset($_GET['archived']) && $_GET['archived'] == 1;
$where .= $show_archived ? " AND c.archived = 1" : " AND (c.archived IS NULL OR c.archived = 0)";

// --- 5. Get total count for pagination ---
$count_query = "SELECT COUNT(*) as total
                FROM claiming c
                JOIN rooms r ON c.room_id = r.id
                JOIN hostels h ON r.hostel_id = h.id
                JOIN info i ON c.regnumber = i.regnumber
                $where";
$stmt = $connection->prepare($count_query);
if (!$stmt) {
    error_log("Count query prepare failed: " . $connection->error);
    echo "<script>alert('Error preparing count query: " . mysqli_error($connection) . "');</script>";
    exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$count_result = $stmt->get_result();
$total_claims = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_claims / $per_page);

// --- 6. Get paginated claims ---
$claims_query = "SELECT c.*, r.room_code, h.name as hostel_name, i.names as student_name, i.phone 
                 FROM claiming c
                 JOIN rooms r ON c.room_id = r.id
                 JOIN hostels h ON r.hostel_id = h.id
                 JOIN info i ON c.regnumber = i.regnumber
                 $where
                 ORDER BY c.created_at DESC
                 LIMIT $per_page OFFSET $offset";
$stmt = $connection->prepare($claims_query);
if (!$stmt) {
    error_log("Claims query prepare failed: " . $connection->error);
    echo "<script>alert('Error preparing claims query: " . mysqli_error($connection) . "');</script>";
    exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$claims_result = $stmt->get_result();

// --- Statistics for cards ---
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'categories' => []
];

// Get stats for status
$status_stats_query = "SELECT c.status, COUNT(*) as count FROM claiming c
    JOIN rooms r ON c.room_id = r.id
    JOIN hostels h ON r.hostel_id = h.id
    WHERE h.campus_id = ? GROUP BY c.status";
$stmt = $connection->prepare($status_stats_query);
$stmt->bind_param("i", $campus);
$stmt->execute();
$status_stats_result = $stmt->get_result();
while ($row = $status_stats_result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
    $stats['total'] += $row['count'];
}

// Get stats for categories
$category_stats_query = "SELECT c.category, COUNT(*) as count FROM claiming c
    JOIN rooms r ON c.room_id = r.id
    JOIN hostels h ON r.hostel_id = h.id
    WHERE h.campus_id = ? GROUP BY c.category";
$stmt = $connection->prepare($category_stats_query);
$stmt->bind_param("i", $campus);
$stmt->execute();
$category_stats_result = $stmt->get_result();
while ($row = $category_stats_result->fetch_assoc()) {
    $stats['categories'][$row['category']] = $row['count'];
}

// Handle archive/unarchive
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['archive_claim'])) {
    $claim_id = mysqli_real_escape_string($connection, $_POST['claim_id']);
    $archive = isset($_POST['unarchive']) ? 0 : 1;
    $update_query = "UPDATE claiming SET archived = ? WHERE id = ?";
    $stmt = $connection->prepare($update_query);
    $stmt->bind_param("ii", $archive, $claim_id);
    $stmt->execute();
    echo "<script>window.location.href='manage_campus_claims.php" . (isset($_GET['archived']) ? "?archived=1" : "") . "';</script>";
    exit();
}

// Handle reply_claim POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_claim'])) {
    $claim_id = mysqli_real_escape_string($connection, $_POST['claim_id']);
    $replay = mysqli_real_escape_string($connection, $_POST['replay']);
    $status = mysqli_real_escape_string($connection, $_POST['status']);
    $repliedby = $_SESSION['id'];
    $update_query = "UPDATE claiming SET replay = ?, status = ?, repliedby = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $connection->prepare($update_query);
    $stmt->bind_param("ssii", $replay, $status, $repliedby, $claim_id);
    if ($stmt->execute()) {
        echo "<script>alert('Claim updated successfully!'); window.location.href='manage_campus_claims.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error updating claim: " . $stmt->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="description">
    <meta content="" name="keywords">
    <link href="assets/img/icon1.png" rel="icon">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/lightbox2/css/lightbox.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <title>UR-HOSTELS - Manage Campus Claims</title>
</head>

<body>
    <?php include("./includes/header.php"); include("./includes/menu.php"); ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Manage Campus Room Claims</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">Manage Campus Claims</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">All Room Claims on Campus</h5>
                            
                            <!-- Filter/Search Form -->
                            <form class="row g-3 mb-4" method="get" action="">
                                <div class="col-md-3">
                                    <label for="hostel" class="form-label">Hostel</label>
                                    <select class="form-select" name="hostel" id="hostel" onchange="this.form.submit()">
                                        <option value="0">All Hostels</option>
                                        <?php foreach ($hostels as $hostel): ?>
                                            <option value="<?php echo $hostel['id']; ?>" <?php if ($selected_hostel == $hostel['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($hostel['name']) . ' (' . htmlspecialchars($hostel['building_code']) . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="room" class="form-label">Room</label>
                                    <select class="form-select" name="room" id="room" onchange="this.form.submit()">
                                        <option value="0">All Rooms</option>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?php echo $room['id']; ?>" <?php if ($selected_room == $room['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($room['room_code']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" name="status" id="status" onchange="this.form.submit()">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?php if ($status_filter == 'pending') echo 'selected'; ?>>Pending</option>
                                        <option value="approved" <?php if ($status_filter == 'approved') echo 'selected'; ?>>Approved</option>
                                        <option value="rejected" <?php if ($status_filter == 'rejected') echo 'selected'; ?>>Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" name="category" id="category" onchange="this.form.submit()">
                                        <option value="">All Categories</option>
                                        <option value="Water" <?php if ($category_filter == 'Water') echo 'selected'; ?>>Water</option>
                                        <option value="Toilets" <?php if ($category_filter == 'Toilets') echo 'selected'; ?>>Toilets</option>
                                        <option value="Room Accessories" <?php if ($category_filter == 'Room Accessories') echo 'selected'; ?>>Room Accessories</option>
                                        <option value="Electricity" <?php if ($category_filter == 'Electricity') echo 'selected'; ?>>Electricity</option>
                                        <option value="Cleanliness" <?php if ($category_filter == 'Cleanliness') echo 'selected'; ?>>Cleanliness</option>
                                        <option value="Other" <?php if ($category_filter == 'Other') echo 'selected'; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" class="form-control" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Enter search term">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                </div>
                            </form>
                            
                            <!-- Toggle Statistics Cards Button -->
                            <div class="mb-3 text-end">
                                <button class="btn btn-outline-info" type="button" id="toggleStatsBtn" onclick="toggleStatsCards()">
                                    <i class="bi bi-bar-chart"></i> Show/Hide Statistics
                                </button>
                            </div>
                            
                            <!-- Statistics Cards Wrapper -->
                            <div id="statsCardsWrapper" style="display:none;">
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="card text-white bg-primary mb-3 shadow">
                                            <div class="card-body">
                                                <h6 class="card-title mb-1"><i class="bi bi-collection me-2"></i>Total Claims</h6>
                                                <h3 class="card-text mb-0"><?php echo $stats['total']; ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card text-white bg-warning mb-3 shadow">
                                            <div class="card-body">
                                                <h6 class="card-title mb-1"><i class="bi bi-hourglass-split me-2"></i>Pending</h6>
                                                <h3 class="card-text mb-0"><?php echo $stats['pending']; ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card text-white bg-success mb-3 shadow">
                                            <div class="card-body">
                                                <h6 class="card-title mb-1"><i class="bi bi-check-circle me-2"></i>Approved</h6>
                                                <h3 class="card-text mb-0"><?php echo $stats['approved']; ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card text-white bg-danger mb-3 shadow">
                                            <div class="card-body">
                                                <h6 class="card-title mb-1"><i class="bi bi-x-circle me-2"></i>Rejected</h6>
                                                <h3 class="card-text mb-0"><?php echo $stats['rejected']; ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-4 g-3">
                                    <?php
                                    $cat_icons = [
                                        'Water' => 'bi-droplet',
                                        'Toilets' => 'bi-bucket',
                                        'Room Accessories' => 'bi-lamp',
                                        'Electricity' => 'bi-lightning-charge',
                                        'Cleanliness' => 'bi-broom',
                                        'Other' => 'bi-three-dots',
                                    ];
                                    $cat_colors = [
                                        'Water' => 'bg-info',
                                        'Toilets' => 'bg-secondary',
                                        'Room Accessories' => 'bg-primary',
                                        'Electricity' => 'bg-warning',
                                        'Cleanliness' => 'bg-success',
                                        'Other' => 'bg-dark',
                                    ];
                                    foreach ($stats['categories'] as $cat => $count):
                                        $icon = isset($cat_icons[$cat]) ? $cat_icons[$cat] : 'bi-tag';
                                        $color = isset($cat_colors[$cat]) ? $cat_colors[$cat] : 'bg-secondary';
                                    ?>
                                        <div class="col-md-2 col-6">
                                            <div class="card text-white <?php echo $color; ?> mb-3 shadow">
                                                <div class="card-body text-center">
                                                    <i class="bi <?php echo $icon; ?>" style="font-size:2rem;"></i>
                                                    <h6 class="card-title mt-2 mb-1"><?php echo htmlspecialchars($cat); ?></h6>
                                                    <h4 class="card-text mb-0"><?php echo $count; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <!-- Toggle archived view -->
                            <div class="mb-3">
                                <?php if ($show_archived): ?>
                                    <a href="manage_campus_claims.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> View Active Claims</a>
                                <?php else: ?>
                                    <a href="manage_campus_claims.php?archived=1" class="btn btn-outline-secondary"><i class="bi bi-archive"></i> View Archived Claims</a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($claims_result->num_rows > 0) { ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Phone</th>
                                                <th>Room</th>
                                                <th>Category</th>
                                                <th>Message</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($claim = $claims_result->fetch_assoc()) { ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($claim['student_name']); ?><br><small><?php echo htmlspecialchars($claim['regnumber']); ?></small></td>
                                                    <td><?php echo htmlspecialchars($claim['phone']); ?></td>
                                                    <td><?php echo htmlspecialchars($claim['hostel_name']) . ' - ' . htmlspecialchars($claim['room_code']); ?></td>
                                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($claim['category']); ?></span></td>
                                                    <td><?php echo substr(htmlspecialchars($claim['message']), 0, 100) . '...'; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $claim['status'] == 'approved' ? 'success' : ($claim['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                            <?php echo ucfirst(htmlspecialchars($claim['status'])); ?>
                                                        </span>
                                                        <?php if (!empty($claim['image'])): ?>
                                                            <div class="mt-2">
                                                                <a href="../<?php echo htmlspecialchars($claim['image']); ?>" data-lightbox="claim-image-<?php echo $claim['id']; ?>" data-title="Claim Image">
                                                                    <i class="bi bi-image"></i> View Image
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($claim['created_at'])); ?></td>
                                                    <td>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                                            <?php if ($show_archived): ?>
                                                                <button type="submit" name="archive_claim" value="1" class="btn btn-success btn-sm" onclick="return confirm('Unarchive this claim?');"><i class="bi bi-arrow-up-circle"></i> Unarchive</button>
                                                                <input type="hidden" name="unarchive" value="1">
                                                            <?php else: ?>
                                                                <button type="submit" name="archive_claim" value="1" class="btn btn-secondary btn-sm" onclick="return confirm('Archive this claim?');"><i class="bi bi-archive"></i> Archive</button>
                                                            <?php endif; ?>
                                                        </form>
                                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#replyModal<?php echo $claim['id']; ?>">
                                                            Reply
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Reply Modal -->
                                                <div class="modal fade" id="replyModal<?php echo $claim['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Reply to Claim</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Student</label>
                                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($claim['student_name']) . ' (' . htmlspecialchars($claim['regnumber']) . ')'; ?>" readonly>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Room</label>
                                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($claim['hostel_name']) . ' - ' . htmlspecialchars($claim['room_code']); ?>" readonly>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Claim Message</label>
                                                                        <textarea class="form-control" rows="4" readonly><?php echo htmlspecialchars($claim['message']); ?></textarea>
                                                                    </div>
                                                                    <?php if (!empty($claim['image'])): ?>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Claim Image</label>
                                                                            <div>
                                                                                <a href="../<?php echo htmlspecialchars($claim['image']); ?>" data-lightbox="claim-image-<?php echo $claim['id']; ?>-modal" data-title="Claim Image">
                                                                                    <img src="../<?php echo htmlspecialchars($claim['image']); ?>" alt="Claim Image" class="img-thumbnail" style="max-height: 200px; width: auto; cursor: pointer;">
                                                                                </a>
                                                                            </div>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Status</label>
                                                                        <select class="form-select" name="status" required>
                                                                            <option value="pending" <?php echo ($claim['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                                            <option value="approved" <?php echo ($claim['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                                                            <option value="rejected" <?php echo ($claim['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Response</label>
                                                                        <textarea class="form-control" name="replay" rows="4" placeholder="Enter your response..."><?php echo htmlspecialchars($claim['replay'] ?? ''); ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="reply_claim" class="btn btn-primary">Save</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Pagination Controls -->
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center mt-4">
                                        <?php
                                        $params = $_GET;
                                        // Previous button
                                        $prev_page = $page - 1;
                                        ?>
                                        <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                            <a class="page-link" href="?<?php $params['page'] = $prev_page; echo http_build_query($params); ?>" tabindex="-1">Previous</a>
                                        </li>
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                                <a class="page-link" href="?<?php $params['page'] = $i; echo http_build_query($params); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <?php
                                        // Next button
                                        $next_page = $page + 1;
                                        ?>
                                        <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                                            <a class="page-link" href="?<?php $params['page'] = $next_page; echo http_build_query($params); ?>">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php } else { ?>
                                <div class="text-center py-5">
                                    <h5>No Claims Found</h5>
                                    <p class="text-muted">No room claims match your criteria.</p>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/lightbox2/js/lightbox.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Initialize lightbox with custom settings
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'showImageNumberLabel': false,
            'disableScrolling': true
        });
    </script>
    <script>
    function toggleStatsCards() {
        var wrapper = document.getElementById('statsCardsWrapper');
        if (wrapper.style.display === 'none') {
            wrapper.style.display = '';
        } else {
            wrapper.style.display = 'none';
        }
    }
    </script>
</body>
</html>