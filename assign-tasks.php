<?php require __DIR__ . '/includes/cc/cc_nav_buffer.php'; ?><?php
if (isset($_SESSION['id'])) {
    $user = get_user_todolist($con, $_SESSION['id']);
}

?>




<?php
session_start();
include "includes/database/helper.php";
require_once 'includes/access_check/access_dashboard.php';
$user = array();
require('includes/database/sqlconnection.php');

if (isset($_SESSION['id'])) {
    $user = get_user_info($con, $_SESSION['id']);
	$a_id = $user['id'];
}



?>

<!-- this is the code for add task tool -->
<?php

if (isset($_POST["assigntask"])) { 
    $task_name = mysqli_real_escape_string($con, $_POST["task_name"]);
    $user_name = mysqli_real_escape_string($con, $_POST["user_name"]);
    $user_classification = mysqli_real_escape_string($con, $_POST["user_classification"]);
    $assigned_By = '' . $user['firstname'] . ' ' . $user['lastname'] . ' ';
	
	//check if task already exist in history table

	$check_histo = "SELECT * FROM history_log where task_id = '$task_name'";
	$result = mysqli_query($con, $check_histo) or die ('Error in query: $query. '. mysql_error()); 
	if(mysqli_num_rows($result) == 0) {
		$query = "INSERT INTO history_log(history_no, task_id, assigner_id, assignee_id)
				VALUES('1', '$task_name', '$a_id', '$user_name');";
		$con->query($query);	
		$sql = "INSERT INTO assigned_to (id_assgined_to, task_id, user_id, user_class, assigned_By, assigning_date)";
		$sql .= "VALUES(' ', ?, ?, ?, ?, NOW())";
		// initialize a statement
		$q = mysqli_stmt_init($con);

		// prepare sql statement
		mysqli_stmt_prepare($q, $sql);

		// bind values
		mysqli_stmt_bind_param($q, 'ssss', $task_name, $user_name, $user_classification, $assigned_By);

		// execute statement
		mysqli_stmt_execute($q);
		if (mysqli_stmt_affected_rows($q) == 1) {
			$sql = "SELECT * FROM task WHERE  id_task='$task_name'";
			$task_select = mysqli_query($con, $sql);
			$row_taskname = mysqli_fetch_array($task_select);
			$notifi_title = 'You have been assigned as a '.$user_classification.'';
			$notifi_type = 'Assignee';
			$notifi_name = ''.$row_taskname['task_name'].'';
			$sql = "INSERT INTO notifications (notification_id, notifi_title, notifi_userid, notifi_type, notifi_name, notifi_date)";
			$sql .= "VALUES(' ', ?, ?, ?, ?, NOW())";
			// initialize a statement
			$q = mysqli_stmt_init($con);
		
			// prepare sql statement
			mysqli_stmt_prepare($q, $sql);
		
			// bind values
			mysqli_stmt_bind_param($q, 'ssss', $notifi_title, $user_name, $notifi_type, $notifi_name);
		
			// execute statement
			mysqli_stmt_execute($q);
			//add to system log
			if (mysqli_stmt_affected_rows($q) == 1) {
				$id = $user['id'];
				$fName = $user['firstname']." ".$user['lastname'];
				$assignee_name = get_user_info($con, $user_name);
				$a_name = $assignee_name['firstname'] . " " . $assignee_name['lastname'];
				$ipaddress = $_SERVER['REMOTE_ADDR'];
				$action_name = "Assgined task ".$row_taskname['task_name']. " to ". $a_name;
				$action_page = "Assign tasks page";
				$sql = "INSERT INTO log_activities (user_id, user_name, ip, action_name, action_page, action_date)
						VALUES('$id', '$fName', '$ipaddress', '$action_name', '$action_page', NOW());";
				$result_ref = mysqli_query($con, $sql);
			}
			$_SESSION['success'] = "Task Assigned";
			$_SESSION['text'] = "New assigning has been saved successfully";
			$_SESSION['icon'] = "success";
		} else {
			$_SESSION['success'] = "Unsuccessfull";
			$_SESSION['text'] = "Unkown error, please try again";
			$_SESSION['icon'] = "error";
		}
	}
	else {
		$sql_maxH = "SELECT MAX(history_no) as max FROM history_log WHERE task_id = '$task_name';";
		$max_histo_no = mysqli_query($con, $sql_maxH);
		foreach($max_histo_no as $r) { $max = $r['max'];}
		//check current task owner
		$sql_curOwner = "SELECT assignee_id, firstname, lastname, rank FROM history_log INNER JOIN users ON history_log.assignee_id = users.id WHERE history_log.task_id = '$task_name' AND history_log.history_no = '$max'";
		$execute_owner = mysqli_query($con, $sql_curOwner);

		$result = mysqli_query($con, $sql_curOwner) or die ('Error in query: $query. '. mysql_error()); 
		foreach($execute_owner as $r_curOwner) { $fullname = $r_curOwner['firstname']." ". $r_curOwner['lastname'];}
		if(mysqli_num_rows($result) >= 1) {
			$_SESSION['success'] = "Task Already Assigned";
			$_SESSION['text'] = "Current task owner is " .$fullname;
			$_SESSION['icon'] = "error";
		}
	}
}
?>

<?php
$query = "SELECT * FROM users ORDER BY id DESC";
$users_res = mysqli_query($con, $query);
?>

<?php
$query_task = "SELECT * FROM task ORDER BY id_task DESC";
$task_res = mysqli_query($con, $query_task);
?>

<?php
$all_notifs_modal_query = "SELECT * FROM notifications WHERE notifi_userid='{$user['id']}' ORDER BY notifi_date DESC";
$all_notifs_result = mysqli_query($con, $all_notifs_modal_query);
?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>NRTDC - Add User</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
	<!-- icon -->
	<link rel="icon" href="NRTDC LOGO.PNG"/>

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <!-- <i class="fas fa-laugh-wink"></i> -->
                </div>
                <div class="sidebar-brand-text mx-3">NRTDC <sup></sup></div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item active">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Personal Space
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <?php if ($user['usertype'] == 7) {
            ?>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                        <i class="bi bi-people"></i>
                        <span>User Management</span>
                    </a>
                    <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <a class="collapse-item" href="new-user.php">Add new user</a>
                            <a class="collapse-item" href="users.php">View User List</a>
                            
                        </div>
                    </div>
                </li>
			<?php } if($user['usertype'] != 2) { ?>
            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages" aria-expanded="true" aria-controls="collapsePages">
                    <i class="bi bi-list-task"></i>
                    <span>Task Management</span>
                </a>
                <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                            <a class="collapse-item" href="add-task.php">Add task</a>
                            <a class="collapse-item" href="assign-tasks.php">Assign tasks</a>
                            <a class="collapse-item" href="tasks-list.php">Tasks list</a>
                    </div>
                </div>
            </li>
			<?php } ?>
            <!-- Nav Item - Utilities Collapse Menu -->

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
			System
            </div>
			
			<li class="nav-item">
				<a class="nav-link" href="system-log.php">
				<i class="bi bi-journal-text"></i>
				<span>System Logs</span></a>
			</li>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <h4 class="welcome-text"> <?php
                                                date_default_timezone_set('Asia/manila');
                                                $hour = date('G');
                                                if ($hour >= 5 && $hour <= 11) {
                                                    echo "Good Morning";
                                                } else if ($hour >= 12 && $hour <= 18) {
                                                    echo "Good Afternoon";
                                                } else if ($hour >= 19 || $hour <= 4) {
                                                    echo "Good Evening";
                                                }
                                                ?>

                        , <span class="text-black fw-bold"><?php echo isset($user['firstname']) ? $user['firstname'] : ''; ?> <?php echo isset($user['lastname']) ? $user['lastname'] : ''; ?></span></h4>


                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>

                        <!-- Nav Item - Alerts -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <!-- Counter - Alerts -->
                                <?php
                                $query = "SELECT * FROM notifications WHERE notifi_status='unread' AND notifi_userid='{$user['id']}' ";
                                $result_count = mysqli_query($con, $query);

                                if (mysqli_num_rows($result_count) == 0) {
                                } else {
                                ?>


                                    <span class="badge badge-danger badge-counter">
                                        <?php
                                        $query = "SELECT * FROM notifications WHERE notifi_status='unread' AND notifi_userid='{$user['id']}' ";
                                        $result_count = mysqli_query($con, $query);

                                        $rows = mysqli_num_rows($result_count);
                                        echo '' . $rows . '';
                                        ?>


                                    </span>
                                <?php } ?>
                            </a>
                            <!-- Dropdown - Alerts -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in overflow-auto" aria-labelledby="alertsDropdown" style="height:400px;">
                                <h6 class="dropdown-header">
                                    notifications
                                </h6>
                                <?php

                                $sql = "SELECT * FROM notifications WHERE  notifi_userid='{$user['id']}' ORDER BY notifi_date DESC";
                                $notification = mysqli_query($con, $sql);
                                $row_notification = mysqli_fetch_array($notification, MYSQLI_ASSOC);
                                if (!empty($row_notification)) {
                                foreach ($notification as $notfi_list) {

                                ?>
                                <a class="dropdown-item d-flex align-items-center" href='readmassege.php?read=<?php echo $notfi_list['notifi_name'] ?>'>
                                    
                                    <div class="mr-3">
                                        <div class="icon-circle bg-primary">
                                        <?php if ($notfi_list['notifi_status'] == 'unread') { ?>
                                            <i class="fas fa-envelope text-white"></i>
                                            <?php } else { ?>
                                                <i class="fas fa-envelope-open text-white"></i>
                                                <?php }?>
                                            
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500"><?php echo isset($notfi_list['notifi_date']) ? $notfi_list['notifi_date'] : ''; ?></div>
                                        <span class="
                                        <?php if ($notfi_list['notifi_status'] == 'unread') { ?>
                                            font-weight-bold
                                            <?php } else { ?>

                                                <?php }?>
                                        ">
                                        <?php echo isset($notfi_list['notifi_title']) ? $notfi_list['notifi_title'] : ''; ?> 
                                        to <?php echo isset($notfi_list['notifi_name']) ? $notfi_list['notifi_name'] : ''; ?> task
                                        </span>
                                    </div>
                                </a>
                                
                                <?php } } else { ?>
                                    <span class="small" style="margin-left: 22%;">There are no notifications available</span>
                                    <?php }?>
                                    <a class="dropdown-item text-center small text-gray-500" href="#allNotifsModal" data-toggle="modal" data-target="#allNotifsModal">Show All Notifications</a>
                            </div>
                        </li>


<!--ALL NOTIFS MODAL-->

<form method="post" enctype="multipart/form-data">
<div id="allNotifsModal" class="modal fade" role="dialog">
  <div class="modal-dialog modal-lg">
    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">NOTIFICATIONS</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <!-- beginning of modal content here -->
      
       
            <div class="card shadow mb-4">
                <div class="card-body">
                    <table class="table align-middle">
                     <thead class="thead-dark">
                     <tr class="text-center">
                         <th scope="col">Notification</th>
                         <th scope="col">Task Name</th>
                         <th scope="col">Timestamp</th>
                     </tr>
                    </thead>
    <tbody>
    <!--all notifs modal list -->
    <?php

    while ($all_notifs_modal_row = mysqli_fetch_array($all_notifs_result)) {
            
        echo '  <tr>  
                <th scope="row"> '. $all_notifs_modal_row ["notifi_title"].' </th>
                <td class="text-center">' . $all_notifs_modal_row["notifi_name"] . '</td>
                <td class="text-center">' . $all_notifs_modal_row["notifi_date"] . '</td> 
                </tr>';
    }
     ?>

  </tbody>
</table>

                
                            </div>
                        </div>
      <!-- end of modal content -->
</div>
      <div class="modal-footer">
        <button type="button" name = "close_update" class="btn btn-danger" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
</form> 

<!--END OF ALL NOTIFS MODAL-->

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo isset($user['firstname']) ? $user['firstname'] : ''; ?> <?php echo isset($user['lastname']) ? $user['lastname'] : ''; ?></span>
                                <img class="img-profile rounded-circle" src="<?php echo isset($user['profilepic']) ? $user['profilepic'] : ''; ?>">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Settings
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Activity Log
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->


                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-0 text-gray-800">Task Assigner</h1>
                    <p class="mb-4">This tool enable you to assign tasks to workers</p>




                    <form action="assign-tasks.php" method="post" enctype="multipart/form-data">
                        <div class="content-wrapper">
                            <div class="row">

                                <div class="col-md-6 grid-margin stretch-card">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="card-title">Assign task</h4>
                                            <p class="card-description">
                                                Users will be able to veiw tasks assgined to them only
                                            </p>

                                            <div class="form-group">
                                                <label>Task name</label>
                                                <div class="" id="task_name" name="task_name">
                                                    <select class="form-control form-control-sm" id="task_name" name="task_name" required>
                                                        <option value="">Select task name</option>
                                                        <?php 
                                                        while ($row_task = mysqli_fetch_array($task_res)) {

                                                        echo '<option value="'.$row_task['id_task'].'">'.$row_task['task_name'].'</option>';

                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>User Name</label>
                                                <div class="" id="user_name" name="user_name">
                                                    <select class="form-control form-control-sm" id="user_name" name="user_name" required>
                                                        <option value="">Select from users</option>
                                                        <?php 
                                                        
                                                        $query_contributor = "SELECT * FROM users ORDER BY firstname, lastname ASC";
                                                        $contributor_res = mysqli_query($con, $query_contributor);
                                                        while ($row_users = mysqli_fetch_array($users_res)) {

                                                        echo '<option value="'.$row_users['id'].'">'.$row_users['firstname'].' '.$row_users['lastname'].'</option>';

                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>User classification</label>
                                                <div class="" id="user_classification" name="user_classification">
                                                    <select class="form-control form-control-sm" id="user_classification" name="user_classification" required>
                                                        <option value="">Select user classification</option>
                                                        <option value="Primary">Primary</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <button type="submit" name="assigntask" class="btn btn-info">Assign Task</button>

                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 d-flex flex-column">

                                    <!-- right side bar to do list seaction check orginal file  start-->
                                    <div class="row flex-grow">
                                        <div class="col-12 grid-margin stretch-card">
                                            <div class="card card-rounded">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <h4 class="card-title card-title-dash">Quick links</h4>
                                                            </div>
                                                            <div class="list-wrapper">
                                                                <ul class="todo-list todo-list-rounded">
                                                                    <li class="border-bottom-0 mt-1">
                                                                        <div class="form-check w-100">
                                                                            <a href="add-task.php" class="text-primary">
                                                                                Add new task
                                                                            </a>
                                                                        </div>
                                                                    </li>
                                                                    <li class="border-bottom-0 mt-1">
                                                                        <div class="form-check w-100">
                                                                            <a href="tasks-list.php" class="text-primary">
                                                                                View tasks
                                                                            </a>
                                                                        </div>
                                                                    </li>
                                                                
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </form>


                    <!-- Content Row -->
                    <div class="row">

                        <!-- Content Column -->
                        <div class="col-lg-6 mb-4">


                        </div>

                        <div class="col-lg-6 mb-4">



                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->


            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; NRTDC 2021</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
    

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/chart.js/Chart.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="js/demo/chart-area-demo.js"></script>
    <script src="js/demo/chart-pie-demo.js"></script>
    <script src="js/sweetalert.min.js"></script>

    <?php
    if (isset($_SESSION['success']) && $_SESSION['success'] != '') {
    ?>
        <script>
            swal({
                title: "<?php echo $_SESSION['success']; ?>",
                text: "<?php echo $_SESSION['text']; ?>",
                icon: "<?php echo $_SESSION['icon']; ?>",
                button: "OK",
            });
        </script>
    <?php
        unset($_SESSION['success']);
    }
    ?>

</body>

</html>