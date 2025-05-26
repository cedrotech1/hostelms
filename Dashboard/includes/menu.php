<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <!-- View Statistics -->
        <!-- <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#icons-nav0" data-bs-toggle="collapse" href="#">
                <i class="bi bi-bar-chart-line"></i><span>View Statistics</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="icons-nav0" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a class="nav-link collapsed" href="statistics.php">
                        <i class="bi bi-file-earmark-bar-graph"></i><span>Statistics Documentation</span>
                    </a>
                </li>

                <li>
                    <a class="nav-link collapsed" href="uploadFiles.php">
                        <i class="bi bi-file-earmark-bar-graph"></i><span>Shared documents</span>
                    </a>
                </li>
            </ul>
        </li> -->

        <!-- Manage Data (Admin Only) -->
        <?php if ($_SESSION['role'] == 'warefare') { ?>

            <li class="nav-item">
                <a class="nav-link collapsed" href="index.php">
                    <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                </a>
            </li>
            <!-- upload hostels -->
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-folder"></i><span>Manage Data</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="icons-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a class="nav-link collapsed" href="welfare_add_data.php">
                            <i class="bi bi-person-plus"></i><span>Upload student information</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link collapsed" href="welfare_add_hostel.php">
                            <i class="bi bi-person-plus"></i><span>upload hostels </span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link collapsed" href="allstudents.php">
                            <i class="bi bi-card-heading"></i><span>All students</span>
                        </a>
                    </li>





                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="search_warefare.php">
                    <i class="bi bi-person-plus"></i><span>search</span>
                </a>
            </li>

            <li>
                <a class="nav-link collapsed" href="hostelslist.php">
                    <i class="bi bi-person-plus"></i><span>list of hostels</span>
                </a>
            </li>
            <li>
                <a class="nav-link collapsed" href="manage_hostels.php">
                    <i class="bi bi-person-plus"></i><span>manage hostels</span>
                </a>
            </li>
            <!-- manage application -->
            <li>
                <a class="nav-link collapsed" href="manage_applications.php">
                    <i class="bi bi-person-plus"></i><span>manage application</span>
                </a>
            </li>
            <!-- logout -->
            <li>
                <a class="nav-link collapsed" href="../logout.php">
                    <i class="bi bi-person-plus"></i><span>logout</span>
                </a>
            </li>



        <?php } ?>


        <?php if ($_SESSION['role'] == 'information_modifier') { ?>
            <!-- dashboard -->

            <li class="nav-item">
                <a class="nav-link collapsed" href="index.php">
                    <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="add_user.php">
                    <i class="bi bi-person"></i><span>Manage Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#icons-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-folder"></i><span>Manage Data</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="icons-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a class="nav-link collapsed" href="add_data.php">
                            <i class="bi bi-person-plus"></i><span>Upload student information</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link collapsed" href="add_hostel.php">
                            <i class="bi bi-person-plus"></i><span>upload hostels</span>
                        </a>
                    </li>

                    <li>
                        <a class="nav-link collapsed" href="hostelslist.php">
                            <i class="bi bi-person-plus"></i><span>list of hostels</span>
                        </a>
                    </li>

                    <li>
                        <a class="nav-link collapsed" href="updateinfo.php">
                            <i class="bi bi-pencil-square"></i><span>Update Info</span>
                        </a>
                    </li>

                    <li>
                        <a class="nav-link collapsed" href="allstudents.php">
                            <i class="bi bi-card-heading"></i><span>All students</span>
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
                    <i class="bi bi-person-plus"></i><span>manage hostels</span>
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
                            <i class="bi bi-person"></i><span>Profile</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link collapsed" href="setexcel.php">
                            <i class="bi bi-card-list"></i><span>Set Excel</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link collapsed" href="system.php">
                            <i class="bi bi-tools"></i><span>System settings</span>
                        </a>
                    </li>


                    <li>
                        <a class="nav-link collapsed" href="download.php">
                            <i class="bi bi-person"></i><span>backup data file</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- normal one menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="search.php">
                    <i class="bi bi-person-plus"></i><span>search</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="../logout.php">
                    <i class="bi bi-person-plus"></i><span>logout</span>
                </a>
            </li>
        <?php } ?>



    </ul>
</aside>