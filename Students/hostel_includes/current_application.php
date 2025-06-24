<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
.application-details .card {
    background: rgba(255, 255, 255, 0.97);
    backdrop-filter: blur(10px);
    border: none;
    border-radius: 12px;
    /* box-shadow: 0 4px 16px rgba(30, 60, 114, 0.08); */
    position: relative;
    overflow: hidden;
}

.application-details .card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #1e3c72, #2a5298, #667eea);
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}

.application-details .card-header {
    border-radius: 12px 12px 0 0 !important;
    padding: 0.7rem 1rem;
    background: transparent;
    border-bottom: 1px solid #eee;
}

.application-details .card-header h4,
.application-details .card-header h5 {
    font-size: 1rem;
    font-weight: 700;
    color: #1e3c72;
    margin: 0;
}

.application-details .card-body {
    padding: 0.7rem;
}

.application-info-item {
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    background-color: #f8f9fa;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.application-info-item:hover {
    background-color: #e9ecef;
}

.application-info-item label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e3c72;
    margin-bottom: 0;
}

.application-info-item p {
    font-size: 0.875rem;
    color: #212529;
    margin-bottom: 0;
}

.receipt-upload .card,
.current-receipt .card {
    border: 1px solid #eee;
    margin-bottom: 0.7rem;
}

.receipt-upload .card-header {
    background-color: transparent;
    border-bottom: 1px solid #eee;
}

.roommate-card {
    padding: 0.7rem;
    margin-bottom: 0.7rem;
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #eee;
    transition: all 0.2s ease;
}

.roommate-card:hover {
    background-color: #e9ecef;
}

.roommate-card:last-child {
    margin-bottom: 0;
}

.roommate-card h6 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1e3c72;
    margin-bottom: 0.3rem;
}

.roommate-card p {
    font-size: 0.8rem;
}

.badge {
    padding: 0.4em 0.6em;
    font-weight: 500;
    font-size: 0.75rem;
}

.btn {
    padding: 0.4rem 0.8rem;
    font-weight: 500;
    font-size: 0.875rem;
}

.btn-sm {
    padding: 0.2rem 0.4rem;
    font-size: 0.8rem;
}

.form-control {
    padding: 0.4rem 0.7rem;
    font-size: 0.875rem;
}

.form-label {
    font-size: 0.875rem;
    margin-bottom: 0.3rem;
}

.text-muted {
    font-size: 0.8rem;
}

.mb-4 {
    margin-bottom: 0.7rem !important;
}

.mt-4 {
    margin-top: 0.7rem !important;
}

.mb-3 {
    margin-bottom: 0.5rem !important;
}

.mt-3 {
    margin-top: 0.5rem !important;
}

.me-2 {
    margin-right: 0.4rem !important;
}

.g-4 {
    gap: 0.7rem !important;
}
</style>

<?php
if (!isset($current_application)) {
    return;
}
?>
<div class="container">
<div class="application-details">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0 text-white"><i class="bi bi-house-door"></i> Current Application</h4>
        </div>
        <div class="card-body">
            <div class="row">
            <?php
            include("./my_application_status.php");


?>



            
            </div>

            <!-- Receipt Upload Section -->
            <div class="receipt-upload mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Payment Receipt</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Please upload your bank payment receipt for RWF 40,000</p>
                        
                        <?php if ($current_application['slep']): ?>
                            <div class="current-receipt mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="mb-3"><i class="bi bi-file-earmark-text me-2"></i>Current Receipt</h6>
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <img src="./uploads/receipts/<?php echo htmlspecialchars($current_application['slep']); ?>" 
                                                     class="img-thumbnail" style="max-height: 150px;" 
                                                     alt="Payment Receipt">
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <a href="./uploads/receipts/<?php echo htmlspecialchars($current_application['slep']); ?>" 
                                                   class="btn btn-sm btn-info me-2" target="_blank">
                                                    <i class="bi bi-eye me-1"></i> View Full
                                                </a>
                                                <form action="delete_receipt.php" method="POST" class="d-inline me-2">
                                                    <input type="hidden" name="application_id" value="<?php echo $current_application['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this receipt?')">
                                                        <i class="bi bi-trash me-1"></i> Delete
                                                    </button>
                                                </form>
                                                <button id="edit-receipt-btn" class="btn btn-warning btn-sm"><i class="bi bi-pencil me-1"></i> Edit</button>
                                            </div>
                                        </div>
                                        <?php if (!empty($current_application['ReceptNumber']) || !empty($current_application['Date_of_payment'])): ?>
                                        <div class="row mt-3">
                                            <?php if (!empty($current_application['ReceptNumber'])): ?>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted">
                                                    <i class="bi bi-receipt me-2"></i>Receipt Number
                                                </label>
                                                <p class="mb-0"><?php echo htmlspecialchars($current_application['ReceptNumber']); ?></p>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($current_application['Date_of_payment'])): ?>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted">
                                                    <i class="bi bi-calendar me-2"></i>Date of Payment
                                                </label>
                                                <p class="mb-0"><?php echo htmlspecialchars($current_application['Date_of_payment']); ?></p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Show Edit button, hide form by default -->
                            <div id="receipt-form-container" style="display:none;">
                        <?php else: ?>
                            <div id="receipt-form-container">
                        <?php endif; ?>
                        <form action="upload_receipt.php" method="POST" enctype="multipart/form-data" class="mt-3">
                            <input type="hidden" name="application_id" value="<?php echo $current_application['id']; ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="receipt_number" class="form-label">
                                        <i class="bi bi-receipt me-2"></i>Receipt Number
                                    </label>
                                    <input type="text" class="form-control" id="receipt_number" name="receipt_number" 
                                           placeholder="Enter receipt number" required value="<?php echo isset($current_application['ReceptNumber']) ? htmlspecialchars($current_application['ReceptNumber']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="date_of_payment" class="form-label">
                                        <i class="bi bi-calendar me-2"></i>Date of Payment
                                    </label>
                                    <input type="date" class="form-control" id="date_of_payment" name="date_of_payment" 
                                           required value="<?php echo isset($current_application['Date_of_payment']) ? htmlspecialchars($current_application['Date_of_payment']) : ''; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="receipt" class="form-label">
                                    <i class="bi bi-upload me-2"></i>Upload Receipt File
                                </label>
                                <input type="file" class="form-control" id="receipt" name="receipt" 
                                       accept="image/*,.pdf" required>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Accepted formats: JPG, PNG, PDF (Max size: 2MB)
                                </small>
                                <div id="receipt-preview-container" class="mt-2" style="display:none;">
                                    <label class="form-label">Preview:</label><br>
                                    <img id="receipt-preview" src="#" alt="Receipt Preview" style="max-width: 200px; max-height: 150px; display: none; border: 1px solid #ccc; border-radius: 6px;" />
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input class="form-check-input" type="checkbox" id="declaration" name="declaration" required>
                                <label class="form-check-label" for="declaration">
                                    <?php
                                    $student_name = isset($_SESSION['student_name']) ? htmlspecialchars($_SESSION['student_name']) : '________';
                                    $student_reg = isset($current_application['regnumber']) ? htmlspecialchars($current_application['regnumber']) : '________';
                                    ?>
                                    I, <strong><?php echo $student_name; ?></strong>, Reg. No: <strong><?php echo $student_reg; ?></strong>, hereby declare that all information provided herein is true and valid. I understand that if any information is found to be false, or if I am found to have sold or transferred my hostel allocation to another person, or violated any hostel rules, I accept all consequences, including forfeiting my hostel allocation to another student without refund.
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-cloud-upload me-2"></i>
                                <?php echo $current_application['slep'] ? 'Update Receipt' : 'Upload Receipt'; ?>
                            </button>
                        </form>
                        </div>
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var editBtn = document.getElementById('edit-receipt-btn');
                            var formContainer = document.getElementById('receipt-form-container');
                            if(editBtn && formContainer) {
                                editBtn.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    formContainer.style.display = 'block';
                                    editBtn.style.display = 'none';
                                });
                            }

                            // Image preview for receipt upload
                            var receiptInput = document.getElementById('receipt');
                            var previewContainer = document.getElementById('receipt-preview-container');
                            var previewImg = document.getElementById('receipt-preview');
                            if(receiptInput && previewContainer && previewImg) {
                                receiptInput.addEventListener('change', function(e) {
                                    var file = this.files[0];
                                    if(file && file.type.startsWith('image/')) {
                                        var reader = new FileReader();
                                        reader.onload = function(e) {
                                            previewImg.src = e.target.result;
                                            previewImg.style.display = 'block';
                                            previewContainer.style.display = 'block';
                                        }
                                        reader.readAsDataURL(file);
                                    } else {
                                        previewImg.src = '#';
                                        previewImg.style.display = 'none';
                                        previewContainer.style.display = 'none';
                                    }
                                });
                            }
                        });
                        </script>
                    </div>
                </div>
            </div>

            <!-- Roommates Section -->
            <div class="roommates-section mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-people me-2"></i>Your Roommates</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $roommates_query = "SELECT s.*, a.status 
                                          FROM applications a
                                          JOIN info s ON a.regnumber = s.regnumber
                                          WHERE a.room_id = ? AND a.regnumber != ?";
                        $roommates_stmt = $connection->prepare($roommates_query);
                        $roommates_stmt->bind_param("is", $current_application['room_id'], $_SESSION['student_regnumber']);
                        $roommates_stmt->execute();
                        $roommates = $roommates_stmt->get_result();
                        
                        if ($roommates->num_rows > 0):
                            while ($roommate = $roommates->fetch_assoc()):
                        ?>
                            <div class="roommate-card">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="mb-1">
                                            <i class="bi bi-person me-2"></i>
                                            <?php echo htmlspecialchars($roommate['names']); ?>
                                        </h6>
                                        <p class="text-muted mb-0">
                                            <i class="bi bi-card-text me-2"></i>
                                            <?php echo htmlspecialchars($roommate['regnumber']); ?> | 
                                            <i class="bi bi-building me-2"></i>
                                            <?php echo htmlspecialchars($roommate['college']); ?> | 
                                            <i class="bi bi-mortarboard me-2"></i>
                                            Year <?php echo htmlspecialchars($roommate['yearofstudy']); ?>
                                            <i class="bi bi-phone me-2"></i>
                                            <?php echo htmlspecialchars($roommate['phone']); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="badge bg-<?php 
                                            echo $roommate['status'] === 'approved' ? 'success' : 
                                                ($roommate['status'] === 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($roommate['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <div class="text-center py-4">
                                <i class="bi bi-people text-muted" style="font-size: 2rem;"></i>
                                <p class="text-muted mt-2 mb-0">No roommates assigned yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 
</div>