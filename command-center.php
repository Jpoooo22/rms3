<?php
/**
 * Command Center - project portfolio overview.
 *
 * Step 1: the six standing projects as editable cards. Task, calendar,
 * evidence and approval features hang off these in the later steps.
 */

require_once __DIR__ . '/includes/cc/cc_bootstrap.php';

$showArchived = isset($_GET['archived']) && $_GET['archived'] === '1';
$canManage    = cc_can_manage_projects($user);
$seesAll      = cc_can_view_all_projects($user);

/* Only the admin / owner sees the full portfolio. Everyone else sees the
   projects they lead or are a member of. */
$projects = cc_projects($con, $showArchived, $seesAll ? null : $user['id']);

/* Member list per project, so the cards can show who is on each one. */
$membersByProject = array();
foreach ($projects as $p) {
    $membersByProject[$p['id']] = cc_project_members($con, $p['id']);
}

/* Task counts per project - 0 for everything until Step 2 links tasks up. */
$taskCounts = array();
$hasProjectCol = mysqli_query($con, "SHOW COLUMNS FROM `task` LIKE 'project_id'");
if ($hasProjectCol && mysqli_num_rows($hasProjectCol) > 0) {
    $res = mysqli_query($con,
        "SELECT project_id,
                COUNT(*) AS total,
                SUM(CASE WHEN task_status = 'open' THEN 1 ELSE 0 END) AS open_count
         FROM task
         WHERE project_id > 0
         GROUP BY project_id");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $taskCounts[(int) $row['project_id']] = $row;
        }
    }
}

$CC_PAGE_TITLE = 'Command Center';
$CC_ACTIVE     = 'command-center';
require __DIR__ . '/includes/cc/cc_header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Command Center</h1>
        <p class="mb-0 text-muted small">
            <?php echo $seesAll
                ? 'Every project in one place &mdash; status, progress and the team behind it.'
                : 'The projects you lead or are assigned to.'; ?>
        </p>
    </div>
    <div>
        <?php if ($showArchived) { ?>
            <a href="command-center.php" class="btn btn-sm btn-outline-secondary shadow-sm">
                <i class="fas fa-eye-slash fa-sm mr-1"></i> Hide archived
            </a>
        <?php } else { ?>
            <a href="command-center.php?archived=1" class="btn btn-sm btn-outline-secondary shadow-sm">
                <i class="fas fa-archive fa-sm mr-1"></i> Show archived
            </a>
        <?php } ?>
        <?php if ($canManage) { ?>
            <a href="project-edit.php" class="btn btn-sm btn-primary shadow-sm ml-1">
                <i class="fas fa-plus fa-sm mr-1"></i> New project
            </a>
        <?php } ?>
    </div>
</div>

<?php
/* ---- portfolio summary strip ---- */
$totalProjects = count($projects);
$avgProgress   = 0;
$atRisk        = 0;
foreach ($projects as $p) {
    $avgProgress += (int) $p['progress'];
    if (in_array($p['status'], array('At Risk', 'On Hold'), true)) { $atRisk++; }
}
$avgProgress = $totalProjects > 0 ? round($avgProgress / $totalProjects) : 0;

$totalMembers = 0;
$seenMembers  = array();
foreach ($membersByProject as $ms) {
    foreach ($ms as $m) { $seenMembers[$m['user_id']] = true; }
}
$totalMembers = count($seenMembers);
?>

<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active Projects</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalProjects; ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Average Progress</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $avgProgress; ?>%</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Needs Attention</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $atRisk; ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">People Assigned</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalMembers; ?></div>
            </div>
        </div>
    </div>
</div>

<h6 class="text-gray-700 font-weight-bold mb-3">Project Portfolio Overview</h6>

<?php if (empty($projects)) { ?>
    <div class="card shadow">
        <div class="card-body text-center py-5">
            <i class="fas fa-folder-open fa-2x text-gray-300 mb-3"></i>
            <?php if ($canManage) { ?>
                <p class="text-muted mb-3">
                    No projects yet. Run the installer once to create the six standing projects.
                </p>
                <a href="install-command-center.php" class="btn btn-primary btn-sm">Run Command Center setup</a>
            <?php } else { ?>
                <p class="text-muted mb-0">
                    You are not assigned to any project yet.<br>
                    Ask the administrator to add you to a project team.
                </p>
            <?php } ?>
        </div>
    </div>
<?php } else { ?>

<div class="row">
    <?php foreach ($projects as $p):
        $members = $membersByProject[$p['id']];
        $counts  = isset($taskCounts[(int) $p['id']]) ? $taskCounts[(int) $p['id']] : null;
        $banner  = trim($p['image']) !== '' && file_exists(__DIR__ . '/' . $p['image'])
            ? "background-image:url('" . cc_e($p['image']) . "')"
            : 'background:' . cc_project_gradient($p);
    ?>
    <div class="col-xl-4 col-lg-6 mb-4">
        <div class="card cc-card shadow">

            <div class="cc-card-banner <?php echo trim($p['image']) === '' ? 'cc-empty-banner' : ''; ?>"
                 style="<?php echo $banner; ?>">
                <?php if (trim($p['image']) === '') { ?>
                    <i class="fas fa-diagram-project"></i>
                <?php } ?>
                <span class="cc-status badge badge-<?php echo cc_status_class($p['status']); ?>">
                    <?php echo cc_e($p['status']); ?>
                </span>
                <span class="cc-code"><?php echo cc_e($p['code']); ?></span>
            </div>

            <div class="card-body d-flex flex-column">

                <h6 class="font-weight-bold text-gray-800 mb-1"><?php echo cc_e($p['title']); ?></h6>
                <div class="cc-meta mb-2"><?php echo cc_e($p['subtitle']); ?></div>

                <p class="cc-tracks mb-3"><?php echo cc_e($p['tracks']); ?></p>

                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-xs font-weight-bold text-gray-600">Progress</span>
                    <span class="text-xs font-weight-bold text-gray-800"><?php echo (int) $p['progress']; ?>%</span>
                </div>
                <div class="progress cc-progress mb-3">
                    <div class="progress-bar bg-<?php echo cc_status_class($p['status']); ?>"
                         role="progressbar"
                         style="width: <?php echo (int) $p['progress']; ?>%"
                         aria-valuenow="<?php echo (int) $p['progress']; ?>"
                         aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <div class="mb-3">
                    <div class="cc-meta text-uppercase font-weight-bold mb-1">Next Milestone</div>
                    <div class="small text-gray-800">
                        <?php echo trim($p['next_milestone']) !== '' ? cc_e($p['next_milestone']) : '<span class="text-muted">Not set</span>'; ?>
                    </div>
                    <?php if (!empty($p['next_milestone_date'])) { ?>
                        <div class="cc-meta"><?php echo date('M j, Y', strtotime($p['next_milestone_date'])); ?></div>
                    <?php } ?>
                </div>

                <div class="mt-auto">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <?php if (empty($members)) { ?>
                                <span class="cc-meta">No members yet</span>
                            <?php } else {
                                foreach (array_slice($members, 0, 4) as $m) { ?>
                                    <span class="cc-avatar" title="<?php echo cc_e(cc_person_name($m) . ' - ' . $m['email']); ?>">
                                        <?php echo cc_e(cc_initials($m)); ?>
                                    </span>
                                <?php }
                                if (count($members) > 4) { ?>
                                    <span class="cc-avatar cc-more">+<?php echo count($members) - 4; ?></span>
                                <?php }
                            } ?>
                        </div>
                        <span class="cc-meta">
                            <?php if ($counts) {
                                echo (int) $counts['open_count'] . ' open / ' . (int) $counts['total'] . ' tasks';
                            } else {
                                echo 'No tasks linked yet';
                            } ?>
                        </span>
                    </div>

                    <div class="d-flex">
                        <a href="project-view.php?id=<?php echo (int) $p['id']; ?>"
                           class="btn btn-sm btn-primary flex-fill mr-1">
                            <i class="fas fa-folder-open fa-sm mr-1"></i> Open
                        </a>
                        <?php if ($canManage) { ?>
                            <a href="project-edit.php?id=<?php echo (int) $p['id']; ?>"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-pen fa-sm"></i>
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <?php if ((int) $p['is_archived'] === 1) { ?>
                    <div class="mt-2 text-center">
                        <span class="badge badge-secondary">Archived</span>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php } ?>

<?php require __DIR__ . '/includes/cc/cc_footer.php'; ?>
