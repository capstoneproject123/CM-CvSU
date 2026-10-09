<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
require_role(['student']);

$userId = $_SESSION['user_id'];
$errors = [];

$categories = ['Academic Concerns', 'Technical Issues', 'Administrative', 'Facilities & Equipment', 'Others'];
$advisers = list_advisers($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = ($_POST['case_type'] ?? 'complaint') === 'inquiry' ? 'inquiry' : 'complaint';
    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? '';
    $categoryOther = trim($_POST['category_other'] ?? '');
    $priority = in_array($_POST['priority'] ?? '', ['Low', 'Medium', 'High']) ? $_POST['priority'] : 'Medium';
    $description = trim($_POST['description'] ?? '');
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;
    $certify = isset($_POST['certify']);
    $suggestedAdviserId = !empty($_POST['suggested_adviser']) ? (int) $_POST['suggested_adviser'] : null;

    if ($title === '')
        $errors[] = 'Title is required.';
    if (!in_array($category, $categories, true))
        $errors[] = 'Please select a category.';
    if ($category === 'Others') {
        if ($categoryOther === '')
            $errors[] = 'Please specify your category under "Others".';
        elseif (mb_strlen($categoryOther) > 100)
            $errors[] = 'The specified category must be 100 characters or fewer.';
    }
    // Only keep the specified text when "Others" is actually the chosen category.
    $categoryOtherValue = ($category === 'Others' && $categoryOther !== '') ? $categoryOther : null;
    if ($description === '')
        $errors[] = 'Please provide a description.';
    if (!$certify)
        $errors[] = 'You must certify that the information provided is true and accurate.';
    if ($suggestedAdviserId && !in_array($suggestedAdviserId, array_column($advisers, 'user_id'), true)) {
        $suggestedAdviserId = null; // ignore tampered/invalid values rather than error out
    }

    // Optional file upload(s) — the input accepts multiple files, no cap on count
    $uploadedFiles = [];
    if (!empty($_FILES['attachment']['name'][0])) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $fileCount = count($_FILES['attachment']['name']);

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
                'name' => $name,
                'tmp_name' => $_FILES['attachment']['tmp_name'][$i],
            ];
        }
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $caseCode = generate_case_code($pdo, $type);
            $stmt = $pdo->prepare("INSERT INTO cases (case_code, user_id, type, title, category, category_other, priority, description, is_anonymous, status, suggested_adviser_id)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', ?)");
            $stmt->execute([$caseCode, $userId, $type, $title, $category, $categoryOtherValue, $priority, $description, $anonymous, $suggestedAdviserId]);
            $caseId = (int) $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO status_history (case_id, status, changed_by, remarks) VALUES (?, 'Submitted', ?, 'Case submitted by student')")
                ->execute([$caseId, $userId]);

            if ($uploadedFiles) {
                $uploadDir = __DIR__ . '/../uploads/';
                $attachStmt = $pdo->prepare("INSERT INTO attachments (case_id, file_name, file_path) VALUES (?, ?, ?)");
                foreach ($uploadedFiles as $index => $file) {
                    // Include the loop index so two files with the same original name
                    // (e.g. two "IMG_0001.jpg" from a phone) never collide on disk.
                    $safeName = $caseCode . '_' . ($index + 1) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
                    move_uploaded_file($file['tmp_name'], $uploadDir . $safeName);
                    $attachStmt->execute([$caseId, $file['name'], 'uploads/' . $safeName]);
                }
            }

            $pdo->commit();
            notify_admins_new_case($pdo, $caseId, $caseCode, $title, $type, $suggestedAdviserId);
            flash_set('success', "Your {$type} \"{$caseCode}\" was submitted successfully.");
            header('Location: ' . BASE_URL . '/student/case.php?id=' . $caseId);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong while saving your submission. Please try again.';
        }
    }
}

$showCategoryOther = (($_POST['category'] ?? '') === 'Others');

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
        <button type="button" class="tab-btn<?= ($type ?? 'complaint') === 'complaint' ? ' active' : '' ?>" data-tab="complaint">Complaint</button>
        <button type="button" class="tab-btn<?= ($type ?? 'complaint') === 'inquiry' ? ' active' : '' ?>" data-tab="inquiry">Inquiry</button>
    </div>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="case_type" id="case-type-field" value="<?= e($type ?? 'complaint') ?>">

        <h3 style="font-size:14px;margin-bottom:14px;">Provide Information</h3>

        <div class="form-group">
            <label><span data-tab-panel="complaint" style="display:<?= ($type ?? 'complaint') === 'complaint' ? 'inline' : 'none' ?>">Complaint Title</span><span data-tab-panel="inquiry" style="display:<?= ($type ?? 'complaint') === 'inquiry' ? 'inline' : 'none' ?>">Inquiry Title</span> <span class="req">*</span></label>
            <input type="text" name="title" value="<?= e($_POST['title'] ?? '') ?>" required placeholder="Brief summary of your concern">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category-select">Category <span class="req">*</span></label>
                <select name="category" id="category-select" required>
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

        <div class="form-group" id="category-other-group" style="display:<?= $showCategoryOther ? 'block' : 'none' ?>">
            <label for="category-other">Please specify the category <span class="req">*</span></label>
            <input type="text" name="category_other" id="category-other" maxlength="100"
                   value="<?= e($_POST['category_other'] ?? '') ?>"
                   placeholder="e.g., Harassment, Scholarship concern"
                   <?= $showCategoryOther ? 'required' : '' ?>>
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
                Click to Browse Files or drag and drop<br><span class="text-muted">JPEG, PNG, or PDF — 10MB each</span>
            </label>
            <input type="file" name="attachment[]" id="file-input" style="display:none" accept=".jpg,.jpeg,.png,.pdf" multiple>
        </div>

        <div class="form-group" style="display:flex;align-items:center;justify-content:space-between;">
            <label style="margin:0;">Submit Anonymously</label>
            <label class="switch">
                <input type="checkbox" name="anonymous" <?= isset($_POST['anonymous']) ? 'checked' : '' ?>>
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

<script>
(function () {
    var group = document.querySelector('[data-tab-group]');
    var typeField = document.getElementById('case-type-field');
    if (!group || !typeField) return;

    function setTab(tab) {
        typeField.value = tab;
        group.querySelectorAll('.tab-btn').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });
        document.querySelectorAll('[data-tab-panel]').forEach(function (el) {
            el.style.display = (el.dataset.tabPanel === tab) ? 'inline' : 'none';
        });
    }

    group.querySelectorAll('.tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () { setTab(btn.dataset.tab); });
    });
})();

// Show a required "Please specify" field only when the category is "Others".
(function () {
    var select = document.getElementById('category-select');
    var wrap = document.getElementById('category-other-group');
    var input = document.getElementById('category-other');
    if (!select || !wrap || !input) return;

    function sync(focusInput) {
        var isOthers = select.value === 'Others';
        wrap.style.display = isOthers ? 'block' : 'none';
        input.required = isOthers;
        if (isOthers && focusInput) input.focus();
    }

    select.addEventListener('change', function () { sync(true); });
    sync(false);
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>