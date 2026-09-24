<?php
/**
 * Create a task under a project, with a target date and a point person.
 *
 * Writes to the app's existing `task` and `assigned_to` tables using the same
 * conventions as add-task.php, so tasks made here still appear in the current
 * task pages. The only addition is `task.project_id`, which files the task
 * under its project.
 */

require_once __DIR__ . '/includes/cc/cc_bootstrap.php';

$projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

/* Which projects may this person file a task under? */
$projects = cc_projects($con, false, cc_can_view_all_projects($user) ? null : $user['id']);
if (empty($projects)) {
    cc_flash('There are no projects you can add a task to.', 'warning');
    header('Location: command-center.php');
    exit;
}

/* Default to the project they came from, if they may see it. */
$allowedIds = array();
foreach ($projects as $p) { $allowedIds[] = (int) $p['id']; }
if ($projectId === 0 || !in_array($projectId, $allowedIds, true)) {
    $projectId = $allowedIds[0];
}

$people = cc_selectable_users($con);
$errors = array();

$form = array(
    'project_id'     => $projectId,
    'task_name'      => '',
    'classification' => '',
    'start_date'     => date('Y-m-d'),
    'end_date'       => '',
    'point_user_id'  => '',
    'approver_id'    => '',
    'needs_approval' => 0,
);

/* ------------------------------------------------------------------ *
 * Save
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {

    $form['project_id']     = (int) $_POST['project_id'];
    $form['task_name']      = trim($_POST['task_name']);
    $form['classification'] = trim($_POST['classification']);
    $form['start_date']     = trim($_POST['start_date']);
    $form['end_date']       = trim($_POST['end_date']);
    $form['point_user_id']  = $_POST['point_user_id'] !== '' ? (int) $_POST['point_user_id'] : '';
    $form['approver_id']    = $_POST['approver_id'] !== '' ? (int) $_POST['approver_id'] : '';

    if ($form['task_name'] === '') {
        $errors[] = 'Task name is required.';
    }
    if (!in_array($form['project_id'], $allowedIds, true)) {
        $errors[] = 'Please choose a project you have access to.';
    }
    if (!in_array($form['classification'], cc_classifications(), true)) {
        $errors[] = 'Please choose a classification.';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['start_date'])) {
        $errors[] = 'Start date is required.';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['end_date'])) {
        $errors[] = 'A target date is required - every task needs one.';
    }
    if ($form['start_date'] !== '' && $form['end_date'] !== ''
        && preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['start_date'])
        && preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['end_date'])
        && strtotime($form['end_date']) < strtotime($form['start_date'])) {
        $errors[] = 'The target date cannot fall before the start date.';
    }
    /* The point person and approver must be real, selectable accounts -
       an id typed straight into the form must not create a dangling task. */
    $peopleIds = array();
    foreach ($people as $pp) { $peopleIds[] = (int) $pp['id']; }

    if ($form['point_user_id'] === '') {
        $errors[] = 'Please assign a point person.';
    } elseif (!in_array((int) $form['point_user_id'], $peopleIds, true)) {
        $errors[] = 'That point person is not a valid active account.';
    }

    if ($form['approver_id'] !== ''
        && !in_array((int) $form['approver_id'], $peopleIds, true)) {
        $errors[] = 'That approver is not a valid active account.';
    }

    if (empty($errors)) {

        $creatorId   = (string) $user['id'];
        $creatorName = cc_person_name($user);

        /* Same column set add-task.php uses, plus project_id. */
        /* Every NOT NULL column is listed explicitly. `task_phase_n` has no
           default, so relying on the relaxed sql_mode set in
           sqlconnection.php would make this fragile. */
        $stmt = mysqli_prepare($con,
            "INSERT INTO task
                (task_name, classification, task_start_date, task_end_date,
                 contributor, added_By, added_date_time, project_id,
                 task_status, percent, ref_file, closed_on,
                 task_phase_n, co_id, co_name)
             VALUES (?,?,?,?,?,?,NOW(),?,'open','0','','',0,'','')");
        mysqli_stmt_bind_param($stmt, 'ssssssi',
            $form['task_name'], $form['classification'],
            $form['start_date'], $form['end_date'],
            $creatorId, $creatorName, $form['project_id']);

        if (!mysqli_stmt_execute($stmt)) {
            $errors[] = 'Could not save the task: ' . mysqli_error($con);
        } else {
            $taskId = mysqli_insert_id($con);

            /* Point person + approver. task_id/user_id are varchar here. */
            $pointId    = (string) $form['point_user_id'];
            $approverId = $form['approver_id'] !== '' ? (string) $form['approver_id'] : '';
            $pointClass = '';
            foreach ($people as $pp) {
                if ((int) $pp['id'] === (int) $form['point_user_id']) {
                    $pointClass = $pp['usertype'];
                    break;
                }
            }

            $taskIdStr = (string) $taskId;
            $asg = mysqli_prepare($con,
                "INSERT INTO assigned_to
                    (task_id, user_id, assigning_date, assigned_By,
                     assigned_by_id, user_class, approver_id)
                 VALUES (?,?,NOW(),?,?,?,?)");
            mysqli_stmt_bind_param($asg, 'ssssss',
                $taskIdStr, $pointId, $creatorName, $creatorId,
                $pointClass, $approverId);
            mysqli_stmt_execute($asg);

            /* Notify the point person, same shape as add-task.php. */
            $notifTitle = 'You have been assigned to task ' . $form['task_name'];
            $nt = mysqli_prepare($con,
                "INSERT INTO notifications
                    (notifi_title, notifi_userid, notifi_type, notifi_name,
                     notifi_status, notifi_date, notifi_fromid, task_id,
                     phase_id, phase_name)
                 VALUES (?,?,'assigned',?,'unread',NOW(),?,?,0,'')");
            mysqli_stmt_bind_param($nt, 'sssii',
                $notifTitle, $pointId, $creatorName, $creatorId, $taskId);
            mysqli_stmt_execute($nt);

            /* Notify the approver too, when one was named. */
            if ($approverId !== '' && $approverId !== $pointId) {
                $apTitle = 'You are the approver for task ' . $form['task_name'];
                $na = mysqli_prepare($con,
                    "INSERT INTO notifications
                        (notifi_title, notifi_userid, notifi_type, notifi_name,
                         notifi_status, notifi_date, notifi_fromid, task_id,
                         phase_id, phase_name)
                     VALUES (?,?,'approval',?,'unread',NOW(),?,?,0,'')");
                mysqli_stmt_bind_param($na, 'sssii',
                    $apTitle, $approverId, $creatorName, $creatorId, $taskId);
                mysqli_stmt_execute($na);
            }

            /* Email the people involved. A task must still be created even
               if mail fails, so failures are reported, never fatal. */
            require_once __DIR__ . '/includes/cc/cc_mail.php';

            $taskRow = array(
                'task_name'      => $form['task_name'],
                'classification' => $form['classification'],
                'task_end_date'  => $form['end_date'],
            );

            $projectTitle = '';
            foreach ($projects as $pr) {
                if ((int) $pr['id'] === (int) $form['project_id']) {
                    $projectTitle = $pr['title'];
                    break;
                }
            }

            $pointRow    = null;
            $approverRow = null;
            foreach ($people as $pp) {
                if ((int) $pp['id'] === (int) $form['point_user_id']) { $pointRow = $pp; }
                if ($form['approver_id'] !== ''
                    && (int) $pp['id'] === (int) $form['approver_id']) { $approverRow = $pp; }
            }

            $mailNotes = array();

            if (!cc_mail_available()) {
                $mailNotes[] = 'outgoing mail is not configured, so no email was sent';
            } else {
                if ($pointRow) {
                    list($sent, $err) = cc_mail_task_assigned(
                        $pointRow, $taskRow, $projectTitle, $creatorName);
                    if (!$sent) {
                        $mailNotes[] = 'could not email ' . cc_person_name($pointRow) . ' (' . $err . ')';
                    }
                }
                if ($approverRow && (int) $form['approver_id'] !== (int) $form['point_user_id']) {
                    list($sent, $err) = cc_mail_task_approver(
                        $approverRow, $taskRow, $projectTitle,
                        $pointRow ? cc_person_name($pointRow) : '', $creatorName);
                    if (!$sent) {
                        $mailNotes[] = 'could not email approver ' . cc_person_name($approverRow) . ' (' . $err . ')';
                    }
                }
            }

            cc_log($con, $user, 'Created task: ' . $form['task_name'], 'Command Center');

            if (empty($mailNotes)) {
                cc_flash('Task created, assigned and everyone notified by email.');
            } else {
                cc_flash('Task created and assigned, but ' . implode('; ', $mailNotes)
                       . '. They will still see it in their notifications.', 'warning');
            }

            header('Location: project-view.php?id=' . (int) $form['project_id']);
            exit;
        }
    }
}

$CC_PAGE_TITLE = 'New Task';
$CC_ACTIVE     = 'command-center';
require __DIR__ . '/includes/cc/cc_header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">New Task</h1>
        <p class="mb-0 text-muted small">Filed under a project, with a target date and a point person.</p>
    </div>
    <a href="project-view.php?id=<?php echo (int) $form['project_id']; ?>"
       class="btn btn-sm btn-outline-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm mr-1"></i> Back to project
    </a>
</div>

<?php if (!empty($errors)) { ?>
    <div class="alert alert-danger">
        <ul class="mb-0 pl-3">
            <?php foreach ($errors as $e) { echo '<li>' . cc_e($e) . '</li>'; } ?>
        </ul>
    </div>
<?php } ?>

<form method="post">
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Task Details</h6>
            </div>
            <div class="card-body">

                <div class="form-group">
                    <label class="small font-weight-bold">Project <span class="text-danger">*</span></label>
                    <select name="project_id" class="form-control">
                        <?php foreach ($projects as $p) { ?>
                            <option value="<?php echo (int) $p['id']; ?>"
                                <?php echo (int) $form['project_id'] === (int) $p['id'] ? 'selected' : ''; ?>>
                                <?php echo cc_e($p['title']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="small font-weight-bold">Task name <span class="text-danger">*</span></label>
                    <input type="text" name="task_name" class="form-control"
                           value="<?php echo cc_e($form['task_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="small font-weight-bold">Classification <span class="text-danger">*</span></label>
                    <select name="classification" class="form-control" required>
                        <option value="" disabled <?php echo $form['classification'] === '' ? 'selected' : ''; ?>>
                            Select Classification
                        </option>
                        <?php foreach (cc_classifications() as $c) { ?>
                            <option value="<?php echo cc_e($c); ?>"
                                <?php echo $form['classification'] === $c ? 'selected' : ''; ?>>
                                <?php echo cc_e($c); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="small font-weight-bold">Start date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control"
                               value="<?php echo cc_e($form['start_date']); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="small font-weight-bold">Target date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control"
                               value="<?php echo cc_e($form['end_date']); ?>" required>
                        <small class="form-text text-muted">
                            Drives the countdown on the project page.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Accountability</h6>
            </div>
            <div class="card-body">

                <div class="form-group">
                    <label class="small font-weight-bold">Point person <span class="text-danger">*</span></label>
                    <select name="point_user_id" class="form-control" required>
                        <option value="">&mdash; choose &mdash;</option>
                        <?php foreach ($people as $pp) { ?>
                            <option value="<?php echo (int) $pp['id']; ?>"
                                <?php echo (string) $form['point_user_id'] === (string) $pp['id'] ? 'selected' : ''; ?>>
                                <?php echo cc_e(cc_person_name($pp)); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <small class="form-text text-muted">
                        Gets a notification, and is who the task is tracked against.
                    </small>
                </div>

                <div class="form-group mb-0">
                    <label class="small font-weight-bold">Approver</label>
                    <select name="approver_id" class="form-control">
                        <option value="">&mdash; none &mdash;</option>
                        <?php foreach ($people as $pp) { ?>
                            <option value="<?php echo (int) $pp['id']; ?>"
                                <?php echo (string) $form['approver_id'] === (string) $pp['id'] ? 'selected' : ''; ?>>
                                <?php echo cc_e(cc_person_name($pp)); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <small class="form-text text-muted">
                        Who signs this off. Digital approval and signing come in a later step.
                    </small>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <button type="submit" name="create_task" class="btn btn-primary btn-block">
                    <i class="fas fa-plus fa-sm mr-1"></i> Create task
                </button>
                <a href="project-view.php?id=<?php echo (int) $form['project_id']; ?>"
                   class="btn btn-link btn-block">Cancel</a>
            </div>
        </div>
    </div>
</div>
</form>

<?php require __DIR__ . '/includes/cc/cc_footer.php'; ?>
