<?php
/**
 * Create or edit a project from inside the app.
 * Everything on a project card - title, image, status, progress, milestone
 * and the team - is editable here. No file editing required.
 */

require_once __DIR__ . '/includes/cc/cc_bootstrap.php';

if (!cc_can_manage_projects($user)) {
    cc_flash('You do not have permission to manage projects.', 'danger');
    header('Location: command-center.php');
    exit;
}

$id        = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isNew     = ($id === 0);
$project   = $isNew ? null : cc_project($con, $id);
$errors    = array();

if (!$isNew && !$project) {
    cc_flash('That project no longer exists.', 'warning');
    header('Location: command-center.php');
    exit;
}

$allUsers = cc_selectable_users($con);
$memberIds = array();
$memberRoles = array();
if (!$isNew) {
    foreach (cc_project_members($con, $id) as $m) {
        $memberIds[] = (int) $m['user_id'];
        $memberRoles[(int) $m['user_id']] = $m['role'];
    }
}

/* Values shown in the form - the saved project, or blanks for a new one. */
$form = array(
    'code'                => $isNew ? '' : $project['code'],
    'title'               => $isNew ? '' : $project['title'],
    'subtitle'            => $isNew ? '' : $project['subtitle'],
    'tracks'              => $isNew ? '' : $project['tracks'],
    'description'         => $isNew ? '' : $project['description'],
    'status'              => $isNew ? 'On Track' : $project['status'],
    'progress'            => $isNew ? 0 : (int) $project['progress'],
    'next_milestone'      => $isNew ? '' : $project['next_milestone'],
    'next_milestone_date' => $isNew ? '' : $project['next_milestone_date'],
    'lead_user_id'        => $isNew ? '' : $project['lead_user_id'],
    'sort_order'          => $isNew ? 0 : (int) $project['sort_order'],
    'is_archived'         => $isNew ? 0 : (int) $project['is_archived'],
    'image'               => $isNew ? '' : $project['image'],
);

/* ------------------------------------------------------------------ *
 * Save
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {

    $form['code']                = trim($_POST['code']);
    $form['title']               = trim($_POST['title']);
    $form['subtitle']            = trim($_POST['subtitle']);
    $form['tracks']              = trim($_POST['tracks']);
    $form['description']         = trim($_POST['description']);
    $form['status']              = trim($_POST['status']);
    $form['progress']            = max(0, min(100, (int) $_POST['progress']));
    $form['next_milestone']      = trim($_POST['next_milestone']);
    $form['next_milestone_date'] = trim($_POST['next_milestone_date']);
    $form['lead_user_id']        = $_POST['lead_user_id'] !== '' ? (int) $_POST['lead_user_id'] : null;
    $form['sort_order']          = (int) $_POST['sort_order'];
    $form['is_archived']         = isset($_POST['is_archived']) ? 1 : 0;

    $postedMembers = isset($_POST['members']) && is_array($_POST['members'])
        ? array_map('intval', $_POST['members'])
        : array();
    $postedRoles = isset($_POST['member_role']) && is_array($_POST['member_role'])
        ? $_POST['member_role']
        : array();

    if ($form['title'] === '')  { $errors[] = 'Project title is required.'; }
    if ($form['code'] === '')   { $errors[] = 'Project code is required.'; }
    if (!array_key_exists($form['status'], cc_status_list())) {
        $errors[] = 'Please choose a valid status.';
    }
    if ($form['next_milestone_date'] !== ''
        && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['next_milestone_date'])) {
        $errors[] = 'Milestone date must be a valid date.';
    }

    /* Code must stay unique - it is how the installer recognises a project. */
    if ($form['code'] !== '') {
        $chk = mysqli_prepare($con, "SELECT id FROM projects WHERE code = ? AND id <> ?");
        mysqli_stmt_bind_param($chk, 'si', $form['code'], $id);
        mysqli_stmt_execute($chk);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($chk))) {
            $errors[] = 'Another project already uses the code "' . $form['code'] . '".';
        }
    }

    /* Image upload is optional; keep the current one when none is sent. */
    list($uploadedPath, $uploadError) = cc_upload_project_image(
        isset($_FILES['image']) ? $_FILES['image'] : null
    );
    if ($uploadError !== '') { $errors[] = $uploadError; }

    $imagePath = $form['image'];
    if ($uploadedPath !== '') {
        $imagePath = $uploadedPath;
    } elseif (isset($_POST['remove_image'])) {
        $imagePath = '';
    }

    if (empty($errors)) {

        $milestoneDate = $form['next_milestone_date'] !== '' ? $form['next_milestone_date'] : null;

        if ($isNew) {
            $stmt = mysqli_prepare($con,
                "INSERT INTO projects
                 (code,title,subtitle,tracks,description,image,status,progress,
                  next_milestone,next_milestone_date,lead_user_id,sort_order,is_archived)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'sssssssisssii',
                $form['code'], $form['title'], $form['subtitle'], $form['tracks'],
                $form['description'], $imagePath, $form['status'], $form['progress'],
                $form['next_milestone'], $milestoneDate, $form['lead_user_id'],
                $form['sort_order'], $form['is_archived']);
            mysqli_stmt_execute($stmt);
            $id = mysqli_insert_id($con);
            cc_log($con, $user, 'Created project: ' . $form['title'], 'Command Center');
            $savedMsg = 'Project created.';
        } else {
            $stmt = mysqli_prepare($con,
                "UPDATE projects SET
                    code=?, title=?, subtitle=?, tracks=?, description=?, image=?,
                    status=?, progress=?, next_milestone=?, next_milestone_date=?,
                    lead_user_id=?, sort_order=?, is_archived=?
                 WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssssssisssiii',
                $form['code'], $form['title'], $form['subtitle'], $form['tracks'],
                $form['description'], $imagePath, $form['status'], $form['progress'],
                $form['next_milestone'], $milestoneDate, $form['lead_user_id'],
                $form['sort_order'], $form['is_archived'], $id);
            mysqli_stmt_execute($stmt);
            cc_log($con, $user, 'Updated project: ' . $form['title'], 'Command Center');
            $savedMsg = 'Project updated.';
        }

        /* Replace the member list with what was submitted. */
        $del = mysqli_prepare($con, "DELETE FROM project_members WHERE project_id = ?");
        mysqli_stmt_bind_param($del, 'i', $id);
        mysqli_stmt_execute($del);

        foreach ($postedMembers as $uid) {
            $role = isset($postedRoles[$uid]) && trim($postedRoles[$uid]) !== ''
                ? trim($postedRoles[$uid])
                : 'Member';
            $ins = mysqli_prepare($con,
                "INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (?,?,?)");
            mysqli_stmt_bind_param($ins, 'iis', $id, $uid, $role);
            mysqli_stmt_execute($ins);
        }

        cc_flash($savedMsg);
        header('Location: command-center.php');
        exit;
    }

    /* Validation failed - keep what the user typed, including member picks. */
    $form['image'] = $imagePath;
    $memberIds     = $postedMembers;
    foreach ($postedMembers as $uid) {
        $memberRoles[$uid] = isset($postedRoles[$uid]) ? $postedRoles[$uid] : 'Member';
    }
}

$CC_PAGE_TITLE = $isNew ? 'New Project' : 'Edit Project';
$CC_ACTIVE     = 'command-center';
require __DIR__ . '/includes/cc/cc_header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?php echo $isNew ? 'New Project' : 'Edit Project'; ?></h1>
    <a href="command-center.php" class="btn btn-sm btn-outline-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm mr-1"></i> Back to Command Center
    </a>
</div>

<?php if (!empty($errors)) { ?>
    <div class="alert alert-danger">
        <ul class="mb-0 pl-3">
            <?php foreach ($errors as $e) { echo '<li>' . cc_e($e) . '</li>'; } ?>
        </ul>
    </div>
<?php } ?>

<form method="post" enctype="multipart/form-data">
<div class="row">

    <!-- ---------------- details ---------------- -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Project Details</h6>
            </div>
            <div class="card-body">

                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label class="small font-weight-bold">Project Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               value="<?php echo cc_e($form['title']); ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="small font-weight-bold">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control"
                               value="<?php echo cc_e($form['code']); ?>" required>
                        <small class="form-text text-muted">Short unique tag, e.g. DAGAT2.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="small font-weight-bold">Subtitle</label>
                    <input type="text" name="subtitle" class="form-control"
                           value="<?php echo cc_e($form['subtitle']); ?>"
                           placeholder="e.g. Maritime Domain Awareness">
                </div>

                <div class="form-group">
                    <label class="small font-weight-bold">What this card tracks</label>
                    <textarea name="tracks" class="form-control" rows="2"
                              placeholder="e.g. GC revisions, acoustic system, DND/DOST/PN coordination, next milestones"><?php echo cc_e($form['tracks']); ?></textarea>
                    <small class="form-text text-muted">Shown on the project card in the portfolio.</small>
                </div>

                <div class="form-group">
                    <label class="small font-weight-bold">Description</label>
                    <textarea name="description" class="form-control" rows="4"
                              placeholder="Longer background, scope and objectives."><?php echo cc_e($form['description']); ?></textarea>
                </div>

                <hr>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label class="small font-weight-bold">Status</label>
                        <select name="status" class="form-control">
                            <?php foreach (cc_status_list() as $label => $class) { ?>
                                <option value="<?php echo cc_e($label); ?>"
                                    <?php echo $form['status'] === $label ? 'selected' : ''; ?>>
                                    <?php echo cc_e($label); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="small font-weight-bold">
                            Progress &mdash; <span id="progressOut"><?php echo (int) $form['progress']; ?></span>%
                        </label>
                        <input type="range" name="progress" class="custom-range mt-2"
                               min="0" max="100" step="5"
                               value="<?php echo (int) $form['progress']; ?>"
                               oninput="document.getElementById('progressOut').textContent = this.value">
                    </div>
                    <div class="form-group col-md-4">
                        <label class="small font-weight-bold">Display order</label>
                        <input type="number" name="sort_order" class="form-control"
                               value="<?php echo (int) $form['sort_order']; ?>">
                        <small class="form-text text-muted">Lower numbers appear first.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label class="small font-weight-bold">Next Milestone</label>
                        <input type="text" name="next_milestone" class="form-control"
                               value="<?php echo cc_e($form['next_milestone']); ?>"
                               placeholder="e.g. GC revisions">
                    </div>
                    <div class="form-group col-md-4">
                        <label class="small font-weight-bold">Milestone date</label>
                        <input type="date" name="next_milestone_date" class="form-control"
                               value="<?php echo cc_e($form['next_milestone_date']); ?>">
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="small font-weight-bold">Project Lead</label>
                    <select name="lead_user_id" class="form-control">
                        <option value="">&mdash; none &mdash;</option>
                        <?php foreach ($allUsers as $u) { ?>
                            <option value="<?php echo (int) $u['id']; ?>"
                                <?php echo (string) $form['lead_user_id'] === (string) $u['id'] ? 'selected' : ''; ?>>
                                <?php echo cc_e(cc_person_name($u)); ?>
                                <?php echo $u['position'] !== '' ? ' - ' . cc_e($u['position']) : ''; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- ---------------- team ---------------- -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Team Members</h6>
                <span class="small text-muted">Tick everyone working on this project</span>
            </div>
            <div class="card-body">
                <?php if (empty($allUsers)) { ?>
                    <p class="text-muted mb-0">
                        No verified users yet. Add them under User Management, then come back here.
                    </p>
                <?php } else { ?>
                    <input type="text" id="memberFilter" class="form-control form-control-sm mb-3"
                           placeholder="Filter by name or email...">
                    <div style="max-height:320px; overflow-y:auto;">
                        <table class="table table-sm table-hover mb-0" id="memberTable">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th style="width:170px;">Role on project</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($allUsers as $u):
                                $uid     = (int) $u['id'];
                                $checked = in_array($uid, $memberIds, true);
                                $role    = isset($memberRoles[$uid]) ? $memberRoles[$uid] : '';
                            ?>
                                <tr>
                                    <td class="align-middle">
                                        <input type="checkbox" name="members[]"
                                               value="<?php echo $uid; ?>"
                                               <?php echo $checked ? 'checked' : ''; ?>>
                                    </td>
                                    <td class="align-middle small">
                                        <?php echo cc_e(cc_person_name($u)); ?>
                                        <?php if ($u['position'] !== '') { ?>
                                            <div class="text-muted" style="font-size:.72rem;"><?php echo cc_e($u['position']); ?></div>
                                        <?php } ?>
                                    </td>
                                    <td class="align-middle small text-muted"><?php echo cc_e($u['email']); ?></td>
                                    <td class="align-middle">
                                        <input type="text" class="form-control form-control-sm"
                                               name="member_role[<?php echo $uid; ?>]"
                                               value="<?php echo cc_e($role); ?>"
                                               placeholder="Member">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- ---------------- image + actions ---------------- -->
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Project Image</h6>
            </div>
            <div class="card-body">
                <?php
                $hasImage = trim($form['image']) !== '' && file_exists(__DIR__ . '/' . $form['image']);
                $preview  = $hasImage
                    ? "background-image:url('" . cc_e($form['image']) . "')"
                    : 'background:' . cc_project_gradient(array('id' => $id));
                ?>
                <div class="cc-card-banner rounded mb-3 <?php echo $hasImage ? '' : 'cc-empty-banner'; ?>"
                     id="imagePreview" style="<?php echo $preview; ?>">
                    <?php if (!$hasImage) { ?><i class="fas fa-image"></i><?php } ?>
                </div>

                <div class="form-group">
                    <input type="file" name="image" accept="image/*"
                           class="form-control-file" id="imageInput">
                    <small class="form-text text-muted">JPG, PNG, GIF or WEBP. Up to 5 MB.</small>
                </div>

                <?php if ($hasImage) { ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remove_image" id="removeImage">
                        <label class="form-check-label small" for="removeImage">
                            Remove the current image
                        </label>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_archived" id="isArchived"
                           <?php echo (int) $form['is_archived'] === 1 ? 'checked' : ''; ?>>
                    <label class="form-check-label small" for="isArchived">
                        Archive this project
                        <span class="d-block text-muted" style="font-size:.72rem;">
                            Hidden from the portfolio, nothing is deleted.
                        </span>
                    </label>
                </div>

                <button type="submit" name="save_project" class="btn btn-primary btn-block">
                    <i class="fas fa-save fa-sm mr-1"></i>
                    <?php echo $isNew ? 'Create project' : 'Save changes'; ?>
                </button>
                <a href="command-center.php" class="btn btn-link btn-block">Cancel</a>
            </div>
        </div>
    </div>
</div>
</form>

<?php
$CC_PAGE_SCRIPTS = <<<'HTML'
<script>
// Live preview of a newly chosen project image.
document.getElementById('imageInput').addEventListener('change', function (e) {
    var file = e.target.files[0];
    if (!file) { return; }
    var reader = new FileReader();
    reader.onload = function (ev) {
        var box = document.getElementById('imagePreview');
        box.style.backgroundImage = "url('" + ev.target.result + "')";
        box.classList.remove('cc-empty-banner');
        box.innerHTML = '';
    };
    reader.readAsDataURL(file);
});

// Filter the member list by name or email.
var filter = document.getElementById('memberFilter');
if (filter) {
    filter.addEventListener('keyup', function () {
        var q = this.value.toLowerCase();
        var rows = document.querySelectorAll('#memberTable tbody tr');
        rows.forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
        });
    });
}
</script>
HTML;

require __DIR__ . '/includes/cc/cc_footer.php';
?>
