<?php
$loginUser = getloginUser();
global $domain, $root_path;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vendor Onboarding Portal</title>
    <link rel="icon" type="image/x-icon" href="images/EXOTIC_FAV_ICO.png">
    <!-- Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Instrument+Sans:wght@600&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap 5 CSS -->
	<!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet" /> -->
	<!-- Bootstrap 5 JS (includes Popper) -->
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script> -->
	<!-- Font Awesome (icons) -->
	<!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" /> -->
	<!-- jQuery (required for AJAX, DOM manipulation, etc.) -->
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script> -->
<link rel="stylesheet" href="style/style.css" />
</head>
<body class="bg-white">
    <!-- Main container for the two panels, now taking full screen height -->
    
        <?= $content ?? '' ?>
<script>
(function(){
    function ensureContainers(){
        let toast = document.getElementById('globalToastContainer');
        if(!toast){
            toast = document.createElement('div');
            toast.id = 'globalToastContainer';
            toast.style.position = 'fixed';
            toast.style.right = '200px';
            toast.style.top = '80px';
            toast.style.zIndex = 99999;
            document.body.appendChild(toast);
        }
        let modal = document.getElementById('globalConfirmModal');
        if(!modal){
            modal = document.createElement('div');
            modal.id = 'globalConfirmModal';
            modal.style.position = 'fixed';
            modal.style.left = '0'; modal.style.top = '0'; modal.style.right = '0'; modal.style.bottom = '0';
            modal.style.display = 'none';
            modal.style.zIndex = 100000;
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.background = 'rgba(0,0,0,0.35)';
            modal.innerHTML = '<div id="globalConfirmBox" style="background:#fff;padding:18px;border-radius:10px;box-shadow:0 12px 40px rgba(0,0,0,0.25);min-width:320px;max-width:90%;">' +
                '<div id="globalConfirmTitle" style="font-weight:700;margin-bottom:6px;color:#111;">Confirm</div>' +
                '<div id="globalConfirmMessage" style="margin-bottom:14px;color:#111"></div>' +
                '<div style="text-align:right">' +
                '<button id="globalConfirmCancel" style="margin-right:8px;padding:8px 12px;border-radius:6px;border:1px solid #e5e7eb;background:#fff;color:#111">Cancel</button>' +
                '<button id="globalConfirmOk" style="padding:8px 12px;border-radius:6px;border:0;background:#059669;color:#fff;">OK</button>' +
                '</div></div>';
            document.body.appendChild(modal);
        }
        return {toast, modal};
    }

    window.showGlobalToast = function(message, type='info', timeout=3000){
        const colors = {
            success: {bg: 'rgba(16,185,129,0.12)', color: '#065f46'},
            error: {bg: 'rgba(239,68,68,0.12)', color: '#991b1b'},
            info: {bg: 'rgba(99,102,241,0.08)', color: '#3730a3'}
        };
        const cfg = colors[type] || colors.info;
        const c = ensureContainers().toast;
        const toast = document.createElement('div');
        toast.style.background = cfg.bg;
        toast.style.color = cfg.color;
        toast.style.border = '1px solid rgba(0,0,0,0.04)';
        toast.style.padding = '8px 12px';
        toast.style.borderRadius = '6px';
        toast.style.boxShadow = '0 6px 18px rgba(0,0,0,0.08)';
        toast.style.marginBottom = '8px';
        toast.style.fontSize = '13px';
        toast.textContent = message;
        c.appendChild(toast);
        setTimeout(() => {
            toast.style.transition = 'opacity 0.25s ease-out, transform 0.25s ease-out';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-6px)';
            setTimeout(() => toast.remove(), 300);
        }, timeout);
    };

    // customConfirm: returns Promise<boolean>
    window.customConfirm = function(message, options = {}){
        return new Promise(resolve => {
            const {modal} = ensureContainers();
            const msgEl = document.getElementById('globalConfirmMessage');
            const titleEl = document.getElementById('globalConfirmTitle');
            const ok = document.getElementById('globalConfirmOk');
            const cancel = document.getElementById('globalConfirmCancel');
            titleEl.textContent = options.title || 'Confirm';
            msgEl.textContent = message;
            modal.style.display = 'flex';
            function cleanup(res){
                modal.style.display = 'none';
                ok.removeEventListener('click', onOk);
                cancel.removeEventListener('click', onCancel);
                document.removeEventListener('keydown', onKey);
                resolve(res);
            }
            function onOk(){ cleanup(true); }
            function onCancel(){ cleanup(false); }
            function onKey(e){ if(e.key === 'Escape'){ cleanup(false); } }
            ok.addEventListener('click', onOk);
            cancel.addEventListener('click', onCancel);
            document.addEventListener('keydown', onKey);
            // auto-focus ok button
            setTimeout(()=> ok.focus(), 50);
        });
    };

    // Create container centered in the page with backdrop
    var alertContainer = document.createElement('div');
    alertContainer.id = 'global-alert';
    alertContainer.className = 'fixed inset-0 z-50 hidden flex items-center justify-center pointer-events-none';
    alertContainer.innerHTML =
        '<div id="global-alert-backdrop" class="absolute inset-0 bg-black bg-opacity-30 hidden"></div>' +
        '<div id="global-alert-inner" class="max-w-md w-full rounded-lg p-4 shadow-xl text-white flex items-start gap-3 transform transition-all duration-200 scale-95 opacity-0 pointer-events-auto">' +
            '<div id="global-alert-icon" class="text-2xl flex-shrink-0"></div>' +
            '<div id="global-alert-message" class="flex-1 text-sm md:text-base"></div>' +
            '<button id="global-alert-close" class="ml-2 text-white text-2xl font-bold leading-none focus:outline-none" aria-label="Close">&times;</button>' +
        '</div>';
    document.body.appendChild(alertContainer);

    var escHandlerAdded = false;

    function hideAlert() {
        var container = document.getElementById('global-alert');
        var backdrop = document.getElementById('global-alert-backdrop');
        var inner = document.getElementById('global-alert-inner');
        inner.classList.remove('scale-100');
        inner.classList.add('scale-95');
        inner.style.opacity = '0';
        if (window._alertTimeout) { clearTimeout(window._alertTimeout); window._alertTimeout = null; }
        setTimeout(function() {
            container.classList.add('hidden');
            if (backdrop) backdrop.classList.add('hidden');
            container.style.pointerEvents = 'none';
        }, 200);
    }

    window.showAlert = function(message, type = 'success', timeout = 0) {
        var container = document.getElementById('global-alert');
        var backdrop = document.getElementById('global-alert-backdrop');
        var inner = document.getElementById('global-alert-inner');
        var msgEl = document.getElementById('global-alert-message');
        var iconEl = document.getElementById('global-alert-icon');
        var closeBtn = document.getElementById('global-alert-close');

        msgEl.textContent = message;
        iconEl.innerHTML = type === 'success' ? '<i class="fas fa-check-circle"></i>' : type === 'warning' ? '<i class="fas fa-exclamation-triangle"></i>' : '<i class="fas fa-exclamation-circle"></i>';
        inner.className = 'max-w-md w-38 rounded-lg p-4 shadow-2xl text-white flex items-start gap-3 transform transition-all duration-200 pointer-events-auto ' + (type === 'success' ? 'bg-green-600' : type === 'warning' ? 'bg-yellow-500' : 'bg-red-600');
        container.classList.remove('hidden');
        if (backdrop) backdrop.classList.remove('hidden');
        container.style.pointerEvents = 'auto';

        // trigger transition
        setTimeout(function() {
            inner.classList.remove('scale-95');
            inner.classList.add('scale-100');
            inner.style.opacity = '1';
        }, 10);

        if (!escHandlerAdded) {
            escHandlerAdded = true;
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') hideAlert();
            });
        }

        if (window._alertTimeout) { clearTimeout(window._alertTimeout); window._alertTimeout = null; }
        if (timeout && timeout > 0) {
            window._alertTimeout = setTimeout(hideAlert, timeout);
        }

        closeBtn.onclick = function() { hideAlert(); };
        if (backdrop) backdrop.onclick = function() { hideAlert(); };
    };
})();
</script>  
</body>
</html>