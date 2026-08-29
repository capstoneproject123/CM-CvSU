<?php
/**
 * Expects the including page to define:
 *   $case            - the case row (array)
 *   $messages        - array of message rows (joined with sender first/last name + sender_role)
 *   $canUpdateStatus - bool, whether to show the status-update control
 *   $hasChatAccess   - bool, whether the current viewer may read/send messages on this case
 *   $submitterLabel  - display name for the submitter ("You" or the student's name / "Anonymous")
 */
$attachments = $attachments ?? [];
$hasChatAccess = $hasChatAccess ?? true;
$steps = ['Submitted', 'Under Review', 'In Progress', 'Resolved'];
$currentIndex = array_search($case['status'], $steps, true);
$pct = (int) round((($currentIndex + 1) / count($steps)) * 100);

$roleLabels = ['student' => 'Student', 'admin' => 'Admin', 'sysadmin' => 'System Admin', 'adviser' => 'Adviser'];
?>
<div class="panel">
    <div class="progress-pct"><?= $pct ?>% Complete</div>
    <div class="stepper">
        <?php foreach ($steps as $i => $s): ?>
            <div class="step <?= $i <= $currentIndex ? 'done' : '' ?>">
                <div class="dot"><?= $i <= $currentIndex ? '✓' : ($i + 1) ?></div>
                <div class="label"><?= e($s) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="case-meta">
        <div>
            <span class="badge <?= status_badge_class($case['status']) ?>"><?= e($case['status']) ?></span>
            <span class="badge <?= priority_badge_class($case['priority']) ?>"><?= e($case['priority']) ?> Priority</span>
            <h3 style="margin:10px 0 2px;"><?= e($case['title']) ?></h3>
            <div class="text-muted"><?= e($case['case_code']) ?> · Submitted by <?= e($submitterLabel) ?> · <?= date('M j, Y', strtotime($case['created_at'])) ?></div>
            <div class="desc"><?= nl2br(e($case['description'])) ?></div>

            <?php if (!empty($attachments)): ?>
                <div style="margin-top:10px;">
                    <?php foreach ($attachments as $a): ?>
                        <a class="link-btn" style="display:block;" href="/ceit-complaint-system/<?= e($a['file_path']) ?>" target="_blank">📎 <?= e($a['file_name']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($canUpdateStatus): ?>
        <form method="post" style="min-width:200px;">
            <label>Update Status</label>
            <select name="new_status">
                <?php foreach ($steps as $s): ?>
                    <option value="<?= $s ?>" <?= $case['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="action" value="update_status" class="btn btn-primary btn-sm btn-block" style="margin-top:8px;">Update</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Conversation</h2></div>

    <?php if (!$hasChatAccess): ?>
        <div class="empty-state">
            This case isn't assigned to you yet, so the conversation is hidden.
            <?php if (!empty($canAssign)): ?>
                <br><br>
                <form method="post" style="display:inline;">
                    <button type="submit" name="action" value="assign_to_me" class="btn btn-primary btn-sm">Assign to Me</button>
                </form>
            <?php endif; ?>
        </div>
    <?php else: ?>
    <div class="chat-box">
        <div class="chat-messages">
            <?php if (!$messages): ?>
                <div class="empty-state" style="padding:20px;">No messages yet. Start the conversation below.</div>
            <?php endif; ?>
            <?php foreach ($messages as $m): ?>
                <?php
                    $mine = $m['sender_id'] == $_SESSION['user_id'];
                    $senderName = $m['first_name'] . ' ' . $m['last_name'];
                    $senderRoleLabel = $roleLabels[$m['sender_role']] ?? ucfirst($m['sender_role']);
                ?>
                <div class="msg <?= $mine ? 'msg-me' : 'msg-them' ?>">
                    <span class="msg-sender"><?= $mine ? 'You' : e($senderName) ?> <span class="msg-sender-role">· <?= e($senderRoleLabel) ?></span></span>
                    <?= nl2br(e($m['message'])) ?>
                    <span class="msg-time"><?= time_ago($m['sent_at']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <form method="post" class="chat-input">
            <input type="text" name="message" placeholder="Type a message..." autocomplete="off" required>
            <button type="submit" name="action" value="send_message" class="btn btn-primary btn-sm">Send</button>
        </form>
    </div>
    <?php endif; ?>
</div>
