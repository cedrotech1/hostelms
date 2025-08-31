<aside id="sidebar" class="sidebar">

     <div class="role-card">
        <?php 
            $role = $_SESSION['role'] ?? '';
            $roleName = '';
            $roleIcon = '';

            if ($role == "warefare") {
                $roleName = "Director of Welfare";
                $roleIcon = "bi-heart-pulse";
            } elseif ($role == "information_modifier") {
                $roleName = "Admin";
                $roleIcon = "bi-person-badge";
            } elseif ($role == "head_quarter") {
                $roleName = "Head Quarter";
                $roleIcon = "bi-building-check";
            } elseif ($role == "wadden") {
                $roleName = "Warden";
                $roleIcon = "bi-person-lines-fill";
            }
        ?>
        <div class="role-icon">
            <i class="bi <?= $roleIcon ?>"></i>
        </div>
        <div class="role-info">
            <h5><?= $roleName ?></h5>
            <p>Welcome back 👋</p>
        </div>
    </div>



<style>
/* Sidebar container */


/* Role card styling */
.role-card {
    background: linear-gradient(135deg,rgb(30, 53, 80),rgb(27, 59, 94));
    border-radius: 15px;
    padding: 5px 5px;
    text-align: center;
    color: #fff;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.role-card .role-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

/* Role icon */
.role-card .role-icon {
    font-size: 45px;
    margin-bottom: 10px;
}
.role-card .role-icon i {
    color: #fff;
}

/* Role text */
.role-card h5 {
    font-size: 18px;
    font-weight: bold;
    margin: 5px 0;
}
.role-card p {
    font-size: 14px;
    opacity: 0.9;
}


</style>

    <ul class="sidebar-nav" id="sidebar-nav">
        <?php if ($_SESSION['role'] == 'wadden') { ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="manage_hostel_occupants.php">
                    <i class="bi bi-person-lines-fill"></i><span>Manage Hostel Occupants</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="manage_claims.php">
                    <i class="bi bi-file-text"></i><span>Manage Claimings</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i><span>Logout</span>
                </a>
            </li>
        <?php } ?>

        <?php if ($_SESSION['role'] == 'head_quarter') { ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="index.php">
                    <i class="bi bi-house-door"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="add_user.php">
                    <i class="bi bi-person-plus"></i><span>Manage Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="updateinfo.php">
                    <i class="bi bi-pencil-square"></i><span>Quick Search</span>
                </a>
            </li>   
            <li class="nav-item">
                <a class="nav-link collapsed" href="hostels.php">
                    <i class="bi bi-building"></i><span>Manage Hostels</span>
                </a>
            </li>
              
            <li class="nav-item">
                <a class="nav-link collapsed" href="manage_applications.php">
                    <i class="bi bi-clock-history"></i><span>Manage Applications<br> Pending(4)</span>
                   
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" href="manage_wadden.php">
                    <i class="bi bi-person-gear"></i><span>Manage Wardens</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="search.php">
                    <i class="bi bi-file-earmark-bar-graph"></i><span>Reports Page</span>
                </a>
            </li>
           
            <li class="nav-item">
                <a class="nav-link collapsed" href="blacklist.php">
                    <i class="bi bi-person-x"></i><span>Blacklist</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#settings-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-gear"></i><span>Settings</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="settings-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a class="nav-link collapsed" href="system.php">
                            <i class="bi bi-gear-fill"></i><span>System Settings</span>
                        </a>
                    </li>
                </ul>
            </li>
        
        
            <li class="nav-item">
                <a class="nav-link collapsed" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i><span>Logout</span>
                </a>
            </li>
        <?php } ?>

        <?php if ($_SESSION['role'] == 'warefare') { ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="index.php">
                    <i class="bi bi-house-door"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="updateinfo.php">
                    <i class="bi bi-pencil-square"></i><span>Quick Search</span>
                </a>
            </li>   
            <li class="nav-item">
                <a class="nav-link collapsed" href="hostels.php">
                    <i class="bi bi-building"></i><span>Manage Hostels</span>
                </a>
            </li>
              
            <li class="nav-item">
                <a class="nav-link collapsed" href="manage_applications.php">
                    <i class="bi bi-clock-history"></i><span>Manage Applications<br> Pending(4)</span>
                   
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" href="manage_wadden.php">
                    <i class="bi bi-person-gear"></i><span>Manage Wardens</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="search_warefare.php">
                    <i class="bi bi-file-earmark-bar-graph"></i><span>Reports Page</span>
                </a>
            </li>
           
            <li class="nav-item">
                <a class="nav-link collapsed" href="blacklist.php">
                    <i class="bi bi-person-x"></i><span>Blacklist</span>
                </a>
            </li>
        
        
            <li class="nav-item">
                <a class="nav-link collapsed" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i><span>Logout</span>
                </a>
            </li>
        <?php } ?>

        <?php if ($_SESSION['role'] == 'information_modifier') { ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="index.php">
                    <i class="bi bi-house-door"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="add_user.php">
                    <i class="bi bi-person-plus"></i><span>Manage Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#manage-data-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-database"></i><span>Manage Data</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="manage-data-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a class="nav-link collapsed" href="add_data.php">
                            <i class="bi bi-upload"></i><span>Upload Student Information</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link collapsed text-danger" href="cleardata.php">
                            <i class="bi bi-trash"></i><span class="text-danger">Reset Student Data</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#settings-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-gear"></i><span>Settings</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="settings-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a class="nav-link collapsed" href="system.php">
                            <i class="bi bi-gear-fill"></i><span>System Settings</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i><span>Logout</span>
                </a>
            </li>
        <?php } ?>
    </ul>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set active state based on current URL
            const currentPath = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-link');

            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href === currentPath) {
                    link.classList.add('active');
                    // If the link is in a collapsible menu, expand it
                    const parentCollapse = link.closest('.nav-content');
                    if (parentCollapse) {
                        parentCollapse.classList.add('show');
                        const parentNavLink = parentCollapse.previousElementSibling;
                        if (parentNavLink) {
                            parentNavLink.classList.remove('collapsed');
                            parentNavLink.setAttribute('aria-expanded', 'true');
                        }
                    }
                }
            });
        });
    </script>
</aside>