<?php
// session_start();  
include('connection.php');
// include('./includes/auth.php');
// checkUserRole(['information_modifier']);

require_once '../../loadEnv.php';

// Load the .env file
$filePath = __DIR__ . '/../../.env';
loadEnv($filePath);
include("../email_functions.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Add User</title>
  <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="assets/img/icon1.png" rel="icon">
    <link href="assets/img/icon1.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="assets/css/style.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind CSS -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

  <style>
    /* Custom styles for additional polish */
    .form-floating input, .form-floating select {
      transition: all 0.3s ease;
    }
    .form-floating input:focus, .form-floating select:focus {
      border-color: #1e40af;
      box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }
    .table th, .table td {
      vertical-align: middle;
    }
    .btn-primary {
      background-color: #1e40af !important;
      border-color: #1e40af !important;
      transition: all 0.3s ease;
    }
    .btn-primary:hover {
      background-color: #1e3a8a !important;
      border-color: #1e3a8a !important;
    }
    .btn-danger, .btn-warning, .btn-success {
      transition: all 0.3s ease;
    }
    .card {
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
    }
    .back-to-top {
      background-color: #1e40af;
      color: white;
      border-radius: 50%;
      padding: 8px;
    }
    .back-to-top:hover {
      background-color: #1e3a8a;
    }
  </style>

  <script>
    // Function to generate a random password
    function generateRandomPassword(length = 12) {
      const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
      let password = "";
      for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
      }
      return password;
    }

    // Set random password on page load
    function setRandomPassword() {
      const passwordField = document.getElementById('password');
      passwordField.value = generateRandomPassword();
    }

    window.onload = setRandomPassword;
  </script>
</head>

<body class="bg-gray-100 font-inter">
  <?php
  include("./includes/header.php");
  include("./includes/menu.php");
  ?>

  <main id="main" class="">
    <!-- Add User Form -->
    <div class="pagetitle">
        <h1>Data</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item">User</li>
                
            </ol>
        </nav>
    </div><!-- End Page Title -->
    <div class="row">
      <div class="col-lg-5">
    <section class="mb-8">
      <div class="card p-6 bg-white">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Add New User</h2>
        <form class="grid grid-cols-1 gap-4" action="add_user.php" method="post">
          <div class="relative">
            <input type="text" id="floatingName" name="name" class="form-floating w-full p-2 border" placeholder="Name" required>
            <!-- <label for="floatingName" class=" top-2 left-3 text-gray-500 transition-all duration-300">Name</label> -->
          </div>
          <div class="relative">
            <input type="email" id="floatingEmail" name="email" class="form-floating w-full p-2 border" placeholder="Email" required>
            <!-- <label for="floatingEmail" class="absolute top-2 left-3 text-gray-500 transition-all duration-300">Email</label> -->
          </div>
          <div class="relative">
            <input type="tel" id="floatingPhone" name="phone" class="form-floating w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Phone" required>
            <!-- <label for="floatingPhone" class="absolute top-2 left-3 text-gray-500 transition-all duration-300">Phone</label> -->
          </div>
          <div class="relative">
            <select id="floatingRole" name="role" class="form-floating w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required onchange="toggleCampusField()">
              <option value="" disabled selected>Select Role</option>
               -->
              <option value="warefare">Director Welfare</option>
              <option value="head_quarter">Head Quarter (HQ)</option>
            </select>
            <!-- <label for="floatingRole" class="absolute top-2 left-3 text-gray-500 transition-all duration-300">Role</label> -->
          </div>
          <div id="campusField" class="relative hidden">
            <select id="floatingCampus" name="campus" class="form-floating w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="" disabled selected>Select Campus</option>
              <?php
              $campusQuery = "SELECT * FROM campuses";
              $campusResult = mysqli_query($connection, $campusQuery);
              while ($campus = mysqli_fetch_assoc($campusResult)) {
                echo "<option value='" . $campus['id'] . "'>" . ucwords($campus['name']) . "</option>";
              }
              ?>
            </select>
            <!-- <label for="floatingCampus" class="absolute top-2 left-3 text-gray-500 transition-all duration-300">Campus</label> -->
          </div>
          <input type="hidden" id="password" name="password">
          <div class="flex justify-center space-x-4">
            <button type="submit" name="saveuser" class="btn-primary px-4 py-2 rounded-md text-white">Save User</button>
            <button type="reset" class="px-4 py-2 rounded-md bg-gray-500 text-white hover:bg-gray-600">Reset</button>
          </div>
        </form>
      </div>
    </section>
    </div>
    <?php
    $query = "SELECT * FROM users WHERE role != 'admin'";
    $result = mysqli_query($connection, $query);
    if (mysqli_num_rows($result) > 0) {
    ?>
      <section class="mb-8">
        <div class="card p-6 bg-white">
          <h2 class="text-2xl font-semibold text-gray-800 text-center mb-4">List of All Users</h2>
        </div>
      </section>
    <?php
    }
    ?>

    <!-- Users Table Section -->
    <section>
      <div class="card p-6 bg-white">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Users List</h2>
        <!-- Search and Filter Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
          <input type="text" id="searchInput" class="p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Search by name or email...">
          <select id="roleFilter" class="p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Roles</option>
            <option value="warefare">Director Welfare</option>
            <option value="head_quarter">Head Quarter (HQ)</option>
          </select>
          <select id="statusFilter" class="p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
          <select id="campusFilter" class="p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Campuses</option>
            <?php
            $campusQuery = "SELECT * FROM campuses ORDER BY name";
            $campusResult = mysqli_query($connection, $campusQuery);
            while ($campus = mysqli_fetch_assoc($campusResult)) {
              echo "<option value='" . $campus['id'] . "'>" . ucwords($campus['name']) . "</option>";
            }
            ?>
          </select>
          <button class="btn-success px-4 py-2 rounded-md bg-green-600 text-white hover:bg-green-700 flex items-center" onclick="exportToExcel()">
            <i class="fas fa-file-excel mr-2"></i> Export to Excel
          </button>
        </div>
        <!-- Users Table -->
        <div class="overflow-x-auto">
          <table class="table w-full text-left">
            <thead>
              <tr class="bg-gray-200 text-gray-700">
                <th class="p-3">Image</th>
                <th class="p-3">Name</th>
                <th class="p-3">Email</th>
                <th class="p-3">Phone</th>
                <th class="p-3">Role</th>
                <th class="p-3">Campus</th>
                <th class="p-3">Status</th>
                <th class="p-3">Actions</th>
              </tr>
            </thead>
            <tbody id="usersTableBody">
              <?php
              $query = "SELECT u.*, c.name as campus_name, c.id as campus_id 
                       FROM users u 
                       LEFT JOIN campuses c ON u.campus = c.id 
                       WHERE u.id != ? AND (u.role != 'wadden' AND u.role != 'information_modifier')";
              $stmt = $connection->prepare($query);
              $stmt->bind_param("i", $id);
              $stmt->execute();
              $result = $stmt->get_result();

              while ($row = $result->fetch_assoc()) {
                echo "<tr class='border-b'>";
                echo "<td class='p-3'><img src='./" . htmlspecialchars($row['image']) . "' class='rounded-full w-10 h-10' alt='User Image'></td>";
                echo "<td class='p-3'>" . htmlspecialchars($row['names']) . "</td>";
                echo "<td class='p-3'>" . htmlspecialchars($row['email']) . "</td>";
                echo "<td class='p-3'>" . htmlspecialchars($row['phone']) . "</td>";
                echo "<td class='p-3' data-role='" . htmlspecialchars($row['role']) . "'>";
                if ($row['role'] == 'warefare') {
                  echo "Director Welfare";
                } elseif ($row['role'] == 'information_modifier') {
                  echo "Admin";
                } elseif ($row['role'] == 'head_quarter') {
                  echo "Head Quarter (HQ)";
                } else {
                  echo ucfirst(htmlspecialchars($row['role']));
                }
                echo "</td>";
                echo "<td class='p-3' data-campus-id='" . ($row['campus_id'] ?? '') . "'>" . (ucfirst($row['campus_name'] ?? 'N/A')) . "</td>";
                echo "<td class='p-3'>" . ($row['active'] ? '<span class="text-green-600 font-medium">Active</span>' : '<span class="text-red-600 font-medium">Inactive</span>') . "</td>";
                echo "<td class='p-3 flex space-x-2'>
                        <a href='user-delete.php?userId=" . htmlspecialchars($row['id']) . "' class='btn-danger px-3 py-1 rounded-md bg-red-600 text-white hover:bg-red-700'><i class='fas fa-trash'></i></a>
                        <button class='px-3 py-1 rounded-md " . ($row['active'] ? 'btn-warning bg-yellow-500 hover:bg-yellow-600' : 'btn-success bg-green-600 hover:bg-green-700') . " text-white' 
                                onclick='" . ($row['active'] ? 'confirmDeactivation' : 'confirmActivation') . "(" . htmlspecialchars($row['id']) . ", \"" . htmlspecialchars($row['names']) . "\")'>
                          <i class='fas " . ($row['active'] ? 'fa-toggle-on' : 'fa-toggle-off') . "'></i>
                        </button>
                      </td>";
                echo "</tr>";
              }
              $stmt->close();
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

  <?php
  include("./includes/footer.php");
  ?>

  <!-- Back to Top -->
  <a href="#" class="back-to-top fixed bottom-4 right-4 flex items-center justify-center"><i class="fas fa-arrow-up"></i></a>

  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
  <script>
    // Toggle campus field visibility
    function toggleCampusField() {
      const roleSelect = document.getElementById('floatingRole');
      const campusField = document.getElementById('campusField');
      const campusSelect = document.getElementById('floatingCampus');
      
      if (roleSelect.value === 'warefare' || roleSelect.value === 'head_quarter') {
        campusField.classList.remove('hidden');
        campusSelect.required = true;
      } else {
        campusField.classList.add('hidden');
        campusSelect.required = false;
      }
    }

    // Debounce function for search input
    function debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }

    // Filter table with improved role filtering
    function filterTable() {
      const searchText = document.getElementById('searchInput').value.toLowerCase();
      const roleFilter = document.getElementById('roleFilter').value;
      const statusFilter = document.getElementById('statusFilter').value;
      const campusFilter = document.getElementById('campusFilter').value;
      const rows = document.getElementById('usersTableBody').getElementsByTagName('tr');

      for (let row of rows) {
        const name = row.cells[1].textContent.toLowerCase();
        const email = row.cells[2].textContent.toLowerCase();
        const role = row.cells[4].getAttribute('data-role');
        const status = row.cells[6].textContent === 'Active' ? '1' : '0';
        const campusId = row.cells[5].getAttribute('data-campus-id');

        const matchesSearch = name.includes(searchText) || email.includes(searchText);
        const matchesRole = !roleFilter || role === roleFilter;
        const matchesStatus = !statusFilter || status === statusFilter;
        const matchesCampus = !campusFilter || campusId === campusFilter;

        row.style.display = matchesSearch && matchesRole && matchesStatus && matchesCampus ? '' : 'none';
      }
    }

    // Excel export function
    function exportToExcel() {
      const table = document.getElementById('usersTableBody');
      const rows = table.getElementsByTagName('tr');
      const wb = XLSX.utils.book_new();
      const ws_data = [['Name', 'Email', 'Phone', 'Role', 'Campus', 'Status']];

      for (let row of rows) {
        if (row.style.display !== 'none') {
          const cells = row.getElementsByTagName('td');
          ws_data.push([
            cells[1].textContent,
            cells[2].textContent,
            cells[3].textContent,
            cells[4].textContent,
            cells[5].textContent,
            cells[6].textContent
          ]);
        }
      }

      const ws = XLSX.utils.aoa_to_sheet(ws_data);
      XLSX.utils.book_append_sheet(wb, ws, "Users");
      const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'binary' });

      const blob = new Blob([s2ab(wbout)], { type: 'application/octet-stream' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'users_list.xlsx';
      a.click();
      window.URL.revokeObjectURL(url);
    }

    function s2ab(s) {
      const buf = new ArrayBuffer(s.length);
      const view = new Uint8Array(buf);
      for (let i = 0; i < s.length; i++) {
        view[i] = s.charCodeAt(i) & 0xFF;
      }
      return buf;
    }

    // Activation/Deactivation confirmation
    function confirmActivation(userId, userName) {
      if (confirm(`Are you sure you want to activate ${userName}?`)) {
        window.location.href = `user-activate.php?userId=${userId}`;
      }
    }

    function confirmDeactivation(userId, userName) {
      if (confirm(`Are you sure you want to deactivate ${userName}?`)) {
        window.location.href = `user-deactivate.php?userId=${userId}`;
      }
    }

    // Event listeners with debouncing for search
    document.getElementById('searchInput').addEventListener('input', debounce(filterTable, 300));
    document.getElementById('roleFilter').addEventListener('change', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);
    document.getElementById('campusFilter').addEventListener('change', filterTable);
  </script>

  <?php
  if (isset($_POST['saveuser'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    $campus = ($role === 'warefare' || $role === 'head_quarter') ? $_POST['campus'] : null;

    if (!empty($name) && !empty($email) && !empty($password)) {
      // Validate email format
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format.')</script>";
      } else {
        // Check for duplicate email
        $stmt = $connection->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
          echo "<script>alert('Email already exists.')</script>";
        } else {
          $hashed_password = password_hash($password, PASSWORD_BCRYPT);
          $stmt = $connection->prepare("INSERT INTO users (names, email, phone, role, password, image, active, campus) VALUES (?, ?, ?, ?, ?, 'assets/img/av.png', 1, ?)");
          $stmt->bind_param("ssssss", $name, $email, $phone, $role, $hashed_password, $campus);

          if ($stmt->execute()) {
            sendWelcomeEmail($email, $name, $password);
            echo "<script>alert('User added successfully.'); window.location.href='add_user.php';</script>";
          } else {
            echo "<script>alert('Error occurred while adding user.')</script>";
          }
        }
        $stmt->close();
      }
    } else {
      echo "<script>alert('Please fill all required fields.')</script>";
    }
  }
  ?>
</body>
</html>