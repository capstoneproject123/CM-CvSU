document.addEventListener('DOMContentLoaded', function () {

    // Tab switching (Complaint / Inquiry) on the Submit page
    document.querySelectorAll('[data-tab-group]').forEach(function (group) {
        var buttons = group.querySelectorAll('.tab-btn');
        var panels = document.querySelectorAll('[data-tab-panel]');
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                buttons.forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                var target = btn.getAttribute('data-tab');
                panels.forEach(function (p) {
                    p.style.display = (p.getAttribute('data-tab-panel') === target) ? 'block' : 'none';
                });
                var hiddenInput = document.querySelector('input[name="case_type"]');
                if (hiddenInput) hiddenInput.value = target;
            });
        });
    });

    // File input: show chosen filename in the drop zone
    var fileInput = document.querySelector('#file-input');
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var label = document.querySelector('#file-drop-label');
            if (label) {
                label.textContent = fileInput.files.length
                    ? fileInput.files.length + ' file(s) selected: ' + Array.from(fileInput.files).map(f => f.name).join(', ')
                    : 'Click to Browse Files or drag and drop (JPEG, PNG, or PDF up to 10MB)';
            }
        });
    }

    // Auto-scroll chat thread to the latest message
    var chatMessages = document.querySelector('.chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Registration form: toggle Student vs Admin fields, keep `required` in sync
    var roleToggles = document.querySelectorAll('[data-role-toggle]');
    if (roleToggles.length) {
        var syncRolePanels = function () {
            var selected = document.querySelector('[data-role-toggle]:checked').value;
            document.querySelectorAll('[data-role-panel]').forEach(function (panel) {
                var match = panel.getAttribute('data-role-panel') === selected;
                panel.style.display = match ? '' : 'none';
                panel.querySelectorAll('input, select').forEach(function (field) {
                    if (field.dataset.optional === 'true') return;
                    field.required = match;
                });
            });
        };
        roleToggles.forEach(function (r) { r.addEventListener('change', syncRolePanels); });
        syncRolePanels();
    }

    // Password show/hide toggle
    document.querySelectorAll('.password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) return;
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.textContent = isHidden ? 'Hide' : 'Show';
            btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });
    });

    // Confirm before destructive actions
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // ---------- Notification bell + profile dropdown ----------
    var API = '/ceit-complaint-system/notifications_api.php';

    function setupDropdown(btnId, panelId) {
        var btn = document.getElementById(btnId);
        var panel = document.getElementById(panelId);
        if (!btn || !panel) return null;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = !panel.hidden;
            closeAllDropdowns();
            if (!isOpen) {
                panel.hidden = false;
                btn.setAttribute('aria-expanded', 'true');
            }
        });
        return { btn: btn, panel: panel };
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.dropdown-panel').forEach(function (p) { p.hidden = true; });
        document.querySelectorAll('.dropdown-wrap button').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.dropdown-wrap')) closeAllDropdowns();
    });

    var notif = setupDropdown('notif-btn', 'notif-panel');
    setupDropdown('profile-btn', 'profile-panel');

    if (notif) {
        var badge = document.getElementById('notif-badge');
        var list = document.getElementById('notif-list');
        var loaded = false;

        notif.btn.addEventListener('click', function () {
            // Panel was just opened (or closed) by setupDropdown's handler above.
            if (notif.panel.hidden) return;

            if (!loaded) {
                fetch(API + '?action=list')
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        renderNotifications(data.notifications || []);
                        loaded = true;
                    })
                    .catch(function () {
                        list.innerHTML = '<div class="empty-state" style="padding:20px;">Couldn\'t load notifications.</div>';
                    });
            }

            // Mark everything read immediately: clear the badge and persist server-side.
            if (badge && badge.textContent !== '') {
                badge.style.display = 'none';
                badge.textContent = '';
                fetch(API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=mark_read'
                }).then(function () {
                    document.querySelectorAll('.notif-item.unread').forEach(function (el) {
                        el.classList.remove('unread');
                    });
                });
            }
        });

        function emptyState() {
            return '<div class="empty-state" style="padding:20px;">No notifications yet.</div>';
        }

        function renderNotifications(items) {
            if (!items.length) {
                list.innerHTML = emptyState();
                return;
            }
            list.innerHTML = items.map(function (n) {
                var cls = 'notif-item' + (n.isRead ? '' : ' unread');
                var inner = n.message.replace(/</g, '&lt;') + '<span class="notif-time">' + n.timeAgo + '</span>'
                    + '<button type="button" class="notif-delete" data-id="' + n.id + '" aria-label="Delete notification" title="Delete">&times;</button>';
                // Use a <div> wrapper (not <a>) whenever there's a delete button, so the
                // click-to-navigate and click-to-delete targets don't get tangled up;
                // clicking the message text itself still navigates via a nested link.
                if (n.link) {
                    inner = '<a class="notif-link" href="' + n.link + '">' + n.message.replace(/</g, '&lt;') + '</a>'
                        + '<span class="notif-time">' + n.timeAgo + '</span>'
                        + '<button type="button" class="notif-delete" data-id="' + n.id + '" aria-label="Delete notification" title="Delete">&times;</button>';
                }
                return '<div class="' + cls + '" data-id="' + n.id + '">' + inner + '</div>';
            }).join('');
        }

        // Delegated handler: delete/dismiss a single notification.
        list.addEventListener('click', function (e) {
            var delBtn = e.target.closest('.notif-delete');
            if (!delBtn) return;

            e.preventDefault();
            e.stopPropagation();

            var item = delBtn.closest('.notif-item');
            var id = delBtn.getAttribute('data-id');
            if (!item || !id) return;

            var wasUnread = item.classList.contains('unread');
            delBtn.disabled = true;

            fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=delete&id=' + encodeURIComponent(id)
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.success === false) {
                        delBtn.disabled = false;
                        return;
                    }
                    item.remove();
                    if (!list.children.length) {
                        list.innerHTML = emptyState();
                    }
                    // Keep the badge count honest if a still-unread item was removed.
                    if (wasUnread && badge && badge.textContent) {
                        var remaining = Math.max(0, (parseInt(badge.textContent, 10) || 0) - 1);
                        if (remaining === 0) {
                            badge.style.display = 'none';
                            badge.textContent = '';
                        } else {
                            badge.textContent = String(remaining);
                        }
                    }
                })
                .catch(function () {
                    delBtn.disabled = false;
                });
        });
    }
});
