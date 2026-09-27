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
    header('Location: ' . BASE_URL . '/student/track.php');
    exit;
}

// Only editable while nobody has started working on it yet.
if ($case['status'] !== 'Submitted') {
    flash_set('error', 'This submission is already being processed and can no longer be edited.');
    header('Location: ' . BASE_URL . '/student/case.php?id=' . $caseId);
    exit;
}

$categories = ['Academic Concerns', 'Technical Issues', 'Administrative', 'Facilities & Equipment', 'Others'];
$advisers = list_advisers($pdo);
$errors = [];
$maxAttachments = 5;

$stmt = $pdo->prepare("SELECT * FROM attachments WHERE case_id = ?");
$stmt->execute([$caseId]);
$attachments = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    // Removing one existing attachment (its own small form/request, separate from the main save)
    if ($action === 'remove_attachment') {
        $attachId = (int) ($_POST['attachment_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM attachments WHERE attachment_id = ? AND case_id = ?");
        $stmt->execute([$attachId, $caseId]);
        $att = $stmt->fetch();
        if ($att) {
            $full = __DIR__ . '/../' . $att['file_path'];
            if (is_file($full)) { @unlink($full); }
            $pdo->prepare("DELETE FROM attachments WHERE attachment_id = ?")->execute([$attachId]);
            flash_set('success', 'Attachment removed.');
        }
        header('Location: ' . BASE_URL . '/student/edit_case.php?id=' . $caseId);
        exit;
    }

    if ($action === 'save') {
        $title       = trim($_POST['title'] ?? '');
        $category    = $_POST['category'] ?? '';
        $priority    = in_array($_POST['priority'] ?? '', ['Low', 'Medium', 'High'], true) ? $_POST['priority'] : 'Medium';
        $description = trim($_POST['description'] ?? '');
        $anonymous   = isset($_POST['anonymous']) ? 1 : 0;
        $suggestedAdviserId = !empty($_POST['suggested_adviser']) ? (int) $_POST['suggested_adviser'] : null;

        if ($title === '') $errors[] = 'Title is required.';
        if (!in_array($category, $categories, true)) $errors[] = 'Please select a category.';
        if ($description === '') $errors[] = 'Please provide a description.';
        if ($suggestedAdviserId && !in_array($suggestedAdviserId, array_column($advisers, 'user_id'), true)) {
            $suggestedAdviserId = null; // ignore tampered/invalid values rather than error out
        }

        // Optional new attachments — same validation rules as the original submit form,
        // capped so existing + new files never exceed the overall limit.
        $uploadedFiles = [];
        if (!empty($_FILES['attachment']['name'][0])) {
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            $fileCount = count($_FILES['attachment']['name']);

            if (count($attachments) + $fileCount > $maxAttachments) {
                $errors[] = "You can have up to {$maxAttachments} attachments total on this submission.";
            } else {
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['attachment']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $name = $_FILES['attachment']['name'][$i];
                    if ($_FILES['attachment']['error'][$i] !== UPLOAD_ERR_OK) {
                        $errors[] = "There was a problem uploading \"{$name}\". Please try again.";
                        continue;
                    }
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed, true)) {
                        $errors[] = "\"{$name}\" isn't a JPEG, PNG, or PDF file.";
                        continue;
                    }
                    if ($_FILES['attachment']['size'][$i] > 10 * 1024 * 1024) {
                        $errors[] = "\"{$name}\" is over 10MB.";
                        continue;
                    }
                    $uploadedFiles[] = [
                        'name'     => $name,
                        'tmp_name' => $_FILES['attachment']['tmp_name'][$i],
                    ];
                }
            }
        }

        if (!$errors) {
            $pdo->prepare("UPDATE cases SET title = ?, category = ?, priority = ?, description = ?, is_anonymous = ?, suggested_adviser_id = ? WHERE case_id = ?")
                ->execute([$title, $category, $priority, $description, $anonymous, $suggestedAdviserId, $caseId]);

            if ($uploadedFiles) {
                $uploadDir = __DIR__ . '/../uploads/';
                $attachStmt = $pdo->prepare("INSERT INTO attachments (case_id, file_name, file_path) VALUES (?, ?, ?)");
                foreach ($uploadedFiles as $index => $file) {
                    $safeName = $case['case_code'] . '_edit' . time() . '_' . ($index + 1) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
                    move_uploaded_file($file['tmp_name'], $uploadDir . $safeName);
                    $attachStmt->execute([$caseId, $file['name'], 'uploads/' . $safeName]);
                }
            }

            flash_set('success', "Your {$case['type']} \"{$case['case_code']}\" was updated.");
            header('Location: ' . BASE_URL . '/student/case.php?id=' . $caseId);
            exit;
        }

        // Re-render the form with what the student just typed rather than the old DB values.
        $case['title'] = $title;
        $case['category'] = $category;
        $case['priority'] = $priority;
        $case['description'] = $description;
        $case['is_anonymous'] = $anonymous;
        $case['suggested_adviser_id'] = $suggestedAdviserId;
    }
}

$pageTitle = 'Edit ' . $case['case_code'] . ' · CEIT CvSU';
$activeNav = 'track';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header">
    <h1>Edit <?= e(ucfirst($case['type'])) ?></h1>
    <p><a class="link-btn" href="<?= BASE_URL ?>/student/case.php?id=<?= $caseId ?>">← Back to Case</a></p>
</div>
<?php render_flash(); ?>
<?php if ($errors): ?><div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

<div class="panel">
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label><?= $case['type'] === 'inquiry' ? 'Inquiry' : 'Complaint' ?> Title <span class="req">*</span></label>
            <input type="text" name="title" value="<?= e($case['title']) ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Category <span class="req">*</span></label>
                <select name="category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $case['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Priority Level</label>
                <select name="priority">
                    <option value="Low" <?= $case['priority'] === 'Low' ? 'selected' : '' ?>>Low – Can wait</option>
                    <option value="Medium" <?= $case['priority'] === 'Medium' ? 'selected' : '' ?>>Medium – Normal timeline</option>
                    <option value="High" <?= $case['priority'] === 'High' ? 'selected' : '' ?>>High – Urgent</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Description <span class="req">*</span></label>
            <textarea name="description" required><?= e($case['description']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Suggest an Adviser (optional)</label>
            <select name="suggested_adviser">
                <option value="">No preference — let the office assign one</option>
                <?php foreach ($advisers as $a): ?>
                    <option value="<?= $a['user_id'] ?>" <?= (int) $case['suggested_adviser_id'] === (int) $a['user_id'] ? 'selected' : '' ?>><?= e($a['first_name'] . ' ' . $a['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="help-text">This is just a suggestion — the office may assign a different adviser.</div>
        </div>

        <?php if (count($attachments) < $maxAttachments): ?>
        <div class="form-group">
            <label>Add More Evidence (optional)</label>
            <label for="file-input" class="file-drop" id="file-drop-label">
                Click to Browse Files or drag and drop<br><span class="text-muted">JPEG, PNG, or PDF — up to <?= $maxAttachments - count($attachments) ?> more file(s), 10MB each</span>
            </label>
            <input type="file" name="attachment[]" id="file-input" style="display:none" accept=".jpg,.jpeg,.png,.pdf" multiple data-max-files="<?= $maxAttachments - count($attachments) ?>">
        </div>
        <?php endif; ?>

        <div class="form-group" style="display:flex;align-items:center;justify-content:space-between;">
            <label style="margin:0;">Submit Anonymously</label>
            <label class="switch">
                <input type="checkbox" name="anonymous" <?= $case['is_anonymous'] ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
        </div>

        <button type="submit" name="action" value="save" class="btn btn-primary">Save Changes</button>
    </form>
</div>

<?php if ($attachments): ?>
<div class="panel">
    <div class="panel-head"><h2>Current Attachments</h2></div>
    <?php foreach ($attachments as $a): ?>
        <form method="post" style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100);" data-confirm="Remove this attachment?">
            <input type="hidden" name="attachment_id" value="<?= $a['attachment_id'] ?>">
            <a class="link-btn" href="<?= BASE_URL ?>/<?= e($a['file_path']) ?>" target="_blank">📎 <?= e($a['file_name']) ?></a>
            <button type="submit" name="action" value="remove_attachment" class="link-btn" style="background:none;border:none;cursor:pointer;color:#c0392b;">Remove</button>
        </form>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>