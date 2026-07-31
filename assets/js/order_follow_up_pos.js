(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var cfg = window.POS_FOLLOW_UP || null;
        var shouldSeed = !!window.POS_FOLLOW_UP_SEED;

        if (cfg && cfg.source_order_number) {
            showFollowUpBanner(cfg);
        }

        if (!shouldSeed || !cfg) {
            return;
        }

        fetch('index.php?page=pos_register&action=follow-up-seed', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (!data || !data.success) {
                    var msg = (data && data.message) ? data.message : 'Could not load items from source order.';
                    if (typeof window.showPosMessageModal === 'function') {
                        window.showPosMessageModal({ title: 'Follow-up order', message: msg, tone: 'error' });
                    }
                    return;
                }
                if (typeof window.refreshPosCartPanel === 'function') {
                    window.refreshPosCartPanel();
                } else if (typeof window.loadCart === 'function') {
                    window.loadCart();
                }
                if (data.follow_up) {
                    window.POS_FOLLOW_UP = data.follow_up;
                    showFollowUpBanner(data.follow_up);
                }
            })
            .catch(function () {
                if (typeof window.showPosMessageModal === 'function') {
                    window.showPosMessageModal({
                        title: 'Follow-up order',
                        message: 'Could not seed cart from source order.',
                        tone: 'error'
                    });
                }
            });
    });

    function showFollowUpBanner(cfg) {
        var existing = document.getElementById('pos-follow-up-banner');
        if (existing) {
            existing.remove();
        }
        var el = document.createElement('div');
        el.id = 'pos-follow-up-banner';
        el.className = 'mx-4 mb-3 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-950';
        el.innerHTML =
            '<p class="font-semibold">' +
            escapeHtml(cfg.follow_up_type_label || 'Follow-up') +
            ' for order #' +
            escapeHtml(cfg.source_order_number || '') +
            '</p>' +
            '<p class="mt-1 text-xs text-indigo-900/90">Pricing: ' +
            escapeHtml(cfg.pricing_mode_label || '') +
            (cfg.scope === 'partial' ? ' · Partial lines' : '') +
            '. Edit the cart if needed, then checkout.</p>';
        var main = document.querySelector('main') || document.body;
        main.insertBefore(el, main.firstChild);
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    window.getPosFollowUpLinePricesOverride = function () {
        var cfg = window.POS_FOLLOW_UP || null;
        if (!cfg || !Array.isArray(cfg.pos_line_prices) || cfg.pos_line_prices.length === 0) {
            return null;
        }
        return cfg.pos_line_prices;
    };

    window.isPosFollowUpWaivedCheckout = function () {
        var cfg = window.POS_FOLLOW_UP || null;
        return !!(cfg && String(cfg.pricing_mode || '').toLowerCase() === 'waived');
    };
})();
