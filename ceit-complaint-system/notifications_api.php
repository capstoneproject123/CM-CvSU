<?php
/**
 * Lightweight JSON endpoint for the notification bell.
 *   GET  ?action=list        -> latest notifications for the logged-in user
 *   POST action=mark_read    -> marks all of the user's notifications as read
 */
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['action'] ?? '') : ($_GET['action'] ?? '');

if ($action === 'list') {
    $stmt = $pdo->prepare("SELECT notification_id, case_id, message, is_read, created_at
                            FROM notifications WHERE user_id = ?
                            ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $caseBase = current_role() === 'student' ? '/ceit-complaint-system/student/case.php' : '/ceit-complaint-system/admin/case.php';

    $out = array_map(function ($r) use ($caseBase) {
        return [
            'id'      => (int) $r['notification_id'],
            'message' => $r['message'],
            'isRead'  => (bool) $r['is_read'],
            'timeAgo' => time_ago($r['created_at']),
            'link'    => $r['case_id'] ? ($caseBase . '?id=' . $r['case_id']) : null,
        ];
    }, $rows);

    echo json_encode(['notifications' => $out]);
    exit;
}

if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")
        ->execute([$userId]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
