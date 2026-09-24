<?php
/**
 * Reminder pop-up for tasks that are overdue or due soon.
 *
 * Builds a Bootstrap modal listing the signed-in person's own tasks, and is
 * injected into the page by cc_nav_buffer.php - which is already prepended to
 * every page that renders the sidebar, so the reminder appears wherever the
 * user lands without editing any obfuscated page.
 *
 * Shown once per session by default, so it does not nag on every click.
 */

if (!function_exists('cc_reminder_html')) {

    /** Days ahead that still counts as "due soon". */
    function cc_reminder_window()
    {
        return 3;
    }

    /**
     * Tasks needing this user's attention: ones they are the point person for,
     * open, with a target date today, soon, or already past.
     */
    function cc_reminder_tasks($link, $userId)
    {
        $sql = "SELECT t.id_task, t.task_name, t.task_end_date, t.task_status,
                       t.percent, p.title AS project_title, p.code AS project_code
                FROM task t
                INNER JOIN assigned_to a ON a.task_id = t.id_task
                LEFT JOIN projects p ON p.id = t.project_id
                WHERE a.user_id = ?
                  AND LOWER(t.task_status) = 'open'
                  AND t.task_end_date <> ''
                  AND DATEDIFF(t.task_end_date, CURDATE()) <= ?
                GROUP BY t.id_task
                ORDER BY t.task_end_date ASC";

        $window = cc_reminder_window();
        $uid    = (string) $userId;

        $stmt = $link->prepare($sql);
        if (!$stmt) { return array(); }
        $stmt->bind_param('si', $uid, $window);
        $stmt->execute();
        $res = $stmt->get_result();

        $out = array();
        while ($row = $res->fetch_assoc()) { $out[] = $row; }
        $stmt->close();

        return $out;
    }

    /**
     * The modal markup, or '' when there is nothing to show.
     * $link is an open mysqli connection.
     */
    function cc_reminder_html($link, $userId)
    {
        $tasks = cc_reminder_tasks($link, $userId);
        if (empty($tasks)) { return ''; }

        $overdue = 0;
        $today   = 0;
        foreach ($tasks as $t) {
            $days = (int) floor((strtotime($t['task_end_date']) - strtotime(date('Y-m-d'))) / 86400);
            if ($days < 0)       { $overdue++; }
            elseif ($days === 0) { $today++; }
        }

        $headline = array();
        if ($overdue > 0) { $headline[] = $overdue . ' overdue'; }
        if ($today > 0)   { $headline[] = $today . ' due today'; }
        $remaining = count($tasks) - $overdue - $today;
        if ($remaining > 0) { $headline[] = $remaining . ' due soon'; }

        $esc = function ($v) {
            return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        };

        $rows = '';
        foreach ($tasks as $t) {
            $days = (int) floor((strtotime($t['task_end_date']) - strtotime(date('Y-m-d'))) / 86400);

            if ($days < 0)        { $cls = 'danger';  $lbl = abs($days) . ' day(s) overdue'; }
            elseif ($days === 0)  { $cls = 'warning'; $lbl = 'Due today'; }
            else                  { $cls = 'info';    $lbl = $days . ' day(s) left'; }

            $rows .= '<tr>'
                  . '<td><a href="task-view.php?id=' . (int) $t['id_task'] . '">'
                  . $esc($t['task_name']) . '</a>'
                  . ($t['project_code'] !== null && $t['project_code'] !== ''
                        ? '<div class="text-muted" style="font-size:.72rem;">' . $esc($t['project_title']) . '</div>'
                        : '')
                  . '</td>'
                  . '<td class="text-nowrap">' . date('M j, Y', strtotime($t['task_end_date'])) . '</td>'
                  . '<td><span class="badge badge-' . $cls . '">' . $esc($lbl) . '</span></td>'
                  . '<td class="text-right">' . (int) $t['percent'] . '%</td>'
                  . '</tr>';
        }

        $title = 'You have ' . implode(', ', $headline);

        return '
<div class="modal fade" id="ccReminderModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">
          <i class="fas fa-bell mr-2"></i>' . $esc($title) . '
        </h5>
        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-3">
          These are tasks you are the point person for. Open one to post an update.
        </p>
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>Task</th><th>Target date</th><th>Status</th><th class="text-right">Progress</th>
              </tr>
            </thead>
            <tbody>' . $rows . '</tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <a href="cc-calendar.php" class="btn btn-primary btn-sm">
          <i class="fas fa-calendar fa-sm mr-1"></i> Open calendar
        </a>
        <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Dismiss</button>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
    function show() {
        if (window.jQuery && jQuery("#ccReminderModal").modal) {
            jQuery("#ccReminderModal").modal("show");
        }
    }
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", show);
    } else {
        show();
    }
})();
</script>';
    }
}
