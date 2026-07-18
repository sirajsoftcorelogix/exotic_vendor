(function (global) {
    'use strict';

    if (typeof global.Chart === 'undefined') {
        return;
    }

    const COLORS = [
        '#ea580c',
        '#f97316',
        '#fb923c',
        '#fdba74',
        '#0d9488',
        '#14b8a6',
        '#2563eb',
        '#7c3aed',
        '#db2777',
        '#059669',
        '#ca8a04',
        '#64748b',
    ];

    const instances = {};

    function palette(count) {
        const colors = [];
        for (let i = 0; i < count; i++) {
            colors.push(COLORS[i % COLORS.length]);
        }
        return colors;
    }

    function destroy(id) {
        if (instances[id]) {
            instances[id].destroy();
            delete instances[id];
        }
    }

    function destroyMany(ids) {
        ids.forEach(destroy);
    }

    function rupee(value) {
        const n = parseFloat(value);
        if (Number.isNaN(n)) {
            return '₹ 0.00';
        }
        return '₹ ' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function baseOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        boxWidth: 12,
                        font: { size: 11 },
                    },
                },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || context.dataset.label || '';
                                    const parsed = context.parsed || {};
                                    const raw = parsed.y ?? parsed.x ?? context.raw;
                                    if (typeof raw === 'number') {
                                        return (label ? label + ': ' : '') + rupee(raw);
                                    }
                                    return label;
                                },
                            },
                        },
            },
        };
    }

    function createBarChart(canvasId, labels, values, datasetLabel, horizontal) {
        destroy(canvasId);
        const canvas = document.getElementById(canvasId);
        if (!canvas || !labels.length) {
            return null;
        }

        const colors = palette(labels.length);
        const indexAxis = horizontal ? 'y' : 'x';
        instances[canvasId] = new global.Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: datasetLabel || 'Amount',
                    data: values,
                    backgroundColor: colors.map(function (c) { return c + 'CC'; }),
                    borderColor: colors,
                    borderWidth: 1,
                    borderRadius: 4,
                }],
            },
            options: Object.assign({}, baseOptions(), {
                indexAxis: indexAxis,
                scales: horizontal ? {
                    x: {
                        ticks: {
                            font: { size: 10 },
                            callback: function (val) { return rupee(val); },
                        },
                        grid: { color: '#f3f4f6' },
                    },
                    y: {
                        ticks: { font: { size: 10 } },
                        grid: { display: false },
                    },
                } : {
                    x: {
                        ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 0 },
                        grid: { display: false },
                    },
                    y: {
                        ticks: {
                            font: { size: 10 },
                            callback: function (val) { return rupee(val); },
                        },
                        grid: { color: '#f3f4f6' },
                    },
                },
            }),
        });
        return instances[canvasId];
    }

    function createDoughnutChart(canvasId, labels, values, countMode) {
        destroy(canvasId);
        const canvas = document.getElementById(canvasId);
        if (!canvas || !labels.length) {
            return null;
        }

        const colors = palette(labels.length);
        instances[canvasId] = new global.Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                }],
            },
            options: Object.assign({}, baseOptions(), {
                cutout: '58%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 10 }, padding: 12 },
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const label = context.label || '';
                                const raw = context.parsed ?? context.raw;
                                if (typeof raw === 'number') {
                                    const formatted = countMode
                                        ? raw.toLocaleString('en-IN') + ' invoices'
                                        : rupee(raw);
                                    return (label ? label + ': ' : '') + formatted;
                                }
                                return label;
                            },
                        },
                    },
                },
            }),
        });
        return instances[canvasId];
    }

    function createLineChart(canvasId, labels, values, datasetLabel) {
        destroy(canvasId);
        const canvas = document.getElementById(canvasId);
        if (!canvas || !labels.length) {
            return null;
        }

        instances[canvasId] = new global.Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: datasetLabel || 'Net sales',
                    data: values,
                    borderColor: '#ea580c',
                    backgroundColor: 'rgba(234, 88, 12, 0.12)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                }],
            },
            options: Object.assign({}, baseOptions(), {
                scales: {
                    x: {
                        ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 0 },
                        grid: { display: false },
                    },
                    y: {
                        ticks: {
                            font: { size: 10 },
                            callback: function (val) { return rupee(val); },
                        },
                        grid: { color: '#f3f4f6' },
                    },
                },
            }),
        });
        return instances[canvasId];
    }

    function createGroupedBarChart(canvasId, labels, datasets) {
        destroy(canvasId);
        const canvas = document.getElementById(canvasId);
        if (!canvas || !labels.length || !datasets.length) {
            return null;
        }

        instances[canvasId] = new global.Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets.map(function (ds, idx) {
                    const color = COLORS[idx % COLORS.length];
                    return {
                        label: ds.label,
                        data: ds.values,
                        backgroundColor: color + 'BB',
                        borderColor: color,
                        borderWidth: 1,
                        borderRadius: 4,
                    };
                }),
            },
            options: Object.assign({}, baseOptions(), {
                scales: {
                    x: { ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 0 }, grid: { display: false } },
                    y: {
                        ticks: { font: { size: 10 }, callback: function (val) { return rupee(val); } },
                        grid: { color: '#f3f4f6' },
                    },
                },
            }),
        });
        return instances[canvasId];
    }

    function setSectionVisible(sectionId, visible) {
        const el = document.getElementById(sectionId);
        if (el) {
            el.classList.toggle('hidden', !visible);
        }
    }

    function renderLevel1(rows) {
        destroyMany(['chartL1NetSalesBar', 'chartL1NetSalesPie', 'chartL1CollectedPending']);

        if (!rows || !rows.length) {
            setSectionVisible('posSalesChartsSection', false);
            return;
        }

        setSectionVisible('posSalesChartsSection', true);

        const labels = rows.map(function (r) { return r.warehouse_name || 'Store'; });
        const netSales = rows.map(function (r) { return parseFloat(r.net_sales) || 0; });
        const collected = rows.map(function (r) { return parseFloat(r.collected_total) || 0; });
        const pending = rows.map(function (r) { return parseFloat(r.pending_total) || 0; });

        createBarChart('chartL1NetSalesBar', labels, netSales, 'Net sales', labels.length > 4);
        createDoughnutChart('chartL1NetSalesPie', labels, netSales);
        createGroupedBarChart('chartL1CollectedPending', labels, [
            { label: 'Collected', values: collected },
            { label: 'Pending', values: pending },
        ]);
    }

    function renderLevel2(data) {
        destroyMany([
            'chartL2PaymentType',
            'chartL2Status',
            'chartL2Discount',
            'chartL2DailyTrend',
        ]);

        const hasOverview = data && data.overview && (parseInt(data.overview.invoice_count, 10) || 0) > 0;
        if (!hasOverview) {
            setSectionVisible('posStoreChartsSection', false);
            return;
        }

        setSectionVisible('posStoreChartsSection', true);

        const paymentRows = ((data.by_payment_type || {}).rows || []);
        createDoughnutChart(
            'chartL2PaymentType',
            paymentRows.map(function (r) { return r.group_label || 'Unknown'; }),
            paymentRows.map(function (r) { return parseFloat(r.net_sales) || 0; })
        );

        const statusRows = ((data.by_status || {}).rows || []);
        createDoughnutChart(
            'chartL2Status',
            statusRows.map(function (r) { return r.group_label || 'Unknown'; }),
            statusRows.map(function (r) { return parseFloat(r.net_sales) || 0; })
        );

        const discountRows = ((data.by_discount || {}).rows || []);
        createDoughnutChart(
            'chartL2Discount',
            discountRows.map(function (r) { return r.group_label || 'Unknown'; }),
            discountRows.map(function (r) { return parseFloat(r.invoice_count) || 0; }),
            true
        );

        const dateRows = ((data.by_date || {}).rows || []).slice().sort(function (a, b) {
            return String(a.summary_date || '').localeCompare(String(b.summary_date || ''));
        });
        createLineChart(
            'chartL2DailyTrend',
            dateRows.map(function (r) { return r.group_label || r.summary_date || ''; }),
            dateRows.map(function (r) { return parseFloat(r.net_sales) || 0; }),
            'Net sales'
        );
    }

    global.PosSalesCharts = {
        renderLevel1: renderLevel1,
        renderLevel2: renderLevel2,
        destroy: destroyMany,
    };
})(window);
