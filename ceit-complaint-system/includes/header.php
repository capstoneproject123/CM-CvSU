<?php
// Expects $pageTitle to be set by the including page.
$unread = is_logged_in() ? unread_notification_count($pdo, $_SESSION['user_id']) : 0;
$initials = is_logged_in() ? strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)) : '';
$myAvatar = null;
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT avatar_path FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $myAvatar = $stmt->fetchColumn() ?: null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'CEIT CvSU Complaint and Inquiry') ?></title>
<link rel="stylesheet" href="/ceit-complaint-system/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: time() ?>">
</head>
<body>
<header class="topbar">
    <div class="topbar-brand">
        <img src="/ceit-complaint-system/assets/img/logo.png" alt="Cavite State University logo" class="brand-logo"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <span class="brand-logo-fallback" aria-hidden="true">CvSU</span>
        <span class="brand-text">
            <span class="brand-title">CEIT CvSU</span>
            <span class="brand-sub">Complaint and Inquiry</span>
        </span>
    </div>
    <?php if (is_logged_in()): ?>
    <div class="topbar-actions">
        <div class="dropdown-wrap" id="notif-wrap">
            <button type="button" class="bell" id="notif-btn" title="Notifications" aria-haspopup="true" aria-expanded="false">
                🔔
                <span class="bell-badge" id="notif-badge" style="<?= $unread > 0 ? '' : 'display:none;' ?>"><?= $unread > 0 ? $unread : '' ?></span>
            </button>
            <div class="dropdown-panel" id="notif-panel" hidden>
                <div class="dropdown-title">Notifications</div>
                <div id="notif-list" class="notif-list">
                    <div class="empty-state" style="padding:20px;">Loading…</div>
                </div>
            </div>
        </div>
        <div class="dropdown-wrap" id="profile-wrap">
            <button type="button" class="avatar" id="profile-btn" title="<?= e($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>" aria-haspopup="true" aria-expanded="false">
                <?php if ($myAvatar): ?>
                    <img src="/ceit-complaint-system/<?= e($myAvatar) ?>" alt="">
                <?php else: ?>
                    <?= e($initials) ?>
                <?php endif; ?>
            </button>
            <div class="dropdown-panel dropdown-panel-right" id="profile-panel" hidden>
                <div class="dropdown-title"><?= e($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?><br><span class="text-muted"><?= e(ucfirst(current_role())) ?></span></div>
                <a class="dropdown-link" href="<?= current_role() === 'student' ? '/ceit-complaint-system/student/settings.php' : '/ceit-complaint-system/admin/settings.php' ?>">⚙️ Settings</a>
                <a class="dropdown-link" href="/ceit-complaint-system/auth/logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</header>
<div class="app-shell">
