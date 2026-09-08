<?php
// Expects $pageTitle to be set by the including page, and (for logged-in pages)
// $activeNav to be set so the dropdown menu can highlight the current page.
$unread = is_logged_in() ? unread_notification_count($pdo, $_SESSION['user_id']) : 0;
$initials = is_logged_in() ? strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)) : '';
$myAvatar = null;
$navItems = [];
$dashboardHref = BASE_URL . '/auth/login.php';

if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT avatar_path FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $myAvatar = $stmt->fetchColumn() ?: null;

    $role = current_role();

    if ($role === 'student') {
        $dashboardHref = BASE_URL . '/student/dashboard.php';
        $navItems = [
            'dashboard' => ['label' => 'Dashboard', 'href' => BASE_URL . '/student/dashboard.php', 'icon' => '🏠'],
            'submit'    => ['label' => 'Submit',    'href' => BASE_URL . '/student/submit.php',    'icon' => '📝'],
            'track'     => ['label' => 'Track',     'href' => BASE_URL . '/student/track.php',     'icon' => '📍'],
            'settings'  => ['label' => 'Settings',  'href' => BASE_URL . '/student/settings.php',  'icon' => '⚙️'],
        ];
    } else {
        $dashboardHref = BASE_URL . '/admin/dashboard.php';
        $navItems = [
            'dashboard' => ['label' => 'Dashboard', 'href' => BASE_URL . '/admin/dashboard.php', 'icon' => '🏠'],
            'case'      => ['label' => 'Case',      'href' => BASE_URL . '/admin/cases.php',      'icon' => '🗂️'],
            'report'    => ['label' => 'Report',    'href' => BASE_URL . '/admin/report.php',     'icon' => '📊'],
            'team'      => ['label' => 'Team',      'href' => BASE_URL . '/admin/team.php',       'icon' => '👥'],
            'settings'  => ['label' => 'Settings',  'href' => BASE_URL . '/admin/settings.php',   'icon' => '⚙️'],
        ];
        if ($role === 'adviser') {
            // Advisers only handle cases assigned to them — no staff management, no full analytics.
            unset($navItems['report'], $navItems['team']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'CEIT CvSU Complaint and Inquiry Management') ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: time() ?>">
</head>
<body>
<header class="topbar">
    <div class="topbar-left">
        <?php if (is_logged_in()): ?>
        <div class="dropdown-wrap" id="menu-wrap">
            <button type="button" class="menu-btn" id="menu-btn" title="Menu" aria-haspopup="true" aria-expanded="false">☰</button>
            <div class="dropdown-panel dropdown-panel-left" id="menu-panel" hidden>
                <?php foreach ($navItems as $key => $item): ?>
                    <a href="<?= e($item['href']) ?>" class="nav-item <?= ($activeNav ?? '') === $key ? 'active' : '' ?>">
                        <span class="nav-icon"><?= $item['icon'] ?></span> <?= e($item['label']) ?>
                    </a>
                <?php endforeach; ?>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-item nav-logout">
                    <span class="nav-icon">🚪</span> Logout
                </a>
            </div>
        </div>
        <?php endif; ?>
        <a class="topbar-brand" href="<?= e($dashboardHref) ?>">
            <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Cavite State University logo" class="brand-logo"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <span class="brand-logo-fallback" aria-hidden="true">CvSU</span>
            <span class="brand-text">
                <span class="brand-title">CEIT CvSU</span>
                <span class="brand-sub">Complaint and Inquiry Management</span>
            </span>
        </a>
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
                    <img src="<?= BASE_URL ?>/<?= e($myAvatar) ?>" alt="">
                <?php else: ?>
                    <?= e($initials) ?>
                <?php endif; ?>
            </button>
            <div class="dropdown-panel dropdown-panel-right" id="profile-panel" hidden>
                <div class="dropdown-title"><?= e($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?><br><span class="text-muted"><?= e(ucfirst(current_role())) ?></span></div>
                <a class="dropdown-link" href="<?= e($role === 'student' ? BASE_URL . '/student/settings.php' : BASE_URL . '/admin/settings.php') ?>">⚙️ Settings</a>
                <a class="dropdown-link" href="<?= BASE_URL ?>/auth/logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</header>
<div class="app-shell">
