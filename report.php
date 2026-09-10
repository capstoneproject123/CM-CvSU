<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['admin', 'sysadmin']);

$total = (int) $pdo->query("SELECT COUNT(*) FROM cases")->fetchColumn();
$resolved = (int) $pdo->query("SELECT COUNT(*) FROM cases WHERE status = 'Resolved'")->fetchColumn();
$resolutionRate = $total > 0 ? round(($resolved / $total) * 100) : 0;

$byCategory = $pdo->query("SELECT category, COUNT(*) c FROM cases GROUP BY category ORDER BY c DESC")->fetchAll();
$byStatus   = $pdo->query("SELECT status, COUNT(*) c FROM cases GROUP BY status")->fetchAll();
$byType     = $pdo->query("SELECT type, COUNT(*) c FROM cases GROUP BY type")->fetchAll();
$byPriority = $pdo->query("SELECT priority, COUNT(*) c FROM cases GROUP BY priority")->fetchAll();

$maxCategory = max(array_column($byCategory, 'c') ?: [1]);

$pageTitle = 'Report · CEIT CvSU';
$activeNav = 'report';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header">
    <h1>Reports &amp; Analytics</h1>
    <p>Summary of complaint and inquiry activity across CEIT.</p>
</div>
<?php render_flash(); ?>

<div class="stat-grid">
    <div class="stat-card"><div><div class="stat-label">Total Cases</div><div class="stat-value"><?= $total ?></div></div><div class="stat-icon">📁</div></div>
    <div class="stat-card"><div><div class="stat-label">Resolved</div><div class="stat-value"><?= $resolved ?></div></div><div class="stat-icon">✅</div></div>
    <div class="stat-card"><div><div class="stat-label">Resolution Rate</div><div class="stat-value"><?= $resolutionRate ?>%</div></div><div class="stat-icon">📈</div></div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Cases by Category</h2></div>
    <?php foreach ($byCategory as $row): ?>
        <div style="margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                <span><?= e($row['category']) ?></span><span><?= $row['c'] ?></span>
            </div>
            <div style="background:var(--gray-100);border-radius:6px;height:8px;">
                <div style="background:var(--green);width:<?= $maxCategory ? round(($row['c'] / $maxCategory) * 100) : 0 ?>%;height:8px;border-radius:6px;"></div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$byCategory): ?><div class="empty-state">No data yet.</div><?php endif; ?>
</div>

<div class="form-row">
    <div class="panel">
        <div class="panel-head"><h2>By Status</h2></div>
        <?php foreach ($byStatus as $row): ?>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100);">
                <span class="badge <?= status_badge_class($row['status']) ?>"><?= e($row['status']) ?></span>
                <strong><?= $row['c'] ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>By Type / Priority</h2></div>
        <?php foreach ($byType as $row): ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;">
                <span><?= ucfirst($row['type']) ?></span><strong><?= $row['c'] ?></strong>
            </div>
        <?php endforeach; ?>
        <hr style="border:none;border-top:1px solid var(--gray-100);margin:8px 0;">
        <?php foreach ($byPriority as $row): ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;">
                <span class="badge <?= priority_badge_class($row['priority']) ?>"><?= e($row['priority']) ?></span><strong><?= $row['c'] ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="panel">
    <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print Report</button>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
