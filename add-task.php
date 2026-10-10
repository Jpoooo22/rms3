<?php
/**
 * Standalone "Add task" is retired.
 *
 * This page created tasks without a project_id, so everything it made was
 * orphaned from the projects it should have belonged to. Tasks are now created
 * from inside a project (project-view.php -> New task -> task-new.php), which
 * sets project_id and lets you define the phases up front.
 *
 * The original file is kept as add-task.php.disabled for reference.
 * This stub stays so any old bookmark or leftover link lands somewhere useful
 * instead of silently creating another orphan task.
 */
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: default.php');
    exit;
}

$_SESSION['success'] = 'Choose a project first';
$_SESSION['text']    = 'Tasks are now created inside a project so they stay linked to it. Open the project, then use "New task".';
$_SESSION['icon']    = 'info';

header('Location: command-center.php');
exit;
