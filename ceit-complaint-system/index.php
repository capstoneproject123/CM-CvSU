<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . (current_role() === 'student'
        ? '' . BASE_URL . '/student/dashboard.php'
        : '' . BASE_URL . '/admin/dashboard.php'));
} else {
    header('Location: ' . BASE_URL . '/auth/login.php');
}
exit;
