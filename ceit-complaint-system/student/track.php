<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['student']);

$userId = $_SESSION['user_id'];

$statusFilter = $_GET['status'] ?? '';
$typeFilter   = $_GET['type'] ?? '';
$dateFrom     = $_GET['date_from'] ?? '';
$dateTo       = $_GET['date_to'] ?? '';
$search       = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM cases WHERE user_id = ?";
$params = [$userId];

if ($statusFilter !== '') { $sql .= " AND status = ?"; $params[] = $statusFilter; }
if ($typeFilter !== '')   { $sql .= " AND type = ?"; $params[] = $typeFilter; }
if ($dateFrom !== '')     { $sql .= " AND DATE(created_at) >= ?"; $params[] = $dateFrom; }
if ($dateTo !== '')       { $sql .= " AND DATE(created_at) <= ?"; $params[] = $dateTo; }
if ($search !== '')       { $sql .= " AND (title LIKE ? OR case_code LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cases = $stmt->fetchAll();

$pageTitle = 'Track · CEIT CvSU';
$activeNav = 'track';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header">
    <h1>Track Your Cases</h1>
    <p>All complaints and inquiries you've submitted.</p>
</div>
<?php render_flash(); ?>

<div class="panel">
    <form method="get" class="filter-row" id="case-filter-form">
        <input type="text" name="q" class="search-input" placeholder="Search by ID or title..." value="<?= e($search) ?>">
        <select id="date-preset" onchange="applyDatePreset(this.value)">
            <option value="">Date: Any time</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
            <option value="custom">Custom Range…</option>
        </select>
        <span id="custom-range-fields" style="display:none;">
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>" onchange="this.form.submit()">
            <span class="text-muted">to</span>
            <input type="date" name="date_to" value="<?= e($dateTo) ?>" onchange="this.form.submit()">
        </span>
        <select name="type" onchange="this.form.submit()">
            <option value="">All Types</option>
            <option value="complaint" <?= $typeFilter === 'complaint' ? 'selected' : '' ?>>Complaint</option>
            <option value="inquiry" <?= $typeFilter === 'inquiry' ? 'selected' : '' ?>>Inquiry</option>
        </select>
        <select name="status" onchange="this.form.submit()">
            <option value="">All Status</option>
            <?php foreach (['Submitted', 'Under Review', 'In Progress', 'Resolved'] as $s): ?>
                <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
    </form>

    <?php if (!$cases): ?>
        <div class="empty-state">No cases match your filters.</div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>ID</th><th>Title</th><th>Category</th><th>Date</th><th>Type</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($cases as $c): ?>
            <tr>
                <td><?= e($c['case_code']) ?></td>
                <td><?= e($c['title']) ?></td>
                <td><?= e($c['category']) ?></td>
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

<script>
function applyDatePreset(preset) {
    var form = document.getElementById('case-filter-form');
    var customFields = document.getElementById('custom-range-fields');
    var fromInput = form.querySelector('[name=date_from]');
    var toInput = form.querySelector('[name=date_to]');
    var today = new Date();

    if (preset === 'custom') {
        customFields.style.display = '';
        return;
    }
    customFields.style.display = (preset === '') ? 'none' : '';

    if (preset === 'month') {
        var first = new Date(today.getFullYear(), today.getMonth(), 1);
        var last = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        fromInput.value = first.toISOString().slice(0, 10);
        toInput.value = last.toISOString().slice(0, 10);
        form.submit();
    } else if (preset === 'year') {
        fromInput.value = today.getFullYear() + '-01-01';
        toInput.value = today.getFullYear() + '-12-31';
        form.submit();
    } else if (preset === '') {
        fromInput.value = '';
        toInput.value = '';
        form.submit();
    }
}
(function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get('date_from') || params.get('date_to')) {
        document.getElementById('date-preset').value = 'custom';
        document.getElementById('custom-range-fields').style.display = '';
    }
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
