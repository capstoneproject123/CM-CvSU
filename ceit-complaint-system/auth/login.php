<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . (current_role() === 'student' ? '/ceit-complaint-system/student/dashboard.php' : '/ceit-complaint-system/admin/dashboard.php'));
    exit;
}

$error = null;
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR employee_id = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = 'Invalid login or password.';
    } elseif ($user['status'] === 'disabled') {
        $error = 'This account has been disabled. Contact a system administrator.';
    } else {
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name']  = $user['last_name'];
        $_SESSION['role']       = $user['role'];

        header('Location: ' . ($user['role'] === 'student'
            ? '/ceit-complaint-system/student/dashboard.php'
            : '/ceit-complaint-system/admin/dashboard.php'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log In · CEIT CvSU</title>
<link rel="stylesheet" href="/ceit-complaint-system/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: time() ?>">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <h2>LOG IN</h2>
        <?php render_flash(); ?>
        <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" novalidate>
            <div class="form-group">
                <label>CvSU Email or Employee ID</label>
                <input type="text" name="identifier" value="<?= e($identifier) ?>" required autofocus>
                <div class="help-text">Students: use your CvSU email. Staff: use your Employee ID.</div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="password-wrap">
                    <input type="password" name="password" id="login-password" required>
                    <button type="button" class="password-toggle" data-target="login-password" aria-label="Show password">Show</button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">LOG IN</button>
        </form>
        <div class="auth-footer">Don't have an account? <a href="/ceit-complaint-system/auth/register.php">Create One Here</a></div>
    </div>
</div>
<script src="/ceit-complaint-system/assets/js/script.js?v=<?= @filemtime(__DIR__ . '/../assets/js/script.js') ?: time() ?>"></script>
</body>
</html>
