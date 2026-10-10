<?php
/**
 * Account administration - Admin only.
 *
 *   1. Approve or reject self-registered accounts (approval = 'unverified').
 *   2. Reset any user's password when they cannot recover it themselves.
 *
 * Self-registration creates accounts as 'unverified', which login-proccessing
 * refuses, so nothing here is optional: without an approval the account is inert.
 */

require_once __DIR__ . '/includes/cc/cc_bootstrap.php';

/* Admin only. Officers manage projects, but accounts are the Dean's call. */
if (strtolower(trim($user['usertype'])) !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$msg = '';
$msgClass = 'success';

/* ---------- actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $targetId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

    if ($targetId === (int) $user['id'] && isset($_POST['reject'])) {
        $msg = 'You cannot reject your own account.';
        $msgClass = 'danger';

    } elseif (isset($_POST['approve']) && $targetId > 0) {
        $q = mysqli_stmt_init($con);
        mysqli_stmt_prepare($q, "UPDATE users SET approval = 'verified' WHERE id = ?");
        mysqli_stmt_bind_param($q, 'i', $targetId);
        $msg = mysqli_stmt_execute($q) ? 'Account approved. They can sign in now.' : 'Could not approve that account.';
        $msgClass = mysqli_stmt_errno($q) ? 'danger' : 'success';

    } elseif (isset($_POST['reject']) && $targetId > 0) {
        $q = mysqli_stmt_init($con);
        mysqli_stmt_prepare($q, "DELETE FROM users WHERE id = ? AND approval = 'unverified'");
        mysqli_stmt_bind_param($q, 'i', $targetId);
        $msg = mysqli_stmt_execute($q) ? 'Registration rejected and removed.' : 'Could not reject that account.';

    } elseif (isset($_POST['set_type']) && $targetId > 0) {
        $t = isset($_POST['account_type']) ? trim($_POST['account_type']) : '';
        if (!in_array($t, array('Faculty', 'Researcher', ''), true)) {
            $msg = 'Invalid account type.';
            $msgClass = 'danger';
        } else {
            $q = mysqli_stmt_init($con);
            mysqli_stmt_prepare($q, "UPDATE users SET account_type = ? WHERE id = ?");
            mysqli_stmt_bind_param($q, 'si', $t, $targetId);
            $msg = mysqli_stmt_execute($q) ? 'Account type updated.' : 'Could not update account type.';
        }

    } elseif (isset($_POST['reset_password']) && $targetId > 0) {
        $new = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        if (strlen($new) < 8) {
            $msg = 'The new password must be at least 8 characters long.';
            $msgClass = 'danger';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            /* firstlogin = 0 forces them through first-login.php to set their own. */
            $q = mysqli_stmt_init($con);
            mysqli_stmt_prepare($q, "UPDATE users SET password = ?, firstlogin = 0 WHERE id = ?");
            mysqli_stmt_bind_param($q, 'si', $hash, $targetId);
            if (mysqli_stmt_execute($q)) {
                $msg = 'Password reset. Give them the new password - they must change it at next sign in.';
            } else {
                $msg = 'Could not reset that password.';
                $msgClass = 'danger';
            }
        }
    }
}

/* ---------- data ---------- */
$pending = array();
$res = mysqli_query($con,
    "SELECT id, firstname, lastname, middlename, username, email, department,
            account_type, position, SerialID, registerDate, addedBy
     FROM users WHERE approval = 'unverified' ORDER BY id DESC");
if ($res) { while ($r = mysqli_fetch_assoc($res)) { $pending[] = $r; } }

$active = array();
$res2 = mysqli_query($con,
    "SELECT id, firstname, lastname, username, email, usertype, account_type, department, firstlogin
     FROM users WHERE approval = 'verified' ORDER BY usertype DESC, lastname ASC");
if ($res2) { while ($r = mysqli_fetch_assoc($res2)) { $active[] = $r; } }

require __DIR__ . '/includes/cc/cc_header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Account Administration</h1>
    <span class="badge badge-primary" style="font-size:.85rem;">
        <?php echo count($pending); ?> pending
    </span>
</div>

<?php if ($msg !== '') { ?>
    <div class="alert alert-<?php echo $msgClass; ?>"><?php echo cc_e($msg); ?></div>
<?php } ?>

<!-- ---------------- pending registrations ---------------- -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="bi bi-person-plus"></i> Pending registrations
        </h6>
    </div>
    <div class="card-body">
        <?php if (empty($pending)) { ?>
            <p class="text-muted mb-0">No accounts are waiting for approval.</p>
        <?php } else { ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th><th>Type</th><th>Department</th>
                            <th>Email</th><th>Username</th><th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pending as $p) { ?>
                        <tr>
                            <td class="small">
                                <strong><?php echo cc_e(trim($p['firstname'] . ' ' . $p['lastname'])); ?></strong>
                                <?php if (trim($p['position']) !== '') { ?>
                                    <div class="text-muted" style="font-size:.72rem;"><?php echo cc_e($p['position']); ?></div>
                                <?php } ?>
                                <?php if (trim($p['SerialID']) !== '') { ?>
                                    <div class="text-muted" style="font-size:.72rem;">ID: <?php echo cc_e($p['SerialID']); ?></div>
                                <?php } ?>
                            </td>
                            <td class="small">
                                <span class="badge badge-info"><?php echo cc_e($p['account_type'] !== '' ? $p['account_type'] : 'n/a'); ?></span>
                            </td>
                            <td class="small"><?php echo cc_e($p['department']); ?></td>
                            <td class="small"><?php echo cc_e($p['email']); ?></td>
                            <td class="small"><?php echo cc_e($p['username']); ?></td>
                            <td class="text-right" style="white-space:nowrap;">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?php echo (int) $p['id']; ?>">
                                    <button name="approve" value="1" class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form method="post" style="display:inline;"
                                      onsubmit="return confirm('Reject and permanently delete this registration?');">
                                    <input type="hidden" name="user_id" value="<?php echo (int) $p['id']; ?>">
                                    <button name="reject" value="1" class="btn btn-sm btn-outline-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>
</div>

<!-- ---------------- password reset ---------------- -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="bi bi-key"></i> Reset a password
        </h6>
    </div>
    <div class="card-body">
        <p class="small text-muted">
            Use this when someone cannot get back into their account. Set a temporary
            password, tell it to them, and they will be required to choose their own
            the next time they sign in.
        </p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="thead-light">
                    <tr><th>Name</th><th>Role</th><th>Type</th><th>Username</th><th style="width:330px;">Temporary password</th></tr>
                </thead>
                <tbody>
                <?php foreach ($active as $a) { ?>
                    <tr>
                        <td class="small">
                            <?php echo cc_e(trim($a['firstname'] . ' ' . $a['lastname'])); ?>
                            <div class="text-muted" style="font-size:.72rem;"><?php echo cc_e($a['email']); ?></div>
                        </td>
                        <td class="small"><?php echo cc_e($a['usertype']); ?></td>
                        <td class="small">
                            <form method="post" class="form-inline">
                                <input type="hidden" name="user_id" value="<?php echo (int) $a['id']; ?>">
                                <select name="account_type" class="form-control form-control-sm mr-1" style="width:120px;">
                                    <?php foreach (array('' => '-', 'Faculty' => 'Faculty', 'Researcher' => 'Researcher') as $v => $lbl) { ?>
                                        <option value="<?php echo $v; ?>" <?php echo $a['account_type'] === $v ? 'selected' : ''; ?>>
                                            <?php echo $lbl; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <button name="set_type" value="1" class="btn btn-sm btn-outline-primary">Set</button>
                            </form>
                        </td>
                        <td class="small"><?php echo cc_e($a['username']); ?></td>
                        <td>
                            <form method="post" class="form-inline"
                                  onsubmit="return confirm('Reset the password for <?php echo cc_e(trim($a['firstname'].' '.$a['lastname'])); ?>?');">
                                <input type="hidden" name="user_id" value="<?php echo (int) $a['id']; ?>">
                                <input type="text" name="new_password" class="form-control form-control-sm mr-2"
                                       placeholder="min 8 characters" minlength="8" required style="width:190px;">
                                <button name="reset_password" value="1" class="btn btn-sm btn-warning">Reset</button>
                            </form>
                            <?php if ((int) $a['firstlogin'] === 0) { ?>
                                <small class="text-danger">must change password at next sign in</small>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/cc/cc_footer.php'; ?>
