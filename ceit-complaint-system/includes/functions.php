<?php
/**
 * Shared helper functions.
 * Included by every page after config/db.php.
 */

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function current_role(): ?string {
    return $_SESSION['role'] ?? null;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /ceit-complaint-system/auth/login.php');
        exit;
    }
}

function require_role(array $roles): void {
    require_login();
    if (!in_array(current_role(), $roles, true)) {
        header('Location: /ceit-complaint-system/auth/login.php');
        exit;
    }
}

function is_adviser(): bool { return current_role() === 'adviser'; }
function is_sysadmin(): bool { return current_role() === 'sysadmin'; }

/** Whether the current logged-in staff member (admin/adviser/sysadmin) can open a given case's chat */
function can_access_case(array $case, int $userId, string $role): bool {
    if ($role === 'student') {
        return (int) $case['user_id'] === $userId;
    }
    if ($role === 'sysadmin') {
        return true; // full oversight access
    }
    // admin / adviser: only if the case is assigned to them
    return (int) ($case['assigned_to'] ?? 0) === $userId;
}

/** Returns active adviser accounts, for the "suggest an adviser" / "assign to" dropdowns */
function list_advisers(PDO $pdo): array {
    return $pdo->query("SELECT user_id, first_name, last_name FROM users WHERE role = 'adviser' AND status = 'active' ORDER BY first_name")->fetchAll();
}

/** The identifier a user actually logs in with: CvSU email for students, Employee ID for staff */
function login_identifier(array $user): string {
    return $user['email'] ?: ($user['employee_id'] ?? '');
}

function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function flash_set(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/** Generates the next case code, e.g. CMP-0001 / INQ-0001 */
function generate_case_code(PDO $pdo, string $type): string {
    $prefix = $type === 'complaint' ? 'CMP' : 'INQ';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE type = ?");
    $stmt->execute([$type]);
    $count = (int) $stmt->fetchColumn() + 1;
    return sprintf('%s-%04d', $prefix, $count);
}

/** Maps a status string to a CSS badge class */
function status_badge_class(string $status): string {
    return match ($status) {
        'Submitted'     => 'badge-submitted',
        'Under Review'  => 'badge-review',
        'In Progress'   => 'badge-progress',
        'Resolved'      => 'badge-resolved',
        default         => 'badge-submitted',
    };
}

function priority_badge_class(string $priority): string {
    return match ($priority) {
        'High'   => 'badge-high',
        'Medium' => 'badge-medium',
        'Low'    => 'badge-low',
        default  => 'badge-medium',
    };
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

/** Records a status change + notifies the case owner */
function update_case_status(PDO $pdo, int $caseId, string $newStatus, ?int $changedBy, string $remarks = ''): void {
    $pdo->prepare("UPDATE cases SET status = ? WHERE case_id = ?")->execute([$newStatus, $caseId]);
    $pdo->prepare("INSERT INTO status_history (case_id, status, changed_by, remarks) VALUES (?, ?, ?, ?)")
        ->execute([$caseId, $newStatus, $changedBy, $remarks]);

    $stmt = $pdo->prepare("SELECT user_id, case_code FROM cases WHERE case_id = ?");
    $stmt->execute([$caseId]);
    $case = $stmt->fetch();
    if ($case) {
        $msg = "Your case {$case['case_code']} status changed to \"{$newStatus}\".";
        $pdo->prepare("INSERT INTO notifications (user_id, case_id, message) VALUES (?, ?, ?)")
            ->execute([$case['user_id'], $caseId, $msg]);
    }
}

/** Notifies every admin/sysadmin (and the suggested adviser, if any) that a new case was submitted */
function notify_admins_new_case(PDO $pdo, int $caseId, string $caseCode, string $title, string $type, ?int $suggestedAdviserId = null): void {
    $msg = 'New ' . $type . ' submitted: ' . $caseCode . ' — ' . $title;
    $admins = $pdo->query("SELECT user_id FROM users WHERE role IN ('admin','sysadmin') AND status = 'active'")->fetchAll();
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, case_id, message) VALUES (?, ?, ?)");
    foreach ($admins as $a) {
        $stmt->execute([$a['user_id'], $caseId, $msg]);
    }
    if ($suggestedAdviserId) {
        $stmt->execute([$suggestedAdviserId, $caseId, 'You were suggested as adviser on new ' . $type . ': ' . $caseCode . ' — ' . $title]);
    }
}

/** Notifies whichever case participants did NOT send this message */
function notify_new_message(PDO $pdo, int $caseId, int $senderId): void {
    $stmt = $pdo->prepare("SELECT case_code, user_id, assigned_to FROM cases WHERE case_id = ?");
    $stmt->execute([$caseId]);
    $case = $stmt->fetch();
    if (!$case) return;

    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
    $stmt->execute([$senderId]);
    $sender = $stmt->fetch();
    $senderName = $sender ? ($sender['first_name'] . ' ' . $sender['last_name']) : 'Someone';

    $msg = 'New message on ' . $case['case_code'] . ' from ' . $senderName . '.';
    $ins = $pdo->prepare("INSERT INTO notifications (user_id, case_id, message) VALUES (?, ?, ?)");

    // Notify the student (case owner), unless they're the one who just sent it
    if ((int) $case['user_id'] !== $senderId) {
        $ins->execute([$case['user_id'], $caseId, $msg]);
    }

    // Notify whoever the case is assigned to (admin or adviser), unless they sent it
    if ($case['assigned_to'] && (int) $case['assigned_to'] !== $senderId) {
        $ins->execute([$case['assigned_to'], $caseId, $msg]);
    }

    // If nobody is assigned yet and a staff member didn't send this, fall back to notifying all admins
    if (!$case['assigned_to'] && (int) $case['user_id'] === $senderId) {
        $admins = $pdo->query("SELECT user_id FROM users WHERE role IN ('admin','sysadmin') AND status = 'active'")->fetchAll();
        foreach ($admins as $a) { $ins->execute([$a['user_id'], $caseId, $msg]); }
    }
}

/**
 * Handles a profile-picture upload from $_FILES['avatar'].
 * Returns null on success, or an error message string on failure.
 * Does nothing (returns null) if no file was actually chosen.
 */
function handle_avatar_upload(PDO $pdo, int $userId): ?string {
    if (empty($_FILES['avatar']['name'])) {
        return null;
    }
    $file = $_FILES['avatar'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'There was a problem uploading that file. Please try again.';
    }
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return 'Profile picture must be a JPG, PNG, GIF, or WEBP image.';
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return 'Profile picture must be under 5MB.';
    }

    $uploadDir = __DIR__ . '/../uploads/avatars/';
    $safeName = 'user' . $userId . '_' . time() . '.' . $ext;

    // Remove any previous avatar file for this user before saving the new one
    $stmt = $pdo->prepare("SELECT avatar_path FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $old = $stmt->fetchColumn();
    if ($old) {
        $oldFull = __DIR__ . '/../' . $old;
        if (is_file($oldFull)) { @unlink($oldFull); }
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {
        return 'Could not save the uploaded file. Please try again.';
    }

    $pdo->prepare("UPDATE users SET avatar_path = ? WHERE user_id = ?")
        ->execute(['uploads/avatars/' . $safeName, $userId]);

    return null;
}

function render_flash(): void {
    $f = flash_get();
    if ($f) {
        echo '<div class="flash flash-' . e($f['type']) . '">' . e($f['message']) . '</div>';
    }
}

function unread_notification_count(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}
