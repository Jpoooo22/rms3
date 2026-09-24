<?php
/**
 * Server-side email validation for the Add User form.
 *
 * Every account needs a working email address, otherwise "Forgot password"
 * has nowhere to send the reset code. The browser enforces this too (the
 * field is type="email" required), but that is only a convenience - this
 * guard is the check that actually cannot be bypassed.
 *
 * Runs BEFORE new-user.php's own POST handling, via a prepended require.
 * It opens its own short-lived connection rather than including
 * sqlconnection.php, because that file uses define() and new-user.php
 * requires it (not require_once) further down the page.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['register-user'])) {
    return;
}

$cc_email = isset($_POST['email']) ? trim($_POST['email']) : '';
$cc_error = '';

if ($cc_email === '') {
    $cc_error = 'An email address is required so this user can reset their own password later.';
} elseif (strlen($cc_email) > 255) {
    $cc_error = 'That email address is too long.';
} elseif (!filter_var($cc_email, FILTER_VALIDATE_EMAIL)) {
    $cc_error = 'Please enter a valid email address, for example name@gmail.com.';
}

/* Reject an address that already belongs to another account, otherwise a
   password reset could not tell the two accounts apart. */
if ($cc_error === '') {

    $cc_cfg_file = __DIR__ . '/../database/db-config.local.php';
    $cc_local    = file_exists($cc_cfg_file) ? include $cc_cfg_file : array();

    $cc_setting = function ($env, $key, $default) use ($cc_local) {
        $fromEnv = getenv($env);
        if ($fromEnv !== false && $fromEnv !== '') { return $fromEnv; }
        if (isset($cc_local[$key]) && $cc_local[$key] !== '') { return $cc_local[$key]; }
        return $default;
    };

    $cc_link = @new mysqli(
        $cc_setting('DB_HOST', 'host', 'localhost'),
        $cc_setting('DB_USER', 'user', 'root'),
        $cc_setting('DB_PASSWORD', 'password', ''),
        $cc_setting('DB_NAME', 'name', 'rms')
    );

    if (!$cc_link->connect_error) {
        $cc_stmt = $cc_link->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $cc_stmt->bind_param('s', $cc_email);
        $cc_stmt->execute();

        if ($cc_stmt->get_result()->fetch_assoc()) {
            $cc_error = 'Another account already uses ' . $cc_email . '. Please use a different address.';
        }

        $cc_stmt->close();
        $cc_link->close();
    }
}

if ($cc_error !== '') {
    // Matches the SweetAlert pattern the page already uses for feedback.
    $_SESSION['success'] = 'Email Address Required';
    $_SESSION['text']    = $cc_error;
    $_SESSION['icon']    = 'warning';

    // Keep what was typed so the form can be refilled.
    $_SESSION['cc_newuser_retry'] = array(
        'serial_number'       => isset($_POST['serial_number']) ? $_POST['serial_number'] : '',
        'department'          => isset($_POST['department']) ? $_POST['department'] : '',
        'lastname'            => isset($_POST['lastname']) ? $_POST['lastname'] : '',
        'firstname'           => isset($_POST['firstname']) ? $_POST['firstname'] : '',
        'middlename'          => isset($_POST['middlename']) ? $_POST['middlename'] : '',
        'username'            => isset($_POST['username']) ? $_POST['username'] : '',
        'email'               => $cc_email,
        'user_classification' => isset($_POST['user_classification']) ? $_POST['user_classification'] : '',
        'position'            => isset($_POST['position']) ? $_POST['position'] : '',
    );

    header('Location: new-user.php');
    exit;
}

// Valid - let new-user.php carry on and create the account.
unset($_SESSION['cc_newuser_retry']);
