<?php
/**
 * Outgoing mail for the Command Center.
 *
 * Uses the PHPMailer copy already bundled at includes/database/src/ and the
 * SMTP settings in db-config.local.php (gitignored), so no credential lives
 * in this file.
 *
 * Every function fails soft: if mail cannot be sent, the caller's work still
 * stands and the reason is returned. Creating a task must never fail just
 * because an email did not go out.
 */

if (!function_exists('cc_mail_settings')) {

    /** SMTP settings, or null when mail is not configured. */
    function cc_mail_settings()
    {
        static $cached = false;
        if ($cached !== false) { return $cached; }

        $file = __DIR__ . '/../database/db-config.local.php';
        if (!file_exists($file)) { return $cached = null; }

        $cfg = include $file;
        if (!is_array($cfg) || empty($cfg['smtp_host']) || empty($cfg['smtp_user'])) {
            return $cached = null;
        }

        return $cached = array(
            'host'     => $cfg['smtp_host'],
            'port'     => isset($cfg['smtp_port']) ? (int) $cfg['smtp_port'] : 587,
            'secure'   => isset($cfg['smtp_secure']) ? $cfg['smtp_secure'] : 'tls',
            'user'     => $cfg['smtp_user'],
            'password' => isset($cfg['smtp_password']) ? $cfg['smtp_password'] : '',
            'from'     => !empty($cfg['smtp_from']) ? $cfg['smtp_from'] : $cfg['smtp_user'],
            'fromname' => !empty($cfg['smtp_fromname']) ? $cfg['smtp_fromname'] : 'RMS Command Center',
        );
    }

    /** True when outgoing mail is configured. */
    function cc_mail_available()
    {
        return cc_mail_settings() !== null;
    }

    /**
     * Send one HTML message.
     * Returns array(sent:bool, error:string).
     */
    function cc_mail_send($toEmail, $toName, $subject, $htmlBody)
    {
        $toEmail = trim((string) $toEmail);
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return array(false, 'no valid recipient address');
        }

        $s = cc_mail_settings();
        if ($s === null) {
            return array(false, 'outgoing mail is not configured');
        }

        $src = __DIR__ . '/../database/src';
        require_once $src . '/Exception.php';
        require_once $src . '/PHPMailer.php';
        require_once $src . '/SMTP.php';

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $s['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $s['user'];
            $mail->Password   = $s['password'];
            $mail->SMTPSecure = $s['secure'];
            $mail->Port       = $s['port'];
            $mail->Timeout    = 15;

            $mail->setFrom($s['from'], $s['fromname']);
            $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = trim(html_entity_decode(strip_tags(
                preg_replace('/<br\s*\/?>/i', "\n", $htmlBody)
            )));

            $mail->send();
            return array(true, '');

        } catch (\Throwable $e) {
            return array(false, $e->getMessage());
        }
    }

    /**
     * Shared message shell, so every Command Center email looks the same.
     */
    function cc_mail_template($heading, $introLine, $rows, $closing = '')
    {
        $html  = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#333;">';
        $html .= '<div style="background:#6d071a;color:#fff;padding:14px 18px;font-size:16px;font-weight:bold;">'
               . htmlspecialchars($heading) . '</div>';
        $html .= '<div style="padding:18px;">';
        $html .= '<p>' . htmlspecialchars($introLine) . '</p>';

        if (!empty($rows)) {
            $html .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:14px;">';
            foreach ($rows as $label => $value) {
                $html .= '<tr>'
                       . '<td style="color:#777;white-space:nowrap;">' . htmlspecialchars($label) . '</td>'
                       . '<td style="font-weight:bold;">' . htmlspecialchars($value) . '</td>'
                       . '</tr>';
            }
            $html .= '</table>';
        }

        if ($closing !== '') {
            $html .= '<p style="margin-top:16px;">' . htmlspecialchars($closing) . '</p>';
        }

        $html .= '<p style="margin-top:20px;color:#999;font-size:12px;">'
               . 'Sent automatically by the RMS Command Center. Please do not reply to this message.'
               . '</p>';
        $html .= '</div></div>';

        return $html;
    }

    /** Notify a point person that a task has been assigned to them. */
    function cc_mail_task_assigned($person, $task, $projectTitle, $assignerName)
    {
        $due = trim((string) $task['task_end_date']) !== ''
            ? date('F j, Y', strtotime($task['task_end_date']))
            : 'not set';

        $body = cc_mail_template(
            'New task assigned to you',
            'You have been assigned as the point person for a task in the RMS Command Center.',
            array(
                'Task'        => $task['task_name'],
                'Project'     => $projectTitle,
                'Category'    => $task['classification'],
                'Target date' => $due,
                'Assigned by' => $assignerName,
            ),
            'Please log in to the RMS Command Center to review it and post your updates.'
        );

        return cc_mail_send($person['email'], trim($person['firstname'] . ' ' . $person['lastname']),
            'New task assigned: ' . $task['task_name'], $body);
    }

    /** Notify an approver that they are on the hook for a task. */
    function cc_mail_task_approver($person, $task, $projectTitle, $pointName, $assignerName)
    {
        $due = trim((string) $task['task_end_date']) !== ''
            ? date('F j, Y', strtotime($task['task_end_date']))
            : 'not set';

        $body = cc_mail_template(
            'You are the approver for a task',
            'A task has been created that lists you as its approver.',
            array(
                'Task'         => $task['task_name'],
                'Project'      => $projectTitle,
                'Point person' => $pointName,
                'Target date'  => $due,
                'Created by'   => $assignerName,
            ),
            'You will be asked to sign this off once the point person submits it.'
        );

        return cc_mail_send($person['email'], trim($person['firstname'] . ' ' . $person['lastname']),
            'Approval assigned: ' . $task['task_name'], $body);
    }

    /** Remind a point person about tasks that are due soon or already late. */
    function cc_mail_reminder($person, $tasks)
    {
        $rows = array();
        foreach ($tasks as $t) {
            list($label, , ) = cc_due_state($t['task_end_date'], $t['task_status']);
            $rows[$t['task_name'] . ' (' . $t['project_title'] . ')'] =
                date('F j, Y', strtotime($t['task_end_date'])) . ' - ' . $label;
        }

        $count = count($tasks);
        $body  = cc_mail_template(
            'Task reminder',
            $count === 1
                ? 'One of your tasks needs attention:'
                : $count . ' of your tasks need attention:',
            $rows,
            'Please log in to the RMS Command Center to update your progress.'
        );

        return cc_mail_send($person['email'], trim($person['firstname'] . ' ' . $person['lastname']),
            'Reminder: ' . $count . ' task' . ($count === 1 ? '' : 's') . ' need your attention',
            $body);
    }
}
