<?php
$isbnLookupApplyBtnClass = $isbnLookupApplyBtnClass ?? 'bg-[#ea8c1e] hover:bg-orange-600';
?>
<div id="isbnLookupModal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-black/70 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="isbnLookupModalTitle">
    <div class="relative w-full max-w-2xl max-h-[92vh] overflow-y-auto rounded-lg bg-white shadow-2xl" onclick="event.stopPropagation();">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3">
            <h2 id="isbnLookupModalTitle" class="text-base font-bold text-gray-800">ISBN Lookup Result</h2>
            <button type="button" id="isbnLookupCloseBtn" class="flex h-8 w-8 items-center justify-center rounded-full text-xl text-gray-500 hover:bg-gray-100" aria-label="Close lookup preview">&times;</button>
        </div>
        <div class="p-4 space-y-4">
            <p id="isbnLookupMessage" class="text-sm text-gray-600"></p>
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="sm:w-36 shrink-0">
                    <img id="isbnLookupCover" src="" alt="Book cover preview" class="hidden w-full rounded border border-gray-300 object-cover aspect-[3/4] bg-gray-100">
                    <div id="isbnLookupCoverPlaceholder" class="flex aspect-[3/4] w-full items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-xs text-gray-400">No cover</div>
                </div>
                <div id="isbnLookupDetails" class="flex-1 space-y-2 text-sm"></div>
            </div>
            <div id="isbnLookupWarnings" class="hidden rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"></div>
        </div>
        <div class="sticky bottom-0 flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-4 py-3">
            <button type="button" id="isbnLookupCancelBtn" class="rounded border border-gray-300 bg-white px-4 py-2 text-xs font-bold uppercase text-gray-700 hover:bg-gray-100">Cancel</button>
            <button type="button" id="isbnLookupApplyBtn" class="rounded <?php echo htmlspecialchars($isbnLookupApplyBtnClass, ENT_QUOTES, 'UTF-8'); ?> px-4 py-2 text-xs font-bold uppercase text-white">Apply to Form</button>
        </div>
    </div>
</div>
