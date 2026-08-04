(function (window) {
  'use strict';

  var MODAL_ID = 'posMessageModal';
  var BACKDROP_ID = 'posMessageModalBackdrop';
  var TITLE_ID = 'posMessageModalTitle';
  var BODY_ID = 'posMessageModalBody';
  var OK_ID = 'posMessageModalOkBtn';
  var CANCEL_ID = 'posMessageModalCancelBtn';
  var ICON_ID = 'posMessageModalIcon';
  var pendingCloseCallback = null;
  var pendingConfirmResolve = null;

  var TONE_STYLES = {
    error: {
      icon: 'fas fa-circle-exclamation',
      iconWrap: 'bg-red-100 text-red-600 dark:bg-red-950/40 dark:text-red-400',
      okBtn: 'bg-red-600 hover:bg-red-700 text-white'
    },
    warning: {
      icon: 'fas fa-triangle-exclamation',
      iconWrap: 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400',
      okBtn: 'bg-amber-600 hover:bg-amber-700 text-white'
    },
    info: {
      icon: 'fas fa-circle-info',
      iconWrap: 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-400',
      okBtn: 'bg-sky-600 hover:bg-sky-700 text-white'
    },
    success: {
      icon: 'fas fa-circle-check',
      iconWrap: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400',
      okBtn: 'bg-emerald-600 hover:bg-emerald-700 text-white'
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
      '" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>' +
      '<div class="relative mx-auto flex min-h-full max-w-md items-center justify-center p-4">' +
      '<div class="w-full overflow-hidden rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-200/80 transition-all dark:bg-slate-800 dark:ring-slate-700 border border-amber-200 dark:border-amber-900">' +
      '<div class="flex items-start gap-4">' +
      '<span id="' +
      ICON_ID +
      '" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400" aria-hidden="true">' +
      '<i class="fas fa-triangle-exclamation text-lg"></i></span>' +
      '<div class="min-w-0 pt-0.5">' +
      '<h2 id="' +
      TITLE_ID +
      '" class="text-base font-bold text-slate-900 dark:text-white">Notice</h2>' +
      '<div id="' +
      BODY_ID +
      '" class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300 whitespace-pre-line"></div>' +
      '</div></div>' +
      '<div class="mt-6 flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-700">' +
      '<button type="button" id="' +
      CANCEL_ID +
      '" class="hidden rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600">Cancel</button>' +
      '<button type="button" id="' +
      OK_ID +
      '" class="rounded-xl px-5 py-2 text-xs font-semibold text-white shadow-md transition bg-amber-600 hover:bg-amber-700">OK</button>' +
      '</div></div></div>';

    document.body.appendChild(modal);

    var backdrop = document.getElementById(BACKDROP_ID);
    var okBtn = document.getElementById(OK_ID);
    var cancelBtn = document.getElementById(CANCEL_ID);

    function handleOk() {
      var resolve = pendingConfirmResolve;
      pendingConfirmResolve = null;
      window.closePosMessageModal();
      if (typeof resolve === 'function') {
        resolve(true);
      }
    }

    function handleCancel() {
      var resolve = pendingConfirmResolve;
      pendingConfirmResolve = null;
      window.closePosMessageModal();
      if (typeof resolve === 'function') {
        resolve(false);
      }
    }

    if (backdrop) {
      backdrop.addEventListener('click', handleCancel);
    }
    if (okBtn) {
      okBtn.addEventListener('click', handleOk);
    }
    if (cancelBtn) {
      cancelBtn.addEventListener('click', handleCancel);
    }

    document.addEventListener('keydown', function (ev) {
      if (ev.key !== 'Escape') {
        return;
      }
      var m = document.getElementById(MODAL_ID);
      if (m && !m.classList.contains('hidden')) {
        handleCancel();
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
   * @param {{ title?: string, message: string, tone?: 'error'|'warning'|'info'|'success', onClose?: function, okText?: string }} opts
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
    var cancelBtn = document.getElementById(CANCEL_ID);

    pendingCloseCallback = typeof opts.onClose === 'function' ? opts.onClose : null;
    pendingConfirmResolve = null;

    if (titleEl) {
      titleEl.textContent = opts.title || (tone === 'error' ? 'Error' : 'Notice');
    }
    if (bodyEl) {
      bodyEl.textContent = message;
    }
    if (iconWrap) {
      iconWrap.className =
        'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ' + style.iconWrap;
      iconWrap.innerHTML = '<i class="' + escapeHtml(style.icon) + ' text-lg"></i>';
    }
    if (okBtn) {
      okBtn.textContent = opts.okText || 'OK';
      okBtn.className =
        'rounded-xl px-5 py-2 text-xs font-semibold shadow-md transition ' + style.okBtn;
    }
    if (cancelBtn) {
      cancelBtn.classList.add('hidden');
    }

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    if (okBtn && typeof okBtn.focus === 'function') {
      okBtn.focus();
    }
  };

  /**
   * @param {{
   *   title?: string,
   *   message: string,
   *   confirmText?: string,
   *   cancelText?: string,
   *   tone?: 'error'|'warning'|'info'|'success',
   *   onConfirm?: function,
   *   onCancel?: function
   * }} opts
   * @returns {Promise<boolean>}
   */
  window.showPosConfirmModal = function (opts) {
    opts = opts || {};
    var message = String(opts.message || '').trim();
    if (!message) {
      return Promise.resolve(false);
    }

    return new Promise(function (resolve) {
      var tone = TONE_STYLES[opts.tone] ? opts.tone : 'warning';
      var style = TONE_STYLES[tone];
      var modal = ensurePosMessageModal();
      var titleEl = document.getElementById(TITLE_ID);
      var bodyEl = document.getElementById(BODY_ID);
      var iconWrap = document.getElementById(ICON_ID);
      var okBtn = document.getElementById(OK_ID);
      var cancelBtn = document.getElementById(CANCEL_ID);

      pendingCloseCallback = null;

      if (titleEl) {
        titleEl.textContent = opts.title || 'Section 269ST Cash Warning';
      }
      if (bodyEl) {
        bodyEl.textContent = message;
      }
      if (iconWrap) {
        iconWrap.className =
          'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ' + style.iconWrap;
        iconWrap.innerHTML = '<i class="' + escapeHtml(style.icon) + ' text-lg"></i>';
      }
      if (okBtn) {
        okBtn.textContent = opts.confirmText || 'Acknowledge & Continue';
        okBtn.className =
          'rounded-xl px-5 py-2 text-xs font-semibold shadow-md transition ' + style.okBtn;
      }
      if (cancelBtn) {
        cancelBtn.textContent = opts.cancelText || 'Switch Payment';
        cancelBtn.classList.remove('hidden');
      }

      pendingConfirmResolve = function (confirmed) {
        if (confirmed) {
          if (typeof opts.onConfirm === 'function') {
            try { opts.onConfirm(); } catch (e) {}
          }
          resolve(true);
        } else {
          if (typeof opts.onCancel === 'function') {
            try { opts.onCancel(); } catch (e) {}
          }
          resolve(false);
        }
      };

      modal.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
      if (okBtn && typeof okBtn.focus === 'function') {
        okBtn.focus();
      }
    });
  };
})(window);
