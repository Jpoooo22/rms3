<?php

// define constant variables
// Values come from environment variables when set (e.g. inside Docker),
// otherwise fall back to the local XAMPP/MySQL setup.
// Local credentials live in db-config.local.php, which is NOT committed to git.
// Copy db-config.local.example.php to db-config.local.php and put your own
// database password there. Environment variables win over both (e.g. Docker).
$local_cfg = __DIR__ . '/db-config.local.php';
$local = file_exists($local_cfg) ? include $local_cfg : array();

function rms_cfg($env, $local, $key, $default) {
    $fromEnv = getenv($env);
    if ($fromEnv !== false && $fromEnv !== '') { return $fromEnv; }
    if (isset($local[$key]) && $local[$key] !== '') { return $local[$key]; }
    return $default;
}

define('DB_NAME',     rms_cfg('DB_NAME',     $local, 'name', 'rms'));
define('DB_USER',     rms_cfg('DB_USER',     $local, 'user', 'root'));
define('DB_PASSWORD', rms_cfg('DB_PASSWORD', $local, 'password', ''));
define('DB_HOST',     rms_cfg('DB_HOST',     $local, 'host', 'localhost'));

try{

    // connection variable
    $con = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    // encoded language
    mysqli_set_charset($con, 'utf8');

    // relax SQL mode so MySQL 8 accepts the loose values this app was built for (MariaDB)
    mysqli_query($con, "SET SESSION sql_mode=''");


}catch (Exception $ex){
    print "An Exception occurred. Message: " . $ex->getMessage();
} catch (Error $e){
    print "The system is busy please try later";
}
?>
<?php
// ---- Force first-time password change ----------------------------------
// Every page includes this file, so this is the single chokepoint that stops a
// user with firstlogin = 0 from browsing anywhere except the change-password page.
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['id']) && isset($con)) {

    $fl_page = strtolower(basename(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH)));
    $fl_allowed = ['first-login.php', 'logout.php', 'default.php'];

    if (!in_array($fl_page, $fl_allowed, true)) {
        $fl_q = mysqli_stmt_init($con);
        if (mysqli_stmt_prepare($fl_q, "SELECT firstlogin FROM users WHERE id = ?")) {
            $fl_uid = (int) $_SESSION['id'];
            mysqli_stmt_bind_param($fl_q, 'i', $fl_uid);
            mysqli_stmt_execute($fl_q);
            $fl_res = mysqli_stmt_get_result($fl_q);
            $fl_row = mysqli_fetch_assoc($fl_res);
            if ($fl_row && (int) $fl_row['firstlogin'] === 0) {
                header('Location: first-login.php');
                exit;
            }
        }
    }
}
