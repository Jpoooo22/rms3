<?php
/**
 * Adds a "Command Center" item to the existing sidebar, server-side.
 *
 * Most of the app's pages are obfuscated (eval(base64_decode(...))), so their
 * sidebar markup cannot be edited in place. This file starts an output buffer
 * and inserts the nav item into the finished HTML on the way out, so the link
 * arrives as ordinary markup with no client-side scripting involved.
 *
 * Added to a page with a single line at the very top of the file:
 *     <?php require __DIR__ . '/includes/cc/cc_nav_buffer.php'; ?>
 *
 * Nothing in the page's own markup is changed or removed.
 */

if (!function_exists('cc_inject_nav_item')) {

    function cc_inject_nav_item($html)
    {
        // Only touch pages that actually render the sidebar.
        if (strpos($html, 'accordionSidebar') === false) {
            return $html;
        }
        // Already present (e.g. the Command Center's own pages).
        if (strpos($html, 'command-center.php') !== false) {
            return $html;
        }

        if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['id'])) {
            return $html;
        }

        $item = '<li class="nav-item userButton">'
              . '<a href="command-center.php" class="nav-link uFont">'
              . '<i class="bi bi-grid-1x2-fill"></i> <span>Command Center</span>'
              . '</a></li>'
              . '<li class="nav-item userButton">'
              . '<a href="cc-calendar.php" class="nav-link uFont">'
              . '<i class="bi bi-calendar-check"></i> <span>Deadlines</span>'
              . '</a></li>';

        // Put it directly beneath the existing Dashboard item. The sidebar brand
        // also points at dashboard.php but is not inside an <li>, so it is skipped.
        $pattern = '/(<li\b[^>]*>\s*<a\b[^>]*href="dashboard\.php"[^>]*>.*?<\/a>\s*<\/li>)/is';

        $count  = 0;
        $result = preg_replace($pattern, '$1' . $item, $html, 1, $count);

        if ($count === 0 || $result === null) {
            // Fallback: drop it in as the first item of the sidebar list.
            $result = preg_replace(
                '/(<ul\b[^>]*id="accordionSidebar"[^>]*>)/i',
                '$1' . $item,
                $html,
                1,
                $count
            );
            if ($count === 0 || $result === null) { $result = $html; }
        }

        return cc_inject_reminder($result);
    }

    /**
     * Append the due/overdue reminder modal, once per session.
     *
     * Uses its own short-lived connection: this runs as an output-buffer
     * callback at shutdown, by which point the page's own $con may already
     * have been closed.
     */
    function cc_inject_reminder($html)
    {
        if (strpos($html, 'ccReminderModal') !== false) { return $html; }
        if (stripos($html, '</body>') === false) { return $html; }

        if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['id'])) {
            return $html;
        }

        // Once per session, so it does not nag on every click.
        if (!empty($_SESSION['cc_reminder_shown'])) { return $html; }

        // Never over the forced password-change or login screens.
        $here = strtolower(basename(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH)));
        if (in_array($here, array('first-login.php', 'default.php', 'logout.php'), true)) {
            return $html;
        }

        $cfgFile = __DIR__ . '/../database/db-config.local.php';
        $local   = file_exists($cfgFile) ? include $cfgFile : array();

        $setting = function ($env, $key, $default) use ($local) {
            $fromEnv = getenv($env);
            if ($fromEnv !== false && $fromEnv !== '') { return $fromEnv; }
            if (isset($local[$key]) && $local[$key] !== '') { return $local[$key]; }
            return $default;
        };

        $link = @new mysqli(
            $setting('DB_HOST', 'host', 'localhost'),
            $setting('DB_USER', 'user', 'root'),
            $setting('DB_PASSWORD', 'password', ''),
            $setting('DB_NAME', 'name', 'rms')
        );

        if ($link->connect_error) { return $html; }

        require_once __DIR__ . '/cc_reminders.php';
        $modal = cc_reminder_html($link, $_SESSION['id']);
        $link->close();

        if ($modal === '') { return $html; }

        $_SESSION['cc_reminder_shown'] = 1;

        $pos = strripos($html, '</body>');
        return substr($html, 0, $pos) . $modal . substr($html, $pos);
    }

    ob_start('cc_inject_nav_item');
}
