<?php
session_start();
include "includes/database/helper.php";
require('includes/database/sqlconnection.php');
require('includes/database/processing/registration-proccessing.php');

function reg_old($k) {
    return isset($_POST[$k]) ? htmlspecialchars($_POST[$k]) : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Request an Account</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-xl-8 col-lg-10 col-md-11">
      <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-0">
          <div class="p-5">
            <div class="text-center">
              <img src="uphsd-logo.png" alt="" style="height:70px;margin-bottom:10px;">
              <h1 class="h4 text-gray-900 mb-1">Request an Account</h1>
              <p class="mb-4 text-muted" style="font-size:.9rem;">College of Engineering &mdash; Records Management System</p>
            </div>
            <?php if ($reg_done) { ?>
              <div class="alert alert-success">
                <h5 class="mb-2"><i class="bi bi-check-circle-fill"></i> Registration submitted</h5>
                Your account has been created but is <strong>waiting for approval</strong>.
                You will not be able to sign in until the Dean approves it.
              </div>
              <div class="text-center"><a href="default.php" class="btn btn-primary btn-user">Back to sign in</a></div>
            <?php } else { ?>
              <?php if (!empty($reg_errors)) { ?>
                <div class="alert alert-danger">
                  <?php foreach ($reg_errors as $e) { echo '<div>' . htmlspecialchars($e) . '</div>'; } ?>
                </div>
              <?php } ?>
              <div class="alert alert-info" style="font-size:.88rem;">
                <i class="bi bi-info-circle"></i>
                Accounts are reviewed before access is granted. Use your institutional email
                so the Dean can verify who you are.
              </div>
              <form method="POST" action="registration.php">
                <div class="form-row">
                  <div class="form-group col-md-4">
                    <label class="small font-weight-bold">First name *</label>
                    <input type="text" name="firstname" class="form-control" required value="<?php echo reg_old('firstname'); ?>">
                  </div>
                  <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Middle name</label>
                    <input type="text" name="middlename" class="form-control" value="<?php echo reg_old('middlename'); ?>">
                  </div>
                  <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Last name *</label>
                    <input type="text" name="lastname" class="form-control" required value="<?php echo reg_old('lastname'); ?>">
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label class="small font-weight-bold">I am a *</label>
                    <select name="account_type" class="form-control" required>
                      <option value="">-- select --</option>
                      <?php foreach (array('Faculty','Researcher') as $at) {
                          $s = (isset($_POST['account_type']) && $_POST['account_type'] === $at) ? ' selected' : '';
                          echo '<option value="' . $at . '"' . $s . '>' . $at . '</option>';
                      } ?>
                    </select>
                  </div>
                  <div class="form-group col-md-6">
                    <label class="small font-weight-bold">Department *</label>
                    <select name="department" class="form-control" required>
                      <option value="">-- select --</option>
                      <?php
                      $depts = array('Civil Engineering','Computer Engineering','Electrical Engineering',
                                     'Electronics Engineering','Industrial Engineering','Mechanical Engineering',
                                     'Administrative Staff','Other');
                      $sel = isset($_POST['department']) ? $_POST['department'] : '';
                      foreach ($depts as $d) {
                          echo '<option value="' . htmlspecialchars($d) . '"' . ($sel === $d ? ' selected' : '') . '>' . htmlspecialchars($d) . '</option>';
                      } ?>
                    </select>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label class="small font-weight-bold">Position / job title</label>
                    <input type="text" name="position" class="form-control" placeholder="e.g. Instructor, Professor" value="<?php echo reg_old('position'); ?>">
                  </div>
                  <div class="form-group col-md-6">
                    <label class="small font-weight-bold">Employee / Serial ID</label>
                    <input type="text" name="serial_number" class="form-control" value="<?php echo reg_old('serial_number'); ?>">
                  </div>
                </div>
                <hr>
                <div class="form-group">
                  <label class="small font-weight-bold">Institutional email *</label>
                  <input type="email" name="email" class="form-control" required value="<?php echo reg_old('email'); ?>">
                </div>
                <div class="form-row">
                  <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Username *</label>
                    <input type="text" name="username" class="form-control" required value="<?php echo reg_old('username'); ?>">
                  </div>
                  <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Password *</label>
                    <input type="password" name="password" class="form-control" required minlength="8" placeholder="at least 8 characters">
                  </div>
                  <div class="form-group col-md-4">
                    <label class="small font-weight-bold">Confirm password *</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8">
                  </div>
                </div>
                <button type="submit" name="register-account" value="1" class="btn btn-primary btn-user btn-block">
                  Submit registration
                </button>
              </form>
              <hr>
              <div class="text-center"><a class="small" href="default.php">Already have an account? Sign in</a></div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
