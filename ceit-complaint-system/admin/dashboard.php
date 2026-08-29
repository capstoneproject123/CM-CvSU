<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['admin', 'sysadmin', 'adviser']);

$role = current_role();
$myId = $_SESSION['user_id'];

if ($role === 'adviser') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE type = 'complaint' AND assigned_to = ?");
    $stmt->execute([$myId]);
    $totalComplaints = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE type = 'inquiry' AND assigned_to = ?");
    $stmt->execute([$myId]);
    $totalInquiries = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE status IN ('Submitted','Under Review') AND assigned_to = ?");
    $stmt->execute([$myId]);
    $totalPending = (int) $stmt->fetchColumn();

    $totalUsers = null; // not meaningful for an adviser's scoped view
} else {
    $totalUsers      = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $totalComplaints = (int) $pdo->query("SELECT COUNT(*) FROM cases WHERE type = 'complaint'")->fetchColumn();
    $totalInquiries  = (int) $pdo->query("SELECT COUNT(*) FROM cases WHERE type = 'inquiry'")->fetchColumn();
    $totalPending    = (int) $pdo->query("SELECT COUNT(*) FROM cases WHERE status IN ('Submitted','Under Review')")->fetchColumn();
}

$recentSql = "SELECT c.*, u.first_name, u.last_name FROM cases c JOIN users u ON u.user_id = c.user_id";
$recentParams = [];
if ($role === 'adviser') {
    $recentSql .= " WHERE c.assigned_to = ?";
    $recentParams[] = $myId;
}
$recentSql .= " ORDER BY c.created_at DESC LIMIT 8";
$stmt = $pdo->prepare($recentSql);
$stmt->execute($recentParams);
$recent = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard · CEIT CvSU';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header">
    <h1>Analytical Overview</h1>
    <p>Welcome back, <?= e($_SESSION['first_name']) ?>. <?= $role === 'adviser' ? 'Here are the cases assigned to you.' : 'Here is your overview for today.' ?></p>
</div>
<?php render_flash(); ?>

<div class="stat-grid">
    <?php if ($totalUsers !== null): ?>
    <div class="stat-card"><div><div class="stat-label">Total Users</div><div class="stat-value"><?= $totalUsers ?></div><div class="stat-sub">Students</div></div><div class="stat-icon">👥</div></div>
    <?php endif; ?>
    <div class="stat-card"><div><div class="stat-label">Total Complaints</div><div class="stat-value"><?= $totalComplaints ?></div><div class="stat-sub"><?= $role === 'adviser' ? 'Assigned to you' : 'All time' ?></div></div><div class="stat-icon">📁</div></div>
    <div class="stat-card"><div><div class="stat-label">Total Inquiries</div><div class="stat-value"><?= $totalInquiries ?></div><div class="stat-sub"><?= $role === 'adviser' ? 'Assigned to you' : 'All time' ?></div></div><div class="stat-icon">❓</div></div>
    <div class="stat-card"><div><div class="stat-label">Total Pending</div><div class="stat-value"><?= $totalPending ?></div><div class="stat-sub">Awaiting review</div></div><div class="stat-icon">🕒</div></div>
</div>

<div class="panel">
    <div class="panel-head">
        <h2>Recent Cases</h2>
        <a href="/ceit-complaint-system/admin/cases.php" class="link-btn">View All →</a>
    </div>
    <?php if (!$recent): ?>
        <div class="empty-state"><?= $role === 'adviser' ? 'No cases have been assigned to you yet.' : 'No cases have been submitted yet.' ?></div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>ID</th><th>Name</th><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent as $c): ?>
            <tr>
                <td><?= e($c['case_code']) ?></td>
                <td><?= $c['is_anonymous'] ? 'Anonymous' : e($c['first_name'] . ' ' . $c['last_name']) ?></td>
                <td><?= e($c['title']) ?></td>
                <td><?= e($c['category']) ?></td>
                <td><span class="badge <?= priority_badge_class($c['priority']) ?>"><?= e($c['priority']) ?></span></td>
                <td><span class="badge <?= status_badge_class($c['status']) ?>"><?= e($c['status']) ?></span></td>
                <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                <td><a class="link-btn" href="/ceit-complaint-system/admin/case.php?id=<?= $c['case_id'] ?>">View Details</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
