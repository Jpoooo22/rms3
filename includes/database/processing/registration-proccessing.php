<?php
/**
 * Self-registration handler.
 *
 * Creates the account as approval = 'unverified' so it cannot log in until an
 * Admin approves it from users.php. The applicant chooses their own password,
 * so firstlogin is 1 (the forced first-login change does not apply to them).
 */

$reg_errors = array();
$reg_done   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register-account'])) {

    $f = array(
        'firstname'   => trim(isset($_POST['firstname'])   ? $_POST['firstname']   : ''),
        'lastname'    => trim(isset($_POST['lastname'])    ? $_POST['lastname']    : ''),
        'middlename'  => trim(isset($_POST['middlename'])  ? $_POST['middlename']  : ''),
        'email'       => trim(isset($_POST['email'])       ? $_POST['email']       : ''),
        'department'  => trim(isset($_POST['department'])  ? $_POST['department']  : ''),
        'account_type'=> trim(isset($_POST['account_type'])? $_POST['account_type']: ''),
        'position'    => trim(isset($_POST['position'])    ? $_POST['position']    : ''),
        'username'    => trim(isset($_POST['username'])    ? $_POST['username']    : ''),
        'serial'      => trim(isset($_POST['serial_number'])? $_POST['serial_number'] : ''),
    );
    $pw1 = isset($_POST['password']) ? $_POST['password'] : '';
    $pw2 = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if ($f['firstname'] === '')  { $reg_errors[] = 'First name is required.'; }
    if ($f['lastname'] === '')   { $reg_errors[] = 'Last name is required.'; }
    if ($f['email'] === '' || !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
        $reg_errors[] = 'A valid email address is required.';
    }
    if ($f['department'] === '') { $reg_errors[] = 'Department is required.'; }
    if (!in_array($f['account_type'], array('Faculty', 'Researcher'), true)) {
        $reg_errors[] = 'Please choose Faculty or Researcher.';
    }
    if ($f['username'] === '')   { $reg_errors[] = 'Username is required.'; }
    if (strlen($pw1) < 8)        { $reg_errors[] = 'Password must be at least 8 characters long.'; }
    if ($pw1 !== $pw2)           { $reg_errors[] = 'The two passwords do not match.'; }

    // Username / email must not already be taken (pending accounts included).
    if (empty($reg_errors)) {
        $chk = mysqli_stmt_init($con);
        mysqli_stmt_prepare($chk, "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        mysqli_stmt_bind_param($chk, 'ss', $f['username'], $f['email']);
        mysqli_stmt_execute($chk);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($chk))) {
            $reg_errors[] = 'That username or email is already registered. If you already applied, please wait for approval.';
        }
    }

    if (empty($reg_errors)) {
        $hash      = password_hash($pw1, PASSWORD_DEFAULT);
        $usertype  = 'User';          // permissions stay lowest until an Admin changes them
        $approval  = 'unverified';    // <- blocks login until approved
        $firstlogin = 1;              // they chose their own password already
        $addedBy   = 'self-registration';
        $pic       = './img/profile_imgs/user_profile_imgs/defaultprofilepic.png';

        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt,
            "INSERT INTO users
                (SerialID, department, lastname, firstname, middlename, username, password,
                 email, phoneNo, profilepic, approval, usertype, account_type, addedBy,
                 firstlogin, code, user_status, registerDate, position)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?, 0, 'offline', NOW(), ?)");
        mysqli_stmt_bind_param($stmt, 'sssssssssssssis',
            $f['serial'], $f['department'], $f['lastname'], $f['firstname'], $f['middlename'],
            $f['username'], $hash, $f['email'], $pic, $approval, $usertype, $f['account_type'],
            $addedBy, $firstlogin, $f['position']);

        if (mysqli_stmt_execute($stmt)) {
            $reg_done = true;
        } else {
            $reg_errors[] = 'Could not submit your registration. Please try again.';
        }
    }
}
?>
