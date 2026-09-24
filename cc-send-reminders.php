<?php
/**
 * Email reminders for tasks that are overdue or due soon.
 *
 * Command line only - it is never served over the web, so it cannot be
 * triggered by a visitor. Schedule it once a day with Windows Task Scheduler:
 *
 *     C:\php-8.3.6\php.exe C:\Users\COE-LAB\Downloads\rms2-main\cc-send-reminders.php
 *
 * Options:
 *     --days=N    how far ahead counts as "due soon" (default 3)
 *     --dry-run   report what would be sent, send nothing
 *
 * One email per person, listing all of their tasks that need attention,
 * rather than one email per task.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("This script runs from the command line only.\n");
}

chdir(__DIR__);

require __DIR__ . '/includes/database/sqlconnection.php';
require __DIR__ . '/includes/cc/cc_mail.php';

/* cc_due_state() lives in the bootstrap, which expects a web session, so the
   two helpers this script needs are defined locally instead. */
if (!function_exists('cc_due_state')) {
    function cc_due_state($endDate, $status = 'open')
    {
        $endDate = trim((string) $endDate);
        if ($endDate === '' || $endDate === '0000-00-00') {
            return array('No target date', 'secondary', null);
        }
        $due = strtotime($endDate);
        if ($due === false) { return array('No target date', 'secondary', null); }
        if (strtolower($status) !== 'open') { return array('Closed', 'success', null); }

        $days = (int) floor(($due - strtotime(date('Y-m-d'))) / 86400);
        if ($days < 0)   { return array(abs($days) . ' day(s) overdue', 'danger', $days); }
        if ($days === 0) { return array('Due today', 'warning', 0); }
        return array($days . ' day(s) left', $days <= 3 ? 'warning' : 'info', $days);
    }
}

/* ---- arguments ---- */
$days   = 3;
$dryRun = false;
foreach ($argv as $arg) {
    if (preg_match('/^--days=(\d+)$/', $arg, $m)) { $days = (int) $m[1]; }
    if ($arg === '--dry-run') { $dryRun = true; }
}

date_default_timezone_set('Asia/Manila');
$stamp = date('Y-m-d H:i:s');

echo "RMS reminder run - {$stamp}\n";
echo "  window : tasks due within {$days} day(s), plus anything overdue\n";
echo "  mode   : " . ($dryRun ? 'DRY RUN, nothing will be sent' : 'sending') . "\n";

if (!cc_mail_available()) {
    exit("  ABORT: outgoing mail is not configured in db-config.local.php\n");
}

/* ---- gather ---- */
$sql = "SELECT t.id_task, t.task_name, t.task_end_date, t.task_status, t.percent,
               p.title AS project_title,
               u.id AS uid, u.firstname, u.lastname, u.email
        FROM task t
        INNER JOIN assigned_to a ON a.task_id = t.id_task
        INNER JOIN users u ON u.id = a.user_id
        LEFT JOIN projects p ON p.id = t.project_id
        WHERE LOWER(t.task_status) = 'open'
          AND t.task_end_date <> ''
          AND DATEDIFF(t.task_end_date, CURDATE()) <= ?
          AND u.email <> ''
        GROUP BY t.id_task, u.id
        ORDER BY u.id, t.task_end_date ASC";

$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, 'i', $days);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$byPerson = array();
while ($row = mysqli_fetch_assoc($res)) {
    $uid = (int) $row['uid'];
    if (!isset($byPerson[$uid])) {
        $byPerson[$uid] = array(
            'person' => array(
                'id'        => $uid,
                'firstname' => $row['firstname'],
                'lastname'  => $row['lastname'],
                'email'     => $row['email'],
            ),
            'tasks' => array(),
        );
    }
    $row['project_title'] = $row['project_title'] !== null ? $row['project_title'] : 'No project';
    $byPerson[$uid]['tasks'][] = $row;
}

if (empty($byPerson)) {
    echo "  nothing to remind anyone about. Done.\n";
    exit(0);
}

/* ---- send ---- */
$sentCount = 0;
$failCount = 0;

foreach ($byPerson as $entry) {
    $person = $entry['person'];
    $tasks  = $entry['tasks'];
    $name   = trim($person['firstname'] . ' ' . $person['lastname']);

    printf("  %-22s %d task(s) -> %s\n", $name, count($tasks), $person['email']);
    foreach ($tasks as $t) {
        list($label, , ) = cc_due_state($t['task_end_date'], $t['task_status']);
        printf("      - %-34s %s  (%s)\n",
            substr($t['task_name'], 0, 34), $t['task_end_date'], $label);
    }

    if ($dryRun) { continue; }

    list($sent, $err) = cc_mail_reminder($person, $tasks);
    if ($sent) {
        $sentCount++;
        echo "      sent\n";
    } else {
        $failCount++;
        echo "      FAILED: {$err}\n";
    }
}

echo "\n  people notified : {$sentCount}\n";
echo "  failures        : {$failCount}\n";
echo "Done.\n";

exit($failCount > 0 ? 1 : 0);
