<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">

        <!-- Manage Data (Admin Only) -->
        <?php if ($_SESSION['role'] == 'warefare') { ?>

            <li class="nav-item">
                <a class="nav-link collapsed" href="index.php">
                    <i class="bi bi-house-door"></i><span>Dashboard</span>
                </a>
            </li>
            <!-- upload hostels -->
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-database"></i><span>Manage Data</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="icons-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a class="nav-link collapsed" href="welfare_add_data.php">
                            <i class="bi bi-upload"></i><span>Upload student information</span>
                        </a>
                    </li>
                        <!-- <li>
                            <a class="nav-link collapsed" href="add_hostel.php">
                                <i class="bi bi-person-plus"></i><span>upload hostels </span>
                            </a>
                        </li> -->
                    <!-- <li>
                        <a class="nav-link collapsed" href="allstudents.php">
                            <i class="bi bi-card-heading"></i><span>All students</span>
                        </a>
                    </li> -->
                    <li>
                        <a class="nav-link collapsed" href="updateinfo.php">
                            <i class="bi bi-pencil-square"></i><span>Update Info</span>
                        </a>
                    </li>





                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="search_warefare.php">
                    <i class="bi bi-file-earmark-bar-graph"></i><span>manage reports</span>
                </a>
            </li>

        
            <li>
                <a class="nav-link collapsed" href="manage_hostels.php">
                    <i class="bi bi-building"></i><span>manage hostels</span>
                </a>
            </li>
            <!-- manage application -->
            <li>
                <a class="nav-link collapsed" href="manage_applications.php">
                    <i class="bi bi-clock-history"></i><span>manage application</span>
                </a>
            </li>
            <!-- logout -->
            <li>
                <a class="nav-link collapsed" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i><span>logout</span>
                </a>
            </li>



        <?php } ?>


        <?php if ($_SESSION['role'] == 'information_modifier') { ?>
            <!-- dashboard -->

            <li class="nav-item">
                <a class="nav-link collapsed" href="index.php">
                    <i class="bi bi-house-door"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="add_user.php">
                    <i class="bi bi-people"></i><span>Manage Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-database"></i><span>Manage Data</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="icons-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a class="nav-link collapsed" href="add_data.php">
                            <i class="bi bi-upload"></i><span>Upload student information</span>
                        </a>
                    </li>
                  

                  

                    <li>
                        <a class="nav-link collapsed" href="updateinfo.php">
                            <i class="bi bi-pencil-square"></i><span>Update Info</span>
                        </a>
                    </li>

               

                    <li>
                        <a class="nav-link collapsed text-danger" href="cleardata.php">
                            <i class="bi bi-trash"></i><span class="text-danger">Delete All System Data</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- manage_hostels -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="manage_hostels.php">
                    <i class="bi bi-building"></i><span>manage hostels</span>
                </a>
            </li>


            <!-- Manage Student Cards -->


            <!-- Settings -->
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#icons-nav10" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-gear"></i><span>Settings</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="icons-nav10" class="nav-content collapse" data-bs-parent="#sidebar-nav">

                    <li>
                        <a class="nav-link collapsed" href="users-profile.php">
                            <i class="bi bi-person-circle"></i><span>Profile</span>
                        </a>
                    </li>
               
                    <li>
                        <a class="nav-link collapsed" href="system.php">
                            <i class="bi bi-gear-fill"></i><span>System settings</span>
                        </a>
                    </li>

                    <li>
                        <a class="nav-link collapsed" href="download.php">
                            <i class="bi bi-download"></i><span>backup data file</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- normal one menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="search.php">
                    <i class="bi bi-file-earmark-bar-graph"></i><span>manage reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i><span>logout</span>
                </a>
            </li>
        <?php } ?>



    </ul>
</aside>