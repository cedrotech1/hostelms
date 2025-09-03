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
  <title>Manage Users</title>
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
    .btn-danger, .btn-warning, .btn-success, .btn-info {
      transition: all 0.3s ease;
    }
    .card {
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    .card:hover {
      box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
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
    .modal-content {
      border-radius: 8px;
    }
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }
    .pagination a {
      margin: 0 5px;
      padding: 8px 16px;
      border: 1px solid #1e40af;
      color: #1e40af;
      border-radius: 4px;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    .pagination a.active {
      background-color: #1e40af;
      color: white;
    }
    .pagination a:hover:not(.active) {
      background-color: #e6f0fa;
    }
    #loadingIndicator {
      display: none;
      text-align: center;
      padding: 20px;
    }
    .stats-card {
      transition: transform 0.3s ease;
    }
    .stats-card:hover {
      transform: translateY(-5px);
    }
    #errorMessage {
      display: none;
      background-color: #fee2e2;
      color: #dc2626;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 10px;
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
    <!-- Page Title -->
    <div class="pagetitle">
      <h1>Data</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">User</li>
          <li class="breadcrumb-item active">Manage Users</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <!-- Statistics Cards -->
    <section class="mb-8">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        // Total Users
        $totalUsersQuery = "SELECT COUNT(*) as total FROM users WHERE role != 'information_modifier'";
        $totalUsersResult = mysqli_query($connection, $totalUsersQuery);
        if (!$totalUsersResult) {
            echo "<script>alert('Error fetching total users: " . mysqli_error($connection) . "');</script>";
        }
        $totalUsers = mysqli_fetch_assoc($totalUsersResult)['total'];

        // Total by Role
        $roles = ['warefare' => 'Director Welfare', 'head_quarter' => 'Head Quarter (HQ)', 'wadden' => 'Hostel Warden'];
        $roleStats = [];
        foreach ($roles as $key => $label) {
          $query = "SELECT COUNT(*) as count FROM users WHERE role = '$key'";
          $result = mysqli_query($connection, $query);
          if (!$result) {
              echo "<script>alert('Error fetching role stats for $key: " . mysqli_error($connection) . "');</script>";
          }
          $roleStats[$key] = mysqli_fetch_assoc($result)['count'];
        }

        // Display Total Users Card
        echo "<div class='stats-card card p-6 bg-blue-100 text-center'>
                <h3 class='text-xl font-semibold text-gray-800'>Total Users</h3>
                <p class='text-3xl font-bold text-blue-600'>$totalUsers</p>
              </div>";

        // Display Role Stats Cards
        foreach ($roles as $key => $label) {
          $count = $roleStats[$key];
          echo "<div class='stats-card card p-6 bg-green-100 text-center'>
                  <h3 class='text-xl font-semibold text-gray-800'>$label</h3>
                  <p class='text-3xl font-bold text-green-600'>$count</p>
                </div>";
        }
        ?>
      </div>
    </section>

    <!-- Campus Summaries Cards -->
    <section class="mb-8">
      <h2 class="text-2xl font-semibold text-gray-800 mb-4">Campus Summaries</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        // Per Campus Summaries
        $campusQuery = "SELECT * FROM campuses ORDER BY name";
        $campusResult = mysqli_query($connection, $campusQuery);
        if (!$campusResult) {
            echo "<script>alert('Error fetching campuses: " . mysqli_error($connection) . "');</script>";
        }
        while ($campus = mysqli_fetch_assoc($campusResult)) {
          $id = $campus['id'];
          $name = ucwords($campus['name']);

          // Total users in campus
          $totalQuery = "SELECT COUNT(*) as total FROM users WHERE campus = $id AND role != 'information_modifier'";
          $totalResult = mysqli_query($connection, $totalQuery);
          if (!$totalResult) {
              echo "<script>alert('Error fetching total users for campus $name: " . mysqli_error($connection) . "');</script>";
          }
          $total = mysqli_fetch_assoc($totalResult)['total'];

          // Wardens (waddens)
          $wardensQuery = "SELECT COUNT(*) as count FROM users WHERE role = 'wadden' AND campus = $id";
          $wardensResult = mysqli_query($connection, $wardensQuery);
          if (!$wardensResult) {
              echo "<script>alert('Error fetching wardens for campus $name: " . mysqli_error($connection) . "');</script>";
          }
          $wardens = mysqli_fetch_assoc($wardensResult)['count'];

          // Directors Welfare
          $directorsQuery = "SELECT COUNT(*) as count FROM users WHERE role = 'warefare' AND campus = $id";
          $directorsResult = mysqli_query($connection, $directorsQuery);
          if (!$directorsResult) {
              echo "<script>alert('Error fetching directors for campus $name: " . mysqli_error($connection) . "');</script>";
          }
          $directors = mysqli_fetch_assoc($directorsResult)['count'];

          echo "<div class='stats-card card p-4 bg-purple-100 text-left'>
                  <h3 class='text-lg font-semibold text-gray-800'>$name</h3>
                  <p class='text-md text-gray-700'>Total: $total</p>
                  <p class='text-md text-gray-700'>Wardens: $wardens</p>
                  <p class='text-md text-gray-700'>Directors Welfare: $directors</p>
                </div>";
        }

        // Headquarter Users
        $hqQuery = "SELECT COUNT(*) as count FROM users WHERE role = 'head_quarter'";
        $hqResult = mysqli_query($connection, $hqQuery);
        if (!$hqResult) {
            echo "<script>alert('Error fetching headquarter users: " . mysqli_error($connection) . "');</script>";
        }
        $hqCount = mysqli_fetch_assoc($hqResult)['count'];

        echo "<div class='stats-card card p-4 bg-yellow-100 text-left'>
                <h3 class='text-lg font-semibold text-gray-800'>Headquarter</h3>
                <p class='text-md text-gray-700'>Total Users: $hqCount</p>
              </div>";
        ?>
      </div>
    </section>

    <!-- Add User Form -->
    <div class="row">
      <div class="col-lg-5">
        <section class="mb-8">
          <div class="card p-6 bg-white">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Add New User</h2>
            <form class="grid grid-cols-1 gap-4" action="add_user.php" method="post">
              <div class="relative">
                <input type="text" id="floatingName" name="name" class="form-floating w-full p-2 border" placeholder="Name" required>
              </div>
              <div class="relative">
                <input type="email" id="floatingEmail" name="email" class="form-floating w-full p-2 border" placeholder="Email" required>
              </div>
              <div class="relative">
                <input type="tel" id="floatingPhone" name="phone" class="form-floating w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Phone" required>
              </div>
              <div class="relative">
                <select id="floatingRole" name="role" class="form-floating w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required onchange="toggleCampusField()">
                  <option value="" disabled selected>Select Role</option>
                  <option value="warefare">Director Welfare</option>
                  <option value="head_quarter">Head Quarter (HQ)</option>
                  <option value="wadden">Hostel Warden</option>
                </select>
              </div>
              <div id="campusField" class="relative hidden">
                <select id="floatingCampus" name="campus" class="form-floating w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                  <option value="" disabled selected>Select Campus</option>
                  <?php
                  $campusQuery = "SELECT * FROM campuses ORDER BY name";
                  $campusResult = mysqli_query($connection, $campusQuery);
                  if ($campusResult) {
                      while ($campus = mysqli_fetch_assoc($campusResult)) {
                          echo "<option value='" . $campus['id'] . "'>" . ucwords($campus['name']) . "</option>";
                      }
                  } else {
                      echo "<script>alert('Error fetching campuses: " . mysqli_error($connection) . "');</script>";
                  }
                  ?>
                </select>
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
    </div>

    <!-- Users Table Section -->
    <section>
      <div class="card p-6 bg-white">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Users List</h2>
        <!-- Error Message -->
        <div id="errorMessage"></div>
        <!-- Search and Filter Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
          <input type="text" id="searchInput" class="p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Search by name or email...">
          <select id="roleFilter" class="p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Roles</option>
            <option value="warefare">Director Welfare</option>
            <option value="head_quarter">Head Quarter (HQ)</option>
            <option value="wadden">Hostel Warden</option>
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
            if ($campusResult) {
                while ($campus = mysqli_fetch_assoc($campusResult)) {
                    echo "<option value='" . $campus['id'] . "'>" . ucwords($campus['name']) . "</option>";
                }
            } else {
                echo "<script>alert('Error fetching campuses: " . mysqli_error($connection) . "');</script>";
            }
            ?>
          </select>
          <button class="btn-success px-4 py-2 rounded-md bg-green-600 text-white hover:bg-green-700 flex items-center" onclick="exportToExcel()">
            <i class="fas fa-file-excel mr-2"></i> Export to Excel
          </button>
        </div>
        <!-- Loading Indicator -->
        <div id="loadingIndicator">
          <i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
          <p>Loading users...</p>
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
              <!-- Users will be loaded dynamically via JS -->
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div id="pagination" class="pagination">
          <!-- Pagination links will be loaded dynamically -->
        </div>
      </div>
    </section>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center">
      <div class="modal-content bg-white p-6 rounded-lg w-full max-w-md">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Edit User</h2>
        <form id="editUserForm" action="add_user.php" method="post">
          <input type="hidden" id="editUserId" name="userId">
          <div class="relative mb-4">
            <input type="text" id="editName" name="name" class="form-floating w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Name" required>
          </div>
          <div class="relative mb-4">
            <input type="email" id="editEmail" name="email" class="form-floating w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Email" required>
          </div>
          <div class="relative mb-4">
            <input type="tel" id="editPhone" name="phone" class="form-floating w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Phone" required>
          </div>
          <div class="relative mb-4">
            <select id="editRole" name="role" class="form-floating w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required onchange="toggleEditCampusField()">
              <option value="" disabled>Select Role</option>
              <option value="warefare">Director Welfare</option>
              <option value="head_quarter">Head Quarter (HQ)</option>
              <option value="wadden">Hostel Warden</option>
            </select>
          </div>
          <div id="editCampusField" class="relative mb-4 hidden">
            <select id="editCampus" name="campus" class="form-floating w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="" disabled selected>Select Campus</option>
              <?php
              $campusQuery = "SELECT * FROM campuses ORDER BY name";
              $campusResult = mysqli_query($connection, $campusQuery);
              if ($campusResult) {
                  while ($campus = mysqli_fetch_assoc($campusResult)) {
                      echo "<option value='" . $campus['id'] . "'>" . ucwords($campus['name']) . "</option>";
                  }
              } else {
                  echo "<script>alert('Error fetching campuses: " . mysqli_error($connection) . "');</script>";
              }
              ?>
            </select>
          </div>
          <div class="flex justify-end space-x-4">
            <button type="button" class="px-4 py-2 rounded-md bg-gray-500 text-white hover:bg-gray-600" onclick="closeEditModal()">Cancel</button>
            <button type="submit" name="updateUser" class="btn-primary px-4 py-2 rounded-md text-white">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </main>

  <?php
  include("./includes/footer.php");
  ?>

  <!-- Back to Top -->
  <a href="#" class="back-to-top fixed bottom-4 right-4 flex items-center justify-center"><i class="fas fa-arrow-up"></i></a>

  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
  <script>
    // Toggle campus field visibility for add form
    function toggleCampusField() {
      const roleSelect = document.getElementById('floatingRole');
      const campusField = document.getElementById('campusField');
      const campusSelect = document.getElementById('floatingCampus');
      
      if (roleSelect.value === 'warefare' || roleSelect.value === 'wadden') {
        campusField.classList.remove('hidden');
        campusSelect.required = true;
      } else {
        campusField.classList.add('hidden');
        campusSelect.required = false;
        campusSelect.value = '';
      }
    }

    // Toggle campus field visibility for edit modal
    function toggleEditCampusField() {
      const roleSelect = document.getElementById('editRole');
      const campusField = document.getElementById('editCampusField');
      const campusSelect = document.getElementById('editCampus');
      
      if (roleSelect.value === 'warefare' || roleSelect.value === 'wadden') {
        campusField.classList.remove('hidden');
        campusSelect.required = true;
      } else {
        campusField.classList.add('hidden');
        campusSelect.required = false;
        campusSelect.value = '';
      }
    }

    // Open edit modal with pre-filled data
    function openEditModal(user) {
      const modal = document.getElementById('editUserModal');
      document.getElementById('editUserId').value = user.id;
      document.getElementById('editName').value = user.names;
      document.getElementById('editEmail').value = user.email;
      document.getElementById('editPhone').value = user.phone;
      document.getElementById('editRole').value = user.role;
      const campusSelect = document.getElementById('editCampus');
      if (user.campus_id) {
        campusSelect.value = user.campus_id;
      } else {
        campusSelect.value = '';
      }
      toggleEditCampusField();
      modal.classList.remove('hidden');
    }

    // Close edit modal
    function closeEditModal() {
      document.getElementById('editUserModal').classList.add('hidden');
    }

    // Debounce function
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

    // Load users dynamically
    function loadUsers(page = 1) {
      const searchText = document.getElementById('searchInput').value;
      const roleFilter = document.getElementById('roleFilter').value;
      const statusFilter = document.getElementById('statusFilter').value;
      const campusFilter = document.getElementById('campusFilter').value;
      const loading = document.getElementById('loadingIndicator');
      const errorMessage = document.getElementById('errorMessage');
      loading.style.display = 'block';
      errorMessage.style.display = 'none';

      fetch(`fetch_users.php?page=${page}&search=${encodeURIComponent(searchText)}&role=${roleFilter}&status=${statusFilter}&campus=${campusFilter}`)
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          loading.style.display = 'none';
          if (data.error) {
            errorMessage.textContent = data.error;
            errorMessage.style.display = 'block';
            return;
          }
          const tableBody = document.getElementById('usersTableBody');
          tableBody.innerHTML = '';
          data.users.forEach(user => {
            let row = `<tr class='border-b'>
                         <td class='p-3'><img src='./${user.image}' class='rounded-full w-10 h-10' alt='User Image'></td>
                         <td class='p-3'>${user.names}</td>
                         <td class='p-3'>${user.email}</td>
                         <td class='p-3'>${user.phone}</td>
                         <td class='p-3' data-role='${user.role}'>`;
            if (user.role === 'warefare') row += 'Director Welfare';
            else if (user.role === 'head_quarter') row += 'Head Quarter (HQ)';
            else if (user.role === 'wadden') row += 'Hostel Warden';
            else row += user.role.charAt(0).toUpperCase() + user.role.slice(1);
            row += `</td>
                    <td class='p-3' data-campus-id='${user.campus_id || ''}'>${user.campus_name}</td>
                    <td class='p-3'>${user.active ? '<span class="text-green-600 font-medium">Active</span>' : '<span class="text-red-600 font-medium">Inactive</span>'}</td>
                    <td class='p-3 flex space-x-2'>
                      <button class='btn-info px-3 py-1 rounded-md bg-blue-600 text-white hover:bg-blue-700' onclick='openEditModal(${JSON.stringify(user)})'>
                        <i class='fas fa-edit'></i>
                      </button>
                      <a href='user-delete.php?userId=${user.id}' class='btn-danger px-3 py-1 rounded-md bg-red-600 text-white hover:bg-red-700'><i class='fas fa-trash'></i></a>
                      <button class='px-3 py-1 rounded-md ${user.active ? 'btn-warning bg-yellow-500 hover:bg-yellow-600' : 'btn-success bg-green-600 hover:bg-green-700'} text-white' 
                              onclick='${user.active ? 'confirmDeactivation' : 'confirmActivation'}(${user.id}, "${user.names}")'>
                        <i class='fas ${user.active ? 'fa-toggle-on' : 'fa-toggle-off'}'></i>
                      </button>
                    </td>
                  </tr>`;
            tableBody.innerHTML += row;
          });

          // Update pagination
          const pagination = document.getElementById('pagination');
          pagination.innerHTML = '';
          if (data.totalPages > 1) {
            for (let i = 1; i <= data.totalPages; i++) {
              const link = document.createElement('a');
              link.href = '#';
              link.textContent = i;
              if (i === data.page) {
                link.classList.add('active');
              }
              link.onclick = (e) => {
                e.preventDefault();
                loadUsers(i);
              };
              pagination.appendChild(link);
            }
          }
        })
        .catch(error => {
          loading.style.display = 'none';
          errorMessage.textContent = 'Error loading users: ' + error.message;
          errorMessage.style.display = 'block';
          console.error('Fetch error:', error);
        });
    }

    // Excel export function (exports current filtered data)
    function exportToExcel() {
      const table = document.getElementById('usersTableBody');
      const rows = table.getElementsByTagName('tr');
      const wb = XLSX.utils.book_new();
      const ws_data = [['Name', 'Email', 'Phone', 'Role', 'Campus', 'Status']];

      for (let row of rows) {
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

    // Event listeners
    document.getElementById('searchInput').addEventListener('input', debounce(() => loadUsers(1), 300));
    document.getElementById('roleFilter').addEventListener('change', () => loadUsers(1));
    document.getElementById('statusFilter').addEventListener('change', () => loadUsers(1));
    document.getElementById('campusFilter').addEventListener('change', () => loadUsers(1));

    // Initial load
    window.addEventListener('load', () => loadUsers(1));
  </script>

  <?php
  // Handle Add User
  if (isset($_POST['saveuser'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    $campus = ($role === 'warefare' || $role === 'wadden') ? ($_POST['campus'] ?? null) : null;

    if (!empty($name) && !empty($email) && !empty($phone) && !empty($role) && !empty($password)) {
      // Validate email format
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format.')</script>";
      } else {
        // Check for duplicate email
        $stmt = $connection->prepare("SELECT id FROM users WHERE email = ?");
        if (!$stmt) {
            echo "<script>alert('Error preparing email check: " . mysqli_error($connection) . "');</script>";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
              echo "<script>alert('Email already exists.')</script>";
            } else {
              $hashed_password = password_hash($password, PASSWORD_BCRYPT);
              $stmt = $connection->prepare("INSERT INTO users (names, email, phone, role, password, image, active, campus) VALUES (?, ?, ?, ?, ?, 'assets/img/av.png', 1, ?)");
              if (!$stmt) {
                  echo "<script>alert('Error preparing insert: " . mysqli_error($connection) . "');</script>";
              } else {
                  $stmt->bind_param("ssssss", $name, $email, $phone, $role, $hashed_password, $campus);
                  if ($stmt->execute()) {
                    sendWelcomeEmail($email, $name, $password);
                    echo "<script>alert('User added successfully.'); window.location.href='add_user.php';</script>";
                  } else {
                    echo "<script>alert('Error occurred while adding user: " . $stmt->error . "');</script>";
                  }
              }
            }
            $stmt->close();
        }
      }
    } else {
      echo "<script>alert('Please fill all required fields.')</script>";
    }
  }

  // Handle Update User
  if (isset($_POST['updateUser'])) {
    $userId = $_POST['userId'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $campus = ($role === 'warefare' || $role === 'wadden') ? ($_POST['campus'] ?? null) : null;

    if (!empty($name) && !empty($email) && !empty($phone) && !empty($role)) {
      // Validate email format
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format.')</script>";
      } else {
        // Check for duplicate email (excluding current user)
        $stmt = $connection->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        if (!$stmt) {
            echo "<script>alert('Error preparing email check: " . mysqli_error($connection) . "');</script>";
        } else {
            $stmt->bind_param("si", $email, $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
              echo "<script>alert('Email already exists.')</script>";
            } else {
              $stmt = $connection->prepare("UPDATE users SET names = ?, email = ?, phone = ?, role = ?, campus = ? WHERE id = ?");
              if (!$stmt) {
                  echo "<script>alert('Error preparing update: " . mysqli_error($connection) . "');</script>";
              } else {
                  $stmt->bind_param("sssssi", $name, $email, $phone, $role, $campus, $userId);
                  if ($stmt->execute()) {
                    echo "<script>alert('User updated successfully.'); window.location.href='add_user.php';</script>";
                  } else {
                    echo "<script>alert('Error occurred while updating user: " . $stmt->error . "');</script>";
                  }
              }
            }
            $stmt->close();
        }
      }
    } else {
      echo "<script>alert('Please fill all required fields.')</script>";
    }
  }
  ?>
</body>
</html>