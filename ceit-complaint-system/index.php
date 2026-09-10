<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . (current_role() === 'student'
        ? '/ceit-complaint-system/student/dashboard.php'
        : '/ceit-complaint-system/admin/dashboard.php'));
} else {
    header('Location: /ceit-complaint-system/auth/login.php');
}
exit;
