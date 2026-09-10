<?php
require __DIR__ . '/../config/db.php';
$_SESSION = [];
session_destroy();
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
