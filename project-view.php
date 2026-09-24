<?php
/**
 * Project detail view - opened from a card in the Command Center.
 *
 * Shows the project's status, progress, milestone, team and linked tasks.
 * Visibility follows the same rule as the portfolio: the admin / owner sees
 * any project, everyone else only the ones they lead or belong to.
 */

require_once __DIR__ . '/includes/cc/cc_bootstrap.php';

$id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$project = $id > 0 ? cc_project($con, $id) : null;

if (!$project) {
    cc_flash('That project no longer exists.', 'warning');
    header('Location: command-center.php');
    exit;
}

if (!cc_can_view_project($con, $user, $id)) {
    cc_flash('You do not have access to that project.', 'danger');
    header('Location: command-center.php');
    exit;
}

$canManage = cc_can_manage_projects($user);
$members   = cc_project_members($con, $id);

$lead = null;
if (!empty($project['lead_user_id'])) {
    $ls = mysqli_prepare($con,
        "SELECT id, firstname, lastname, email, position, profilepic FROM users WHERE id = ?");
    $lid = (int) $project['lead_user_id'];
    mysqli_stmt_bind_param($ls, 'i', $lid);
    mysqli_stmt_execute($ls);
    $lead = mysqli_fetch_assoc(mysqli_stmt_get_result($ls));
}

/* Tasks linked to this project, soonest target date first. */
$tasks = cc_project_tasks($con, $id);

$openTasks = 0;
$overdue   = 0;
foreach ($tasks as $t) {
    if (strtolower($t['task_status']) === 'open') {
        $openTasks++;
        list(, , $days) = cc_due_state($t['task_end_date'], $t['task_status']);
        if ($days !== null && $days < 0) { $overdue++; }
    }
}

$banner = trim($project['image']) !== '' && file_exists(__DIR__ . '/' . $project['image'])
    ? "background-image:url('" . cc_e($project['image']) . "')"
    : 'background:' . cc_project_gradient($project);

$CC_PAGE_TITLE = $project['title'];
$CC_ACTIVE     = 'command-center';
require __DIR__ . '/includes/cc/cc_header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800"><?php echo cc_e($project['title']); ?></h1>
        <p class="mb-0 text-muted small"><?php echo cc_e($project['subtitle']); ?></p>
    </div>
    <div>
        <a href="command-center.php" class="btn btn-sm btn-outline-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> Back
        </a>
        <?php if ($canManage) { ?>
            <a href="project-edit.php?id=<?php echo $id; ?>" class="btn btn-sm btn-primary shadow-sm ml-1">
                <i class="fas fa-pen fa-sm mr-1"></i> Edit project
            </a>
        <?php } ?>
    </div>
</div>

<div class="row">

    <!-- ---------------- main column ---------------- -->
    <div class="col-lg-8">

        <div class="card shadow mb-4">
            <div class="cc-card-banner <?php echo trim($project['image']) === '' ? 'cc-empty-banner' : ''; ?>"
                 style="<?php echo $banner; ?>; height:170px;">
                <?php if (trim($project['image']) === '') { ?>
                    <i class="fas fa-diagram-project"></i>
                <?php } ?>
                <span class="cc-status badge badge-<?php echo cc_status_class($project['status']); ?>">
                    <?php echo cc_e($project['status']); ?>
                </span>
                <span class="cc-code"><?php echo cc_e($project['code']); ?></span>
            </div>

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-xs font-weight-bold text-gray-600">Overall Progress</span>
                    <span class="text-xs font-weight-bold text-gray-800"><?php echo (int) $project['progress']; ?>%</span>
                </div>
                <div class="progress cc-progress mb-4">
                    <div class="progress-bar bg-<?php echo cc_status_class($project['status']); ?>"
                         role="progressbar" style="width: <?php echo (int) $project['progress']; ?>%"></div>
                </div>

                <h6 class="text-xs font-weight-bold text-uppercase text-gray-600 mb-2">What this project tracks</h6>
                <p class="text-gray-800">
                    <?php echo trim($project['tracks']) !== ''
                        ? nl2br(cc_e($project['tracks']))
                        : '<span class="text-muted">Not set yet.</span>'; ?>
                </p>

                <?php if (trim($project['description']) !== '') { ?>
                    <hr>
                    <h6 class="text-xs font-weight-bold text-uppercase text-gray-600 mb-2">Description</h6>
                    <p class="text-gray-800 mb-0"><?php echo nl2br(cc_e($project['description'])); ?></p>
                <?php } ?>
            </div>
        </div>

        <!-- linked tasks -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Tasks</h6>
                <div class="d-flex align-items-center">
                    <span class="small text-muted mr-3">
                        <?php echo count($tasks); ?> total
                        <?php if (count($tasks)) { ?>
                            &middot; <?php echo $openTasks; ?> open
                            <?php if ($overdue > 0) { ?>
                                &middot; <span class="text-danger font-weight-bold"><?php echo $overdue; ?> overdue</span>
                            <?php } ?>
                        <?php } ?>
                    </span>
                    <a href="task-new.php?project_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus fa-sm mr-1"></i> Add task
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($tasks)) { ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300 mb-3"></i>
                        <p class="text-muted mb-3">No tasks linked to this project yet.</p>
                        <a href="task-new.php?project_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus fa-sm mr-1"></i> Add the first task
                        </a>
                    </div>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Task</th>
                                    <th>Point person</th>
                                    <th>Target date</th>
                                    <th>Status</th>
                                    <th class="text-right">Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($tasks as $t):
                                list($dueLabel, $dueClass, $dueDays) = cc_due_state($t['task_end_date'], $t['task_status']);
                                $pointName = trim($t['point_firstname'] . ' ' . $t['point_lastname']);
                            ?>
                                <tr>
                                    <td class="small">
                                        <a href="task-view.php?id=<?php echo (int) $t['id_task']; ?>">
                                            <?php echo cc_e($t['task_name']); ?>
                                        </a>
                                        <?php if (trim($t['classification']) !== '') { ?>
                                            <div class="text-muted" style="font-size:.72rem;">
                                                <?php echo cc_e($t['classification']); ?>
                                            </div>
                                        <?php } ?>
                                    </td>
                                    <td class="small">
                                        <?php if ($pointName !== '') { ?>
                                            <?php echo cc_e($pointName); ?>
                                            <?php if (!empty($t['approver_firstname'])) { ?>
                                                <div class="text-muted" style="font-size:.72rem;">
                                                    approver: <?php echo cc_e(trim($t['approver_firstname'] . ' ' . $t['approver_lastname'])); ?>
                                                </div>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php } ?>
                                    </td>
                                    <td class="small">
                                        <?php echo trim($t['task_end_date']) !== ''
                                            ? date('M j, Y', strtotime($t['task_end_date']))
                                            : '<span class="text-muted">-</span>'; ?>
                                        <div>
                                            <span class="badge badge-<?php echo $dueClass; ?>"
                                                  style="font-size:.66rem;"><?php echo cc_e($dueLabel); ?></span>
                                        </div>
                                    </td>
                                    <td class="small">
                                        <span class="badge badge-<?php echo strtolower($t['task_status']) === 'open' ? 'warning' : 'success'; ?>">
                                            <?php echo cc_e($t['task_status']); ?>
                                        </span>
                                    </td>
                                    <td class="small text-right"><?php echo (int) $t['percent']; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- ---------------- side column ---------------- -->
    <div class="col-lg-4">

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Next Milestone</h6>
            </div>
            <div class="card-body">
                <?php if (trim($project['next_milestone']) !== '') { ?>
                    <div class="font-weight-bold text-gray-800"><?php echo cc_e($project['next_milestone']); ?></div>
                    <?php if (!empty($project['next_milestone_date'])) {
                        $due  = strtotime($project['next_milestone_date']);
                        $days = (int) floor(($due - strtotime(date('Y-m-d'))) / 86400);
                    ?>
                        <div class="small text-muted mt-1"><?php echo date('F j, Y', $due); ?></div>
                        <div class="mt-2">
                            <?php if ($days < 0) { ?>
                                <span class="badge badge-danger"><?php echo abs($days); ?> day(s) overdue</span>
                            <?php } elseif ($days === 0) { ?>
                                <span class="badge badge-warning">Due today</span>
                            <?php } else { ?>
                                <span class="badge badge-info"><?php echo $days; ?> day(s) remaining</span>
                            <?php } ?>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p class="text-muted small mb-0">No milestone set.</p>
                <?php } ?>
            </div>
        </div>

        <?php if ($lead) { ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Project Lead</h6>
            </div>
            <div class="card-body d-flex align-items-center">
                <span class="cc-avatar mr-3" style="width:40px;height:40px;font-size:.85rem;">
                    <?php echo cc_e(cc_initials($lead)); ?>
                </span>
                <div>
                    <div class="font-weight-bold text-gray-800 small"><?php echo cc_e(cc_person_name($lead)); ?></div>
                    <?php if (trim($lead['position']) !== '') { ?>
                        <div class="text-muted" style="font-size:.72rem;"><?php echo cc_e($lead['position']); ?></div>
                    <?php } ?>
                    <a class="small" href="mailto:<?php echo cc_e($lead['email']); ?>"><?php echo cc_e($lead['email']); ?></a>
                </div>
            </div>
        </div>
        <?php } ?>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Team</h6>
                <span class="small text-muted"><?php echo count($members); ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($members)) { ?>
                    <p class="text-muted small mb-0">
                        No members assigned yet.
                        <?php if ($canManage) { ?>
                            <a href="project-edit.php?id=<?php echo $id; ?>">Add some</a>.
                        <?php } ?>
                    </p>
                <?php } else { ?>
                    <?php foreach ($members as $m): ?>
                        <div class="d-flex align-items-center mb-3">
                            <span class="cc-avatar mr-3" style="width:36px;height:36px;font-size:.78rem;">
                                <?php echo cc_e(cc_initials($m)); ?>
                            </span>
                            <div class="flex-fill">
                                <div class="small font-weight-bold text-gray-800">
                                    <?php echo cc_e(cc_person_name($m)); ?>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;">
                                    <?php echo cc_e($m['role']); ?>
                                </div>
                                <a class="small" href="mailto:<?php echo cc_e($m['email']); ?>">
                                    <?php echo cc_e($m['email']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php
                    $emails = array();
                    foreach ($members as $m) {
                        if (trim($m['email']) !== '') { $emails[] = $m['email']; }
                    }
                    if (!empty($emails)) { ?>
                        <hr>
                        <a class="btn btn-sm btn-outline-primary btn-block"
                           href="mailto:<?php echo cc_e(implode(',', $emails)); ?>?subject=<?php echo rawurlencode($project['title']); ?>">
                            <i class="fas fa-envelope fa-sm mr-1"></i> Email the whole team
                        </a>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body small text-muted">
                <div class="d-flex justify-content-between mb-1">
                    <span>Code</span><span class="text-gray-800"><?php echo cc_e($project['code']); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Created</span>
                    <span class="text-gray-800">
                        <?php echo !empty($project['created_at']) ? date('M j, Y', strtotime($project['created_at'])) : '-'; ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Last updated</span>
                    <span class="text-gray-800">
                        <?php echo !empty($project['updated_at']) ? date('M j, Y', strtotime($project['updated_at'])) : '-'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/cc/cc_footer.php'; ?>
