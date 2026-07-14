<div id="picklist-image-lightbox"
     class="fixed inset-0 z-[200] hidden flex-col items-center justify-center bg-black/85 p-4 sm:p-6"
     role="dialog"
     aria-modal="true"
     aria-labelledby="picklist-image-lightbox-title">
    <p id="picklist-image-lightbox-title" class="sr-only">Enlarged product image</p>
    <button type="button"
            id="picklist-image-lightbox-close"
            class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/30 bg-white/10 text-white text-xl font-light hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400"
            aria-label="Close">&times;</button>
    <img id="picklist-image-lightbox-img"
         src=""
         alt=""
         class="max-h-[90vh] max-w-full rounded-lg object-contain shadow-2xl ring-1 ring-white/10 bg-white">
</div>
<script>
(function() {
    var lb = document.getElementById('picklist-image-lightbox');
    var lbImg = document.getElementById('picklist-image-lightbox-img');
    var lbClose = document.getElementById('picklist-image-lightbox-close');
    if (!lb || !lbImg) return;

    function openPicklistImage(url, alt) {
        if (!url || !String(url).trim()) return;
        lbImg.src = url;
        lbImg.alt = alt || 'Product image';
        lb.classList.remove('hidden');
        lb.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closePicklistImage() {
        lb.classList.add('hidden');
        lb.classList.remove('flex');
        lbImg.src = '';
        lbImg.alt = '';
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('.js-picklist-expand-image');
        if (!trigger) return;
        e.preventDefault();
        e.stopPropagation();
        var url = trigger.getAttribute('data-full-src') || trigger.getAttribute('src') || '';
        var alt = trigger.getAttribute('alt') || trigger.getAttribute('data-image-alt') || 'Product image';
        openPicklistImage(url, alt);
    });

    if (lbClose) {
        lbClose.addEventListener('click', function(e) {
            e.stopPropagation();
            closePicklistImage();
        });
    }

    lb.addEventListener('click', function(e) {
        if (e.target === lb) closePicklistImage();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !lb.classList.contains('hidden')) {
            closePicklistImage();
        }
    });
})();
</script>
