<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['admin', 'sysadmin', 'adviser']);

$myId  = $_SESSION['user_id'];
$role  = current_role();

$category = $_GET['category'] ?? '';
$status   = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';
$search   = trim($_GET['q'] ?? '');

$sql = "SELECT c.*, u.first_name, u.last_name,
               a.first_name AS assignee_first, a.last_name AS assignee_last
        FROM cases c
        JOIN users u ON u.user_id = c.user_id
        LEFT JOIN users a ON a.user_id = c.assigned_to
        WHERE 1=1";
$params = [];

// Advisers only ever see cases assigned to them; admins/sysadmins see everything.
if ($role === 'adviser') {
    $sql .= " AND c.assigned_to = ?";
    $params[] = $myId;
}

if ($category !== '') { $sql .= " AND c.category = ?"; $params[] = $category; }
if ($status !== '')   { $sql .= " AND c.status = ?"; $params[] = $status; }
if ($dateFrom !== '') { $sql .= " AND DATE(c.created_at) >= ?"; $params[] = $dateFrom; }
if ($dateTo !== '')   { $sql .= " AND DATE(c.created_at) <= ?"; $params[] = $dateTo; }
if ($search !== '')   {
    $sql .= " AND (c.title LIKE ? OR c.case_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}
$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cases = $stmt->fetchAll();

$categories = ['Academic Concerns', 'Technical Issues', 'Administrative', 'Facilities & Equipment', 'Others'];

$pageTitle = 'Case Management · CEIT CvSU';
$activeNav = 'case';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header">
    <h1>Case Management</h1>
    <p><?= $role === 'adviser' ? 'Complaints and inquiries assigned to you.' : 'Every complaint and inquiry submitted across CEIT.' ?></p>
</div>
<?php render_flash(); ?>

<div class="panel">
    <form method="get" class="filter-row" id="case-filter-form">
        <input type="text" name="q" class="search-input" placeholder="Search by Complaint ID, student name, or title..." value="<?= e($search) ?>">

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

        <select name="category" onchange="this.form.submit()">
            <option value="">All Category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" onchange="this.form.submit()">
            <option value="">All Status</option>
            <?php foreach (['Submitted', 'Under Review', 'In Progress', 'Resolved'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        <a href="/ceit-complaint-system/admin/cases.php" class="btn btn-outline btn-sm">Reset Filter</a>
    </form>

    <?php if (!$cases): ?>
        <div class="empty-state">No cases match your filters.</div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr><th>ID</th><th>Name</th><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Assigned To</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($cases as $c): ?>
            <tr>
                <td><?= e($c['case_code']) ?></td>
                <td><?= $c['is_anonymous'] ? 'Anonymous' : e($c['first_name'] . ' ' . $c['last_name']) ?></td>
                <td><?= e($c['title']) ?></td>
                <td><?= e($c['category']) ?></td>
                <td><span class="badge <?= priority_badge_class($c['priority']) ?>"><?= e($c['priority']) ?></span></td>
                <td><span class="badge <?= status_badge_class($c['status']) ?>"><?= e($c['status']) ?></span></td>
                <td><?= $c['assignee_first'] ? e($c['assignee_first'] . ' ' . $c['assignee_last']) : '<span class="text-muted">Unassigned</span>' ?></td>
                <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                <td><a class="link-btn" href="/ceit-complaint-system/admin/case.php?id=<?= $c['case_id'] ?>">View Details</a></td>
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
// Restore preset dropdown + field visibility on page load if a range is already active
(function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get('date_from') || params.get('date_to')) {
        document.getElementById('date-preset').value = 'custom';
        document.getElementById('custom-range-fields').style.display = '';
    }
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
