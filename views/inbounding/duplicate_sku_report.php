<?php
/**
 * Duplicate Product SKUs & Inbound Audit Report View
 */
$rows = $data['rows'] ?? [];
$stats = $data['stats'] ?? [];
$page = (int)($data['page'] ?? 1);
$totalPages = (int)($data['total_pages'] ?? 1);
$totalRows = (int)($data['total'] ?? 0);
$limit = (int)($data['limit'] ?? 50);

$search = $data['search'] ?? '';
$filters = $data['filters'] ?? [];
$selInboundStatus = $filters['inbound_status'] ?? '';
$inboundFrom = $filters['inbound_from'] ?? '';
$inboundTo = $filters['inbound_to'] ?? '';

// Build query string for pagination links
function buildDuplicateReportUrl($p, $search, $filters, $limit) {
    $params = [
        'page' => 'inbounding',
        'action' => 'duplicateSkuReport',
        'page_no' => $p,
        'limit' => $limit,
    ];
    if (!empty($search)) $params['search_text'] = $search;
    if (!empty($filters['inbound_status'])) $params['inbound_status'] = $filters['inbound_status'];
    if (!empty($filters['inbound_from'])) $params['inbound_from'] = $filters['inbound_from'];
    if (!empty($filters['inbound_to'])) $params['inbound_to'] = $filters['inbound_to'];
    return '?' . http_build_query($params);
}

// Build query string for CSV export link
$exportParams = array_merge([
    'page' => 'inbounding',
    'action' => 'exportDuplicateSkuReport',
], array_filter([
    'search_text' => $search,
    'inbound_status' => $selInboundStatus,
    'inbound_from' => $inboundFrom,
    'inbound_to' => $inboundTo,
]));
$exportUrl = '?' . http_build_query($exportParams);
?>

<div class="w-full max-w-7xl mx-auto space-y-5 p-2 md:p-4">

    <!-- Top Header & Breadcrumb -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-1">
                <a href="<?php echo base_url('?page=products&action=list'); ?>" class="hover:text-amber-600 transition flex items-center gap-1">
                    <i class="fas fa-arrow-left text-xs"></i> Product Listing
                </a>
                <span>/</span>
                <span class="text-gray-800">Duplicate SKU Audit Report</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-clone text-amber-600"></i> Duplicate Product SKUs & Inbound Audit Report
            </h1>
            <p class="text-xs text-gray-500 mt-1">Audit products sharing identical SKUs and trace if they were created via the Inbound process & which user inbounded them.</p>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
            <a href="<?php echo base_url($exportUrl); ?>" class="w-full md:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition">
                <i class="fas fa-file-excel"></i> Export CSV
            </a>
            <a href="<?php echo base_url('?page=products&action=list'); ?>" class="w-full md:w-auto inline-flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs px-4 py-2.5 rounded-xl border border-gray-300 transition">
                <i class="fas fa-boxes"></i> Back to Products
            </a>
        </div>
    </div>

    <!-- Filter & Search Panel -->
    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
        <form method="get" action="" class="space-y-4">
            <input type="hidden" name="page" value="inbounding">
            <input type="hidden" name="action" value="duplicateSkuReport">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Search Keywords</label>
                    <div class="relative">
                        <input type="text" name="search_text" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search SKU, Title, Code, User..." class="w-full h-10 border border-gray-300 rounded-xl pl-3 pr-8 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <span class="absolute right-2.5 top-2.5 text-gray-400"><i class="fas fa-search text-xs"></i></span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Inbound Status</label>
                    <select name="inbound_status" class="w-full h-10 border border-gray-300 rounded-xl px-3 text-xs bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="inbounded" <?php echo $selInboundStatus === 'inbounded' ? 'selected' : ''; ?>>Inbounded Only (Matched in Inbound process)</option>
                        <option value="not_inbounded" <?php echo $selInboundStatus === 'not_inbounded' ? 'selected' : ''; ?>>Non-Inbounded Only (Direct Catalog / Import)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Inbound From Date</label>
                    <input type="date" name="inbound_from" value="<?php echo htmlspecialchars($inboundFrom, ENT_QUOTES, 'UTF-8'); ?>" class="w-full h-10 border border-gray-300 rounded-xl px-3 text-xs bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Inbound To Date</label>
                    <input type="date" name="inbound_to" value="<?php echo htmlspecialchars($inboundTo, ENT_QUOTES, 'UTF-8'); ?>" class="w-full h-10 border border-gray-300 rounded-xl px-3 text-xs bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                <div class="text-xs text-gray-500 font-medium">
                    Showing <span class="font-bold text-gray-800"><?php echo count($rows); ?></span> of <span class="font-bold text-gray-800"><?php echo number_format($totalRows); ?></span> duplicate product records
                </div>
                <div class="flex items-center gap-2">
                    <a href="<?php echo base_url('?page=inbounding&action=duplicateSkuReport'); ?>" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition">
                        Reset Filters
                    </a>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center gap-1.5">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <?php if (empty($rows)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 text-2xl mx-auto mb-3">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="text-base font-bold text-gray-800">No Duplicate SKUs Found</h3>
                <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto">There are no duplicate SKUs matching your filter criteria in <code class="bg-gray-100 px-1 rounded">vp_products</code>.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Duplicate SKU</th>
                            <th class="py-3 px-4">Product Info</th>
                            <th class="py-3 px-4">Group Name</th>
                            <th class="py-3 px-4">Title & Attributes</th>
                            <th class="py-3 px-4">Product Added</th>
                            <th class="py-3 px-4">Inbound Process?</th>
                            <th class="py-3 px-4">Inbound Record</th>
                            <th class="py-3 px-4">Inbound Date</th>
                            <th class="py-3 px-4">Inbound Users</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs text-gray-700">
                        <?php 
                        $currentClusterSku = null;
                        $clusterIndex = 0;
                        $clusterColors = ['bg-amber-50/50', 'bg-white'];
                        
                        foreach ($rows as $idx => $r):
                            $skuLower = strtolower(trim((string)($r['product_sku'] ?? '')));
                            if ($skuLower !== $currentClusterSku) {
                                $currentClusterSku = $skuLower;
                                $clusterIndex++;
                            }
                            $rowBg = $clusterIndex % 2 === 0 ? 'bg-amber-50/20' : 'bg-white';
                            
                            $inboundStatus = $r['inbound_status'] ?? 'Not Inbounded';
                            $isInbounded = ($inboundStatus !== 'Not Inbounded');
                        ?>
                            <tr class="<?php echo $rowBg; ?> hover:bg-amber-100/30 transition-colors">
                                <td class="py-3.5 px-4 font-mono text-[11px] text-gray-400">
                                    <?php echo ($page - 1) * $limit + $idx + 1; ?>
                                </td>

                                <td class="py-3.5 px-4 font-mono font-bold text-gray-900 select-all">
                                    <span class="inline-block bg-amber-100 text-amber-900 px-2 py-0.5 rounded border border-amber-200">
                                        <?php echo htmlspecialchars((string)($r['product_sku'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-gray-900">ID: #<?php echo (int)($r['product_id'] ?? 0); ?></div>
                                    <div class="text-[11px] font-mono text-gray-500 mt-0.5">Code: <?php echo htmlspecialchars((string)($r['product_item_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>

                                <td class="py-3.5 px-4 font-medium text-gray-800">
                                    <span class="inline-block bg-gray-100 text-gray-800 px-2 py-0.5 rounded border border-gray-200 font-semibold text-[11px]">
                                        <?php echo htmlspecialchars((string)($r['product_group_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="font-medium text-gray-800 line-clamp-2" title="<?php echo htmlspecialchars((string)($r['product_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars((string)($r['product_title'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="flex items-center gap-2 text-[11px] text-gray-500 mt-1">
                                        <?php if (!empty($r['product_size'])): ?>
                                            <span class="bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">Size: <strong><?php echo htmlspecialchars((string)$r['product_size'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
                                        <?php endif; ?>
                                        <?php if (!empty($r['product_color'])): ?>
                                            <span class="bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">Color: <strong><?php echo htmlspecialchars((string)$r['product_color'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-gray-600 text-[11px]">
                                    <?php 
                                        $prodDate = $r['product_created_on'] ?? $r['product_updated_at'] ?? '';
                                        echo !empty($prodDate) ? date('d M Y, h:i A', strtotime($prodDate)) : '<span class="text-gray-400">N/A</span>';
                                    ?>
                                </td>

                                <td class="py-3.5 px-4">
                                    <?php if ($inboundStatus === 'Main Item Inbound'): ?>
                                        <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 font-bold text-[10px] px-2.5 py-1 rounded-full border border-emerald-200">
                                            <i class="fas fa-check-circle text-[9px]"></i> Main Inbound
                                        </span>
                                    <?php elseif ($inboundStatus === 'Variation Inbound'): ?>
                                        <span class="inline-flex items-center gap-1 bg-teal-100 text-teal-800 font-bold text-[10px] px-2.5 py-1 rounded-full border border-teal-200">
                                            <i class="fas fa-layer-group text-[9px]"></i> Variation Inbound
                                        </span>
                                    <?php elseif ($inboundStatus === 'Item Code Match'): ?>
                                        <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-800 font-bold text-[10px] px-2.5 py-1 rounded-full border border-blue-200">
                                            <i class="fas fa-link text-[9px]"></i> Code Match
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 font-bold text-[10px] px-2.5 py-1 rounded-full border border-gray-200">
                                            <i class="fas fa-times-circle text-[9px]"></i> Direct / Catalog
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3.5 px-4">
                                    <?php if (!empty($r['inbound_id'])): ?>
                                        <a href="<?php echo base_url('?page=inbounding&action=desktopform&id=' . (int)$r['inbound_id']); ?>" target="_blank" class="font-bold text-amber-700 hover:text-amber-900 hover:underline flex items-center gap-1">
                                            #<?php echo (int)$r['inbound_id']; ?> <i class="fas fa-external-link-alt text-[10px]"></i>
                                        </a>
                                        <div class="text-[11px] text-gray-500 font-mono mt-0.5">
                                            Code: <?php echo htmlspecialchars((string)($r['inbound_item_code'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400 font-italic text-[11px]">N/A</span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3.5 px-4 text-gray-700 text-[11px]">
                                    <?php 
                                        $inbDate = $r['inbound_created_at'] ?? $r['inbound_added_date'] ?? '';
                                        if (!empty($inbDate)) {
                                            echo date('d M Y, h:i A', strtotime($inbDate));
                                        } else {
                                            echo '<span class="text-gray-400">N/A</span>';
                                        }
                                    ?>
                                </td>

                                <td class="py-3.5 px-4">
                                    <?php if (!empty($r['received_by_user_name'])): ?>
                                        <div class="flex items-center gap-1 text-[11px] text-gray-800 font-semibold" title="Received / Created By">
                                            <i class="fas fa-user-plus text-emerald-600 text-[10px]"></i>
                                            <span><?php echo htmlspecialchars((string)$r['received_by_user_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-[11px]">User N/A</span>
                                    <?php endif; ?>

                                    <?php if (!empty($r['updated_by_user_name'])): ?>
                                        <div class="flex items-center gap-1 text-[10px] text-gray-500 mt-0.5" title="Feeded / Updated By">
                                            <i class="fas fa-user-edit text-amber-600 text-[9px]"></i>
                                            <span><?php echo htmlspecialchars((string)$r['updated_by_user_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <button type="button" 
                                            onclick="deleteDuplicateProduct(<?php echo (int)$r['product_id']; ?>, '<?php echo htmlspecialchars(addslashes((string)$r['product_sku']), ENT_QUOTES, 'UTF-8'); ?>')"
                                            class="px-3 py-1 bg-red-50 hover:bg-red-100 text-red-600 hover:text-red-800 font-bold text-[11px] rounded-lg border border-red-200 transition inline-flex items-center gap-1.5 shadow-sm">
                                        <i class="fas fa-trash-alt text-[10px]"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <?php if ($totalPages > 1): ?>
                <div class="p-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-gray-500 font-medium">
                        Page <span class="font-bold text-gray-800"><?php echo $page; ?></span> of <span class="font-bold text-gray-800"><?php echo $totalPages; ?></span>
                    </div>

                    <div class="flex items-center gap-1">
                        <?php if ($page > 1): ?>
                            <a href="<?php echo base_url(buildDuplicateReportUrl($page - 1, $search, $filters, $limit)); ?>" class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-lg transition">
                                &laquo; Prev
                            </a>
                        <?php endif; ?>

                        <?php 
                        $startP = max(1, $page - 2);
                        $endP = min($totalPages, $page + 2);
                        for ($p = $startP; $p <= $endP; $p++):
                            $activeClass = ($p === $page) ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100';
                        ?>
                            <a href="<?php echo base_url(buildDuplicateReportUrl($p, $search, $filters, $limit)); ?>" class="px-3 py-1.5 border text-xs font-bold rounded-lg transition <?php echo $activeClass; ?>">
                                <?php echo $p; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?php echo base_url(buildDuplicateReportUrl($page + 1, $search, $filters, $limit)); ?>" class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-lg transition">
                                Next &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div>

<script>
function deleteDuplicateProduct(productId, sku) {
    if (!productId) return;

    const confirmMessage = `Are you sure you want to delete duplicate product #${productId} (SKU: ${sku}) from vp_products?`;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete Duplicate Product?',
            text: `Are you sure you want to delete product #${productId} (SKU: ${sku}) from vp_products? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Delete Product'
        }).then((result) => {
            if (result.isConfirmed) {
                executeProductDelete(productId);
            }
        });
    } else {
        if (confirm(confirmMessage)) {
            executeProductDelete(productId);
        }
    }
}

function executeProductDelete(productId) {
    const formData = new FormData();
    formData.append('product_id', productId);

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Deleting Product...',
            text: 'Removing product from catalog...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    }

    fetch('index.php?page=inbounding&action=deleteDuplicateProduct', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: data.message || 'Product deleted successfully.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                alert(data.message || 'Product deleted successfully.');
                window.location.reload();
            }
        } else {
            const errorMsg = (data && data.message) ? data.message : 'Failed to delete product.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Delete Failed',
                    text: errorMsg
                });
            } else {
                alert(errorMsg);
            }
        }
    })
    .catch(err => {
        console.error('Delete error:', err);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while deleting the product.'
            });
        } else {
            alert('An error occurred while deleting the product.');
        }
    });
}
</script>
