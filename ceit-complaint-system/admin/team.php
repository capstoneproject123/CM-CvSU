<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['admin', 'sysadmin']);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_staff') {
        $firstName  = trim($_POST['first_name'] ?? '');
        $lastName   = trim($_POST['last_name'] ?? '');
        $employeeId = trim($_POST['employee_id'] ?? '');
        $role       = in_array($_POST['role'] ?? '', ['admin', 'sysadmin', 'adviser'], true) ? $_POST['role'] : 'admin';
        $tempPass   = $_POST['temp_password'] ?? '';

        if ($firstName === '' || $lastName === '') $errors[] = 'First and last name are required.';
        if ($employeeId === '') $errors[] = 'Employee ID is required.';
        if (strlen($tempPass) < 8) $errors[] = 'Temporary password must be at least 8 characters.';

        if (!$errors) {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE employee_id = ?");
            $stmt->execute([$employeeId]);
            if ($stmt->fetch()) $errors[] = 'That Employee ID is already registered.';
        }

        if (!$errors) {
            $pdo->prepare("INSERT INTO users (first_name, last_name, employee_id, password_hash, role) VALUES (?, ?, ?, ?, ?)")
                ->execute([$firstName, $lastName, $employeeId, password_hash($tempPass, PASSWORD_BCRYPT), $role]);
            flash_set('success', ucfirst($role) . " account for {$firstName} {$lastName} created.");
            header('Location: /ceit-complaint-system/admin/team.php');
            exit;
        }
    }

    if ($action === 'toggle_status') {
        $targetId = (int) ($_POST['user_id'] ?? 0);
        if ($targetId !== (int) $_SESSION['user_id']) {
            $pdo->prepare("UPDATE users SET status = IF(status = 'active', 'disabled', 'active') WHERE user_id = ?")
                ->execute([$targetId]);
        }
        header('Location: /ceit-complaint-system/admin/team.php');
        exit;
    }
}

$staff = $pdo->query("SELECT * FROM users WHERE role IN ('admin','sysadmin','adviser') ORDER BY created_at DESC")->fetchAll();
$students = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Team · CEIT CvSU';
$activeNav = 'team';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header"><h1>Team &amp; Users</h1><p>Manage staff/adviser accounts and view registered students.</p></div>
<?php render_flash(); ?>
<?php if ($errors): ?><div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

<div class="panel">
    <div class="panel-head"><h2>Staff &amp; Adviser Accounts</h2></div>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Employee ID</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($staff as $s): ?>
            <tr>
                <td><?= e($s['first_name'] . ' ' . $s['last_name']) ?></td>
                <td><?= e($s['employee_id']) ?></td>
                <td><?= e(ucfirst($s['role'])) ?></td>
                <td><span class="badge <?= $s['status'] === 'active' ? 'badge-resolved' : 'badge-high' ?>"><?= e(ucfirst($s['status'])) ?></span></td>
                <td><?= date('M j, Y', strtotime($s['created_at'])) ?></td>
                <td>
                    <?php if ($s['user_id'] != $_SESSION['user_id']): ?>
                    <form method="post" style="display:inline;" data-confirm="Change status for this user?">
                        <input type="hidden" name="user_id" value="<?= $s['user_id'] ?>">
                        <button type="submit" name="action" value="toggle_status" class="link-btn" style="background:none;border:none;cursor:pointer;">
                            <?= $s['status'] === 'active' ? 'Disable' : 'Enable' ?>
                        </button>
                    </form>
                    <?php else: ?>
                        <span class="text-muted">You</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="panel">
    <div class="panel-head"><h2>Add Staff / Adviser Account</h2></div>
    <form method="post">
        <div class="form-row">
            <div class="form-group"><label>First Name</label><input type="text" name="first_name" required></div>
            <div class="form-group"><label>Last Name</label><input type="text" name="last_name" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Employee ID</label><input type="text" name="employee_id" required></div>
            <div class="form-group"><label>Role</label>
                <select name="role">
                    <option value="adviser">Adviser</option>
                    <option value="admin">Admin</option>
                    <option value="sysadmin">System Administrator</option>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Temporary Password</label><input type="text" name="temp_password" required minlength="8"></div>
        <button type="submit" name="action" value="add_staff" class="btn btn-primary">Create Account</button>
    </form>
    <p class="help-text" style="margin-top:10px;">Advisers can only see and respond to cases that are assigned to them — they won't see this Team page or the full Report page.</p>
</div>

<div class="panel">
    <div class="panel-head"><h2>Registered Students (<?= count($students) ?>)</h2></div>
    <table class="data-table">
        <thead><tr><th>Name</th><th>CvSU Email</th><th>Department</th><th>Year Level</th><th>Status</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($students, 0, 25) as $s): ?>
            <tr>
                <td><?= e($s['first_name'] . ' ' . $s['last_name']) ?></td>
                <td><?= e($s['email']) ?></td>
                <td><?= e($s['department'] ?: '—') ?></td>
                <td><?= e($s['year_level'] ?: '—') ?></td>
                <td><span class="badge <?= $s['status'] === 'active' ? 'badge-resolved' : 'badge-high' ?>"><?= e(ucfirst($s['status'])) ?></span></td>
                <td><?= date('M j, Y', strtotime($s['created_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$students): ?><div class="empty-state">No students registered yet.</div><?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
