<?php
/**
 * Email validation for the Edit User form (user-edit.php).
 *
 * The password reset flow finds an account by email alone:
 *     SELECT * FROM users WHERE email = '...'
 *     UPDATE users SET code = <otp> WHERE email = '...'
 * so two accounts sharing an address makes a reset ambiguous - the same OTP is
 * written to both rows and the verify step takes whichever row comes back
 * first. A Faculty account's reset could then land on the Dean's account.
 *
 * This guard keeps every address unique and well-formed. It mirrors
 * cc_newuser_guard.php, which does the same job for the Add User form, and
 * runs BEFORE user-edit.php's own POST handling via a prepended require.
 *
 * It opens its own short-lived connection rather than including
 * sqlconnection.php, because that file uses define() and the page requires
 * it (not require_once) further down.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['profile_update'])) {
    return;
}

$cc_email  = isset($_POST['email']) ? trim($_POST['email']) : '';
$cc_edit_id = isset($_POST['editID']) ? (int) $_POST['editID'] : 0;
$cc_error  = '';

if ($cc_email === '') {
    $cc_error = 'An email address is required so this user can reset their own password.';
} elseif (strlen($cc_email) > 255) {
    $cc_error = 'That email address is too long.';
} elseif (!filter_var($cc_email, FILTER_VALIDATE_EMAIL)) {
    $cc_error = 'Please enter a valid email address, for example name@gmail.com.';
}

/* Any OTHER account already using this address makes password reset ambiguous. */
if ($cc_error === '' && $cc_edit_id > 0) {

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
        $cc_stmt = $cc_link->prepare(
            'SELECT username FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $cc_stmt->bind_param('si', $cc_email, $cc_edit_id);
        $cc_stmt->execute();
        $cc_clash = $cc_stmt->get_result()->fetch_assoc();

        if ($cc_clash) {
            $cc_error = 'The account "' . $cc_clash['username'] . '" already uses '
                      . $cc_email . '. Two accounts cannot share an address, because '
                      . 'password reset would not know which one to reset. '
                      . 'Tip: a Gmail alias such as name+john@gmail.com reaches the '
                      . 'same inbox but counts as a separate address.';
        }

        $cc_stmt->close();
        $cc_link->close();
    }
}

if ($cc_error !== '') {
    // Matches the SweetAlert pattern these pages already use.
    $_SESSION['success'] = 'Email Address Problem';
    $_SESSION['text']    = $cc_error;
    $_SESSION['icon']    = 'warning';

    header('Location: user-edit.php?editUser=' . $cc_edit_id);
    exit;
}
