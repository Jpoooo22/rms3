<?php
session_start();
include "includes/database/helper.php";
require_once 'includes/access_check/access_dashboard.php';
$user = array();
require('includes/database/sqlconnection.php');

if (isset($_SESSION['id'])) {
    $user = get_user_info($con, $_SESSION['id']);
	$u_id = $user['id'];
}

// Get search term from query string
$q = $_GET['q'];

// Prepare and execute SQL query
// $sql = "SELECT * FROM users WHERE department LIKE '%$q%'";
// $sql = "SELECT DISTINCT department FROM users WHERE usertype = 'dean' AND department LIKE '%$q%'";
// $sql = "SELECT department, GROUP_CONCAT(id) as ids FROM users WHERE usertype = '2' AND department LIKE '%$q%' GROUP BY department";
// $sql = "SELECT department, GROUP_CONCAT(id) as ids FROM users WHERE usertype = '2' ";
$sql = "SELECT department FROM users WHERE usertype = '2'";

$result = $con->query($sql);

// Format results as JSON
$data = [];
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $data[] = [
	  'id' => $row['department'],
      'text' => $row['department']
    ];
  }
}
echo json_encode($data);
