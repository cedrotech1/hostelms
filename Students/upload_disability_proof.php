<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION['student_regnumber']) || !isset($_SESSION['student_disability'])) {
    header("Location: login.php");
    exit();
}

// Check if student has disability
if ($_SESSION['student_disability'] != 1) {
    header("Location: index.php");
    exit();
}

// Include database connection
require_once 'connection.php';
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize variables
$regnumber = mysqli_real_escape_string($connection, $_SESSION['student_regnumber']);
$errors = [];
$success = '';

// Check if student already uploaded a proof
$query = "SELECT id, file, status, created_at FROM proof_of_disability WHERE regnumber = '$regnumber'";
$result = mysqli_query($connection, $query);
$existing_proof = mysqli_fetch_assoc($result);

// Handle form submission for new or replacement upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['proof_file'])) {
    // Allow upload if no proof exists or status is pending/rejected
    if (!$existing_proof || in_array($existing_proof['status'], ['pending', 'rejected'])) {
        $file = $_FILES['proof_file'];
        $upload_dir = 'uploads/';
        $max_file_size = 5 * 1024 * 1024; // 5MB

        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload failed. Please try again.";
        } elseif ($file['size'] > $max_file_size) {
            $errors[] = "File size exceeds 5MB limit.";
        } elseif ($file['type'] !== 'application/pdf' || pathinfo($file['name'], PATHINFO_EXTENSION) !== 'pdf') {
            $errors[] = "Only PDF files are allowed.";
        } else {
            // Generate unique file name
            $file_name = $regnumber . '_' . time() . '.pdf';
            $file_path = $upload_dir . $file_name;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Delete old file if replacing
                if ($existing_proof && file_exists($existing_proof['file'])) {
                    unlink($existing_proof['file']);
                }

                // Insert or update proof in database
                if ($existing_proof) {
                    $query = "UPDATE proof_of_disability SET file = '$file_path', status = 'pending', approved_by = NULL, created_at = CURRENT_TIMESTAMP WHERE regnumber = '$regnumber'";
                } else {
                    $query = "INSERT INTO proof_of_disability (regnumber, file, status, created_at) VALUES ('$regnumber', '$file_path', 'pending', CURRENT_TIMESTAMP)";
                }
                $result = mysqli_query($connection, $query);
                if ($result) {
                    $success = "Proof of disability uploaded successfully. It is now pending approval.";
                    $existing_proof = ['id' => mysqli_insert_id($connection) ?: $existing_proof['id'], 'file' => $file_path, 'status' => 'pending', 'created_at' => date('Y-m-d H:i:s')];
                } else {
                    $errors[] = "Failed to save proof to database.";
                    unlink($file_path); // Remove file if DB insert fails
                }
            } else {
                $errors[] = "Failed to move uploaded file.";
            }
        }
    } else {
        $errors[] = "You cannot upload a new proof while the current one is " . ucfirst($existing_proof['status']) . ".";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Proof of Disability</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
   
    <div class="container mx-auto p-6">
    <a href="index.php" style="margin-bottom: 200px;background-color: #4CAF50;color: white;padding: 10px 20px;text-decoration: none;border-radius: 5px;">Back</a>

    <br><br>
       <br> <h1 class="text-2xl font-bold mb-4">Upload Proof of Disability</h1>

        <!-- Error/Success Messages -->
        <?php if (!empty($errors)): ?>
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Current Proof Status -->
        <?php if ($existing_proof): ?>
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <h2 class="text-lg font-semibold mb-2">Current Proof of Disability</h2>
                <p>Status: <span class="font-semibold"><?php echo ucfirst(htmlspecialchars($existing_proof['status'])); ?></span></p>
                <p>Uploaded On: <span class="font-semibold"><?php echo date('Y-m-d H:i', strtotime($existing_proof['created_at'])); ?></span></p>
                <button onclick="openModal('<?php echo htmlspecialchars($existing_proof['file']); ?>')" 
                        class="mt-2 bg-green-500 text-white p-2 rounded hover:bg-green-600">View Proof</button>
                <?php if ($existing_proof['status'] === 'pending'): ?>
                    <p class="mt-2 text-sm text-gray-600">You can replace the pending proof by uploading a new file below.</p>
                <?php else: ?>
                    <p class="mt-2 text-sm text-gray-600">You can upload a new proof only if the current one is rejected.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <?php if (!$existing_proof || in_array($existing_proof['status'], ['pending', 'rejected'])): ?>
            <div class="bg-white p-6 rounded-lg shadow">
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label for="proof_file" class="block text-sm font-medium text-gray-700">
                            <?php echo $existing_proof && $existing_proof['status'] === 'pending' ? 'Replace Pending Proof (PDF, Max 5MB)' : 'Upload Proof of Disability (PDF, Max 5MB)'; ?>
                        </label>
                        <input type="file" name="proof_file" id="proof_file" accept=".pdf" required
                               class="mt-1 block w-full p-2 border rounded">
                    </div>
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">
                        <?php echo $existing_proof && $existing_proof['status'] === 'pending' ? 'Replace Proof' : 'Upload Proof'; ?>
                    </button>
                </form>
                <p class="mt-2 text-sm text-gray-600">Please upload a PDF document verifying your disability. Only PDF files up to 5MB are allowed.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal for PDF View -->
    <div id="pdfModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-4 rounded-lg w-3/4 h-3/4 max-w-4xl max-h-screen overflow-auto">
            <div class="flex justify-between mb-2">
                <h2 class="text-xl font-bold">View Proof of Disability</h2>
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
        // Validate file input on client side
        document.getElementById('proof_file')?.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.type !== 'application/pdf') {
                    alert('Please select a PDF file.');
                    this.value = '';
                } else if (file.size > 5 * 1024 * 1024) {
                    alert('File size exceeds 5MB limit.');
                    this.value = '';
                }
            }
        });

        // Modal functions
        function openModal(filePath) {
            const modal = document.getElementById('pdfModal');
            const frame = document.getElementById('pdfFrame');
            const downloadLink = document.getElementById('downloadLink');
            const newTabLink = document.getElementById('newTabLink');

            frame.src = filePath;
            downloadLink.href = filePath;
            downloadLink.download = filePath.split('/').pop();
            newTabLink.href = filePath;

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