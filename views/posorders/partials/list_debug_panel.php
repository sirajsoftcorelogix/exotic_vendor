<?php
$listDebug = $list_debug ?? null;
if (!is_array($listDebug)) {
    return;
}

$renderQueryBlock = static function (?array $queryDebug, string $title): void {
    if (!is_array($queryDebug)) {
        echo '<p class="text-sm text-gray-500">No ' . htmlspecialchars($title) . ' captured.</p>';
        return;
    }
    ?>
    <div class="mb-4">
        <h4 class="text-sm font-semibold text-gray-800 mb-2"><?= htmlspecialchars($title) ?></h4>
        <?php if (!empty($queryDebug['params'])): ?>
            <p class="text-xs font-medium text-gray-600 mb-1">Bound parameters</p>
            <pre class="text-xs bg-gray-100 border border-gray-200 rounded p-3 overflow-x-auto mb-2"><?=
                htmlspecialchars(json_encode($queryDebug['params'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
            ?></pre>
        <?php endif; ?>
        <p class="text-xs font-medium text-gray-600 mb-1">Prepared SQL</p>
        <pre class="text-xs bg-gray-100 border border-gray-200 rounded p-3 overflow-x-auto mb-2 whitespace-pre-wrap"><?=
            htmlspecialchars((string) ($queryDebug['sql'] ?? ''))
        ?></pre>
        <p class="text-xs font-medium text-gray-600 mb-1">Interpolated SQL (debug only)</p>
        <pre class="text-xs bg-amber-50 border border-amber-200 rounded p-3 overflow-x-auto whitespace-pre-wrap"><?=
            htmlspecialchars((string) ($queryDebug['interpolated'] ?? ''))
        ?></pre>
    </div>
    <?php
};
?>
<section class="mx-2 mb-6 mt-4 border border-dashed border-amber-400 rounded-lg bg-amber-50/40">
    <details class="p-4" open>
        <summary class="cursor-pointer text-sm font-semibold text-amber-900 select-none">
            Debug — POS Orders list query &amp; login user
        </summary>
        <div class="mt-4 space-y-4">
            <div>
                <h4 class="text-sm font-semibold text-gray-800 mb-2">Login user</h4>
                <pre class="text-xs bg-white border border-gray-200 rounded p-3 overflow-x-auto"><?=
                    htmlspecialchars(json_encode([
                        'user' => $listDebug['user'] ?? [],
                        'session_user_id' => $listDebug['session_user_id'] ?? null,
                        'session_warehouse_id' => $listDebug['session_warehouse_id'] ?? null,
                        'resolved_warehouse_id' => $listDebug['resolved_warehouse_id'] ?? null,
                        'is_administrator' => $listDebug['is_administrator'] ?? false,
                        'can_view_all_warehouses' => $listDebug['can_view_all_warehouses'] ?? false,
                        'page_module_names' => $listDebug['page_module_names'] ?? [],
                        'request_page' => $listDebug['request_page'] ?? '',
                        'request_action' => $listDebug['request_action'] ?? '',
                        'generated_at' => $listDebug['generated_at'] ?? '',
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                ?></pre>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-gray-800 mb-2">Active filters (after warehouse scope)</h4>
                <pre class="text-xs bg-white border border-gray-200 rounded p-3 overflow-x-auto"><?=
                    htmlspecialchars(json_encode($listDebug['filters'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                ?></pre>
            </div>

            <?php $renderQueryBlock($listDebug['list_query'] ?? null, 'List query (getAllOrders)'); ?>
            <?php $renderQueryBlock($listDebug['count_query'] ?? null, 'Count query (getOrdersCount)'); ?>
        </div>
    </details>
</section>
