(function (window) {
  'use strict';

  var MODAL_ID = 'posMessageModal';
  var BACKDROP_ID = 'posMessageModalBackdrop';
  var TITLE_ID = 'posMessageModalTitle';
  var BODY_ID = 'posMessageModalBody';
  var OK_ID = 'posMessageModalOkBtn';
  var ICON_ID = 'posMessageModalIcon';
  var pendingCloseCallback = null;

  var TONE_STYLES = {
    error: {
      icon: 'fas fa-circle-exclamation',
      iconWrap: 'bg-red-100 text-red-600',
      okBtn: 'bg-red-600 hover:bg-red-700'
    },
    warning: {
      icon: 'fas fa-triangle-exclamation',
      iconWrap: 'bg-amber-100 text-amber-700',
      okBtn: 'bg-orange-600 hover:bg-orange-700'
    },
    info: {
      icon: 'fas fa-circle-info',
      iconWrap: 'bg-sky-100 text-sky-700',
      okBtn: 'bg-sky-600 hover:bg-sky-700'
    },
    success: {
      icon: 'fas fa-circle-check',
      iconWrap: 'bg-emerald-100 text-emerald-700',
      okBtn: 'bg-emerald-600 hover:bg-emerald-700'
    }
  };

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function ensurePosMessageModal() {
    var modal = document.getElementById(MODAL_ID);
    if (modal) {
      return modal;
    }

    modal = document.createElement('div');
    modal.id = MODAL_ID;
    modal.className = 'fixed inset-0 z-[10060] hidden';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', TITLE_ID);
    modal.innerHTML =
      '<div id="' +
      BACKDROP_ID +
      '" class="absolute inset-0 bg-black/45 backdrop-blur-[1px]" aria-hidden="true"></div>' +
      '<div class="relative mx-auto flex min-h-full max-w-md items-center justify-center p-4">' +
      '<div class="w-full overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200/80">' +
      '<div class="flex items-start gap-3 border-b border-slate-100 px-5 py-4">' +
      '<span id="' +
      ICON_ID +
      '" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700" aria-hidden="true">' +
      '<i class="fas fa-triangle-exclamation"></i></span>' +
      '<div class="min-w-0 pt-0.5">' +
      '<h2 id="' +
      TITLE_ID +
      '" class="text-base font-semibold text-slate-900">Notice</h2>' +
      '<p id="' +
      BODY_ID +
      '" class="mt-1 text-sm leading-relaxed text-slate-600"></p>' +
      '</div></div>' +
      '<div class="flex justify-end px-5 py-4">' +
      '<button type="button" id="' +
      OK_ID +
      '" class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition bg-orange-600 hover:bg-orange-700">OK</button>' +
      '</div></div></div>';

    document.body.appendChild(modal);

    var backdrop = document.getElementById(BACKDROP_ID);
    var okBtn = document.getElementById(OK_ID);
    if (backdrop) {
      backdrop.addEventListener('click', window.closePosMessageModal);
    }
    if (okBtn) {
      okBtn.addEventListener('click', window.closePosMessageModal);
    }

    document.addEventListener('keydown', function (ev) {
      if (ev.key !== 'Escape') {
        return;
      }
      var m = document.getElementById(MODAL_ID);
      if (m && !m.classList.contains('hidden')) {
        window.closePosMessageModal();
      }
    });

    return modal;
  }

  window.closePosMessageModal = function () {
    var modal = document.getElementById(MODAL_ID);
    if (!modal) {
      return;
    }
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    var cb = pendingCloseCallback;
    pendingCloseCallback = null;
    if (typeof cb === 'function') {
      try {
        cb();
      } catch (eCb) {
        /* ignore */
      }
    }
  };

  /**
   * @param {{ title?: string, message: string, tone?: 'error'|'warning'|'info'|'success', onClose?: function }} opts
   */
  window.showPosMessageModal = function (opts) {
    opts = opts || {};
    var message = String(opts.message || '').trim();
    if (!message) {
      return;
    }

    var tone = TONE_STYLES[opts.tone] ? opts.tone : 'warning';
    var style = TONE_STYLES[tone];
    var modal = ensurePosMessageModal();
    var titleEl = document.getElementById(TITLE_ID);
    var bodyEl = document.getElementById(BODY_ID);
    var iconWrap = document.getElementById(ICON_ID);
    var okBtn = document.getElementById(OK_ID);

    pendingCloseCallback = typeof opts.onClose === 'function' ? opts.onClose : null;

    if (titleEl) {
      titleEl.textContent = opts.title || (tone === 'error' ? 'Error' : 'Notice');
    }
    if (bodyEl) {
      bodyEl.textContent = message;
    }
    if (iconWrap) {
      iconWrap.className =
        'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full ' + style.iconWrap;
      iconWrap.innerHTML = '<i class="' + escapeHtml(style.icon) + '"></i>';
    }
    if (okBtn) {
      okBtn.className =
        'rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition ' + style.okBtn;
    }

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    if (okBtn && typeof okBtn.focus === 'function') {
      okBtn.focus();
    }
  };
})(window);
