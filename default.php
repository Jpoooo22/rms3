<?php 
session_start();
require_once 'includes/access_check/access_login.php';

?>

<?php 
include "includes/database/helper.php";

$user = array();
    require ('includes/database/sqlconnection.php');

    if(isset($_SESSION['id'])){
        $user = get_user_info($con, $_SESSION['id']);
    }
	$username1 = "";
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        require ('includes/database/processing/login-proccessing.php');
		$username1 = $_POST['username'];
    }
	
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Login</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
	<!-- icon -->
	<link rel="icon" href="#"/>
</head>
<style>
	.largeThis {
		font-size: 40px;
	}
	body {
		 background-image: url("img/main-imgs/this22.png");
		background-repeat:no-repeat; background-size:cover; 
		/*background-color: #1E3862; */
	}

	input {
		padding:8px;
		display:block;
		/*border:none;
		backdrop-filter: blur(3px);*/
		width: 60%;
		border-radius:10px;
		color: black;
	}
	/*
	input:focus{
		outline: none;
		border-bottom:1px solid #ccc;
		background-color: rgba(255, 255, 255, .15);  
	}
	*/
	.logo {
		height: 90px;
	}
	.glassy-row {
		background-color: rgba(128, 0, 0, 0.1); /* Adjust the opacity as needed */
		backdrop-filter: blur(15px);
	}
</style>
<body >
<div class="container">
    <!-- Outer Row -->
    <div class="row justify-content-center">
        <div class="col-xl-6">
            <div class="card o-hidden border-0 shadow-login my-5 glassy-row">
                <div class="card-body p-0">
                    <!-- Nested Row within Card Body -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="p-5 d-lg-block">
                                <div class="text-center mb-2">
                                    <img src="img/main-imgs/uphsdlogo.png" alt="UPHSD logo" class="logo">
                                    <h1 class="h5 mt-3 mb-0" style="color:#6d071a;font-weight:800;letter-spacing:.3px;">University of Perpetual Help System DALTA</h1>
                                    <div style="color:#4a0511;font-weight:600;font-size:.95rem;">Las Pi&ntilde;as Campus</div>
                                    <div class="mt-2" style="display:inline-block;background:#6d071a;color:#fff;padding:4px 18px;border-radius:20px;font-weight:700;letter-spacing:.5px;border:2px solid #d4af37;">College of Engineering</div>
                                    <h2 class="h6 mt-3 mb-4" style="color:#4a0511;font-weight:600;">Records Management System</h2>
                                </div>
                                <form class="user" action="default.php" method="post" enctype="multipart/form-data">
                                    <center>
                                        <div class="form-group">
                                            <input type="text" name="username" id="username" aria-describedby="emailHelp" value="<?php echo $username1; ?>" placeholder="Username" required autofocus class="form-control" style = "width: 40%;">
                                        </div>
                                        <div class="form-group">
                                            <input type="password" name="password" id="password" placeholder="Password" required class="form-control" style = "width: 40%;">
                                        </div>
                                        <input type="submit" value="Sign In" class="btn btn-primary btn-user" style = "width: 40%;">
                                    </center>
                                </form>
                                <hr>
                                <div class="text-center">
                                    <!-- <a class="small" href="forgot-password.php">Register</a> -->
                                    <a class="small" href="forgot-password.php" style="color: black;">Forgot Password</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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

</body>

</html>