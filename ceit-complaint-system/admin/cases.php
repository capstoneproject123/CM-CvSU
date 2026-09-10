<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['admin', 'sysadmin', 'adviser']);

$myId = $_SESSION['user_id'];
$role = current_role();

$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$search = trim($_GET['q'] ?? '');

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

// Simple equality/range filters, applied in one pass instead of one if-block each.
$filters = [
    'c.category = ?' => $category,
    'c.status = ?' => $status,
    'DATE(c.created_at) >= ?' => $dateFrom,
    'DATE(c.created_at) <= ?' => $dateTo,
];
foreach ($filters as $clause => $value) {
    if ($value !== '') {
        $sql .= " AND $clause";
        $params[] = $value;
    }
}

if ($search !== '') {
    $sql .= " AND (c.title LIKE ? OR c.case_code LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like, $like);
}
$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cases = $stmt->fetchAll();

// Which of ID / Name / Title actually contain the search term, across the
// returned rows — so a search only pins the column(s) it really matched,
// instead of always grouping all three together.
$matchedFields = [];
if ($search !== '') {
    foreach ($cases as $c) {
        $fullName = trim($c['first_name'] . ' ' . $c['last_name']);
        if (stripos($c['case_code'], $search) !== false)
            $matchedFields['id'] = true;
        if (!$c['is_anonymous'] && stripos($fullName, $search) !== false)
            $matchedFields['name'] = true;
        if (stripos($c['title'], $search) !== false)
            $matchedFields['title'] = true;
    }
}
$matchedFields = array_keys($matchedFields);

$categories = ['Academic Concerns', 'Technical Issues', 'Administrative', 'Facilities & Equipment', 'Others'];

// Shared renderer for the two badge-style columns (priority, status).
function badge_cell($value, callable $classFn)
{
    return '<span class="badge ' . $classFn($value) . '">' . e($value) . '</span>';
}

// --- Column definitions: label + how to render each cell -----------------
// This list also defines the DEFAULT column order.
$columnDefs = [
    'id' => ['label' => 'ID', 'cell' => fn($c) => e($c['case_code'])],
    'name' => ['label' => 'Name', 'cell' => fn($c) => $c['is_anonymous'] ? 'Anonymous' : e($c['first_name'] . ' ' . $c['last_name'])],
    'title' => ['label' => 'Title', 'cell' => fn($c) => e($c['title'])],
    'category' => ['label' => 'Category', 'cell' => fn($c) => e($c['category'])],
    'priority' => ['label' => 'Priority', 'cell' => fn($c) => badge_cell($c['priority'], 'priority_badge_class')],
    'status' => ['label' => 'Status', 'cell' => fn($c) => badge_cell($c['status'], 'status_badge_class')],
    'assigned' => ['label' => 'Assigned To', 'cell' => fn($c) => $c['assignee_first'] ? e($c['assignee_first'] . ' ' . $c['assignee_last']) : '<span class="text-muted">Unassigned</span>'],
    'date' => ['label' => 'Date', 'cell' => fn($c) => date('M j, Y', strtotime($c['created_at']))],
];
$defaultOrder = array_keys($columnDefs);

// --- Work out which column(s) should be pinned to the front --------------
// 'search' pins only the column(s) that actually matched the search term
// (falls back to all three if, for some reason, none were detected).
$pin = $_GET['pin'] ?? '';
if ($pin === 'search') {
    $front = !empty($matchedFields)
        ? array_values(array_intersect(['id', 'name', 'title'], $matchedFields))
        : ['id', 'name', 'title'];
} elseif (in_array($pin, $defaultOrder, true)) {
    $front = [$pin];
} else {
    $pin = '';
    $front = [];
}
$orderedKeys = array_merge($front, array_values(array_diff($defaultOrder, $front)));

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
        <input type="hidden" name="pin" id="pin-field" value="<?= e($pin) ?>">

        <input type="text" name="q" class="search-input" placeholder="Search by Complaint ID, student name, or title..." value="<?= e($search) ?>" oninput="setPin('search')">

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

        <select name="category" onchange="setPin('category'); this.form.submit()">
            <option value="">All Category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" onchange="setPin('status'); this.form.submit()">
            <option value="">All Status</option>
            <?php foreach (['Submitted', 'Under Review', 'In Progress', 'Resolved'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        <a href="/ceit-complaint-system/admin/cases.php" class="btn btn-outline btn-sm">Reset Filter</a>
    </form>

    <?php if ($search !== ''): ?>
        <p class="text-muted" style="margin: 0 0 8px;">
            Showing results for "<?= e($search) ?>"
        </p>
    <?php endif; ?>

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
                <td><a class="link-btn" href="/ceit-complaint-system/admin/case.php?id=<?= $c['case_id'] ?>">View Details</a></td>
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