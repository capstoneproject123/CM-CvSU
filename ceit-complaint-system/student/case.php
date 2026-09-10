<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['student']);

$userId = $_SESSION['user_id'];
$caseId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM cases WHERE case_id = ? AND user_id = ?");
$stmt->execute([$caseId, $userId]);
$case = $stmt->fetch();

if (!$case) {
    flash_set('error', 'Case not found.');
    header('Location: /ceit-complaint-system/student/track.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_message') {
    $msg = trim($_POST['message'] ?? '');
    if ($msg !== '') {
        $pdo->prepare("INSERT INTO messages (case_id, sender_id, message) VALUES (?, ?, ?)")
            ->execute([$caseId, $userId, $msg]);
        notify_new_message($pdo, $caseId, $userId);
    }
    header('Location: /ceit-complaint-system/student/case.php?id=' . $caseId);
    exit;
}

$stmt = $pdo->prepare("SELECT m.*, u.first_name, u.last_name, u.role AS sender_role FROM messages m JOIN users u ON u.user_id = m.sender_id WHERE case_id = ? ORDER BY sent_at ASC");
$stmt->execute([$caseId]);
$messages = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM attachments WHERE case_id = ?");
$stmt->execute([$caseId]);
$attachments = $stmt->fetchAll();

$hasChatAccess = true; // students always have access to their own case's conversation
$canUpdateStatus = false;
$submitterLabel = 'You';

$pageTitle = $case['case_code'] . ' · CEIT CvSU';
$activeNav = 'track';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header">
    <h1>Case Progress</h1>
    <p><a class="link-btn" href="/ceit-complaint-system/student/track.php">← Back to Track</a></p>
</div>
<?php render_flash(); ?>
<?php require __DIR__ . '/../includes/case_partial.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
