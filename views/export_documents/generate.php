<?php
/** @var array<string, mixed> $session */
/** @var array<string, array<string, mixed>> $forms */
/** @var array<string, string> $requiredDocs */
/** @var string $activeTab */

$activeForm = $forms[$activeTab] ?? [];
$activeFormData = $activeForm['form_data'] ?? [];
$activeTitle = $requiredDocs[$activeTab] ?? 'Export Document';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    <!-- Top Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="bg-blue-600 text-white text-xs font-bold px-2.5 py-0.5 rounded-full">
                    <?= htmlspecialchars($session['session_code']) ?>
                </span>
                <span class="text-xs font-semibold text-gray-500 uppercase">
                    Shipment: <strong class="text-gray-900"><?= htmlspecialchars(strtoupper($session['shipment_type'])) ?></strong>
                </span>
                <span class="text-xs font-semibold text-gray-500 uppercase">
                    Carrier: <strong class="text-gray-900"><?= htmlspecialchars(strtoupper($session['courier_partner'])) ?></strong>
                </span>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mt-1">
                Invoice: <?= htmlspecialchars($session['invoice_number'] ?: 'N/A') ?>
                <?php if ($session['order_number']): ?>
                    <span class="text-sm font-normal text-gray-500">(Order #<?= htmlspecialchars($session['order_number']) ?>)</span>
                <?php endif; ?>
            </h1>
        </div>

        <div class="flex items-center space-x-3">
            <a href="index.php?page=export_documents" class="px-3.5 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 flex items-center gap-1.5">
                <i class="fas fa-arrow-left"></i> Change Configuration
            </a>
            <a href="index.php?page=export_documents&action=preview&session_code=<?= urlencode($session['session_code']) ?>" target="_blank"
               class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-1.5">
                <i class="fas fa-print"></i> Preview & Print All
            </a>
        </div>
    </div>

    <!-- Wizard Layout: Sidebar Tabs + Form Container -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Document Tabs Navigation Sidebar -->
        <div class="lg:col-span-1 space-y-2">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider px-1">
                Required Documents Checklist
            </h3>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden divide-y divide-gray-100">
                <?php foreach ($requiredDocs as $docCode => $docTitle): ?>
                    <?php
                        $isCurrent = ($docCode === $activeTab);
                        $hasForm = isset($forms[$docCode]);
                        $isDone = $hasForm && !empty($forms[$docCode]['is_completed']);
                    ?>
                    <a href="index.php?page=export_documents&action=generate&session_code=<?= urlencode($session['session_code']) ?>&tab=<?= urlencode($docCode) ?>"
                       class="p-3.5 flex items-center justify-between text-xs font-medium transition-colors <?= $isCurrent ? 'bg-blue-50/90 text-blue-900 border-l-4 border-blue-600 font-bold' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <span class="flex items-center gap-2">
                            <i class="fas <?= $isDone ? 'fa-check-circle text-emerald-600' : 'fa-file-lines text-gray-400' ?>"></i>
                            <?= htmlspecialchars($docTitle) ?>
                        </span>
                        <?php if ($isDone): ?>
                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-1.5 py-0.5 rounded">Ready</span>
                        <?php else: ?>
                            <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-1.5 py-0.5 rounded">Draft</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Document Form Work Area -->
        <div class="lg:col-span-3">
            <form id="documentForm" method="POST" action="index.php?page=export_documents&action=save_form" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
                <input type="hidden" name="session_code" value="<?= htmlspecialchars($session['session_code']) ?>">
                <input type="hidden" name="document_code" value="<?= htmlspecialchars($activeTab) ?>">
                <input type="hidden" name="document_title" value="<?= htmlspecialchars($activeTitle) ?>">

                <!-- Active Document Header -->
                <div class="flex items-center justify-between border-b border-gray-200 pb-4">
                    <div>
                        <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Active Document Form</span>
                        <h2 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($activeTitle) ?></h2>
                    </div>
                    <label class="inline-flex items-center space-x-2 text-xs text-gray-800 cursor-pointer bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                        <input type="checkbox" name="is_completed" value="1" <?= !empty($activeForm['is_completed']) ? 'checked' : '' ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="font-bold text-emerald-900">Mark Document Complete</span>
                    </label>
                </div>

                <!-- Form Dynamic Content Fields -->
                <div class="space-y-4">
                    <!-- Standard Common Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1">Document Header Title</label>
                            <input type="text" name="form_data[document_title]" value="<?= htmlspecialchars($activeFormData['document_title'] ?? $activeTitle) ?>"
                                   class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-900">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1">Declaration Date</label>
                            <input type="date" name="form_data[declaration_date]" value="<?= htmlspecialchars($activeFormData['declaration_date'] ?? date('Y-m-d')) ?>"
                                   class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-900">
                        </div>
                    </div>

                    <!-- Category / Document specific clauses -->
                    <?php if (isset($activeFormData['declaration_clause'])): ?>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Declaration & Legal Clause</label>
                            <textarea name="form_data[declaration_clause]" rows="3"
                                      class="w-full bg-white border border-gray-300 rounded p-2 text-xs text-gray-900"><?= htmlspecialchars($activeFormData['declaration_clause']) ?></textarea>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($activeFormData['antiquity_declaration'])): ?>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Antiquity Non-Applicability Declaration</label>
                            <textarea name="form_data[antiquity_declaration]" rows="3"
                                      class="w-full bg-white border border-gray-300 rounded p-2 text-xs text-gray-900"><?= htmlspecialchars($activeFormData['antiquity_declaration']) ?></textarea>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($activeFormData['non_objection_statement'])): ?>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Non-Objection Publication Statement</label>
                            <textarea name="form_data[non_objection_statement]" rows="3"
                                      class="w-full bg-white border border-gray-300 rounded p-2 text-xs text-gray-900"><?= htmlspecialchars($activeFormData['non_objection_statement']) ?></textarea>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($activeFormData['copyright_statement'])): ?>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Audio / Media Copyright Statement</label>
                            <textarea name="form_data[copyright_statement]" rows="3"
                                      class="w-full bg-white border border-gray-300 rounded p-2 text-xs text-gray-900"><?= htmlspecialchars($activeFormData['copyright_statement']) ?></textarea>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($activeFormData['textile_statement'])): ?>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Textile & Handloom Origin Declaration</label>
                            <textarea name="form_data[textile_statement]" rows="3"
                                      class="w-full bg-white border border-gray-300 rounded p-2 text-xs text-gray-900"><?= htmlspecialchars($activeFormData['textile_statement']) ?></textarea>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($activeFormData['lacey_statement'])): ?>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Lacey Act Declaration Statement</label>
                            <textarea name="form_data[lacey_statement]" rows="3"
                                      class="w-full bg-white border border-gray-300 rounded p-2 text-xs text-gray-900"><?= htmlspecialchars($activeFormData['lacey_statement']) ?></textarea>
                        </div>
                    <?php endif; ?>

                    <!-- Items Table -->
                    <?php if (!empty($activeFormData['items'])): ?>
                        <div class="space-y-2 pt-2">
                            <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Document Item Line Details</h3>
                            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="bg-gray-100 text-gray-700 border-b border-gray-200">
                                            <th class="p-2 w-12 text-center">#</th>
                                            <th class="p-2 w-28">Item Code</th>
                                            <th class="p-2">Description</th>
                                            <th class="p-2 w-28">HSN Code</th>
                                            <th class="p-2 w-20 text-center">Qty</th>
                                            <th class="p-2 w-24 text-right">Unit Rate</th>
                                            <th class="p-2 w-28 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <?php foreach ($activeFormData['items'] as $idx => $it): ?>
                                            <tr>
                                                <td class="p-2 text-center text-gray-500"><?= $idx + 1 ?></td>
                                                <td class="p-2">
                                                    <input type="text" name="form_data[items][<?= $idx ?>][item_code]" value="<?= htmlspecialchars($it['item_code'] ?? '') ?>"
                                                           class="w-full border border-gray-300 rounded px-1.5 py-1 text-xs">
                                                </td>
                                                <td class="p-2">
                                                    <input type="text" name="form_data[items][<?= $idx ?>][description]" value="<?= htmlspecialchars($it['description'] ?? '') ?>"
                                                           class="w-full border border-gray-300 rounded px-1.5 py-1 text-xs">
                                                </td>
                                                <td class="p-2">
                                                    <input type="text" name="form_data[items][<?= $idx ?>][hsn_code]" value="<?= htmlspecialchars($it['hsn_code'] ?? '') ?>"
                                                           class="w-full border border-gray-300 rounded px-1.5 py-1 text-xs">
                                                </td>
                                                <td class="p-2">
                                                    <input type="number" name="form_data[items][<?= $idx ?>][quantity]" value="<?= (int)($it['quantity'] ?? 1) ?>"
                                                           class="w-full border border-gray-300 rounded px-1.5 py-1 text-xs text-center">
                                                </td>
                                                <td class="p-2">
                                                    <input type="number" step="0.01" name="form_data[items][<?= $idx ?>][unit_price]" value="<?= (float)($it['unit_price'] ?? 0) ?>"
                                                           class="w-full border border-gray-300 rounded px-1.5 py-1 text-xs text-right">
                                                </td>
                                                <td class="p-2 font-semibold text-gray-900 text-right">
                                                    <?= number_format((float)($it['amount'] ?? 0), 2) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Additional Remarks Field -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Document Remarks / Special Instructions</label>
                        <input type="text" name="form_data[remarks]" value="<?= htmlspecialchars($activeFormData['remarks'] ?? '') ?>" placeholder="Additional notes or custom references..."
                               class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-900">
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="pt-4 border-t border-gray-200 flex items-center justify-between">
                    <button type="button" id="saveDraftBtn" class="py-2.5 px-4 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium text-xs rounded-lg border border-gray-300 flex items-center gap-1.5">
                        <i class="fas fa-save"></i> Save Form Progress
                    </button>

                    <a href="index.php?page=export_documents&action=preview&session_code=<?= urlencode($session['session_code']) ?>&doc=<?= urlencode($activeTab) ?>" target="_blank"
                       class="py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-eye"></i> Preview This Document
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const docForm = document.getElementById('documentForm');
    const saveBtn = document.getElementById('saveDraftBtn');

    if (saveBtn && docForm) {
        saveBtn.addEventListener('click', function () {
            const formData = new FormData(docForm);

            fetch(docForm.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (window.showPosMessageModal) {
                        window.showPosMessageModal({
                            title: 'Saved',
                            message: 'Document saved successfully.',
                            tone: 'success'
                        });
                    } else {
                        alert('Document saved successfully.');
                    }
                } else {
                    if (window.showPosMessageModal) {
                        window.showPosMessageModal({
                            title: 'Error',
                            message: data.message || 'Failed to save document.',
                            tone: 'error'
                        });
                    } else {
                        alert(data.message || 'Failed to save document.');
                    }
                }
            })
            .catch(err => {
                console.error(err);
            });
        });
    }
});
</script>
