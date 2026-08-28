<?php
/**
 * @var array<array<string, mixed>> $tickets
 * @var int $total
 * @var int $currentPage
 * @var int $totalPages
 * @var int $limit
 * @var string $search
 * @var string $status
 * @var string $type
 * @var string $priority
 * @var string $scope
 * @var array<string, int> $stats
 * @var bool $isAdmin
 */

function getSupportTypeBadge(string $type): string
{
    switch ($type) {
        case 'bug':
            return '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200"><i class="fas fa-bug text-[10px]"></i> Bug / Error</span>';
        case 'change':
            return '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200"><i class="fas fa-sync-alt text-[10px]"></i> Change Request</span>';
        case 'feature':
            return '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-200"><i class="fas fa-lightbulb text-[10px]"></i> New Feature</span>';
        default:
            return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">' . htmlspecialchars(ucfirst($type)) . '</span>';
    }
}

function getSupportPriorityBadge(string $priority): string
{
    switch ($priority) {
        case 'urgent':
            return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-600 text-white"><i class="fas fa-exclamation-triangle mr-1"></i> Urgent</span>';
        case 'high':
            return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-orange-100 text-orange-800 border border-orange-200">High</span>';
        case 'medium':
            return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Medium</span>';
        case 'low':
            return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">Low</span>';
        default:
            return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">' . htmlspecialchars(ucfirst($priority)) . '</span>';
    }
}

function getSupportStatusBadge(string $status): string
{
    switch ($status) {
        case 'open':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span> Open</span>';
        case 'in_progress':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200"><span class="w-1.5 h-1.5 rounded-full bg-indigo-500 mr-1.5"></span> In Progress</span>';
        case 'resolved':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Resolved</span>';
        case 'closed':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200"><span class="w-1.5 h-1.5 rounded-full bg-gray-400 mr-1.5"></span> Closed</span>';
        default:
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">' . htmlspecialchars(ucfirst($status)) . '</span>';
    }
}
?>

<div class="p-4 md:p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header Title & Action -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-headset text-[#d97824]"></i> Support & Request Tracker
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Report bugs, request system changes, or suggest new features to the development team.
            </p>
        </div>
        <div>
            <button type="button" 
                    onclick="openCreateTicketModal()" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#d97824] hover:bg-[#c2671b] text-white font-semibold text-sm rounded-lg shadow-sm transition cursor-pointer">
                <i class="fas fa-plus-circle"></i> Submit New Request
            </button>
        </div>
    </div>

    <!-- Stats Summary Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <div class="bg-white p-3.5 rounded-lg border border-gray-200 shadow-sm">
            <div class="text-xs font-semibold text-gray-500 uppercase">Total Tickets</div>
            <div class="text-xl font-bold text-gray-800 mt-1"><?= (int) ($stats['total'] ?? 0) ?></div>
        </div>
        <div class="bg-amber-50/60 p-3.5 rounded-lg border border-amber-200/80 shadow-sm">
            <div class="text-xs font-semibold text-amber-700 uppercase">Open</div>
            <div class="text-xl font-bold text-amber-800 mt-1"><?= (int) ($stats['open'] ?? 0) ?></div>
        </div>
        <div class="bg-indigo-50/60 p-3.5 rounded-lg border border-indigo-200/80 shadow-sm">
            <div class="text-xs font-semibold text-indigo-700 uppercase">In Progress</div>
            <div class="text-xl font-bold text-indigo-800 mt-1"><?= (int) ($stats['in_progress'] ?? 0) ?></div>
        </div>
        <div class="bg-emerald-50/60 p-3.5 rounded-lg border border-emerald-200/80 shadow-sm">
            <div class="text-xs font-semibold text-emerald-700 uppercase">Resolved</div>
            <div class="text-xl font-bold text-emerald-800 mt-1"><?= (int) ($stats['resolved'] ?? 0) ?></div>
        </div>
        <div class="bg-red-50/50 p-3.5 rounded-lg border border-red-200/60 shadow-sm">
            <div class="text-xs font-semibold text-red-700 uppercase">Bugs</div>
            <div class="text-xl font-bold text-red-800 mt-1"><?= (int) ($stats['bug'] ?? 0) ?></div>
        </div>
        <div class="bg-blue-50/50 p-3.5 rounded-lg border border-blue-200/60 shadow-sm">
            <div class="text-xs font-semibold text-blue-700 uppercase">Changes</div>
            <div class="text-xl font-bold text-blue-800 mt-1"><?= (int) ($stats['change'] ?? 0) ?></div>
        </div>
        <div class="bg-purple-50/50 p-3.5 rounded-lg border border-purple-200/60 shadow-sm col-span-2 sm:col-span-1">
            <div class="text-xs font-semibold text-purple-700 uppercase">Features</div>
            <div class="text-xl font-bold text-purple-800 mt-1"><?= (int) ($stats['feature'] ?? 0) ?></div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <form method="GET" action="index.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
            <input type="hidden" name="page" value="support">
            <input type="hidden" name="action" value="list">

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Search Keywords</label>
                <input type="text" 
                       name="search" 
                       value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Ticket #, title, module..." 
                       class="w-full h-9 px-3 text-xs border border-gray-300 rounded-md focus:outline-none focus:border-[#d97824]">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Type</label>
                <select name="type" class="w-full h-9 px-3 text-xs border border-gray-300 rounded-md focus:outline-none focus:border-[#d97824] bg-white">
                    <option value="">All Types</option>
                    <option value="bug" <?= $type === 'bug' ? 'selected' : '' ?>>Bug / Error</option>
                    <option value="change" <?= $type === 'change' ? 'selected' : '' ?>>Change Request</option>
                    <option value="feature" <?= $type === 'feature' ? 'selected' : '' ?>>New Feature</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Status</label>
                <select name="status" class="w-full h-9 px-3 text-xs border border-gray-300 rounded-md focus:outline-none focus:border-[#d97824] bg-white">
                    <option value="">All Statuses</option>
                    <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
                    <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Priority</label>
                <select name="priority" class="w-full h-9 px-3 text-xs border border-gray-300 rounded-md focus:outline-none focus:border-[#d97824] bg-white">
                    <option value="">All Priorities</option>
                    <option value="urgent" <?= $priority === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                    <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>High</option>
                    <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Low</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 h-9 bg-gray-800 hover:bg-gray-900 text-white font-semibold text-xs rounded-md shadow-sm transition">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="index.php?page=support&action=list" class="h-9 px-3 bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold text-xs rounded-md flex items-center justify-center transition" title="Reset Filters">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>

        <?php if ($isAdmin): ?>
        <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100 text-xs">
            <span class="font-bold text-gray-500">View Scope:</span>
            <a href="index.php?page=support&action=list&scope=all" class="<?= $scope === 'all' ? 'font-bold text-[#d97824] underline' : 'text-gray-600 hover:text-gray-800' ?>">All Tickets</a>
            <span class="text-gray-300">|</span>
            <a href="index.php?page=support&action=list&scope=my" class="<?= $scope === 'my' ? 'font-bold text-[#d97824] underline' : 'text-gray-600 hover:text-gray-800' ?>">My Tickets Only</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tickets Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Ticket #</th>
                        <th class="px-4 py-3">Subject / Title</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Submitted By</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-gray-700">
                    <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                            <i class="fas fa-ticket-alt text-3xl mb-2 text-gray-300"></i>
                            <p class="font-medium text-sm text-gray-500">No support tickets found.</p>
                            <p class="text-xs text-gray-400 mt-1">Submit a new ticket or try adjusting your search filters.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="px-4 py-3.5 font-mono font-bold text-[#d97824]">
                            <a href="index.php?page=support&action=view&id=<?= (int) $t['id'] ?>" class="hover:underline">
                                <?= htmlspecialchars($t['ticket_number']) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3.5">
                            <a href="index.php?page=support&action=view&id=<?= (int) $t['id'] ?>" class="font-bold text-gray-900 hover:text-[#d97824] block">
                                <?= htmlspecialchars($t['title']) ?>
                            </a>
                            <?php if (!empty($t['module_name'])): ?>
                            <span class="inline-block mt-0.5 text-[11px] text-gray-400">
                                <i class="fas fa-cube mr-1 text-gray-300"></i><?= htmlspecialchars($t['module_name']) ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3.5">
                            <?= getSupportTypeBadge((string) $t['type']) ?>
                        </td>
                        <td class="px-4 py-3.5">
                            <?= getSupportPriorityBadge((string) $t['priority']) ?>
                        </td>
                        <td class="px-4 py-3.5">
                            <?= getSupportStatusBadge((string) $t['status']) ?>
                        </td>
                        <td class="px-4 py-3.5 text-gray-600">
                            <div class="font-medium text-gray-800"><?= htmlspecialchars($t['user_name'] ?? 'System User') ?></div>
                            <div class="text-[11px] text-gray-400"><?= htmlspecialchars($t['user_email'] ?? '') ?></div>
                        </td>
                        <td class="px-4 py-3.5 text-gray-500 whitespace-nowrap">
                            <?= date('M d, Y H:i', strtotime($t['created_at'])) ?>
                        </td>
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <a href="index.php?page=support&action=view&id=<?= (int) $t['id'] ?>" 
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-xs rounded border border-gray-200 transition">
                                <i class="fas fa-comments"></i> View
                                <?php if ((int)($t['comment_count'] ?? 0) > 0): ?>
                                <span class="bg-gray-200 text-gray-700 px-1.5 py-0.2 text-[10px] rounded-full font-bold">
                                    <?= (int) $t['comment_count'] ?>
                                </span>
                                <?php endif; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between text-xs text-gray-600">
            <div>
                Showing page <span class="font-bold"><?= $currentPage ?></span> of <span class="font-bold"><?= $totalPages ?></span> (<?= $total ?> total)
            </div>
            <div class="flex gap-1">
                <?php if ($currentPage > 1): ?>
                <a href="index.php?page=support&action=list&page_no=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&priority=<?= urlencode($priority) ?>&scope=<?= urlencode($scope) ?>" 
                   class="px-3 py-1 bg-white border border-gray-300 rounded text-gray-700 hover:bg-gray-100 font-semibold">Prev</a>
                <?php endif; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                <a href="index.php?page=support&action=list&page_no=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&priority=<?= urlencode($priority) ?>&scope=<?= urlencode($scope) ?>" 
                   class="px-3 py-1 bg-white border border-gray-300 rounded text-gray-700 hover:bg-gray-100 font-semibold">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- CREATE TICKET MODAL -->
<div id="createTicketModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-2xl overflow-hidden transform transition-all">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-plus-circle text-[#d97824]"></i> Raise Support Request / Report Bug
            </h3>
            <button type="button" onclick="closeCreateTicketModal()" class="text-gray-400 hover:text-gray-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>

        <!-- Modal Form -->
        <form id="createTicketForm" onsubmit="handleCreateTicketSubmit(event)" enctype="multipart/form-data" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Subject / Title <span class="text-red-500">*</span></label>
                <input type="text" 
                       name="title" 
                       required 
                       placeholder="e.g. Cannot save invoice due to error / Request to add PDF export option" 
                       class="w-full h-10 px-3 text-xs border border-gray-300 rounded-md focus:outline-none focus:border-[#d97824]">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Category / Type <span class="text-red-500">*</span></label>
                    <select name="type" required class="w-full h-9 px-3 text-xs border border-gray-300 rounded-md focus:outline-none focus:border-[#d97824] bg-white">
                        <option value="bug">🐛 Bug / System Error</option>
                        <option value="change">🔄 Change Request</option>
                        <option value="feature">💡 New Feature Need</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Priority Level <span class="text-red-500">*</span></label>
                    <select name="priority" required class="w-full h-9 px-3 text-xs border border-gray-300 rounded-md focus:outline-none focus:border-[#d97824] bg-white">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent / Blocking</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Module / Page Name</label>
                    <input type="text" 
                           name="module_name" 
                           placeholder="e.g. POS / Inbounding / Direct Purchase" 
                           class="w-full h-9 px-3 text-xs border border-gray-300 rounded-md focus:outline-none focus:border-[#d97824]">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Page URL (Optional)</label>
                <input type="text" 
                       name="page_url" 
                       id="page_url_input" 
                       placeholder="https://..." 
                       class="w-full h-9 px-3 text-xs border border-gray-300 rounded-md focus:outline-none focus:border-[#d97824]">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Description / Details <span class="text-red-500">*</span></label>
                <textarea name="description" 
                          required 
                          rows="5" 
                          placeholder="Describe the issue, steps to reproduce, or requirements for the new feature in detail..." 
                          class="w-full p-3 text-xs border border-gray-300 rounded-md focus:outline-none focus:border-[#d97824] resize-y"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Screenshot or Attachment (Optional)</label>
                <input type="file" 
                       name="attachment" 
                       accept="image/*,.pdf,.doc,.docx,.zip,.txt" 
                       class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                <p class="text-[11px] text-gray-400 mt-1">Accepted file types: PNG, JPG, PDF, DOC, TXT, ZIP</p>
            </div>

            <!-- Error Box -->
            <div id="createTicketError" class="hidden p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-md"></div>

            <!-- Modal Footer -->
            <div class="pt-3 border-t border-gray-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeCreateTicketModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-xs rounded-md transition">
                    Cancel
                </button>
                <button type="submit" id="submitTicketBtn" class="px-5 py-2 bg-[#d97824] hover:bg-[#c2671b] text-white font-semibold text-xs rounded-md shadow-sm transition flex items-center gap-1.5">
                    <i class="fas fa-paper-plane"></i> Submit Ticket
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateTicketModal() {
    const modal = document.getElementById('createTicketModal');
    if (modal) {
        modal.classList.remove('hidden');
        // Pre-fill URL field if empty
        const urlInput = document.getElementById('page_url_input');
        if (urlInput && !urlInput.value) {
            urlInput.value = window.location.href;
        }
    }
}

function closeCreateTicketModal() {
    const modal = document.getElementById('createTicketModal');
    if (modal) {
        modal.classList.add('hidden');
        document.getElementById('createTicketForm').reset();
        document.getElementById('createTicketError').classList.add('hidden');
    }
}

function handleCreateTicketSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('submitTicketBtn');
    const errBox = document.getElementById('createTicketError');

    errBox.classList.add('hidden');
    errBox.innerText = '';
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    const formData = new FormData(form);

    fetch('index.php?page=support&action=save', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Ticket';

        if (data.success) {
            closeCreateTicketModal();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Ticket Created!',
                    text: data.message || 'Your support request has been submitted.',
                    icon: 'success',
                    confirmButtonColor: '#d97824'
                }).then(() => {
                    window.location.href = 'index.php?page=support&action=view&id=' + data.ticket_id;
                });
            } else {
                window.location.href = 'index.php?page=support&action=view&id=' + data.ticket_id;
            }
        } else {
            errBox.innerText = data.message || 'Error submitting support ticket.';
            errBox.classList.remove('hidden');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Ticket';
        errBox.innerText = 'Network error or server failed to respond.';
        errBox.classList.remove('hidden');
    });
}
</script>
