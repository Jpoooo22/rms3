<?php

require_once "includes/database/processing/forgot-password-proccessing.php";

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>PN Records - Forgot Password</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body class="bg-gradient-primary">

    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center">

            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-password-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-2">Create Your New Password</h1>
                                        <p class="mb-4">
                                        </p>
                                    </div>
                                    <form class="user" action="new-password.php" method="POST" autocomplete="">
                                        <div class="form-group">
                                            <input type="password" name="password" class="form-control form-control-user" id="password" aria-describedby="emailHelp" placeholder="Enter your new password" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" name="cpassword" class="form-control form-control-user" id="cpassword" aria-describedby="emailHelp" placeholder="Enter your new password confirmation" required>
                                        </div>
                                        <button type="submit" name="change-password" class="btn btn-primary btn-user btn-block">
                                            Submit
                                        </button>
                                        <br />
                                        <?php
                                        if (isset($_SESSION['info'])) {
                                        ?>
                                            <div class="alert alert-success text-center" style="padding: 0.4rem 0.4rem">
                                                <?php echo $_SESSION['info']; ?>
                                            </div>
                                        <?php
                                        }
                                        ?>
                                        <?php
                                        if (count($errors) > 0) {
                                        ?>
                                            <div class="alert alert-danger text-center">
                                                <?php
                                                foreach ($errors as $showerror) {
                                                    echo $showerror;
                                                }
                                                ?>
                                            </div>
                                        <?php
                                        }
                                        ?>
                                    </form>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <script>

var password = document.getElementById("password")
  , confirm_password = document.getElementById("cpassword");

function validatePassword(){
  if(password.value != confirm_password.value) {
    confirm_password.setCustomValidity("Passwords Don't Match");
  } else {
    confirm_password.setCustomValidity('');
  }
}

password.onchange = validatePassword;
confirm_password.onkeyup = validatePassword;



   </script>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

</body>

</html>