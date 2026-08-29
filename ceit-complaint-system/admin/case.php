<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['admin', 'sysadmin', 'adviser']);

$myId = $_SESSION['user_id'];
$role = current_role();
$caseId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT c.*, u.first_name, u.last_name,
                               sa.first_name AS suggested_first, sa.last_name AS suggested_last
                        FROM cases c
                        JOIN users u ON u.user_id = c.user_id
                        LEFT JOIN users sa ON sa.user_id = c.suggested_adviser_id
                        WHERE c.case_id = ?");
$stmt->execute([$caseId]);
$case = $stmt->fetch();

if (!$case) {
    flash_set('error', 'Case not found.');
    header('Location: /ceit-complaint-system/admin/cases.php');
    exit;
}

// Advisers never see cases that aren't assigned to them, full stop.
if ($role === 'adviser' && (int) $case['assigned_to'] !== $myId) {
    flash_set('error', 'That case is not assigned to you.');
    header('Location: /ceit-complaint-system/admin/cases.php');
    exit;
}

$hasChatAccess = can_access_case($case, $myId, $role);
$canAssign = in_array($role, ['admin', 'sysadmin'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_message' && $hasChatAccess) {
        $msg = trim($_POST['message'] ?? '');
        if ($msg !== '') {
            $pdo->prepare("INSERT INTO messages (case_id, sender_id, message) VALUES (?, ?, ?)")
                ->execute([$caseId, $myId, $msg]);
            notify_new_message($pdo, $caseId, $myId);
        }
    }

    if ($action === 'update_status' && $hasChatAccess) {
        $newStatus = $_POST['new_status'] ?? '';
        if (in_array($newStatus, ['Submitted', 'Under Review', 'In Progress', 'Resolved'], true) && $newStatus !== $case['status']) {
            update_case_status($pdo, $caseId, $newStatus, $myId, 'Updated by ' . $role);
            $pdo->prepare("UPDATE cases SET assigned_to = COALESCE(assigned_to, ?) WHERE case_id = ?")->execute([$myId, $caseId]);
            flash_set('success', "Status updated to \"{$newStatus}\".");
        }
    }

    if ($action === 'assign_case' && $canAssign) {
        $assigneeId = (int) ($_POST['assignee_id'] ?? 0);
        if ($assigneeId > 0) {
            $pdo->prepare("UPDATE cases SET assigned_to = ? WHERE case_id = ?")->execute([$assigneeId, $caseId]);
            $pdo->prepare("INSERT INTO notifications (user_id, case_id, message) VALUES (?, ?, ?)")
                ->execute([$assigneeId, $caseId, "You've been assigned to case {$case['case_code']}."]);
            flash_set('success', 'Case assigned.');
        }
    }

    if ($action === 'assign_to_me') {
        $pdo->prepare("UPDATE cases SET assigned_to = ? WHERE case_id = ?")->execute([$myId, $caseId]);
        flash_set('success', 'Case assigned to you. You can now message the student.');
    }

    header('Location: /ceit-complaint-system/admin/case.php?id=' . $caseId);
    exit;
}

$stmt = $pdo->prepare("SELECT m.*, u.first_name, u.last_name, u.role AS sender_role FROM messages m JOIN users u ON u.user_id = m.sender_id WHERE case_id = ? ORDER BY sent_at ASC");
$stmt->execute([$caseId]);
$messages = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM attachments WHERE case_id = ?");
$stmt->execute([$caseId]);
$attachments = $stmt->fetchAll();

$assignOptions = $pdo->query("SELECT user_id, first_name, last_name, role FROM users WHERE role IN ('admin','sysadmin','adviser') AND status = 'active' ORDER BY role, first_name")->fetchAll();

$canUpdateStatus = $hasChatAccess;
$submitterLabel = $case['is_anonymous'] ? 'Anonymous' : ($case['first_name'] . ' ' . $case['last_name']);

$pageTitle = $case['case_code'] . ' · Case · CEIT CvSU';
$activeNav = 'case';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header">
    <h1>Case Progress</h1>
    <p><a class="link-btn" href="/ceit-complaint-system/admin/cases.php">← Back to Case List</a></p>
</div>
<?php render_flash(); ?>

<?php if ($canAssign): ?>
<div class="panel">
    <div class="panel-head"><h2>Assignment</h2></div>
    <?php if ($case['suggested_first']): ?>
        <p class="text-muted" style="margin-top:0;">Student suggested adviser: <strong><?= e($case['suggested_first'] . ' ' . $case['suggested_last']) ?></strong></p>
    <?php endif; ?>
    <form method="post" class="filter-row" style="margin:0;">
        <select name="assignee_id">
            <option value="">— Select who should handle this —</option>
            <?php foreach ($assignOptions as $opt): ?>
                <option value="<?= $opt['user_id'] ?>" <?= (int) $case['assigned_to'] === (int) $opt['user_id'] ? 'selected' : '' ?>>
                    <?= e($opt['first_name'] . ' ' . $opt['last_name']) ?> (<?= e(ucfirst($opt['role'])) ?>)<?= $case['suggested_adviser_id'] == $opt['user_id'] ? ' — suggested' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="action" value="assign_case" class="btn btn-primary btn-sm">Assign / Forward</button>
    </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/case_partial.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
