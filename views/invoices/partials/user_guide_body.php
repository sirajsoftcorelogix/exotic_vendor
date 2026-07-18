<?php
/**
 * Invoice module — end-user guide body.
 * Edit this file, then run: php scripts/build_invoice_user_guide_html.php
 * Keep in sync with docs/INVOICE_CREATION.md (technical / AI reference).
 *
 * @see docs/invoice/README.md
 */
?>
<article class="invoice-user-guide prose prose-slate max-w-none">
    <header class="not-prose mb-8 rounded-2xl border border-orange-200/60 bg-gradient-to-br from-orange-50/80 via-white to-slate-50 p-6 sm:p-8">
        <div class="flex flex-wrap items-start gap-4">
            <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-100 text-orange-700 text-2xl">
                <i class="fas fa-file-invoice" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-orange-800/80 mb-1">Sales · Tax invoices</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 m-0">Invoice Module — User Guide</h1>
                <p class="mt-2 text-sm sm:text-base text-gray-600 m-0 max-w-2xl">
                    How tax invoices are created at POS, from order lists, dispatch, and payments — plus GST rules, PDF download, and fixing common issues.
                </p>
                <p class="mt-3 text-xs text-gray-500 m-0">Last updated: July 2026</p>
            </div>
        </div>
    </header>

    <nav class="not-prose mb-8 rounded-xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm" aria-label="On this page">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">On this page</p>
        <ul class="grid sm:grid-cols-2 gap-x-6 gap-y-2 text-sm m-0 p-0 list-none">
            <li><a href="#overview" class="text-orange-700 hover:text-orange-900 font-medium">Overview</a></li>
            <li><a href="#how-created" class="text-orange-700 hover:text-orange-900 font-medium">How invoices are created</a></li>
            <li><a href="#proforma-final" class="text-orange-700 hover:text-orange-900 font-medium">Proforma vs final</a></li>
            <li><a href="#gst" class="text-orange-700 hover:text-orange-900 font-medium">GST (IGST / CGST / SGST)</a></li>
            <li><a href="#pdf" class="text-orange-700 hover:text-orange-900 font-medium">Downloading PDFs</a></li>
            <li><a href="#pdf-layout" class="text-orange-700 hover:text-orange-900 font-medium">What’s on the PDF</a></li>
            <li><a href="#pos-listing" class="text-orange-700 hover:text-orange-900 font-medium">POS invoice listing</a></li>
            <li><a href="#overseas" class="text-orange-700 hover:text-orange-900 font-medium">Overseas customers</a></li>
            <li><a href="#high-value-compliance" class="text-orange-700 hover:text-orange-900 font-medium">PAN &amp; passport (high value)</a></li>
            <li><a href="#fix-old" class="text-orange-700 hover:text-orange-900 font-medium">Fixing old invoices</a></li>
            <li><a href="#settings" class="text-orange-700 hover:text-orange-900 font-medium">App settings</a></li>
            <li><a href="#faq" class="text-orange-700 hover:text-orange-900 font-medium">FAQ</a></li>
        </ul>
    </nav>

    <section id="overview" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">Overview</h2>
        <p>A <strong>tax invoice</strong> in the portal includes:</p>
        <ul>
            <li><strong>Invoice header</strong> — number, date, customer, totals, status, store/warehouse</li>
            <li><strong>Line items</strong> — products, HSN, quantity, prices, GST columns, box number</li>
            <li><strong>Customer address</strong> — billing and shipping (from the order)</li>
        </ul>
        <div class="not-prose my-4 rounded-xl border border-sky-200 bg-sky-50/60 p-4 text-sm text-sky-950">
            <p class="font-semibold m-0 mb-1"><i class="fas fa-info-circle text-sky-600 mr-1" aria-hidden="true"></i> Good to know</p>
            <ul class="m-0 pl-5 space-y-1">
                <li>PDF files are <strong>not stored</strong> on the server — each download builds a fresh PDF from current data.</li>
                <li>Re-downloading a PDF after a system update often fixes display issues <strong>without recreating</strong> the invoice.</li>
                <li>Only <strong>one active invoice</strong> per order (cancelled invoices don’t count).</li>
            </ul>
        </div>
    </section>

    <section id="how-created" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">How invoices are created</h2>
        <div class="not-prose overflow-x-auto">
            <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="p-3 font-semibold">Path</th>
                        <th class="p-3 font-semibold">When</th>
                        <th class="p-3 font-semibold">Where in portal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr>
                        <td class="p-3 font-medium">POS checkout</td>
                        <td class="p-3">Customer pays at register; order is imported</td>
                        <td class="p-3">POS Register → checkout</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">Manual create</td>
                        <td class="p-3">Staff selects order lines and creates invoice</td>
                        <td class="p-3">Orders / POS Orders → Create Invoice</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">Dispatch bulk</td>
                        <td class="p-3">Invoices created while dispatching shipments</td>
                        <td class="p-3">Dispatch → bulk create invoices</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">Payment finalize</td>
                        <td class="p-3">Remaining payment recorded; proforma promoted or invoice created</td>
                        <td class="p-3">Payments screen</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="mt-4"><strong>Custom invoice number</strong> at POS checkout is allowed only when the payment amount equals the full order total.</p>
    </section>

    <section id="proforma-final" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">Proforma vs final</h2>
        <div class="not-prose overflow-x-auto">
            <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="p-3 font-semibold"></th>
                        <th class="p-3 font-semibold">Proforma</th>
                        <th class="p-3 font-semibold">Final (tax invoice)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr>
                        <td class="p-3 font-medium">Payment</td>
                        <td class="p-3">Partial or not complete</td>
                        <td class="p-3">Paid in full</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">Stock deducted</td>
                        <td class="p-3">No</td>
                        <td class="p-3">Yes</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">PDF title</td>
                        <td class="p-3">Proforma invoice</td>
                        <td class="p-3">Tax invoice</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">After full payment</td>
                        <td class="p-3">Status updated to final</td>
                        <td class="p-3">Already final</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section id="gst" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">GST (IGST / CGST / SGST)</h2>
        <p>The system decides tax type from <strong>place of supply</strong> vs <strong>seller state</strong> (Exotic India — typically Delhi).</p>
        <ul>
            <li><strong>Same state</strong> (e.g. Delhi → Delhi): <strong>CGST + SGST</strong> (half rate each)</li>
            <li><strong>Different states</strong> (e.g. Delhi → Rajasthan): <strong>IGST</strong> only (full rate)</li>
            <li><strong>Overseas customer</strong> (default): <strong>no GST</strong> (export)</li>
            <li><strong>Overseas + “Apply GST”</strong> at POS: GST charged (usually IGST)</li>
        </ul>
        <p><strong>Place of supply</strong> = shipping state if a shipping address exists; otherwise billing state.</p>
        <p>On the PDF, <strong>Ship To</strong> shows <strong>Place of Supply: {state}</strong> at the bottom of the address block.</p>
        <p><strong>Bill To</strong> shows <strong>State Code</strong> (GST numeric code, e.g. 08 for Rajasthan) for Indian customers.</p>
    </section>

    <section id="pdf" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">Downloading PDFs</h2>
        <h3 class="text-lg font-semibold text-gray-800">POS invoices</h3>
        <ol>
            <li>Open <strong>POS Invoice Listing</strong> (<code>?page=posinvoice</code>)</li>
            <li>Find the invoice (by date, order number, or customer)</li>
            <li>Click the <strong>PDF download</strong> icon in the Action column</li>
        </ol>
        <p>Direct link format: <code>?page=posinvoice&amp;action=generate_pdf&amp;invoice_id={id}</code></p>
        <h3 class="text-lg font-semibold text-gray-800 mt-6">Manual / order-list invoices</h3>
        <p>From the invoices list, use <strong>Download</strong> or open <code>?page=invoices&amp;action=generate_pdf&amp;invoice_id={id}</code></p>
    </section>

    <section id="pdf-layout" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">What’s on the PDF</h2>
        <h3 class="text-lg font-semibold text-gray-800">Header</h3>
        <ul>
            <li>Company name, warehouse address, GSTIN, PAN</li>
            <li>Invoice number, date, “Tax Invoice” / “Proforma”</li>
        </ul>
        <h3 class="text-lg font-semibold text-gray-800 mt-4">Bill To / Ship To</h3>
        <ul>
            <li>Customer name in <strong>bold</strong></li>
            <li>Address, city, state, pin</li>
            <li>Bill To: State Code, GSTIN (if present), phone</li>
            <li>Ship To: phone, <strong>Place of Supply</strong></li>
        </ul>
        <h3 class="text-lg font-semibold text-gray-800 mt-4">Line items</h3>
        <ul>
            <li>Description, HSN, qty, list price, taxable value (when discounted)</li>
            <li>SGST / CGST / IGST columns and line total</li>
        </ul>
        <h3 class="text-lg font-semibold text-gray-800 mt-4">Summary (POS invoices with discounts)</h3>
        <div class="not-prose overflow-x-auto">
            <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="p-3 font-semibold">Label</th>
                        <th class="p-3 font-semibold">Meaning</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr>
                        <td class="p-3 font-medium">Total Before Discount (incl. GST)</td>
                        <td class="p-3">Goods total before order-level discounts</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">Line Discount</td>
                        <td class="p-3">Manual per-line discounts</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">Custom Discount / Coupon / Gift Voucher</td>
                        <td class="p-3">Checkout reductions</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">Total GST</td>
                        <td class="p-3">Tax amount (may note “included in line totals”)</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">GRAND Total</td>
                        <td class="p-3">Amount payable</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section id="pos-listing" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">POS invoice listing</h2>
        <p>The listing shows invoice ID, date, order number, store/warehouse, customer (with <strong>billing state and country</strong> on a second line), amounts, paid/pending, status, and actions (PDF, cancel, delete).</p>
        <p>Use date range and filters to find invoices. Status badges: <span class="not-prose inline-block px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Final</span>,
        <span class="not-prose inline-block px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700">Proforma</span>,
        <span class="not-prose inline-block px-2 py-0.5 rounded text-xs bg-red-100 text-red-700">Cancelled</span>.</p>
    </section>

    <section id="overseas" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">Overseas customers</h2>
        <p>At POS checkout, after delivery details, if the customer country is not India you may see a modal:</p>
        <ul>
            <li><strong>No GST (export)</strong> — default; zero GST on invoice</li>
            <li><strong>Yes, apply GST</strong> — charge GST per normal rules</li>
        </ul>
        <p>On manual invoice create, use the <strong>Apply GST</strong> checkbox for non-Indian addresses.</p>
    </section>

    <section id="high-value-compliance" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">PAN &amp; passport — when required</h2>
        <p>These fields apply at <strong>POS checkout</strong> when confirming the customer address (before the order completes and the invoice is created). They are <strong>not</strong> asked on every invoice — only when the transaction meets the high-value rule below.</p>

        <div class="not-prose my-4 rounded-xl border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-950">
            <p class="font-semibold m-0 mb-2"><i class="fas fa-exclamation-circle text-amber-600 mr-1" aria-hidden="true"></i> When the compliance panel appears</p>
            <p class="m-0">When the order total is <strong>₹2,00,000 or more</strong> (or the limit set in App Settings → <strong>High value transaction limit</strong>), the amber panel <strong>High Value Transaction – Compliance Required</strong> is shown on the POS billing step.</p>
        </div>

        <p><strong>Note:</strong> <em>Passport Number</em> refers to the customer’s travel document — not your login password.</p>

        <h3 class="text-lg font-semibold text-gray-800 mt-6">If customer enters a valid GSTIN</h3>
        <ul>
            <li><strong>PAN is not required separately</strong> — PAN is derived automatically from the GSTIN for B2B handling.</li>
            <li>Passport and country of residence are not required in this case.</li>
        </ul>

        <h3 class="text-lg font-semibold text-gray-800 mt-6">If high value and <strong>no GSTIN</strong> — by residency</h3>
        <div class="not-prose overflow-x-auto">
            <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="p-3 font-semibold">Customer type</th>
                        <th class="p-3 font-semibold">Required</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr>
                        <td class="p-3 font-medium">Indian Resident</td>
                        <td class="p-3"><strong>PAN</strong> (10 characters, e.g. ABCDE1234F)</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">NRI</td>
                        <td class="p-3"><strong>PAN</strong> <em>or</em> <strong>Passport Number</strong> + <strong>Country of Residence</strong></td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">Foreign National</td>
                        <td class="p-3"><strong>Passport Number</strong> + <strong>Country of Residence</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h3 class="text-lg font-semibold text-gray-800 mt-6">Optional &amp; other rules</h3>
        <ul>
            <li><strong>Aadhaar</strong> — optional (12 digits) when the compliance panel is shown.</li>
            <li><strong>Below the high-value limit</strong> — PAN and passport are not required for checkout.</li>
            <li><strong>Cash ₹2,00,000+</strong> (or limit): a separate <strong>Section 269ST</strong> cash warning must be acknowledged if paying by cash — this is independent of PAN/passport.</li>
        </ul>

        <p class="text-sm text-gray-600">Details are saved on the customer record and noted on the invoice for compliance. Manual invoice create from the order list does not run this same POS checkout panel.</p>
    </section>

    <section id="fix-old" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">Fixing old invoices</h2>
        <div class="not-prose rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 text-sm text-emerald-950 mb-4">
            <p class="font-semibold m-0 mb-1"><i class="fas fa-check-circle text-emerald-600 mr-1" aria-hidden="true"></i> PDF display fixes (IGST, layout, labels)</p>
            <p class="m-0">Usually <strong>re-download the PDF</strong> — no need to recreate the invoice. The system recalculates tax from the customer address when generating the PDF.</p>
        </div>
        <div class="not-prose rounded-xl border border-amber-200 bg-amber-50/60 p-4 text-sm text-amber-950">
            <p class="font-semibold m-0 mb-1"><i class="fas fa-exclamation-triangle text-amber-600 mr-1" aria-hidden="true"></i> When you must recreate or fix database</p>
            <ul class="m-0 pl-5 space-y-1">
                <li>An <strong>e-invoice / IRN</strong> was already filed with wrong tax split</li>
                <li><strong>GSTR export</strong> needs corrected stored line items (not just PDF)</li>
            </ul>
            <p class="mt-2 mb-0">Contact your administrator or development team for database corrections.</p>
        </div>
    </section>

    <section id="settings" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">App settings</h2>
        <p>For reliable GST and invoice headers, configure in <strong>App Settings → Firm Details</strong>:</p>
        <ul>
            <li><strong>Firm state</strong> — e.g. <code>Delhi</code> (used for IGST vs CGST/SGST)</li>
            <li><strong>Firm GSTIN</strong>, address, PAN</li>
            <li><strong>High value transaction limit</strong> — default <code>200000</code> (₹2,00,000); triggers PAN/passport compliance at POS checkout</li>
            <li><strong>Invoice prefix / series</strong> — auto numbering</li>
            <li><strong>Terms and conditions</strong> — shown on PDF when set</li>
        </ul>
    </section>

    <section id="faq" class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 border-b border-gray-200 pb-2 mb-4">FAQ</h2>
        <details class="not-prose mb-3 rounded-lg border border-gray-200 bg-white p-4">
            <summary class="font-semibold cursor-pointer text-gray-900">Why does inter-state invoice show IGST on re-download but showed CGST+SGST before?</summary>
            <p class="mt-3 text-sm text-gray-600 mb-0">Older invoices stored a same-state tax split. PDF generation now recalculates from billing/shipping state. Re-download to get the correct IGST display.</p>
        </details>
        <details class="not-prose mb-3 rounded-lg border border-gray-200 bg-white p-4">
            <summary class="font-semibold cursor-pointer text-gray-900">Grand total is correct but tax columns look wrong on an old PDF file I saved.</summary>
            <p class="mt-3 text-sm text-gray-600 mb-0">Saved PDFs are snapshots. Download again from the portal for the latest layout and tax split.</p>
        </details>
        <details class="not-prose mb-3 rounded-lg border border-gray-200 bg-white p-4">
            <summary class="font-semibold cursor-pointer text-gray-900">Invoice not created after partial payment.</summary>
            <p class="mt-3 text-sm text-gray-600 mb-0">Partial payment creates a <strong>proforma</strong> invoice. A final tax invoice is issued when payment is complete (or via Payments → create from payment).</p>
        </details>
        <details class="not-prose mb-3 rounded-lg border border-gray-200 bg-white p-4">
            <summary class="font-semibold cursor-pointer text-gray-900">Checkout blocked — PAN or passport required.</summary>
            <p class="mt-3 text-sm text-gray-600 mb-0">Order total is at or above the high value limit (default ₹2,00,000). Enter GSTIN (PAN auto-derived), or complete PAN/passport fields per residency type in the compliance panel on the POS billing step.</p>
        </details>
        <details class="not-prose mb-3 rounded-lg border border-gray-200 bg-white p-4">
            <summary class="font-semibold cursor-pointer text-gray-900">“Invoice already exists for this order”.</summary>
            <p class="mt-3 text-sm text-gray-600 mb-0">Each order can have only one active invoice. Cancel the existing invoice first if your process allows, or use the existing invoice PDF.</p>
        </details>
    </section>

    <footer class="not-prose mt-12 pt-6 border-t border-gray-200 text-xs text-gray-500">
        <p class="m-0">Invoice module user guide · Exotic India Vendor Portal · Technical reference: <code>docs/INVOICE_CREATION.md</code></p>
    </footer>
</article>
