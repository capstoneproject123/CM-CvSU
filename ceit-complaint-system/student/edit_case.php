<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['student']);

// Editing submitted cases is no longer supported — redirect back to Track.
flash_set('error', 'Editing submissions is no longer available.');
header('Location: ' . BASE_URL . '/student/track.php');
exit;