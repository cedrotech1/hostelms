<?php
require_once 'components/hostel_card.php';
require_once 'components/rooms_modal.php';

$studentregnumber = $_SESSION['student_regnumber'];

// check for blacklist
$query = "SELECT * FROM blacklist WHERE regnumber = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("s", $studentregnumber);
$stmt->execute();
$result = $stmt->get_result();

$reson = $result->fetch_assoc();

$isBlacklisted = false;
$reason = null;

if ($reson) {
    $isBlacklisted = true;
    $reason = $reson['reason'] ?? 'Not specified';
}
?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<?php if ($isBlacklisted): ?>
    <div class="container-fluid py-4">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            You are currently blacklisted from applying for hostels.
            <br>Reason: <?php echo htmlspecialchars($reason); ?>
            <br>
            Please contact the hostel management for more information.
        </div>
    </div>
<?php else: ?>
    <div class="container-fluid py-4">
        <?php if (!isset($hostels) || empty($hostels)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No hostels available at the moment.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($hostels as $hostel): ?>
                    <?php displayHostelCard($hostel, $connection); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
