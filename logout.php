<?php
session_start();
require('includes/database/sqlconnection.php');
include "includes/database/helper.php";
if (isset($_SESSION['id'])) {
    $user = get_user_info($con, $_SESSION['id']);
}
$user_id = $user['id'];
    
$sql = "UPDATE users SET user_status='offline' WHERE id='$user_id'";
$result = mysqli_query($con, $sql);
if ($result > 0) {
    
    session_destroy();
    header("Location:default.php");
    exit();

}

