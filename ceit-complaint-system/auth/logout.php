<?php
require __DIR__ . '/../config/db.php';
$_SESSION = [];
session_destroy();
header('Location: /ceit-complaint-system/auth/login.php');
exit;
