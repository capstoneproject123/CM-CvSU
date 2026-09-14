<?php
// The nav items ($navItems, $activeNav) were already computed in includes/header.php.
// They're rendered TWICE from the same data:
//   1. Here, as the permanent left sidebar — shown on desktop only.
//   2. In header.php's hamburger dropdown — shown on mobile only.
// CSS (see the @media block in style.css) decides which one is actually visible.
?>
<aside class="sidebar">
    <nav>
        <?php foreach ($navItems as $key => $item): ?>
            <a href="<?= e($item['href']) ?>" class="nav-item <?= ($activeNav ?? '') === $key ? 'active' : '' ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span> <?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-item nav-logout">
            <span class="nav-icon">🚪</span> Logout
        </a>
    </nav>
</aside>
<main class="main-content">
