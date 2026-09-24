<?php
/**
 * Command Center calendar - every task target date in one view.
 *
 * Draws two sources: the project task deadlines from cc-events.php, and the
 * app's existing personal events from get-events.php, so nothing that was
 * already on the calendar disappears.
 */

require_once __DIR__ . '/includes/cc/cc_bootstrap.php';

$seesAll = cc_can_view_all_projects($user);
$uid     = (int) $user['id'];

/* Upcoming and overdue lists beside the calendar, same scope as the feed. */
$sql = "SELECT t.id_task, t.task_name, t.task_end_date, t.task_status, t.percent,
               p.id AS project_id, p.title AS project_title, p.code AS project_code,
               pu.firstname AS point_firstname, pu.lastname AS point_lastname
        FROM task t
        INNER JOIN projects p ON p.id = t.project_id
        LEFT JOIN assigned_to a ON a.task_id = t.id_task
        LEFT JOIN users pu ON pu.id = a.user_id
        LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        WHERE t.project_id > 0
          AND t.task_end_date <> ''
          AND LOWER(t.task_status) = 'open'
          AND (? = 1
               OR a.user_id = ?
               OR a.approver_id = ?
               OR pm.user_id IS NOT NULL
               OR p.lead_user_id = ?)
        GROUP BY t.id_task
        ORDER BY t.task_end_date ASC";

$stmt     = mysqli_prepare($con, $sql);
$seesAllI = $seesAll ? 1 : 0;
mysqli_stmt_bind_param($stmt, 'iiiii', $uid, $seesAllI, $uid, $uid, $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$overdue  = array();
$dueSoon  = array();
$later    = array();

while ($row = mysqli_fetch_assoc($res)) {
    list($label, $class, $days) = cc_due_state($row['task_end_date'], $row['task_status']);
    $row['due_label'] = $label;
    $row['due_class'] = $class;

    if ($days === null)      { $later[]   = $row; }
    elseif ($days < 0)       { $overdue[] = $row; }
    elseif ($days <= 7)      { $dueSoon[] = $row; }
    else                     { $later[]   = $row; }
}

$CC_PAGE_TITLE = 'Calendar';
$CC_ACTIVE     = 'calendar';
require __DIR__ . '/includes/cc/cc_header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Calendar</h1>
        <p class="mb-0 text-muted small">
            <?php echo $seesAll
                ? 'Every task target date across all projects.'
                : 'Target dates for your tasks and projects.'; ?>
        </p>
    </div>
    <a href="command-center.php" class="btn btn-sm btn-outline-secondary shadow-sm">
        <i class="fas fa-th-large fa-sm mr-1"></i> Command Center
    </a>
</div>

<div class="row">
    <div class="col-xl-9 mb-4">
        <div class="card shadow">
            <div class="card-body">
                <div id="ccCalendar"></div>
                <div class="mt-3 small text-muted">
                    <span class="badge" style="background:#e74a3b;color:#fff;">Overdue</span>
                    <span class="badge ml-1" style="background:#f6c23e;color:#000;">Due within 3 days</span>
                    <span class="badge ml-1" style="background:#36b9cc;color:#fff;">Upcoming</span>
                    <span class="badge ml-1" style="background:#1cc88a;color:#fff;">Closed</span>
                    <span class="ml-2">Click any task to open it.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3">
        <?php
        $panels = array(
            array('Overdue',        $overdue, 'danger',  'fa-exclamation-triangle'),
            array('Next 7 days',    $dueSoon, 'warning', 'fa-hourglass-half'),
            array('Later',          $later,   'info',    'fa-calendar'),
        );
        foreach ($panels as $panel):
            list($title, $items, $class, $icon) = $panel;
        ?>
        <div class="card shadow mb-4 border-left-<?php echo $class; ?>">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-<?php echo $class; ?>">
                    <i class="fas <?php echo $icon; ?> fa-sm mr-1"></i> <?php echo $title; ?>
                </h6>
                <span class="badge badge-<?php echo $class; ?>"><?php echo count($items); ?></span>
            </div>
            <div class="card-body py-2">
                <?php if (empty($items)) { ?>
                    <p class="small text-muted mb-0">Nothing here.</p>
                <?php } else { ?>
                    <?php foreach (array_slice($items, 0, 8) as $t):
                        $point = trim($t['point_firstname'] . ' ' . $t['point_lastname']);
                    ?>
                        <div class="py-2 border-bottom">
                            <a class="small font-weight-bold d-block"
                               href="task-view.php?id=<?php echo (int) $t['id_task']; ?>">
                                <?php echo cc_e($t['task_name']); ?>
                            </a>
                            <div class="text-muted" style="font-size:.7rem;">
                                <?php echo cc_e($t['project_code']); ?>
                                &middot; <?php echo $point !== '' ? cc_e($point) : 'Unassigned'; ?>
                            </div>
                            <div style="font-size:.7rem;">
                                <?php echo date('M j, Y', strtotime($t['task_end_date'])); ?>
                                <span class="badge badge-<?php echo $t['due_class']; ?> ml-1">
                                    <?php echo cc_e($t['due_label']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($items) > 8) { ?>
                        <div class="pt-2 small text-muted">
                            and <?php echo count($items) - 8; ?> more
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$CC_PAGE_SCRIPTS = <<<'HTML'
<script src="fullcalendar-6.1.4/dist/index.global.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('ccCalendar');
    if (!el || typeof FullCalendar === 'undefined') { return; }

    var calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek,listMonth'
        },
        height: 680,
        dayMaxEvents: 3,
        eventSources: [
            { url: 'cc-events.php',  failure: function () {} },
            // the app's existing personal events, so nothing is lost
            { url: 'get-events.php', failure: function () {} }
        ],
        eventDidMount: function (info) {
            var p = info.event.extendedProps || {};
            if (!p.project) { return; }
            info.el.setAttribute(
                'title',
                info.event.title
                    + '\nProject: ' + p.project
                    + '\nPoint person: ' + p.pointPerson
                    + '\nProgress: ' + p.percent + '%'
                    + '\n' + p.dueLabel
            );
        }
    });

    calendar.render();
});
</script>
HTML;

require __DIR__ . '/includes/cc/cc_footer.php';
?>
