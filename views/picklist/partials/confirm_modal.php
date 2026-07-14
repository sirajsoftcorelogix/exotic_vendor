<div id="picklist-confirm-modal"
     class="fixed inset-0 z-[210] hidden items-center justify-center p-4 sm:p-6"
     role="dialog"
     aria-modal="true"
     aria-labelledby="picklist-confirm-modal-title"
     aria-describedby="picklist-confirm-modal-message">
    <button type="button"
            id="picklist-confirm-modal-backdrop"
            class="absolute inset-0 bg-slate-900/45 backdrop-blur-[2px]"
            aria-label="Close dialog"></button>
    <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-amber-200/40 bg-white shadow-2xl shadow-amber-900/10 ring-1 ring-black/5">
        <div class="px-6 pt-7 pb-5 text-center">
            <div id="picklist-confirm-modal-icon-wrap" class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm">
                <span id="picklist-confirm-modal-icon"><i class="fas fa-box-open text-2xl" aria-hidden="true"></i></span>
            </div>
            <h3 id="picklist-confirm-modal-title" class="text-lg font-bold tracking-tight text-gray-900 mb-2">Confirm</h3>
            <p id="picklist-confirm-modal-message" class="text-sm text-gray-600 leading-relaxed">Are you sure?</p>
        </div>
        <div class="border-t border-gray-100 bg-gradient-to-b from-gray-50/90 to-white px-6 py-4 flex justify-center gap-3">
            <button type="button"
                    id="picklist-confirm-modal-cancel"
                    class="inline-flex min-w-[6rem] items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 transition">
                Cancel
            </button>
            <button type="button"
                    id="picklist-confirm-modal-ok"
                    class="inline-flex min-w-[6rem] items-center justify-center rounded-xl bg-gradient-to-b from-emerald-600 to-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/15 hover:from-emerald-700 hover:to-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 transition">
                Confirm
            </button>
        </div>
    </div>
</div>
<script>
(function() {
    var modal = document.getElementById('picklist-confirm-modal');
    var backdrop = document.getElementById('picklist-confirm-modal-backdrop');
    var titleEl = document.getElementById('picklist-confirm-modal-title');
    var messageEl = document.getElementById('picklist-confirm-modal-message');
    var cancelBtn = document.getElementById('picklist-confirm-modal-cancel');
    var okBtn = document.getElementById('picklist-confirm-modal-ok');
    var iconWrap = document.getElementById('picklist-confirm-modal-icon-wrap');
    var iconEl = document.getElementById('picklist-confirm-modal-icon');
    if (!modal || !titleEl || !messageEl || !cancelBtn || !okBtn) return;

    var defaultIconWrapClass = iconWrap ? iconWrap.className : '';
    var defaultIconHtml = iconEl ? iconEl.innerHTML : '';

    var resolver = null;

    function closeModal(result) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        if (resolver) {
            resolver(!!result);
            resolver = null;
        }
    }

    window.openPicklistConfirmModal = function(options) {
        options = options || {};
        return new Promise(function(resolve) {
            resolver = resolve;
            titleEl.textContent = options.title || 'Confirm';
            messageEl.textContent = options.message || 'Are you sure?';
            okBtn.textContent = options.confirmText || 'Confirm';
            cancelBtn.textContent = options.cancelText || 'Cancel';
            if (iconWrap && iconEl) {
                iconWrap.className = options.iconWrapClass || defaultIconWrapClass;
                iconEl.innerHTML = options.iconHtml || defaultIconHtml;
            }
            if (options.okClass) {
                okBtn.className = options.okClass;
            } else {
                okBtn.className = 'inline-flex min-w-[6rem] items-center justify-center rounded-xl bg-gradient-to-b from-emerald-600 to-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/15 hover:from-emerald-700 hover:to-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 transition';
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            okBtn.focus();
        });
    };

    cancelBtn.addEventListener('click', function() { closeModal(false); });
    backdrop.addEventListener('click', function() { closeModal(false); });
    okBtn.addEventListener('click', function() { closeModal(true); });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal(false);
        }
    });
})();
</script>
