<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['student']);

$userId = $_SESSION['user_id'];

$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM cases WHERE user_id = ?";
$params = [$userId];

// Simple equality/range filters, applied in one pass instead of one if-block each.
$filters = [
    'status = ?' => $statusFilter,
    'type = ?' => $typeFilter,
    'DATE(created_at) >= ?' => $dateFrom,
    'DATE(created_at) <= ?' => $dateTo,
];
foreach ($filters as $clause => $value) {
    if ($value !== '') {
        $sql .= " AND $clause";
        $params[] = $value;
    }
}

if ($search !== '') {
    $sql .= " AND (title LIKE ? OR case_code LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cases = $stmt->fetchAll();

// Which of ID / Title actually contain the search term, across the returned
// rows — so a search only pins the column(s) it really matched.
$matchedFields = [];
if ($search !== '') {
    foreach ($cases as $c) {
        if (stripos($c['case_code'], $search) !== false)
            $matchedFields['id'] = true;
        if (stripos($c['title'], $search) !== false)
            $matchedFields['title'] = true;
    }
}
$matchedFields = array_keys($matchedFields);

// Shared renderer for the badge-style columns (type, status).
// $label lets a column show something other than the raw value (e.g. "Complaint" instead of "complaint").
function badge_cell($value, callable $classFn, $label = null)
{
    return '<span class="badge ' . $classFn($value) . '">' . ($label ?? e($value)) . '</span>';
}

// --- Column definitions: label + how to render each cell -----------------
// This list also defines the DEFAULT column order.
$columnDefs = [
    'id' => ['label' => 'ID', 'cell' => fn($c) => e($c['case_code'])],
    'title' => ['label' => 'Title', 'cell' => fn($c) => e($c['title'])],
    'category' => ['label' => 'Category', 'cell' => fn($c) => e($c['category'])],
    'date' => ['label' => 'Date', 'cell' => fn($c) => date('M j, Y', strtotime($c['created_at']))],
    'type' => ['label' => 'Type', 'cell' => fn($c) => badge_cell($c['type'], fn($v) => $v === 'complaint' ? 'badge-complaint' : 'badge-inquiry', ucfirst($c['type']))],
    'status' => ['label' => 'Status', 'cell' => fn($c) => badge_cell($c['status'], 'status_badge_class')],
];
$defaultOrder = array_keys($columnDefs);

// --- Work out which column(s) should be pinned to the front --------------
// 'search' pins only the column(s) that actually matched the search term
// (falls back to both if, for some reason, neither was detected).
$pin = $_GET['pin'] ?? '';
if ($pin === 'search') {
    $front = !empty($matchedFields)
        ? array_values(array_intersect(['id', 'title'], $matchedFields))
        : ['id', 'title'];
} elseif (in_array($pin, $defaultOrder, true)) {
    $front = [$pin];
} else {
    $pin = '';
    $front = [];
}
$orderedKeys = array_merge($front, array_values(array_diff($defaultOrder, $front)));

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
        <input type="hidden" name="pin" id="pin-field" value="<?= e($pin) ?>">

        <input type="text" name="q" class="search-input" placeholder="Search by ID or title..." value="<?= e($search) ?>" oninput="setPin('search')">
        <select id="date-preset" onchange="applyDatePreset(this.value)">
            <option value="">Date: Any time</option>
            <option value="month">This Month</option>
            <option value="year">This Year</option>
            <option value="custom">Custom Range…</option>
        </select>
        <span id="custom-range-fields" style="display:none;">
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>" onchange="setPin('date'); this.form.submit()">
            <span class="text-muted">to</span>
            <input type="date" name="date_to" value="<?= e($dateTo) ?>" onchange="setPin('date'); this.form.submit()">
        </span>
        <select name="type" onchange="setPin('type'); this.form.submit()">
            <option value="">All Types</option>
            <option value="complaint" <?= $typeFilter === 'complaint' ? 'selected' : '' ?>>Complaint</option>
            <option value="inquiry" <?= $typeFilter === 'inquiry' ? 'selected' : '' ?>>Inquiry</option>
        </select>
        <select name="status" onchange="setPin('status'); this.form.submit()">
            <option value="">All Status</option>
            <?php foreach (['Submitted', 'Under Review', 'In Progress', 'Resolved'] as $s): ?>
                <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        <a href="/ceit-complaint-system/student/track.php" class="btn btn-outline btn-sm">Reset Filter</a>
    </form>

    <?php if (!$cases): ?>
        <div class="empty-state">No cases match your filters.</div>
    <?php else: ?>
    <table class="data-table">
        <thead>
        <tr>
            <?php foreach ($orderedKeys as $key): ?>
                <?php $isPinned = ($pin === $key) || ($pin === 'search' && in_array($key, $front, true)); ?>
                <th class="<?= $isPinned ? 'th-pinned' : '' ?>"><?= e($columnDefs[$key]['label']) ?></th>
            <?php endforeach; ?>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($cases as $c): ?>
            <tr>
                <?php foreach ($orderedKeys as $key): ?>
                    <td><?= $columnDefs[$key]['cell']($c) ?></td>
                <?php endforeach; ?>
                <td><a class="link-btn" href="/ceit-complaint-system/student/case.php?id=<?= $c['case_id'] ?>">View Detail</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<style>
th.th-pinned {
    background: #eaf3ff;
}
</style>

<script>
function setPin(key) {
    var f = document.getElementById('pin-field');
    if (f) f.value = key;
}

function applyDatePreset(preset) {
    var form = document.getElementById('case-filter-form');
    var customFields = document.getElementById('custom-range-fields');
    var fromInput = form.querySelector('[name=date_from]');
    var toInput = form.querySelector('[name=date_to]');

    customFields.style.display = (preset === '') ? 'none' : '';
    if (preset === 'custom') return;

    var today = new Date();
    var ranges = {
        month: [new Date(today.getFullYear(), today.getMonth(), 1), new Date(today.getFullYear(), today.getMonth() + 1, 0)],
        year: [new Date(today.getFullYear(), 0, 1), new Date(today.getFullYear(), 11, 31)],
    };
    var range = ranges[preset] || [null, null];
    fromInput.value = range[0] ? range[0].toISOString().slice(0, 10) : '';
    toInput.value = range[1] ? range[1].toISOString().slice(0, 10) : '';
    setPin(preset ? 'date' : '');
    form.submit();
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