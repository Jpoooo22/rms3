<?php
/**
 * JSON feed of task target dates for FullCalendar.
 *
 * Derived live from the `task` table, so a date change on a task is reflected
 * immediately - nothing is duplicated into the `events` table and there is no
 * sync to drift.
 *
 * Scope follows the Command Center rule: the Dean / admin sees every project's
 * deadlines, everyone else sees tasks they are the point person or approver
 * for, plus tasks in projects they lead or belong to.
 */

require_once __DIR__ . '/includes/cc/cc_bootstrap.php';

header('Content-Type: application/json');

$seesAll = cc_can_view_all_projects($user);
$uid     = (int) $user['id'];

$sql = "SELECT t.id_task, t.task_name, t.task_start_date, t.task_end_date,
               t.task_status, t.percent,
               p.title AS project_title, p.code AS project_code,
               a.user_id AS point_user_id,
               pu.firstname AS point_firstname, pu.lastname AS point_lastname
        FROM task t
        INNER JOIN projects p ON p.id = t.project_id
        LEFT JOIN assigned_to a ON a.task_id = t.id_task
        LEFT JOIN users pu ON pu.id = a.user_id
        LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        WHERE t.project_id > 0
          AND t.task_end_date <> ''
          AND (? = 1
               OR a.user_id = ?
               OR a.approver_id = ?
               OR pm.user_id IS NOT NULL
               OR p.lead_user_id = ?)
        GROUP BY t.id_task
        ORDER BY t.task_end_date ASC";

$stmt    = mysqli_prepare($con, $sql);
$seesAllI = $seesAll ? 1 : 0;
mysqli_stmt_bind_param($stmt, 'iiiii', $uid, $seesAllI, $uid, $uid, $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

/* Colours match the status badges used across the Command Center. */
$palette = array(
    'danger'    => '#e74a3b',
    'warning'   => '#f6c23e',
    'info'      => '#36b9cc',
    'success'   => '#1cc88a',
    'secondary' => '#858796',
);

$events = array();

while ($row = mysqli_fetch_assoc($res)) {

    $due = strtotime($row['task_end_date']);
    if ($due === false) { continue; }

    list($dueLabel, $dueClass, ) = cc_due_state($row['task_end_date'], $row['task_status']);
    $colour = isset($palette[$dueClass]) ? $palette[$dueClass] : $palette['secondary'];

    $point = trim($row['point_firstname'] . ' ' . $row['point_lastname']);

    /* FullCalendar treats an all-day end as exclusive, so push it a day on. */
    $events[] = array(
        'id'              => 'task-' . $row['id_task'],
        'title'           => $row['project_code'] . ': ' . $row['task_name'],
        'start'           => date('Y-m-d', $due),
        'end'             => date('Y-m-d', strtotime('+1 day', $due)),
        'allDay'          => true,
        'backgroundColor' => $colour,
        'borderColor'     => $colour,
        'url'             => 'task-view.php?id=' . (int) $row['id_task'],
        'extendedProps'   => array(
            'project'     => $row['project_title'],
            'pointPerson' => $point !== '' ? $point : 'Unassigned',
            'status'      => $row['task_status'],
            'percent'     => (int) $row['percent'],
            'dueLabel'    => $dueLabel,
        ),
    );
}

echo json_encode($events);
