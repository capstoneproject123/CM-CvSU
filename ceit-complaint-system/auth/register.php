<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';

// NOTE: the wireframe shows a Student/Admin toggle on the public registration form.
// Adviser accounts are NOT self-registered here — they're created by an Admin from
// the Team page (see admin/team.php), since advisers need to be vetted staff.

$departments = ['BS Information Technology', 'BS Computer Science', 'BS Computer Engineering', 'BS Information Systems'];
$yearLevels  = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$cvsuDomains = ['cvsu.edu.ph']; // any subdomain (e.g. ceit.cvsu.edu.ph, cvsu.edu.ph) is accepted

$errors = [];
$firstName = $lastName = $email = $employeeId = $department = $yearLevel = '';
$role = 'student';

function is_cvsu_email(string $email, array $domains): bool {
    $at = strrchr($email, '@');
    if ($at === false) return false;
    $host = strtolower(ltrim($at, '@'));
    foreach ($domains as $d) {
        if ($host === $d || str_ends_with($host, '.' . $d)) return true;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName  = trim($_POST['first_name'] ?? '');
    $lastName   = trim($_POST['last_name'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $role       = ($_POST['role'] ?? 'student') === 'admin' ? 'admin' : 'student';

    if ($firstName === '' || $lastName === '') $errors[] = 'First and last name are required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if ($role === 'student') {
        $email      = trim($_POST['email'] ?? '');
        $department = $_POST['department'] ?? '';
        $yearLevel  = $_POST['year_level'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (!is_cvsu_email($email, $cvsuDomains)) {
            $errors[] = 'Please use your official CvSU email address (e.g. yourname@cvsu.edu.ph).';
        }
        if (!in_array($department, $departments, true)) $errors[] = 'Please select your college department.';
        if (!in_array($yearLevel, $yearLevels, true)) $errors[] = 'Please select your year level.';

        if (!$errors) {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) $errors[] = 'An account with that CvSU email already exists.';
        }
    } else {
        $employeeId = trim($_POST['employee_id'] ?? '');
        if ($employeeId === '') $errors[] = 'Employee ID is required.';

        if (!$errors) {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE employee_id = ?");
            $stmt->execute([$employeeId]);
            if ($stmt->fetch()) $errors[] = 'An account with that Employee ID already exists.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($role === 'student') {
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, department, year_level, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, 'student')");
            $stmt->execute([$firstName, $lastName, $email, $department, $yearLevel, $hash]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, employee_id, password_hash, role) VALUES (?, ?, ?, ?, 'admin')");
            $stmt->execute([$firstName, $lastName, $employeeId, $hash]);
        }

        flash_set('success', 'Account created successfully. Please log in.');
        header('Location: /ceit-complaint-system/auth/login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account · CEIT CvSU</title>
<link rel="stylesheet" href="/ceit-complaint-system/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: time() ?>">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <h2>CREATE ACCOUNT</h2>
        <?php render_flash(); ?>
        <?php if ($errors): ?>
            <div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div>
        <?php endif; ?>
        <form method="post" novalidate id="register-form">
            <div class="role-toggle">
                <label><input type="radio" name="role" value="student" data-role-toggle <?= $role === 'student' ? 'checked' : '' ?>> Student</label>
                <label><input type="radio" name="role" value="admin" data-role-toggle <?= $role === 'admin' ? 'checked' : '' ?>> Admin</label>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= e($firstName) ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= e($lastName) ?>" required>
                </div>
            </div>

            <div data-role-panel="student" style="<?= $role === 'admin' ? 'display:none' : '' ?>">
                <div class="form-group">
                    <label>CvSU Email Address</label>
                    <input type="email" name="email" value="<?= e($email) ?>" placeholder="yourname@cvsu.edu.ph" <?= $role === 'student' ? 'required' : '' ?>>
                    <div class="help-text">Must be your official CvSU email address.</div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>College Department</label>
                        <select name="department">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= e($d) ?>" <?= $department === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Year Level</label>
                        <select name="year_level">
                            <option value="">Select Year Level</option>
                            <?php foreach ($yearLevels as $y): ?>
                                <option value="<?= e($y) ?>" <?= $yearLevel === $y ? 'selected' : '' ?>><?= e($y) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div data-role-panel="admin" style="<?= $role === 'student' ? 'display:none' : '' ?>">
                <div class="form-group">
                    <label>Employee ID</label>
                    <input type="text" name="employee_id" value="<?= e($employeeId) ?>" <?= $role === 'admin' ? 'required' : '' ?>>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="password-wrap">
                    <input type="password" name="password" id="reg-password" required minlength="8">
                    <button type="button" class="password-toggle" data-target="reg-password" aria-label="Show password">Show</button>
                </div>
                <div class="help-text">At least 8 characters.</div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <div class="password-wrap">
                    <input type="password" name="confirm_password" id="reg-confirm-password" required minlength="8">
                    <button type="button" class="password-toggle" data-target="reg-confirm-password" aria-label="Show password">Show</button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">REGISTER</button>
        </form>
        <div class="auth-footer">Already have an account? <a href="/ceit-complaint-system/auth/login.php">Log In Here</a></div>
    </div>
</div>
<script src="/ceit-complaint-system/assets/js/script.js?v=<?= @filemtime(__DIR__ . '/../assets/js/script.js') ?: time() ?>"></script>
</body>
</html>
