<?php
/**
 * Shared page shell for the Command Center pages.
 * Reuses the existing SB Admin 2 theme, sidebar and topbar so the new pages
 * sit alongside the current app rather than replacing anything.
 *
 * Expects $user and $con from cc_bootstrap.php.
 * Set $CC_PAGE_TITLE and (optionally) $CC_ACTIVE before including.
 */

$CC_PAGE_TITLE = isset($CC_PAGE_TITLE) ? $CC_PAGE_TITLE : 'Command Center';
$CC_ACTIVE     = isset($CC_ACTIVE) ? $CC_ACTIVE : 'command-center';

$cc_unread = 0;
$cc_q = mysqli_prepare($con, "SELECT COUNT(*) AS c FROM notifications WHERE notifi_status='unread' AND notifi_userid=?");
$cc_uid = (string) $user['id'];
mysqli_stmt_bind_param($cc_q, 's', $cc_uid);
mysqli_stmt_execute($cc_q);
$cc_row = mysqli_fetch_assoc(mysqli_stmt_get_result($cc_q));
if ($cc_row) { $cc_unread = (int) $cc_row['c']; }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo cc_e($CC_PAGE_TITLE); ?></title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="#" />

    <style>
        /* Command Center - additive styles only, nothing here overrides the theme. */
        .cc-card {
            border: none;
            border-radius: .5rem;
            overflow: hidden;
            transition: transform .15s ease, box-shadow .15s ease;
            height: 100%;
        }
        .cc-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 .5rem 1.25rem rgba(58, 59, 69, .25) !important;
        }
        .cc-card-banner {
            height: 120px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .cc-card-banner .cc-code {
            position: absolute;
            left: .75rem;
            bottom: .6rem;
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-shadow: 0 1px 3px rgba(0, 0, 0, .6);
        }
        .cc-card-banner .cc-status {
            position: absolute;
            right: .75rem;
            top: .6rem;
        }
        .cc-tracks {
            font-size: .8rem;
            color: #6e707e;
            min-height: 3.2rem;
        }
        .cc-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #4e73df;
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: -6px;
            border: 2px solid #fff;
        }
        .cc-avatar.cc-more { background: #858796; }
        .cc-progress { height: 8px; border-radius: 4px; }
        .cc-meta { font-size: .72rem; color: #858796; }
        .cc-empty-banner {
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, .55);
            font-size: 2rem;
        }
    </style>
</head>

<body id="page-top">

    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon rotate-n-15"></div>
                <div class="sidebar-brand-text mx-3">NRTDC <sup></sup></div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <li class="nav-item <?php echo $CC_ACTIVE === 'command-center' ? 'active' : ''; ?>">
                <a class="nav-link" href="command-center.php">
                    <i class="fas fa-fw fa-th-large"></i>
                    <span>Command Center</span></a>
            </li>

            <li class="nav-item <?php echo $CC_ACTIVE === 'calendar' ? 'active' : ''; ?>">
                <a class="nav-link" href="cc-calendar.php">
                    <i class="fas fa-fw fa-calendar-check"></i>
                    <span>Deadlines</span></a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">Personal Space</div>

            <?php if (cc_can_manage_projects($user)) { ?>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#ccCollapseUsers" aria-expanded="false" aria-controls="ccCollapseUsers">
                        <i class="bi bi-people"></i>
                        <span>User Management</span>
                    </a>
                    <div id="ccCollapseUsers" class="collapse" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <a class="collapse-item" href="new-user.php">Add new user</a>
                            <a class="collapse-item" href="users.php">View User List</a>
                        </div>
                    </div>
                </li>
            <?php } ?>

            <?php if ((string) $user['usertype'] !== '2') { ?>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#ccCollapseTasks" aria-expanded="false" aria-controls="ccCollapseTasks">
                        <i class="bi bi-list-task"></i>
                        <span>Task Management</span>
                    </a>
                    <div id="ccCollapseTasks" class="collapse" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <a class="collapse-item" href="add-task.php">Add task</a>
                            <a class="collapse-item" href="assign-tasks.php">Assign tasks</a>
                        </div>
                    </div>
                </li>
            <?php } ?>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">System</div>

            <li class="nav-item">
                <a class="nav-link" href="system-log.php">
                    <i class="bi bi-journal-text"></i>
                    <span>System Logs</span></a>
            </li>

            <hr class="sidebar-divider d-none d-md-block">

            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>
        <!-- End of Sidebar -->

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <h4 class="welcome-text"><?php
                        $hour = (int) date('G');
                        if ($hour >= 5 && $hour <= 11)      { echo 'Good Morning'; }
                        else if ($hour >= 12 && $hour <= 18) { echo 'Good Afternoon'; }
                        else                                 { echo 'Good Evening'; }
                        ?>, <span class="text-black fw-bold"><?php echo cc_e(cc_person_name($user)); ?></span>
                    </h4>

                    <ul class="navbar-nav ml-auto">

                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="ccAlertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <?php if ($cc_unread > 0) { ?>
                                    <span class="badge badge-danger badge-counter"><?php echo $cc_unread; ?></span>
                                <?php } ?>
                            </a>
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in overflow-auto" aria-labelledby="ccAlertsDropdown" style="max-height:400px;">
                                <h6 class="dropdown-header">notifications</h6>
                                <?php
                                $cc_nq = mysqli_prepare($con,
                                    "SELECT notifi_title, notifi_name, notifi_status, notifi_date
                                     FROM notifications WHERE notifi_userid = ?
                                     ORDER BY notifi_date DESC LIMIT 15");
                                mysqli_stmt_bind_param($cc_nq, 's', $cc_uid);
                                mysqli_stmt_execute($cc_nq);
                                $cc_nres  = mysqli_stmt_get_result($cc_nq);
                                $cc_any   = false;
                                while ($n = mysqli_fetch_assoc($cc_nres)) {
                                    $cc_any = true; ?>
                                    <a class="dropdown-item d-flex align-items-center" href="notifications.php">
                                        <div class="mr-3">
                                            <div class="icon-circle bg-primary">
                                                <i class="fas <?php echo $n['notifi_status'] === 'unread' ? 'fa-envelope' : 'fa-envelope-open'; ?> text-white"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="small text-gray-500"><?php echo cc_e($n['notifi_date']); ?></div>
                                            <span class="<?php echo $n['notifi_status'] === 'unread' ? 'font-weight-bold' : ''; ?>">
                                                <?php echo cc_e($n['notifi_title']); ?>
                                            </span>
                                        </div>
                                    </a>
                                <?php }
                                if (!$cc_any) { ?>
                                    <span class="small d-block text-center py-3 text-gray-500">There are no notifications available</span>
                                <?php } ?>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="ccUserDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo cc_e(cc_person_name($user)); ?></span>
                                <img class="img-profile rounded-circle" src="<?php echo cc_e($user['profilepic']); ?>">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="ccUserDropdown">
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#ccLogoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <div class="container-fluid">
                <?php $cc_flash = cc_flash(); if ($cc_flash) { ?>
                    <div class="alert alert-<?php echo cc_e($cc_flash['type']); ?> alert-dismissible fade show" role="alert">
                        <?php echo cc_e($cc_flash['msg']); ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php } ?>
