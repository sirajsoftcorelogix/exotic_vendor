<?php
/** @var array $data — Picklist user wiki (in-portal) */
?>
<div class="w-full max-w-4xl mx-auto px-2 py-4 sm:px-4">
    <nav class="mb-4" aria-label="Wiki navigation">
        <a href="?page=picklist&action=list"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold shadow-sm hover:bg-gray-50 hover:border-gray-400 transition">
            <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
            Back to picklists
        </a>
    </nav>
    <?php include __DIR__ . '/partials/wiki_body.php'; ?>
</div>
