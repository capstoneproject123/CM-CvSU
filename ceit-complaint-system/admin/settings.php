<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['admin', 'sysadmin', 'adviser']);

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');

        if ($firstName === '' || $lastName === '') {
            $errors[] = 'First and last name are required.';
        }

        $avatarError = handle_avatar_upload($pdo, $userId);
        if ($avatarError) $errors[] = $avatarError;

        if (!$errors) {
            $pdo->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE user_id = ?")
                ->execute([$firstName, $lastName, $userId]);
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            flash_set('success', 'Profile updated.');
            header('Location: /ceit-complaint-system/admin/settings.php');
            exit;
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")
                ->execute([password_hash($new, PASSWORD_BCRYPT), $userId]);
            flash_set('success', 'Password changed successfully.');
            header('Location: /ceit-complaint-system/admin/settings.php');
            exit;
        }
    }
}

$pageTitle = 'Settings · CEIT CvSU';
$activeNav = 'settings';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header"><h1>Settings</h1><p>Manage your profile and account security.</p></div>
<?php render_flash(); ?>
<?php if ($errors): ?><div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

<div class="panel">
    <div class="panel-head"><h2>Profile</h2></div>
    <form method="post" enctype="multipart/form-data">
        <div class="profile-pic-row">
            <div class="profile-pic-preview">
                <?php if ($user['avatar_path']): ?>
                    <img src="/ceit-complaint-system/<?= e($user['avatar_path']) ?>" alt="">
                <?php else: ?>
                    <span><?= e(strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1))) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <label for="avatar-input" class="btn btn-outline btn-sm">Change Photo</label>
                <input type="file" name="avatar" id="avatar-input" accept=".jpg,.jpeg,.png,.gif,.webp" style="display:none;" onchange="document.getElementById('avatar-filename').textContent = this.files[0] ? this.files[0].name : '';">
                <div class="text-muted" id="avatar-filename" style="margin-top:6px;"></div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?= e($user['first_name']) ?>"></div>
            <div class="form-group"><label>Last Name</label><input type="text" name="last_name" value="<?= e($user['last_name']) ?>"></div>
        </div>
        <div class="form-group"><label>Employee ID</label><input type="text" value="<?= e($user['employee_id']) ?>" disabled></div>
        <div class="form-group"><label>Role</label><input type="text" value="<?= e(ucfirst($user['role'])) ?>" disabled></div>
        <button type="submit" name="action" value="update_profile" class="btn btn-primary">Save Changes</button>
    </form>
</div>

<div class="panel">
    <div class="panel-head"><h2>Change Password</h2></div>
    <form method="post">
        <div class="form-group"><label>Current Password</label>
            <div class="password-wrap">
                <input type="password" name="current_password" id="a-cur-pw" required>
                <button type="button" class="password-toggle" data-target="a-cur-pw" aria-label="Show password">Show</button>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>New Password</label>
                <div class="password-wrap">
                    <input type="password" name="new_password" id="a-new-pw" required minlength="8">
                    <button type="button" class="password-toggle" data-target="a-new-pw" aria-label="Show password">Show</button>
                </div>
            </div>
            <div class="form-group"><label>Confirm New Password</label>
                <div class="password-wrap">
                    <input type="password" name="confirm_password" id="a-confirm-pw" required minlength="8">
                    <button type="button" class="password-toggle" data-target="a-confirm-pw" aria-label="Show password">Show</button>
                </div>
            </div>
        </div>
        <button type="submit" name="action" value="change_password" class="btn btn-primary">Update Password</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
