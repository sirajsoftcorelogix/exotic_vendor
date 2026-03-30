<div class="min-h-screen bg-gray-50">

    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-[1500px] items-center gap-3 px-4 py-3">
            <h1 class="text-lg font-semibold">POS Invoice Listing</h1>
        </div>
    </header>

    <main class="mx-auto max-w-[1500px] px-4 py-5">

        <div class="bg-white rounded-xl border p-4 mb-4">

            <div class="grid grid-cols-6 gap-3 text-xs">

                <input type="date" id="from_date" class="border rounded px-2 py-2">
                <input type="date" id="to_date" class="border rounded px-2 py-2">
                <input type="text" id="order_number" placeholder="Order Number" class="border rounded px-2 py-2">
                <select id="type" class="border rounded px-2 py-2">
                    <option value="">Type</option>
                    <option value="offline">Offline</option>
                    <option value="cod">Cash</option>
                    <option value="razorpay">Razorpay</option>
                    <option value="bank_transfer">Bank</option>
                </select>
                <select id="customer_id"
                    name="customer_id"
                    class="w-full border rounded-lg px-3 py-2 text-sm">

                    <option value="">Walk-in Customer</option>

                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>"
                            data-name="<?= htmlspecialchars($c['name']) ?>"
                            data-phone="<?= htmlspecialchars($c['phone']) ?>"
                            data-email="<?= htmlspecialchars($c['email']) ?>">
                            <?= htmlspecialchars($c['name']) ?> | <?= $c['phone'] ?> | <?= $c['email'] ?>
                        </option>
                    <?php endforeach; ?>

                </select>
                <!-- <input type="text" id="customer" placeholder="Customer" class="border rounded px-2 py-2"> -->

                <input type="number" id="amount_min" placeholder="Min Amount" class="border rounded px-2 py-2">
                <input type="number" id="amount_max" placeholder="Max Amount" class="border rounded px-2 py-2">
                <select id="status" class="border rounded px-2 py-2">
                    <option value="">All</option>
                    <option value="draft">Draft</option>
                    <option value="proforma">Proforma</option>
                    <option value="final">Final</option>
                </select>

                <button onclick="loadInvoices()" class="bg-orange-600 text-white rounded px-3">
                    Search
                </button>

            </div>

        </div>

        <div class="bg-white rounded-xl border overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-100 text-xs">
                    <tr>
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Date</th>
                        <th class="p-3 text-left">Order</th>
                        <th class="p-3 text-left">Invoice</th>
                        <th class="p-3 text-left">Customer</th>
                        <th class="p-3 text-left">Amount</th>
                        <th class="p-3 text-left">Paid</th>
                        <th class="p-3 text-left">Due</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Action</th>
                    </tr>
                </thead>

                <tbody id="invoiceTable"></tbody>

            </table>

        </div>

    </main>
</div>
<div id="deleteConfirmModal" class="fixed inset-0 z-[9999] hidden">

    <div class="absolute inset-0 bg-black/40"></div>

    <div class="relative mx-auto mt-40 w-[90%] max-w-md bg-white rounded-2xl shadow-xl">

        <div class="p-6 text-center">
            <h3 class="text-lg font-semibold mb-2">Confirm Delete</h3>
            <p class="text-sm text-gray-600 mb-5" id="deleteConfirmText">
                Are you sure you want to delete ?
            </p>

            <div class="flex justify-center gap-4">
                <button onclick="closeDeleteModal()"
                    class="px-5 py-2 bg-gray-300 rounded">
                    Cancel
                </button>

                <button onclick="confirmDelete()"
                    class="px-5 py-2 bg-red-600 text-white rounded">
                    Delete
                </button>
            </div>
        </div>

    </div>

</div>
<script>
    $(document).ready(function() {

        $('#customer_id').select2({
            placeholder: "Search Customer",
            allowClear: true,
            width: '100%',

            templateResult: formatCustomer,
            templateSelection: formatCustomerSelection
        });

    });

    function formatCustomer(data) {

        if (!data.id) return data.text;

        let el = $(data.element);

        let name = el.data('name');
        let phone = el.data('phone');
        let email = el.data('email');

        return $(`
        <div style="line-height:1.3">
            <div style="font-weight:600">${name}</div>
            <div style="font-size:11px;color:#888">
                ${phone} ${email ? ' | ' + email : ''}
            </div>
        </div>
    `);
    }

    function formatCustomerSelection(data) {

        if (!data.id) return data.text;

        let el = $(data.element);
        return el.data('name');
    }
    document.addEventListener("DOMContentLoaded", loadInvoices);

    function loadInvoices() {

        let url = `?page=posinvoice&action=list_ajax&from_date=${document.getElementById('from_date').value}
&to_date=${document.getElementById('to_date').value}
&order_number=${document.getElementById('order_number').value}
&type=${document.getElementById('type').value}
&customer_id=${document.getElementById('customer_id').value}
&amount_min=${document.getElementById('amount_min').value}
&amount_max=${document.getElementById('amount_max').value}
&status=${document.getElementById('status').value}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {

                let html = '';

                if (!data.length) {
                    html = `<tr>
<td colspan="9" class="p-6 text-center text-gray-400">
No invoices
</td>
</tr>`;
                }

                data.forEach(i => {

                    let badge = '';

                    if (i.status === 'final')
                        badge = `<span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Final</span>`;

                    if (i.status === 'proforma')
                        badge = `<span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">Proforma</span>`;

                    if (i.status === 'draft')
                        badge = `<span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">Draft</span>`;

                    html += `
<tr class="border-t hover:bg-gray-50">

<td class="p-3">${i.id ?? ''}</td>
<td class="p-3">${i.invoice_date ?? ''}</td>
<td class="p-3">${i.order_number ?? ''}</td>
<td class="p-3 font-semibold">${i.invoice_number ?? ''}</td>
<td class="p-3">${i.customer_name ?? ''}</td>

<td class="p-3 font-semibold">₹ ${i.total_amount}</td>
<td class="p-3 text-green-600">₹ ${i.paid_amount}</td>
<td class="p-3 text-red-600">₹ ${i.due_amount}</td>

<td class="p-3">${badge}</td>

 <td class="p-3 flex gap-4 items-center">
<a href="/?page=posinvoice&action=generate_pdf&invoice_id=${i.id}"
target="_blank"
class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold text-xs">

<svg width="15" height="15" viewBox="0 0 15 15" fill="none">
<path d="M2.62925 10.3889C1.64271 9.68768 1 8.54159 1 7.24672C1 5.47783 2.3 3.84375 4.25 3.52778C4.86168 2.07349 6.30934 1 7.99783 1C10.1607 1 11.9284 2.67737 12.05 4.79167C13.1978 5.29352 14 6.52522 14 7.85887C14 8.98648 13.4266 9.98004 12.5556 10.5634M7.5 14V6.77778M7.5 14L5.33333 11.8333M7.5 14L9.66667 11.8333"
stroke="currentColor"
stroke-width="1.5"
stroke-linecap="round"
stroke-linejoin="round"/>
</svg>

</a>

  <button onclick="openDeleteModal(${i.id}, '?page=posinvoice&action=delete', 'Delete this invoice?')"
        class="flex items-center gap-1 text-red-600 hover:text-red-800 text-xs font-semibold">

        <i class="fa-solid fa-trash"></i>
        
    </button>
</td>

</tr>`;
                });

                invoiceTable.innerHTML = html;

            });
    }
</script>
<script>
    function deleteInvoice(id) {

        if (!confirm("Are you sure to delete this invoice?")) return;

        let form = new FormData();
        form.append("id", id);

        fetch("?page=posinvoice&action=delete", {
                method: "POST",
                body: form
            })
            .then(res => res.json())
            .then(data => {

                if (data.success) {
                    alert("Invoice deleted");
                    loadInvoices();
                } else {
                    alert(data.message || "Delete failed");
                }

            });

    }

    function confirmDelete() {

        let form = new FormData();
        form.append("id", deleteId);

        fetch("?page=posinvoice&action=delete", {
                method: "POST",
                body: form
            })
            .then(res => res.json())
            .then(data => {

                if (data.success) {
                    alert("Invoice deleted");
                    loadInvoices();
                } else {
                    alert(data.message || "Delete failed");
                }

            });

    }

    function openDeleteModal(id, url, text = "Are you sure?") {

        deleteId = id;
        deleteUrl = url;

        document.getElementById("deleteConfirmText").innerText = text;
        document.getElementById("deleteConfirmModal").classList.remove("hidden");
    }

    function closeDeleteModal() {
        document.getElementById("deleteConfirmModal").classList.add("hidden");
    }

    function confirmDelete() {

        let form = new FormData();
        form.append("id", deleteId);

        fetch(deleteUrl, {
                method: "POST",
                body: form
            })
            .then(res => res.json())
            .then(data => {

                if (data.success) {
                    closeDeleteModal();
                    loadInvoices();
                } else {
                    alert(data.message || "Delete failed");
                }

            });
    }
</script>