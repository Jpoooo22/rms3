<?php
session_start();

// Must be logged in to be here.
if (!isset($_SESSION['id'])) {
    header('Location: default.php');
    exit;
}

include "includes/database/helper.php";
require('includes/database/sqlconnection.php');

$user = get_user_info($con, $_SESSION['id']);

// If this account has already completed its first login, don't show this page again.
if ($user && isset($user['firstlogin']) && $user['firstlogin'] != 0) {
    header('Location: dashboard.php');
    exit;
}

require('includes/database/processing/firstlogin-update.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Set Your Password</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-8 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="p-5">

                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-2">Welcome<?php echo !empty($user['firstname']) ? ', ' . htmlspecialchars($user['firstname']) : ''; ?>!</h1>
                                <p class="mb-4">
                                    This is your first time logging in. For your security you must
                                    replace the default password before you can continue.
                                </p>
                            </div>

                            <?php if (!empty($fl_errors)) { ?>
                                <div class="alert alert-danger">
                                    <?php foreach ($fl_errors as $e) { echo '<div>' . htmlspecialchars($e) . '</div>'; } ?>
                                </div>
                            <?php } ?>

                            <form class="user" method="POST" action="first-login.php">
                                <div class="form-group">
                                    <input type="password" name="new_password" id="new_password"
                                           class="form-control form-control-user"
                                           placeholder="New password (at least 8 characters)" required minlength="8">
                                </div>
                                <div class="form-group">
                                    <input type="password" name="confirm_password" id="confirm_password"
                                           class="form-control form-control-user"
                                           placeholder="Confirm new password" required minlength="8">
                                </div>
                                <button type="submit" name="set-new-password" value="1"
                                        class="btn btn-primary btn-user btn-block">
                                    Save password and continue
                                </button>
                            </form>

                            <hr>
                            <div class="text-center">
                                <a class="small" href="logout.php">Sign out instead</a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
</body>

</html>
