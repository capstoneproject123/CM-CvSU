<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['student']);

$userId = $_SESSION['user_id'];
$errors = [];

$categories = ['Academic Concerns', 'Technical Issues', 'Administrative', 'Facilities & Equipment', 'Others'];
$advisers = list_advisers($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type        = ($_POST['case_type'] ?? 'complaint') === 'inquiry' ? 'inquiry' : 'complaint';
    $title       = trim($_POST['title'] ?? '');
    $category    = $_POST['category'] ?? '';
    $priority    = in_array($_POST['priority'] ?? '', ['Low', 'Medium', 'High']) ? $_POST['priority'] : 'Medium';
    $description = trim($_POST['description'] ?? '');
    $anonymous   = isset($_POST['anonymous']) ? 1 : 0;
    $certify     = isset($_POST['certify']);
    $suggestedAdviserId = !empty($_POST['suggested_adviser']) ? (int) $_POST['suggested_adviser'] : null;

    if ($title === '') $errors[] = 'Title is required.';
    if (!in_array($category, $categories, true)) $errors[] = 'Please select a category.';
    if ($description === '') $errors[] = 'Please provide a description.';
    if (!$certify) $errors[] = 'You must certify that the information provided is true and accurate.';
    if ($suggestedAdviserId && !in_array($suggestedAdviserId, array_column($advisers, 'user_id'), true)) {
        $suggestedAdviserId = null; // ignore tampered/invalid values rather than error out
    }

    // Optional file upload
    $uploadedFile = null;
    if (!empty($_FILES['attachment']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Only JPEG, PNG, or PDF files are allowed.';
        } elseif ($_FILES['attachment']['size'] > 10 * 1024 * 1024) {
            $errors[] = 'File must be under 10MB.';
        } else {
            $uploadedFile = $_FILES['attachment'];
        }
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $caseCode = generate_case_code($pdo, $type);
            $stmt = $pdo->prepare("INSERT INTO cases (case_code, user_id, type, title, category, priority, description, is_anonymous, status, suggested_adviser_id)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', ?)");
            $stmt->execute([$caseCode, $userId, $type, $title, $category, $priority, $description, $anonymous, $suggestedAdviserId]);
            $caseId = (int) $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO status_history (case_id, status, changed_by, remarks) VALUES (?, 'Submitted', ?, 'Case submitted by student')")
                ->execute([$caseId, $userId]);

            if ($uploadedFile) {
                $uploadDir = __DIR__ . '/../uploads/';
                $safeName = $caseCode . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $uploadedFile['name']);
                move_uploaded_file($uploadedFile['tmp_name'], $uploadDir . $safeName);
                $pdo->prepare("INSERT INTO attachments (case_id, file_name, file_path) VALUES (?, ?, ?)")
                    ->execute([$caseId, $uploadedFile['name'], 'uploads/' . $safeName]);
            }

            $pdo->commit();
            notify_admins_new_case($pdo, $caseId, $caseCode, $title, $type, $suggestedAdviserId);
            flash_set('success', "Your {$type} \"{$caseCode}\" was submitted successfully.");
            header('Location: /ceit-complaint-system/student/case.php?id=' . $caseId);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong while saving your submission. Please try again.';
        }
    }
}

$pageTitle = 'Submit · CEIT CvSU';
$activeNav = 'submit';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<div class="page-header">
    <h1>Submit a Complaint or Inquiry</h1>
    <p>Please provide the details below.</p>
</div>

<?php if ($errors): ?><div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

<div class="panel">
    <div class="tab-group" data-tab-group>
        <button type="button" class="tab-btn active" data-tab="complaint">Complaint</button>
        <button type="button" class="tab-btn" data-tab="inquiry">Inquiry</button>
    </div>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="case_type" value="complaint">

        <h3 style="font-size:14px;margin-bottom:14px;">Provide Information</h3>

        <div class="form-group">
            <label><span data-tab-panel="complaint" style="display:inline">Complaint Title</span><span data-tab-panel="inquiry" style="display:none">Inquiry Title</span> <span class="req">*</span></label>
            <input type="text" name="title" value="<?= e($_POST['title'] ?? '') ?>" required placeholder="Brief summary of your concern">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Category <span class="req">*</span></label>
                <select name="category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= (($_POST['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Priority Level</label>
                <select name="priority">
                    <option value="Low" <?= (($_POST['priority'] ?? '') === 'Low') ? 'selected' : '' ?>>Low – Can wait</option>
                    <option value="Medium" <?= (($_POST['priority'] ?? 'Medium') === 'Medium') ? 'selected' : '' ?>>Medium – Normal timeline</option>
                    <option value="High" <?= (($_POST['priority'] ?? '') === 'High') ? 'selected' : '' ?>>High – Urgent</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Description <span class="req">*</span></label>
            <textarea name="description" required placeholder="Describe your concern in detail..."><?= e($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Suggest an Adviser (optional)</label>
            <select name="suggested_adviser">
                <option value="">No preference — let the office assign one</option>
                <?php foreach ($advisers as $a): ?>
                    <option value="<?= $a['user_id'] ?>" <?= (($_POST['suggested_adviser'] ?? '') == $a['user_id']) ? 'selected' : '' ?>><?= e($a['first_name'] . ' ' . $a['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="help-text">This is just a suggestion — the office may assign a different adviser.</div>
        </div>

        <div class="form-group">
            <label>Supporting Evidence (optional)</label>
            <label for="file-input" class="file-drop" id="file-drop-label">
                Click to Browse Files or drag and drop<br><span class="text-muted">JPEG, PNG, or PDF up to 10MB</span>
            </label>
            <input type="file" name="attachment" id="file-input" style="display:none" accept=".jpg,.jpeg,.png,.pdf">
        </div>

        <div class="form-group" style="display:flex;align-items:center;justify-content:space-between;">
            <label style="margin:0;">Submit Anonymously</label>
            <label class="switch">
                <input type="checkbox" name="anonymous">
                <span class="slider"></span>
            </label>
        </div>

        <div class="form-group checkbox-row">
            <input type="checkbox" name="certify" id="certify" required>
            <label for="certify" style="margin:0;font-weight:400;">I certify that the information provided is true and accurate to the best of my knowledge. I understand how my data will be processed according to the Privacy Policy.</label>
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
