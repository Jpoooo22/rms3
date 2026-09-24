<?php
/**
 * Command Center bootstrap.
 *
 * Every Command Center page starts with:
 *     require_once __DIR__ . '/includes/cc/cc_bootstrap.php';
 *
 * It opens the session, connects to the database, loads the signed-in user
 * into $user, and provides the cc_* helpers used by the project pages.
 * Nothing here touches the existing RMS pages.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$CC_ROOT = dirname(dirname(__DIR__));           // application root

require_once $CC_ROOT . '/includes/database/helper.php';

if (!isset($_SESSION['id'])) {
    header('Location: default.php');
    exit;                                        // access_dashboard.php forgets this exit
}

require_once $CC_ROOT . '/includes/database/sqlconnection.php';

$user = get_user_info($con, $_SESSION['id']);
if (!$user) {
    session_destroy();
    header('Location: default.php');
    exit;
}

/* get_user_info() does not select `position` or `approval`, and the Dean is
   identified by position, so load them here rather than editing the helper
   (which is obfuscated). */
$cc_extra_q = mysqli_prepare($con, 'SELECT position, approval FROM users WHERE id = ?');
$cc_extra_id = (int) $_SESSION['id'];
mysqli_stmt_bind_param($cc_extra_q, 'i', $cc_extra_id);
mysqli_stmt_execute($cc_extra_q);
$cc_extra = mysqli_fetch_assoc(mysqli_stmt_get_result($cc_extra_q));

$user['position'] = $cc_extra && isset($cc_extra['position']) ? $cc_extra['position'] : '';
$user['approval'] = $cc_extra && isset($cc_extra['approval']) ? $cc_extra['approval'] : '';

date_default_timezone_set('Asia/Manila');

/* ------------------------------------------------------------------ *
 * Helpers
 * ------------------------------------------------------------------ */

/** Escape for HTML output. */
function cc_e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/**
 * Who may create, edit and archive projects, and add people to them.
 *
 * Only the Dean and the administrator. In this app the "Dean" user
 * classification is stored as usertype 'Admin' (the Add User form labels
 * 'Admin' as "Dean"), and older accounts used the numeric 7. The separate
 * `position` field is also honoured so an account explicitly holding the
 * Dean post qualifies regardless of how its usertype was set.
 *
 * Department Chairpersons ('Officer') and Faculty/Staff ('User') do not
 * qualify - they only see the projects they lead or belong to.
 */
function cc_can_manage_projects($user)
{
    $type = isset($user['usertype']) ? trim((string) $user['usertype']) : '';
    if (in_array($type, array('Admin', '7'), true)) {
        return true;
    }

    $position = isset($user['position']) ? trim((string) $user['position']) : '';
    return strcasecmp($position, 'Dean') === 0;
}

/**
 * Only the admin / owner sees the whole portfolio.
 * Everyone else sees just the projects they lead or are a member of.
 */
function cc_can_view_all_projects($user)
{
    return cc_can_manage_projects($user);
}

/**
 * May this user open this specific project?
 * Admin/owner: always. Everyone else: only their own projects.
 */
function cc_can_view_project($con, $user, $projectId)
{
    if (cc_can_view_all_projects($user)) {
        return true;
    }

    $stmt = mysqli_prepare($con,
        "SELECT p.id
         FROM projects p
         LEFT JOIN project_members pm
                ON pm.project_id = p.id AND pm.user_id = ?
         WHERE p.id = ? AND (pm.user_id IS NOT NULL OR p.lead_user_id = ?)");

    $uid = (int) $user['id'];
    $pid = (int) $projectId;
    mysqli_stmt_bind_param($stmt, 'iii', $uid, $pid, $uid);
    mysqli_stmt_execute($stmt);

    return (bool) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

/** The statuses a project card can show, mapped to a Bootstrap colour. */
function cc_status_list()
{
    return array(
        'On Track'    => 'success',
        'In Progress' => 'warning',
        'Engaged'     => 'info',
        'At Risk'     => 'danger',
        'On Hold'     => 'secondary',
        'Completed'   => 'primary',
    );
}

function cc_status_class($status)
{
    $list = cc_status_list();
    return isset($list[$status]) ? $list[$status] : 'secondary';
}

/**
 * A stable background for projects that have no uploaded image yet,
 * so the portfolio still reads as a set of distinct cards.
 */
function cc_project_gradient($project)
{
    $palette = array(
        array('#1e3c72', '#2a5298'),
        array('#134e5e', '#71b280'),
        array('#42275a', '#734b6d'),
        array('#0f2027', '#2c5364'),
        array('#6d071a', '#a8324a'),
        array('#232526', '#414345'),
    );
    $i = abs((int) $project['id']) % count($palette);
    return 'linear-gradient(135deg, ' . $palette[$i][0] . ' 0%, ' . $palette[$i][1] . ' 100%)';
}

/**
 * Projects for the portfolio, ordered for display.
 *
 * Pass $scopeUserId to limit the list to the projects that user leads or is a
 * member of. Pass null (admin / owner) to return the whole portfolio.
 */
function cc_projects($con, $includeArchived = false, $scopeUserId = null)
{
    $conds = array();
    if (!$includeArchived) { $conds[] = 'p.is_archived = 0'; }

    if ($scopeUserId === null) {
        $sql = "SELECT p.* FROM projects p";
    } else {
        $sql = "SELECT DISTINCT p.*
                FROM projects p
                LEFT JOIN project_members pm
                       ON pm.project_id = p.id AND pm.user_id = ?";
        $conds[] = '(pm.user_id IS NOT NULL OR p.lead_user_id = ?)';
    }

    if (!empty($conds)) { $sql .= ' WHERE ' . implode(' AND ', $conds); }
    $sql .= ' ORDER BY p.sort_order ASC, p.id ASC';

    $stmt = mysqli_prepare($con, $sql);
    if ($scopeUserId !== null) {
        $uid = (int) $scopeUserId;
        mysqli_stmt_bind_param($stmt, 'ii', $uid, $uid);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $out = array();
    while ($row = mysqli_fetch_assoc($res)) { $out[] = $row; }
    return $out;
}

/** One project by id, or null. */
function cc_project($con, $id)
{
    $stmt = mysqli_prepare($con, "SELECT * FROM projects WHERE id = ?");
    $id   = (int) $id;
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $row ? $row : null;
}

/** Team members of a project, with the email each one is reachable at. */
function cc_project_members($con, $projectId)
{
    $sql = "SELECT pm.id AS membership_id, pm.role, pm.user_id,
                   u.firstname, u.lastname, u.email, u.position, u.profilepic
            FROM project_members pm
            JOIN users u ON u.id = pm.user_id
            WHERE pm.project_id = ?
            ORDER BY u.lastname ASC, u.firstname ASC";
    $stmt      = mysqli_prepare($con, $sql);
    $projectId = (int) $projectId;
    mysqli_stmt_bind_param($stmt, 'i', $projectId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $out = array();
    while ($row = mysqli_fetch_assoc($res)) { $out[] = $row; }
    return $out;
}

/** Everyone who can be added to a project. */
function cc_selectable_users($con)
{
    $res = mysqli_query($con,
        "SELECT id, firstname, lastname, email, position, usertype
         FROM users
         WHERE approval = 'verified'
         ORDER BY lastname ASC, firstname ASC");

    $out = array();
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) { $out[] = $row; }
    }
    return $out;
}

/** Display name for a user row. */
function cc_person_name($row)
{
    $name = trim($row['firstname'] . ' ' . $row['lastname']);
    return $name !== '' ? $name : 'Unnamed user';
}

/** Initials used for the small member avatars. */
function cc_initials($row)
{
    $f = isset($row['firstname'][0]) ? strtoupper($row['firstname'][0]) : '';
    $l = isset($row['lastname'][0]) ? strtoupper($row['lastname'][0]) : '';
    $i = $f . $l;
    return $i !== '' ? $i : '?';
}

/**
 * Store an uploaded project image under img/project_imgs/.
 * Returns array(path, error) - path is '' when nothing was uploaded.
 */
function cc_upload_project_image($file)
{
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('', '');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return array('', 'The image failed to upload. Please try again.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return array('', 'Image is larger than 5 MB.');
    }

    $allowed = array(
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    );

    $info = @getimagesize($file['tmp_name']);
    if ($info === false || !isset($allowed[$info[2]])) {
        return array('', 'Only JPG, PNG, GIF or WEBP images are accepted.');
    }

    $dir = dirname(dirname(__DIR__)) . '/img/project_imgs';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return array('', 'Could not create img/project_imgs/.');
    }

    $name = 'project_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return array('', 'Could not save the uploaded image.');
    }

    return array('img/project_imgs/' . $name, '');
}

/**
 * Task classifications, matching the options on the existing Add Task form.
 */
function cc_classifications()
{
    return array('Administrative', 'Creative', 'Technical', 'Strategic', 'Research');
}

/**
 * Tasks belonging to a project, with the assigned point person and approver
 * resolved from `assigned_to`.
 *
 * `assigned_to.task_id` and `user_id` are varchar in this schema, so the join
 * is written to tolerate that rather than assuming integers.
 */
function cc_project_tasks($con, $projectId)
{
    $sql = "SELECT t.id_task, t.task_name, t.classification,
                   t.task_start_date, t.task_end_date,
                   t.task_status, t.percent, t.added_date_time,
                   a.user_id      AS point_user_id,
                   a.approver_id  AS approver_user_id,
                   pu.firstname   AS point_firstname,
                   pu.lastname    AS point_lastname,
                   pu.email       AS point_email,
                   au.firstname   AS approver_firstname,
                   au.lastname    AS approver_lastname
            FROM task t
            LEFT JOIN assigned_to a ON a.task_id = t.id_task
            LEFT JOIN users pu      ON pu.id = a.user_id
            LEFT JOIN users au      ON au.id = a.approver_id
            WHERE t.project_id = ?
            GROUP BY t.id_task
            ORDER BY (t.task_end_date = '' OR t.task_end_date IS NULL),
                     t.task_end_date ASC, t.id_task DESC";

    $stmt = mysqli_prepare($con, $sql);
    $pid  = (int) $projectId;
    mysqli_stmt_bind_param($stmt, 'i', $pid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $out = array();
    while ($row = mysqli_fetch_assoc($res)) { $out[] = $row; }
    return $out;
}

/**
 * How a task's target date stands relative to today.
 * Returns array(label, bootstrap class, days) - days is null when no date.
 */
function cc_due_state($endDate, $status = 'open')
{
    $endDate = trim((string) $endDate);
    if ($endDate === '' || $endDate === '0000-00-00') {
        return array('No target date', 'secondary', null);
    }

    $due = strtotime($endDate);
    if ($due === false) {
        return array('No target date', 'secondary', null);
    }

    if (strtolower($status) !== 'open') {
        return array('Closed', 'success', null);
    }

    $days = (int) floor(($due - strtotime(date('Y-m-d'))) / 86400);

    if ($days < 0)  { return array(abs($days) . 'd overdue', 'danger', $days); }
    if ($days === 0) { return array('Due today', 'warning', 0); }
    if ($days <= 3)  { return array($days . 'd left', 'warning', $days); }

    return array($days . 'd left', 'info', $days);
}

/** Write to the existing activity log so project edits show in System Logs. */
function cc_log($con, $user, $action, $page)
{
    $stmt = mysqli_prepare($con,
        "INSERT INTO log_activities (user_id, user_name, ip, action_name, action_page, action_date)
         VALUES (?,?,?,?,?,?)");

    $uid  = (string) $user['id'];
    $name = cc_person_name($user);
    $ip   = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $date = date('Y-m-d H:i:s');

    mysqli_stmt_bind_param($stmt, 'ssssss', $uid, $name, $ip, $action, $page, $date);
    mysqli_stmt_execute($stmt);
}

/** One-shot flash message between redirects. */
function cc_flash($msg = null, $type = 'success')
{
    if ($msg !== null) {
        $_SESSION['cc_flash'] = array('msg' => $msg, 'type' => $type);
        return null;
    }
    if (!isset($_SESSION['cc_flash'])) { return null; }

    $flash = $_SESSION['cc_flash'];
    unset($_SESSION['cc_flash']);
    return $flash;
}
