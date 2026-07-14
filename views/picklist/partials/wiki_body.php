<?php
/**
 * Picklist module — end-user wiki body (included by views/picklist/wiki.php).
 */
?>
<article class="picklist-wiki prose prose-slate max-w-none">
    <header class="not-prose mb-8 rounded-2xl border border-amber-200/60 bg-gradient-to-br from-amber-50/80 via-white to-slate-50 p-6 sm:p-8">
        <div class="flex flex-wrap items-start gap-4">
            <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 text-2xl">
                <i class="fas fa-clipboard-list" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-800/80 mb-1">Warehouse · User guide</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 m-0">Picklist Module</h1>
                <p class="mt-2 text-sm sm:text-base text-gray-600 m-0 max-w-2xl">
                    Create picklists from orders, pick items by warehouse location, print pick sheets and order labels, and track progress on desktop or tablet.
                </p>
            </div>
        </div>
    </header>

    <nav class="not-prose mb-8 rounded-xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm" aria-label="On this page">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">On this page</p>
        <ul class="grid sm:grid-cols-2 gap-x-6 gap-y-2 text-sm m-0 p-0 list-none">
            <li><a href="#overview" class="text-amber-700 hover:text-amber-900 font-medium">Overview</a></li>
            <li><a href="#add-orders" class="text-amber-700 hover:text-amber-900 font-medium">Adding orders to a picklist</a></li>
            <li><a href="#manage" class="text-amber-700 hover:text-amber-900 font-medium">Managing picklists</a></li>
            <li><a href="#picking" class="text-amber-700 hover:text-amber-900 font-medium">Picking items</a></li>
            <li><a href="#print-picklist" class="text-amber-700 hover:text-amber-900 font-medium">Printing the picklist</a></li>
            <li><a href="#print-labels" class="text-amber-700 hover:text-amber-900 font-medium">Printing order labels</a></li>
            <li><a href="#remove" class="text-amber-700 hover:text-amber-900 font-medium">Removing items</a></li>
            <li><a href="#statuses" class="text-amber-700 hover:text-amber-900 font-medium">Statuses explained</a></li>
            <li><a href="#faq" class="text-amber-700 hover:text-amber-900 font-medium">FAQ &amp; troubleshooting</a></li>
        </ul>
    </nav>

    <section id="overview" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">Overview</h2>
        <p>A <strong>picklist</strong> is a warehouse picking list. Each line is one order item (order number + SKU) sorted by <strong>warehouse location</strong> so pickers can walk the floor efficiently.</p>
        <div class="not-prose my-4 rounded-xl border border-sky-200 bg-sky-50/60 p-4 text-sm text-sky-950">
            <p class="font-semibold m-0 mb-1"><i class="fas fa-info-circle text-sky-600 mr-1" aria-hidden="true"></i> Good to know</p>
            <ul class="m-0 pl-5 space-y-1">
                <li>One picklist cannot mix <strong>books</strong> and <strong>non-books</strong>.</li>
                <li>The same order number + SKU cannot appear on two active picklists.</li>
                <li>When you add an order to a picklist, its status becomes <strong>Added to picklist</strong>.</li>
            </ul>
        </div>
    </section>

    <section id="add-orders" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">Adding orders to a picklist</h2>
        <p>Orders are added from the <strong>Orders</strong> list — not from inside an empty picklist.</p>
        <ol class="space-y-3 pl-5">
            <li>Open <strong>Orders</strong> and select one or more rows (checkboxes), or use the <strong>⋮</strong> menu on a single row.</li>
            <li>Choose <strong>Add to Pick List</strong> (bulk action or row menu).</li>
            <li>In the popup, choose:
                <ul class="mt-2 space-y-1">
                    <li><strong>Create new picklist</strong> — name is pre-filled (e.g. <code>PL-20260714-0001</code>); you may edit it. Optionally assign a picker.</li>
                    <li><strong>Add to existing picklist</strong> — pick from open lists (Pending or In progress).</li>
                </ul>
            </li>
            <li>Review selected orders in the popup; remove any with the <strong>×</strong> button if needed.</li>
            <li>Click <strong>Add to Picklist</strong>. You are taken to the new or updated picklist detail page.</li>
        </ol>
        <div class="not-prose mt-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4 text-sm">
            <p class="font-semibold text-amber-950 m-0 mb-1">Row menu when already on a picklist</p>
            <p class="m-0 text-amber-900">The menu shows <strong>Added to pickup list</strong> (green) with the picklist name underneath. Click it to <strong>remove</strong> the order from that picklist.</p>
        </div>
    </section>

    <section id="manage" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">Managing picklists</h2>
        <p>Open <strong>Picklist</strong> in the sidebar to see all picklists. Use search and filters for status or picker.</p>
        <p>Click a picklist number to open the <strong>detail view</strong>:</p>
        <ul>
            <li>Progress bar shows how many items are picked.</li>
            <li><strong>Tablet</strong> — large touch-friendly picking screen.</li>
            <li><strong>Print</strong> — paper pick sheet for the warehouse.</li>
            <li><strong>Print labels</strong> — adhesive labels for every item on the list (see below).</li>
        </ul>
    </section>

    <section id="picking" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">Picking items</h2>
        <h3 class="text-lg font-semibold text-gray-800 mt-6 mb-2">Desktop detail view</h3>
        <ul>
            <li>Items are sorted by <strong>location</strong>, then <strong>item code</strong> (so everything at location 567 appears together).</li>
            <li>The <strong>Order Qty</strong> column is how many units to pick in one go for that line.</li>
            <li>Use row checkboxes + <strong>Mark picked</strong> for bulk pick, or pick individually on tablet.</li>
            <li><strong>Revert picks</strong> undoes a pick (order status returns to Added to picklist).</li>
            <li><strong>Partial</strong> — stock is short; mark as <strong>Partially Available</strong>.</li>
            <li><strong>N/A</strong> — no stock; mark as <strong>Not Available</strong>.</li>
            <li><strong>Revert</strong> — reset picked, partial, or not-available items back to <strong>Pending</strong>.</li>
            <li><strong>Remove</strong> deletes the line from the picklist and restores the order’s previous status.</li>
        </ul>
        <h3 class="text-lg font-semibold text-gray-800 mt-6 mb-2">Tablet mode</h3>
        <p>Designed for handheld use on the warehouse floor. Tap to mark items as picked. Use the same header buttons for Print and Print labels.</p>
    </section>

    <section id="print-picklist" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">Printing the picklist</h2>
        <ol class="pl-5 space-y-2">
            <li>On the picklist detail (or tablet) page, click <strong>Print</strong>.</li>
            <li>Two sections are printed:
                <ul class="mt-2 space-y-1">
                    <li><strong>A) Full quantity available</strong> — physical stock meets order qty; pick the full order quantity.</li>
                    <li><strong>B) Partially available &amp; not available</strong> — stock is short; shows shortfall for follow-up.</li>
                </ul>
            </li>
            <li>The print dialog opens automatically. Use your browser’s print settings as usual.</li>
        </ol>
    </section>

    <section id="print-labels" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">Printing order labels</h2>
        <p>Click <strong>Print labels</strong> next to <strong>Print</strong> on the picklist header. Labels are printed <strong>one per order quantity unit</strong> (e.g. order qty 2 prints two labels for that line). Items are laid out in the same order as the picklist: <strong>location</strong>, then <strong>item code</strong>.</p>

        <div class="not-prose my-4 overflow-hidden rounded-xl border border-gray-200">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Setting</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td class="px-4 py-2">Sheet</td><td class="px-4 py-2">Lotus Label A4 ST-65 (or equivalent 65-label sheet)</td></tr>
                    <tr><td class="px-4 py-2">Layout</td><td class="px-4 py-2">5 labels wide × 13 labels high (portrait A4)</td></tr>
                    <tr><td class="px-4 py-2">Each label</td><td class="px-4 py-2">38.1 mm × 21.2 mm</td></tr>
                </tbody>
            </table>
        </div>

        <p>Each label shows:</p>
        <ul>
            <li><strong>Order number</strong> (e.g. Ord 3023237)</li>
            <li><strong>SKU</strong></li>
            <li><strong>Barcode</strong> (scannable code for the internal order line ID)</li>
            <li>Numeric ID printed under the barcode</li>
        </ul>

        <h3 class="text-lg font-semibold text-gray-800 mt-6 mb-2">Printer settings</h3>
        <ul>
            <li><strong>Scale:</strong> 100% (do not use “Fit to page”)</li>
            <li><strong>Margins:</strong> None or minimum</li>
            <li>Load the label sheet in portrait orientation</li>
        </ul>
        <p>Labels fill the sheet from the <strong>top-left</strong>, row by row. If you have more than 65 items, a second page is generated.</p>
    </section>

    <section id="remove" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">Removing items &amp; deleting picklists</h2>
        <ul>
            <li><strong>From picklist detail:</strong> use <strong>Remove</strong> on a row (confirmation required).</li>
            <li><strong>From Orders list:</strong> ⋮ menu → <strong>Added to pickup list</strong> → confirm removal.</li>
            <li>Removing the <strong>last item</strong> deletes the empty picklist automatically.</li>
        </ul>
        <p>When an order is removed, its status is restored to what it was <strong>before</strong> it was added to the picklist (when the system can determine that from the order history).</p>
    </section>

    <section id="statuses" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">Statuses explained</h2>

        <h3 class="text-lg font-semibold text-gray-800 mt-4 mb-2">Picklist status</h3>
        <div class="not-prose overflow-hidden rounded-xl border border-gray-200 mb-6">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left">Status</th><th class="px-4 py-2 text-left">Meaning</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td class="px-4 py-2 font-medium">Pending</td><td class="px-4 py-2">Created; no items picked yet</td></tr>
                    <tr><td class="px-4 py-2 font-medium">In progress</td><td class="px-4 py-2">At least one item has been picked</td></tr>
                    <tr><td class="px-4 py-2 font-medium">Completed</td><td class="px-4 py-2">Every item on the list is picked</td></tr>
                </tbody>
            </table>
        </div>

        <h3 class="text-lg font-semibold text-gray-800 mt-4 mb-2">Order status (during picklist workflow)</h3>
        <div class="not-prose overflow-hidden rounded-xl border border-gray-200">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left">Order status</th><th class="px-4 py-2 text-left">When</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td class="px-4 py-2 font-medium">Added to picklist</td><td class="px-4 py-2">Order was added to a picklist; not picked yet</td></tr>
                    <tr><td class="px-4 py-2 font-medium">Item picked</td><td class="px-4 py-2">Picker marked the line as picked</td></tr>
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-sm text-gray-600">Reverting a pick (unpick) sets the order back to <strong>Added to picklist</strong>. Removing from the picklist restores the earlier order status.</p>
    </section>

    <section id="faq" class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">FAQ &amp; troubleshooting</h2>

        <div class="space-y-4 not-prose">
            <details class="group rounded-xl border border-gray-200 bg-white p-4 open:shadow-sm">
                <summary class="cursor-pointer font-semibold text-gray-900 list-none flex items-center justify-between">
                    Why can’t I add this order to a picklist?
                    <i class="fas fa-chevron-down text-gray-400 text-xs group-open:rotate-180 transition-transform" aria-hidden="true"></i>
                </summary>
                <p class="mt-3 text-sm text-gray-600 m-0">Common reasons: the same order number + SKU is already on another active picklist; the picklist would mix books and non-books; or the order was not found.</p>
            </details>
            <details class="group rounded-xl border border-gray-200 bg-white p-4">
                <summary class="cursor-pointer font-semibold text-gray-900 list-none flex items-center justify-between">
                    Labels print too large or misaligned
                    <i class="fas fa-chevron-down text-gray-400 text-xs group-open:rotate-180 transition-transform" aria-hidden="true"></i>
                </summary>
                <p class="mt-3 text-sm text-gray-600 m-0">Set print scale to <strong>100%</strong> and margins to <strong>none</strong>. Confirm you are using a 65-label A4 sheet (38.1 × 21.2 mm per label, 5×13 grid).</p>
            </details>
            <details class="group rounded-xl border border-gray-200 bg-white p-4">
                <summary class="cursor-pointer font-semibold text-gray-900 list-none flex items-center justify-between">
                    Order status did not change after removal
                    <i class="fas fa-chevron-down text-gray-400 text-xs group-open:rotate-180 transition-transform" aria-hidden="true"></i>
                </summary>
                <p class="mt-3 text-sm text-gray-600 m-0">The system only restores status when the order is still in Added to picklist or Item picked, and a valid previous status exists in order history. Contact an administrator if a manual status update is needed.</p>
            </details>
            <details class="group rounded-xl border border-gray-200 bg-white p-4">
                <summary class="cursor-pointer font-semibold text-gray-900 list-none flex items-center justify-between">
                    I don’t see Picklist in the menu
                    <i class="fas fa-chevron-down text-gray-400 text-xs group-open:rotate-180 transition-transform" aria-hidden="true"></i>
                </summary>
                <p class="mt-3 text-sm text-gray-600 m-0">Your role may not have permission. Ask an administrator to enable the Picklist module and assign it to your role.</p>
            </details>
        </div>
    </section>

    <footer class="not-prose mt-10 pt-6 border-t border-gray-200 text-sm text-gray-500">
        <p class="m-0">Picklist user guide · Exotic India Vendor Portal · For technical documentation see <code>docs/PICKLIST_MODULE.md</code> in the repository.</p>
    </footer>
</article>
