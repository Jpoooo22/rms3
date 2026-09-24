<?php
require('includes/database/sqlconnection.php');
if(!empty($_GET['read'])){
    $notif_id = $_GET['read'];
    $sql = "UPDATE notifications SET notifi_status='read' WHERE notification_id ='$notif_id'";
    $result = mysqli_query($con, $sql);
	
	$sql = "SELECT * FROM notifications WHERE notification_id = {$notif_id}";
	$fetch = mysqli_query($con, $sql);
	foreach($fetch as $row) {
		$task_id = $row['task_id'];
		$phase_name = $row['phase_name'];
		$pid = $row['phase_id'];
	}
	if($row['notifi_type'] == "disapprove") {
		header("location: task-phase.php?view_task_id={$task_id}&phase={$phase_name}&phase_id={$pid}");
	}
	else if($row['notifi_type'] == "assigned") {
		header("location: task-view.php?view_task_id={$task_id}");
	}
	else
		header("location: dashboard.php");
}