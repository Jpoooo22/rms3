<?php
// Announcement add/delete handler for the dashboard. Admin-only. Redirects back.
session_start();
require 'includes/database/sqlconnection.php';

// make sure the table exists (safe no-op if it already does)
mysqli_query($con, "CREATE TABLE IF NOT EXISTS announcements (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    posted_by VARCHAR(100) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// only a logged-in Admin (Dean) may add/remove announcements
$is_admin = false;
if (isset($_SESSION['id'])) {
    $uid = intval($_SESSION['id']);
    $ur = mysqli_query($con, "SELECT usertype FROM users WHERE id = $uid");
    if ($ur && ($urow = mysqli_fetch_assoc($ur))) {
        $is_admin = ($urow['usertype'] === 'Admin');
    }
}

if ($is_admin) {
    if (isset($_POST['add_announcement'])) {
        $t = trim($_POST['ann_title'] ?? '');
        $b = trim($_POST['ann_body'] ?? '');
        if ($t !== '' && $b !== '') {
            $pid = (string)($_SESSION['id'] ?? '');
            $st = mysqli_prepare($con, "INSERT INTO announcements (title, body, posted_by) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($st, "sss", $t, $b, $pid);
            mysqli_stmt_execute($st);
        }
    } elseif (isset($_GET['del'])) {
        $did = intval($_GET['del']);
        $st = mysqli_prepare($con, "DELETE FROM announcements WHERE id = ?");
        mysqli_stmt_bind_param($st, "i", $did);
        mysqli_stmt_execute($st);
    }
}

header('Location: dashboard.php');
exit;
