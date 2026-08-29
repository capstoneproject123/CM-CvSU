<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['student']);

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT status, COUNT(*) c FROM cases WHERE user_id = ? GROUP BY status");
$stmt->execute([$userId]);
$counts = ['Submitted' => 0, 'Under Review' => 0, 'In Progress' => 0, 'Resolved' => 0];
foreach ($stmt->fetchAll() as $row) { $counts[$row['status']] = (int) $row['c']; }
$total = array_sum($counts);
$pending = $counts['Submitted'] + $counts['Under Review'];

$stmt = $pdo->prepare("SELECT * FROM cases WHERE user_id = ? ORDER BY created_at DESC LIMIT 8");
$stmt->execute([$userId]);
$cases = $stmt->fetchAll();

$pageTitle = 'Dashboard · CEIT CvSU';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header">
    <h1>Track Complaints</h1>
    <p>College of Engineering and Information Technology</p>
</div>
<?php render_flash(); ?>

<div class="stat-grid">
    <div class="stat-card">
        <div><div class="stat-label">In Progress</div><div class="stat-value"><?= $counts['In Progress'] ?></div><div class="stat-sub">Being resolved</div></div>
        <div class="stat-icon">⏳</div>
    </div>
    <div class="stat-card">
        <div><div class="stat-label">Total Complaints</div><div class="stat-value"><?= $total ?></div><div class="stat-sub">All time</div></div>
        <div class="stat-icon">📁</div>
    </div>
    <div class="stat-card">
        <div><div class="stat-label">Resolved</div><div class="stat-value"><?= $counts['Resolved'] ?></div><div class="stat-sub">Completed</div></div>
        <div class="stat-icon">✅</div>
    </div>
    <div class="stat-card">
        <div><div class="stat-label">Pending</div><div class="stat-value"><?= $pending ?></div><div class="stat-sub">Awaiting review</div></div>
        <div class="stat-icon">🕒</div>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <h2>Your Complaints</h2>
        <a href="/ceit-complaint-system/student/track.php" class="link-btn">View All →</a>
    </div>
    <?php if (!$cases): ?>
        <div class="empty-state">You haven't submitted any complaints or inquiries yet.<br><a href="/ceit-complaint-system/student/submit.php" class="link-btn">Submit one now →</a></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>ID</th><th>Title</th><th>Date</th><th>Type</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($cases as $c): ?>
            <tr>
                <td><?= e($c['case_code']) ?></td>
                <td><?= e($c['title']) ?></td>
                <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                <td><span class="badge <?= $c['type'] === 'complaint' ? 'badge-complaint' : 'badge-inquiry' ?>"><?= ucfirst($c['type']) ?></span></td>
                <td><span class="badge <?= status_badge_class($c['status']) ?>"><?= e($c['status']) ?></span></td>
                <td><a class="link-btn" href="/ceit-complaint-system/student/case.php?id=<?= $c['case_id'] ?>">View Detail</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
