<?php
// Expects $activeNav to be set by the including page (e.g. 'dashboard', 'submit', 'track'...)
$role = current_role();

$studentNav = [
    'dashboard' => ['label' => 'Dashboard', 'href' => '/ceit-complaint-system/student/dashboard.php', 'icon' => '🏠'],
    'submit'    => ['label' => 'Submit',    'href' => '/ceit-complaint-system/student/submit.php',    'icon' => '📝'],
    'track'     => ['label' => 'Track',     'href' => '/ceit-complaint-system/student/track.php',     'icon' => '📍'],
    'settings'  => ['label' => 'Settings',  'href' => '/ceit-complaint-system/student/settings.php',  'icon' => '⚙️'],
];

$adminNav = [
    'dashboard' => ['label' => 'Dashboard', 'href' => '/ceit-complaint-system/admin/dashboard.php', 'icon' => '🏠'],
    'case'      => ['label' => 'Case',      'href' => '/ceit-complaint-system/admin/cases.php', 'icon' => '🗂️'],
    'report'    => ['label' => 'Report',    'href' => '/ceit-complaint-system/admin/report.php',    'icon' => '📊'],
    'team'      => ['label' => 'Team',      'href' => '/ceit-complaint-system/admin/team.php',      'icon' => '👥'],
    'settings'  => ['label' => 'Settings',  'href' => '/ceit-complaint-system/admin/settings.php',  'icon' => '⚙️'],
];

if ($role === 'adviser') {
    // Advisers only handle cases assigned to them — no staff management, no full analytics.
    unset($adminNav['report'], $adminNav['team']);
}

$nav = $role === 'student' ? $studentNav : $adminNav;
?>
<aside class="sidebar">
    <nav>
        <?php foreach ($nav as $key => $item): ?>
            <a href="<?= e($item['href']) ?>" class="nav-item <?= ($activeNav ?? '') === $key ? 'active' : '' ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span> <?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>
        <a href="/ceit-complaint-system/auth/logout.php" class="nav-item nav-logout">
            <span class="nav-icon">🚪</span> Logout
        </a>
    </nav>
</aside>
<main class="main-content">
