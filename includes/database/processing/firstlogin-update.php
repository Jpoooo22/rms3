<?php
// Handles the forced password change shown on first login.
// On success: stores the new hash, clears the firstlogin flag and sends the user to the dashboard.

$fl_errors = [];
$fl_done   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set-new-password'])) {

    $new1 = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $new2 = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $uid  = isset($_SESSION['id']) ? (int) $_SESSION['id'] : 0;

    if ($uid <= 0) {
        $fl_errors[] = 'Your session has expired. Please log in again.';
    }
    if ($new1 === '' || $new2 === '') {
        $fl_errors[] = 'Please fill in both password fields.';
    }
    if ($new1 !== '' && strlen($new1) < 8) {
        $fl_errors[] = 'Your new password must be at least 8 characters long.';
    }
    if ($new1 !== '' && $new1 !== $new2) {
        $fl_errors[] = 'The two passwords do not match.';
    }
    if ($new1 === 'nrtdc2023') {
        $fl_errors[] = 'You cannot reuse the default password. Please choose a new one.';
    }

    if (empty($fl_errors)) {
        $hash = password_hash($new1, PASSWORD_DEFAULT);
        $stmt = mysqli_stmt_init($con);
        mysqli_stmt_prepare($stmt, "UPDATE users SET password = ?, firstlogin = 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $hash, $uid);

        if (mysqli_stmt_execute($stmt)) {
            $fl_done = true;
            // mirror what a normal login does
            $up = mysqli_stmt_init($con);
            mysqli_stmt_prepare($up, "UPDATE users SET user_status = 'online' WHERE id = ?");
            mysqli_stmt_bind_param($up, 'i', $uid);
            mysqli_stmt_execute($up);

            header('Location: dashboard.php');
            exit;
        } else {
            $fl_errors[] = 'Could not update your password. Please try again.';
        }
    }
}
?>
