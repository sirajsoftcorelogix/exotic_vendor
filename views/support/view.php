<?php
/**
 * @var array<string, mixed> $ticket
 * @var array<array<string, mixed>> $comments
 * @var bool $isAdmin
 * @var int $currentUserId
 */

function getViewTypeBadge(string $type): string
{
    switch ($type) {
        case 'bug':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200"><i class="fas fa-bug"></i> Bug / System Error</span>';
        case 'change':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200"><i class="fas fa-sync-alt"></i> Change Request</span>';
        case 'feature':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-200"><i class="fas fa-lightbulb"></i> New Feature Need</span>';
        default:
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">' . htmlspecialchars(ucfirst($type)) . '</span>';
    }
}

function getViewPriorityBadge(string $priority): string
{
    switch ($priority) {
        case 'urgent':
            return '<span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-red-600 text-white"><i class="fas fa-exclamation-triangle mr-1"></i> Urgent</span>';
        case 'high':
            return '<span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold bg-orange-100 text-orange-800 border border-orange-200">High Priority</span>';
        case 'medium':
            return '<span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Medium Priority</span>';
        case 'low':
            return '<span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">Low Priority</span>';
        default:
            return '<span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700">' . htmlspecialchars(ucfirst($priority)) . '</span>';
    }
}

function getViewStatusBadge(string $status): string
{
    switch ($status) {
        case 'open':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200"><span class="w-2 h-2 rounded-full bg-amber-500 mr-2"></span> Open</span>';
        case 'in_progress':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 border border-indigo-200"><span class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></span> In Progress</span>';
        case 'resolved':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"><span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span> Resolved</span>';
        case 'closed':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600 border border-gray-200"><span class="w-2 h-2 rounded-full bg-gray-400 mr-2"></span> Closed</span>';
        default:
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-800">' . htmlspecialchars(ucfirst($status)) . '</span>';
    }
}
?>

<div class="p-4 md:p-6 max-w-5xl mx-auto space-y-6">
    <!-- Back button -->
    <div>
        <a href="index.php?page=support&action=list" class="inline-flex items-center gap-2 text-xs font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-2xs transition">
            <i class="fas fa-arrow-left"></i> Back to Support List
        </a>
    </div>

    <!-- Ticket Summary Card -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 border-b border-gray-100 pb-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-xs text-gray-500 font-mono">
                    <span class="font-bold text-[#d97824]"><?= htmlspecialchars($ticket['ticket_number']) ?></span>
                    <span>•</span>
                    <span>Created <?= date('M d, Y \a\t H:i', strtotime($ticket['created_at'])) ?></span>
                </div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900">
                    <?= htmlspecialchars($ticket['title']) ?>
                </h1>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <?= getViewTypeBadge((string) $ticket['type']) ?>
                <?= getViewPriorityBadge((string) $ticket['priority']) ?>
                <?= getViewStatusBadge((string) $ticket['status']) ?>
            </div>
        </div>

        <!-- Ticket Context Meta -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs bg-gray-50/80 p-3.5 rounded-lg border border-gray-100">
            <div>
                <span class="text-gray-400 font-medium block mb-0.5">Submitted By:</span>
                <span class="font-bold text-gray-800"><?= htmlspecialchars($ticket['user_name'] ?? 'System User') ?></span>
                <span class="text-gray-500 block text-[11px]"><?= htmlspecialchars($ticket['user_email'] ?? '') ?></span>
            </div>
            <div>
                <span class="text-gray-400 font-medium block mb-0.5">Module / Component:</span>
                <span class="font-bold text-gray-800"><?= !empty($ticket['module_name']) ? htmlspecialchars($ticket['module_name']) : 'N/A' ?></span>
            </div>
            <div>
                <span class="text-gray-400 font-medium block mb-0.5">Context Page URL:</span>
                <?php if (!empty($ticket['page_url'])): ?>
                <a href="<?= htmlspecialchars($ticket['page_url']) ?>" target="_blank" class="text-blue-600 font-medium hover:underline truncate block max-w-full">
                    <i class="fas fa-external-link-alt mr-1"></i> Open Page
                </a>
                <?php else: ?>
                <span class="text-gray-500">N/A</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Description -->
        <div class="space-y-2 pt-2">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Issue Description / Details:</h3>
            <div class="text-sm text-gray-800 leading-relaxed bg-white border border-gray-100 p-4 rounded-lg whitespace-pre-wrap">
                <?= nl2br(htmlspecialchars($ticket['description'])) ?>
            </div>
        </div>

        <!-- Attachment -->
        <?php if (!empty($ticket['attachment'])): ?>
        <div class="pt-2">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Attachment:</h3>
            <a href="<?= base_url($ticket['attachment']) ?>" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold text-xs rounded-lg border border-gray-200 transition">
                <i class="fas fa-paperclip text-[#d97824]"></i> View Attached File / Screenshot
            </a>
            <?php 
            $ext = strtolower(pathinfo($ticket['attachment'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)): 
            ?>
            <div class="mt-3 max-w-md border border-gray-200 rounded-lg overflow-hidden shadow-xs">
                <img src="<?= base_url($ticket['attachment']) ?>" alt="Support attachment" class="w-full h-auto object-cover">
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Admin Status Manager (If Admin) -->
    <?php if ($isAdmin): ?>
    <div class="bg-amber-50/60 p-4 rounded-xl border border-amber-200/80 shadow-sm space-y-3">
        <h3 class="text-xs font-bold text-amber-900 flex items-center gap-1.5 uppercase tracking-wider">
            <i class="fas fa-user-shield text-[#d97824]"></i> Admin Status & Priority Management
        </h3>
        <form id="updateStatusForm" onsubmit="handleStatusUpdateSubmit(event)" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="ticket_id" value="<?= (int) $ticket['id'] ?>">

            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-gray-700">Status:</label>
                <select name="status" class="h-9 px-3 text-xs border border-gray-300 rounded-md bg-white focus:outline-none focus:border-[#d97824]">
                    <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                    <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-gray-700">Priority:</label>
                <select name="priority" class="h-9 px-3 text-xs border border-gray-300 rounded-md bg-white focus:outline-none focus:border-[#d97824]">
                    <option value="low" <?= $ticket['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                    <option value="medium" <?= $ticket['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="high" <?= $ticket['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                    <option value="urgent" <?= $ticket['priority'] === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                </select>
            </div>

            <button type="submit" id="updateStatusBtn" class="h-9 px-4 bg-gray-800 hover:bg-gray-900 text-white font-semibold text-xs rounded-md shadow-sm transition">
                Save Changes
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Conversation & Comments Section -->
    <div class="space-y-4">
        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <i class="fas fa-comments text-[#d97824]"></i> Discussion & Progress Updates (<?= count($comments) ?>)
        </h2>

        <?php if (empty($comments)): ?>
        <div class="bg-white p-6 rounded-xl border border-gray-200 text-center text-gray-400 text-xs">
            No updates or comments posted yet. Add a reply below to update the ticket.
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($comments as $c): ?>
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm space-y-2">
                <div class="flex items-center justify-between text-xs border-b border-gray-100 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-gray-900"><?= htmlspecialchars($c['user_name'] ?? 'User') ?></span>
                        <span class="text-gray-400">•</span>
                        <span class="text-gray-500 text-[11px]"><?= date('M d, Y \a\t H:i', strtotime($c['created_at'])) ?></span>
                    </div>
                    <?php if ((int) $c['user_id'] === (int) $ticket['user_id']): ?>
                    <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-semibold rounded border border-blue-100">Ticket Creator</span>
                    <?php else: ?>
                    <span class="px-2 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-semibold rounded border border-purple-100">Support Responder</span>
                    <?php endif; ?>
                </div>

                <div class="text-xs text-gray-800 leading-relaxed whitespace-pre-wrap">
                    <?= nl2br(htmlspecialchars($c['comment'])) ?>
                </div>

                <?php if (!empty($c['attachment'])): ?>
                <div class="pt-2">
                    <a href="<?= base_url($c['attachment']) ?>" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-blue-600 hover:underline">
                        <i class="fas fa-paperclip"></i> View Comment Attachment
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Post Reply Form -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-reply text-[#d97824]"></i> Post a Reply / Update
            </h3>

            <form id="addCommentForm" onsubmit="handleAddCommentSubmit(event)" enctype="multipart/form-data" class="space-y-3">
                <input type="hidden" name="ticket_id" value="<?= (int) $ticket['id'] ?>">

                <div>
                    <textarea name="comment" 
                              required 
                              rows="3" 
                              placeholder="Type your response or update here..." 
                              class="w-full p-3 text-xs border border-gray-300 rounded-md focus:outline-none focus:border-[#d97824] resize-y"></textarea>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <input type="file" 
                               name="attachment" 
                               accept="image/*,.pdf,.doc,.docx,.zip,.txt" 
                               class="text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                    </div>

                    <div class="flex items-center gap-2">
                        <?php if ($isAdmin): ?>
                        <select name="status" class="h-9 px-2.5 text-xs border border-gray-300 rounded-md bg-white focus:outline-none focus:border-[#d97824]">
                            <option value="">Keep Status (<?= htmlspecialchars(ucfirst($ticket['status'])) ?>)</option>
                            <option value="open">Set to Open</option>
                            <option value="in_progress">Set to In Progress</option>
                            <option value="resolved">Set to Resolved</option>
                            <option value="closed">Set to Closed</option>
                        </select>
                        <?php endif; ?>

                        <button type="submit" id="submitReplyBtn" class="px-5 py-2 bg-[#d97824] hover:bg-[#c2671b] text-white font-semibold text-xs rounded-md shadow-sm transition flex items-center gap-1.5">
                            <i class="fas fa-paper-plane"></i> Send Reply
                        </button>
                    </div>
                </div>

                <div id="replyError" class="hidden p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-md"></div>
            </form>
        </div>
    </div>
</div>

<script>
function handleAddCommentSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('submitReplyBtn');
    const errBox = document.getElementById('replyError');

    errBox.classList.add('hidden');
    errBox.innerText = '';
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

    const formData = new FormData(form);

    fetch('index.php?page=support&action=add_comment', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reply';

        if (data.success) {
            window.location.reload();
        } else {
            errBox.innerText = data.message || 'Error sending reply.';
            errBox.classList.remove('hidden');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reply';
        errBox.innerText = 'Network error or server failed to respond.';
        errBox.classList.remove('hidden');
    });
}

function handleStatusUpdateSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('updateStatusBtn');

    btn.disabled = true;
    btn.innerText = 'Saving...';

    const formData = new FormData(form);

    fetch('index.php?page=support&action=update_status', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerText = 'Save Changes';

        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Failed to update status.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = 'Save Changes';
        alert('Network error.');
    });
}
</script>
