<?php
/**
 * Command Center installer - idempotent.
 *
 * Creates the projects layer on an EXISTING rms database. Safe to run repeatedly:
 * it only creates what is missing and never drops or overwrites your data.
 *
 * Run from the browser as a logged-in Admin, or from the command line:
 *     php install-command-center.php
 */

$cli = (php_sapi_name() === 'cli');

if (!$cli) {
    session_start();
    require_once 'includes/database/helper.php';
    if (!isset($_SESSION['id'])) { header('Location: default.php'); exit; }
}

require 'includes/database/sqlconnection.php';

if (!$cli) {
    $me = get_user_info($con, $_SESSION['id']);
    if (!in_array((string) $me['usertype'], array('Admin', '7'), true)) {
        http_response_code(403);
        exit('Only an Admin can run the Command Center installer.');
    }
}

$log = array();
function step($msg, $ok = true) { global $log; $log[] = array($ok, $msg); }

/* ---------- projects ---------- */
$exists = mysqli_query($con, "SHOW TABLES LIKE 'projects'");
if ($exists && mysqli_num_rows($exists) > 0) {
    step('Table `projects` already present - left as is.');
} else {
    $sql = "CREATE TABLE `projects` (
      `id` int NOT NULL AUTO_INCREMENT,
      `code` varchar(50) NOT NULL,
      `title` varchar(255) NOT NULL,
      `subtitle` varchar(255) NOT NULL DEFAULT '',
      `tracks` text,
      `description` text,
      `image` varchar(500) NOT NULL DEFAULT '',
      `status` varchar(50) NOT NULL DEFAULT 'On Track',
      `progress` int NOT NULL DEFAULT '0',
      `next_milestone` varchar(255) NOT NULL DEFAULT '',
      `next_milestone_date` date DEFAULT NULL,
      `lead_user_id` int DEFAULT NULL,
      `sort_order` int NOT NULL DEFAULT '0',
      `is_archived` tinyint NOT NULL DEFAULT '0',
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_project_code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($con, $sql)) {
        step('Created table `projects`.');
    } else {
        step('FAILED creating `projects`: ' . mysqli_error($con), false);
    }
}

/* ---------- project_members ---------- */
$exists = mysqli_query($con, "SHOW TABLES LIKE 'project_members'");
if ($exists && mysqli_num_rows($exists) > 0) {
    step('Table `project_members` already present - left as is.');
} else {
    $sql = "CREATE TABLE `project_members` (
      `id` int NOT NULL AUTO_INCREMENT,
      `project_id` int NOT NULL,
      `user_id` int NOT NULL,
      `role` varchar(100) NOT NULL DEFAULT 'Member',
      `added_at` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_project_member` (`project_id`,`user_id`),
      KEY `idx_pm_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (mysqli_query($con, $sql)) {
        step('Created table `project_members`.');
    } else {
        step('FAILED creating `project_members`: ' . mysqli_error($con), false);
    }
}

/* ---------- task.project_id ---------- */
$col = mysqli_query($con, "SHOW COLUMNS FROM `task` LIKE 'project_id'");
if ($col && mysqli_num_rows($col) > 0) {
    step('Column `task`.`project_id` already present - left as is.');
} else {
    if (mysqli_query($con, "ALTER TABLE `task` ADD COLUMN `project_id` int NOT NULL DEFAULT '0'")) {
        mysqli_query($con, "ALTER TABLE `task` ADD INDEX `idx_task_project` (`project_id`)");
        step('Added column `task`.`project_id` (existing tasks stay at 0 = unassigned).');
    } else {
        step('FAILED adding `task`.`project_id`: ' . mysqli_error($con), false);
    }
}

/* ---------- users.rank (repairs the Edit User form) ----------
   user-edit.php reads and writes a `rank` column that was missing from this
   database, which made every "Save changes" fail. The column is added here so
   a fresh install does not reintroduce the fault. */
$col = mysqli_query($con, "SHOW COLUMNS FROM `users` LIKE 'rank'");
if ($col && mysqli_num_rows($col) > 0) {
    step('Column `users`.`rank` already present - left as is.');
} else {
    if (mysqli_query($con, "ALTER TABLE `users` ADD COLUMN `rank` varchar(100) NOT NULL DEFAULT ''")) {
        step('Added column `users`.`rank` - the Edit User form needs it to save.');
    } else {
        step('FAILED adding `users`.`rank`: ' . mysqli_error($con), false);
    }
}

/* ---------- seed the six standing projects ---------- */
$seed = array(
    array('DAGAT2',    'PROJECT DAGAT II',        'Maritime Domain Awareness',      'GC revisions, acoustic system, DND/DOST/PN coordination, next milestones', 'On Track',    1),
    array('NEXUSPRO',  'NEXUS PRO',               'LORA-IOT Solutions',             'Beta rollout, universities, industry partners, CoLab, commercialization',  'On Track',    2),
    array('ALTAIHUB',  'ALTA-iHUB / KIST',        'Innovation & Commercialization', 'Incubation, PEZA requirements, locators, commercialization',               'In Progress', 3),
    array('SPACEPROG', 'SPACE PROGRAM',           'PERPSAT & Beyond',               'PERPSAT / satellite activities and partnerships',                          'On Track',    4),
    array('COE',       'COLLEGE OF ENGINEERING',  'Education & Research',           'Accreditation, faculty, students, laboratories and academic deliverables',  'On Track',    5),
    array('DNDAFP',    'DND / AFP Collaboration', 'Defense & Security',             'Defense R&D, meetings, proposals and partnership actions',                 'Engaged',     6),
);

$added = 0;
$kept  = 0;

foreach ($seed as $p) {
    $chk = mysqli_prepare($con, "SELECT id FROM projects WHERE code = ?");
    mysqli_stmt_bind_param($chk, 's', $p[0]);
    mysqli_stmt_execute($chk);
    $found = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
    if ($found) { $kept++; continue; }

    $ins = mysqli_prepare($con,
        "INSERT INTO projects (code,title,subtitle,tracks,status,progress,sort_order)
         VALUES (?,?,?,?,?,0,?)");
    mysqli_stmt_bind_param($ins, 'sssssi', $p[0], $p[1], $p[2], $p[3], $p[4], $p[5]);
    if (mysqli_stmt_execute($ins)) { $added++; }
}
step("Seeded projects: {$added} added, {$kept} already existed (existing ones untouched).");

/* ---------- upload folder ---------- */
$dir = __DIR__ . '/img/project_imgs';
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
$dirOk = is_dir($dir) && is_writable($dir);
step($dirOk
    ? 'Project image folder ready: img/project_imgs/'
    : 'WARNING: img/project_imgs/ missing or not writable - image uploads will fail.',
    $dirOk);

/* ---------- output ---------- */
if ($cli) {
    foreach ($log as $l) { echo ($l[0] ? '[ ok ] ' : '[FAIL] ') . $l[1] . PHP_EOL; }
    echo PHP_EOL . 'Done. Open command-center.php in the app.' . PHP_EOL;
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Command Center Setup</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow my-5">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Command Center - Setup</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush mb-4">
                        <?php foreach ($log as $l): ?>
                            <li class="list-group-item d-flex align-items-start">
                                <i class="fas <?php echo $l[0] ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-danger'; ?> mr-3 mt-1"></i>
                                <span><?php echo htmlspecialchars($l[1]); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="command-center.php" class="btn btn-primary">
                        <i class="fas fa-th-large mr-2"></i>Open the Command Center
                    </a>
                    <a href="dashboard.php" class="btn btn-link">Back to dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
