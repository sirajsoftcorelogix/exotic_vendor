<div class="min-h-screen bg-gray-50">

    <!-- HEADER -->
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-[1500px] items-center gap-3 px-4 py-3">

            <h1 class="text-lg font-semibold">Payment List</h1>

            <div class="ml-auto">
                <a href="?page=pos_register"
                    class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-700">
                    Back to POS
                </a>
            </div>

        </div>
    </header>

    <!-- CONTENT -->
    <main class="mx-auto max-w-[1500px] px-4 py-5">

        <!-- FILTER BOX -->
        <div class="bg-white rounded-xl border p-4 mb-4">

            <input type="hidden" id="payments_filter_order_id" value="">

            <div class="grid grid-cols-7 gap-3 text-xs">

                <div>
                    <label>Date From</label>
                    <input type="date" id="from_date"
                        class="w-full border rounded px-2 py-2">
                </div>

                <div>
                    <label>Date To</label>
                    <input type="date" id="to_date"
                        class="w-full border rounded px-2 py-2">
                </div>

                <div>
                    <label>Order Number</label>
                    <input type="text" id="order_number"
                        class="w-full border rounded px-2 py-2">
                </div>

                <div>
                    <label>Payment Mode</label>
                    <select id="payment_mode"
                        class="w-full border rounded px-2 py-2">
                        <option value="">All</option>
                        <option value="cod">Cash</option>
                        <option value="offline">Offline</option>
                        <option value="bank_transfer">Bank</option>
                        <option value="pos_machine">POS</option>
                        <option value="razorpay">Razorpay</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>

                <div>
                    <label>Amount Min</label>
                    <input type="number" id="amount_min"
                        class="w-full border rounded px-2 py-2">
                </div>

                <div>
                    <label>Amount Max</label>
                    <input type="number" id="amount_max"
                        class="w-full border rounded px-2 py-2">
                </div>

                <div class="flex items-end">
                    <button onclick="loadPayments()"
                        class="w-full bg-orange-600 text-white py-2 rounded hover:bg-orange-700">
                        Search
                    </button>
                </div>

            </div>

        </div>

        <!-- TABLE -->
        <div class="bg-white rounded-xl border overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-100 text-xs">
                    <tr>
                        <th class="p-3 text-left">Receipt No</th>
                        <th class="p-3 text-left">Order Number</th>
                        <th class="p-3 text-left">Payment Date</th>
                        <th class="p-3 text-left">Show Room</th>
                        <th class="p-3 text-left">Amount</th>
                        <th class="p-3 text-left">Amount</th>
                        <th class="p-3 text-left">Mode</th>
                        <th class="p-3 text-left">Stage</th>
                        <th class="p-3 text-left">User</th>
                        <th class="p-3 text-left">Action</th>
                    </tr>
                </thead>

                <tbody id="paymentTable">
                    <tr>
                        <td colspan="8" class="text-center p-6 text-gray-400">
                            Loading...
                        </td>
                    </tr>
                </tbody>

            </table>

        </div>

    </main>

</div>
<div id="paymentModal" class="fixed inset-0 z-[9999] hidden">
  
    <!-- overlay -->
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closePaymentModal()"></div>

    <!-- modal -->
    <div class="relative mx-auto mt-16 w-[95%] max-w-3xl bg-white rounded-2xl shadow-2xl">

        <!-- header -->
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h2 id="paymentModalTitle" class="text-lg font-semibold text-gray-800">
                Add Payment
            </h2>

            <button onclick="closePaymentModal()"
                class="text-gray-400 hover:text-gray-700 text-xl">
                ✕
            </button>
        </div>
 <div id="payment_error_box"
        class="hidden mx-6 mt-4 bg-red-50 border border-red-300 text-red-700 text-sm px-4 py-3 rounded-lg">
    </div>
        <!-- body -->
        <div class="p-6 space-y-6">

            <input type="hidden" id="edit_payment_id">
            <input type="hidden" id="payment_order_id">

            <!-- ORDER INFO -->
            <div class="bg-gray-50 border rounded-lg p-4 grid grid-cols-2 gap-4">

                <div>
                    <div class="text-xs text-gray-500">Order Number</div>
                    <div id="payment_order_label" class="text-sm font-semibold text-gray-800">--</div>
                </div>

                <div>
                    <div class="text-xs text-gray-500">Pending Amount</div>
                    <div id="payment_pending_label" class="text-sm font-semibold text-red-600">₹ 0</div>
                </div>

            </div>

            <!-- ROW 1 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div>
                    <label class="text-xs text-gray-500">Payment Stage</label>
                    <select id="payment_stage"
                        class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500">
                        <option value="final">Final</option>
                        <option value="partial">Partial</option>
                        <option value="advance">Advance</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs text-gray-500">Payment Mode</label>
                    <select id="payment_type"
                        class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500">
                        <option value="offline">Offline</option>
                        <option value="cod">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="pos_machine">POS Machine</option>
                        <option value="razorpay">Razorpay</option>
                        <option value="specialpay">SpecialPay</option>
                        <option value="cheque">Cheque</option>
                        <option value="demand_draft">Demand Draft</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs text-gray-500">Payment Date</label>
                    <input type="date"
                        id="payment_date"
                        value="<?= date('Y-m-d') ?>"
                        class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500">
                </div>

            </div>

            <!-- ROW 2 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="text-xs text-gray-500">Amount</label>
                    <input type="number"
                        id="payment_amount"
                        class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="text-xs text-gray-500">Transaction ID</label>
                    <input type="text"
                        id="transaction_id"
                        class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500">
                </div>

            </div>

            <!-- NOTE -->
            <div>
                <label class="text-xs text-gray-500">Note</label>
                <textarea id="payment_note"
                    class="w-full mt-1 border rounded-lg px-3 py-2 h-24 focus:ring-2 focus:ring-orange-500"></textarea>
            </div>

        </div>

        <!-- footer -->
        <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-2xl">

            <button onclick="closePaymentModal()"
                class="px-6 py-2 rounded-lg bg-gray-300 hover:bg-gray-400 text-sm">
                Cancel
            </button>

            <button onclick="savePayment()"
                id="paymentSaveBtn"
                class="px-6 py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold">
                Confirm Payment
            </button>

        </div>

    </div>
</div>
<div id="invoicePreviewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50" onclick="closePreviewModal()">
    <div class="bg-white max-w-4xl w-full max-h-[90vh] overflow-y-auto rounded-lg" onclick="event.stopPropagation()">
        <div class="sticky top-0 bg-gray-100 p-4 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold">Invoice Preview</h2>
            <button type="button" onclick="closePreviewModal()" class="text-red-600 hover:text-red-800 text-2xl">&times;</button>
        </div>
        <div id="invoicePreviewContent" class="p-4"></div>
        <div class="sticky bottom-0 bg-gray-100 p-4 border-t flex justify-end space-x-2">
            <button type="button" onclick="closePreviewModal()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Close</button>
            <button type="button" onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Print</button>
        </div>
    </div>
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
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('payment_date').max = new Date().toISOString().split('T')[0];
        loadPayments();
    });

    /* ================= LOAD PAYMENT LIST ================= */

    function loadPayments() {


    const oidEl = document.getElementById('payments_filter_order_id');
    const oidParam = oidEl && oidEl.value ? `&order_id=${encodeURIComponent(oidEl.value)}` : '';
    let url = `?page=payments&action=list_ajax&from_date=${encodeURIComponent(document.getElementById('from_date').value)}&to_date=${encodeURIComponent(document.getElementById('to_date').value)}&order_number=${encodeURIComponent(document.getElementById('order_number').value)}&payment_mode=${encodeURIComponent(document.getElementById('payment_mode').value)}&amount_min=${encodeURIComponent(document.getElementById('amount_min').value)}&amount_max=${encodeURIComponent(document.getElementById('amount_max').value)}${oidParam}`;


        fetch(url)
            .then(res => res.json())
            .then(data => {

                let html = '';

                if (!data.length) {
                    html = `<tr>
            <td colspan="9" class="text-center p-6 text-gray-400">
                No payments found
            </td>
        </tr>`;
                }

                data.forEach(p => {



                    html += `
    <tr class="border-t hover:bg-gray-50">
        <td class="p-3">#${p.id}</td>
        <td class="p-3">${p.order_number ?? ''}</td>
        <td class="p-3">${p.payment_date ?? ''}</td>
        <td class="p-3">${p.warehouse ?? ''}</td>
  
        <td class="p-3 font-semibold">
     ₹ ${p.amount} <br>
   
</td>
<td> <span class="text-red-600 text-xs"> ₹ ${p.pending_amount ?? 0}</span></td>
        <td class="p-3">${p.payment_mode ?? ''}</td>
        <td class="p-3">${p.payment_stage ?? ''}</td>
        <td class="p-3">${p.user_name ?? ''}</td>

       <td class="p-3 flex gap-4 items-center">

    <!-- ADD PAYMENT -->
    <button onclick="openAddPayment(${p.order_id}, '${p.order_number}')"
        class="flex items-center gap-1 text-orange-600 hover:text-orange-800 text-xs font-semibold">

        <i class="fa-solid fa-credit-card"></i>
        
    </button>

    <!-- EDIT -->
    <button onclick="editPayment(${p.id})"
        class="flex items-center gap-1 text-blue-600 hover:text-blue-800 text-xs font-semibold">

        <i class="fa-solid fa-pen-to-square"></i>
        
    </button>

    <!-- DELETE -->
    <button onclick="openDeleteModal(${p.id}, '?page=payments&action=delete', 'Delete this payment ?')"
        class="flex items-center gap-1 text-red-600 hover:text-red-800 text-xs font-semibold">

        <i class="fa-solid fa-trash"></i>
        
    </button>

    <!-- RECEIPT -->
    <button onclick="downloadReceipt(${p.id})"
        class="flex items-center gap-1 text-green-600 hover:text-green-800 text-xs font-semibold">

        <i class="fa-solid fa-file-invoice"></i>
        
    </button>

</td>
    </tr>`;
                });

                document.getElementById('paymentTable').innerHTML = html;
            });

    }
    let deleteId = null;
    let deleteUrl = null;
    let CURRENT_PENDING = 0;

    function openDeleteModal(id, url, text = "Are you sure you want to delete ?") {

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
                    loadPayments(); // reload table
                } else {
                    alert(data.message || "Delete failed");
                }

            });

    }
    async function editPayment(id) {

    clearPaymentError();

    let res = await fetch(`?page=payments&action=get_single_payment&id=${id}`);
    let data = await res.json();

    if (!data.success) {
        showPaymentError("Payment load failed");
        return;
    }

    let p = data.payment;

    // open modal
    document.getElementById("paymentModal").classList.remove("hidden");

    // edit mode
    document.getElementById("edit_payment_id").value = p.id;
    document.getElementById("payment_order_id").value = p.order_id;

    document.getElementById("payment_order_label").innerText = p.order_number;

    // ⭐ KEEP ORIGINAL PAYMENT AMOUNT
    document.getElementById("payment_amount").value = p.amount;

    document.getElementById("payment_stage").value = p.payment_stage;
    document.getElementById("payment_type").value = p.payment_mode;
    document.getElementById("transaction_id").value = p.transaction_id;
    document.getElementById("payment_note").value = p.note;
    document.getElementById("payment_date").value = p.payment_date;

    // ⭐ NOW LOAD PENDING
    fetch(`?page=payments&action=get_payment_summary&order_number=${p.order_number}`)
        .then(res => res.json())
        .then(sum => {

            if (!sum.success) return;

            CURRENT_PENDING = parseFloat(sum.pending);

            document.getElementById("payment_pending_label").innerText =
                "₹ " + CURRENT_PENDING;

        });

    // change UI
    document.getElementById("paymentModalTitle").innerText = "Edit Payment";
    document.getElementById("paymentSaveBtn").innerText = "Update Payment";
}
    async function deletePayment(id) {

        if (!confirm("Delete this payment ?")) return;

        let form = new FormData();
        form.append("id", id);

        let res = await fetch(`?page=payments&action=delete`, {
            method: "POST",
            body: form
        });

        let data = await res.json();

        if (data.success) {
            alert("Deleted");
            loadPayments();
        } else {
            alert("Delete failed");
        }

    }

    function downloadReceipt(paymentId) {
        window.open(`?page=payments&action=receipt&id=${paymentId}`, '_blank');
    }

    // function openAddPayment(orderId, orderNumber) {
    //     document.getElementById("paymentModal").classList.remove("hidden");

    //     document.getElementById("payment_order_id").value = orderId;
    //     document.getElementById("payment_order_label").innerText = orderNumber;

    //     fetch(`?page=payments&action=get_payment_summary&order_number=${orderNumber}`)
    //         .then(res => res.json())
    //         .then(data => {
    //             if (!data.success) return;
    //             document.getElementById("payment_amount").value = data.pending;
    //         });
    // }
    function openAddPayment(orderId, orderNumber) {

        document.getElementById("paymentModal").classList.remove("hidden");

        document.getElementById("payment_order_id").value = orderId;
        document.getElementById("payment_order_label").innerText = orderNumber;

        fetch(`?page=payments&action=get_payment_summary&order_number=${orderNumber}`)
            .then(res => res.json())
            .then(data => {

                if (!data.success) return;

                CURRENT_PENDING = parseFloat(data.pending);

                document.getElementById("payment_pending_label").innerText = "₹ " + CURRENT_PENDING;

                document.getElementById("payment_amount").value = CURRENT_PENDING;
                clearPaymentError();
            });
    }
    /* ================= OPEN MODAL ================= */
    function createInvoiceFromPayment(paymentId) {

        fetch('index.php?page=payments&action=create_from_payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    payment_id: paymentId
                })
            })
            .then(res => res.json())
            .then(data => {

                if (!data.success) {
                    alert(data.message);
                    return;
                }

                window.open(
                    `?page=invoices&action=generate_pdf&invoice_id=${data.invoice_id}`,
                    '_blank'
                );

                loadPayments();
            });

    }

    /* ================= SAVE PAYMENT ================= */
    function savePayment() {

    clearPaymentError();

    let editId = document.getElementById("edit_payment_id").value;
    let stage = document.getElementById('payment_stage').value;
    let amount = parseFloat(document.getElementById('payment_amount').value);

    if (!amount || amount <= 0) {
        showPaymentError("Amount must be greater than 0");
        return;
    }

    // ⭐ FINAL STRICT VALIDATION
    if (stage === "final") {

        if (amount !== CURRENT_PENDING) {
            showPaymentError("Final payment must be exactly pending amount ₹ " + CURRENT_PENDING);
            return;
        }

    }

    let formData = new FormData();

    formData.append('order_id', document.getElementById('payment_order_id').value);
    formData.append('amount', amount);
    formData.append('payment_type', document.getElementById('payment_type').value);
    formData.append('payment_stage', stage);
    formData.append('transaction_id', document.getElementById('transaction_id').value);
    formData.append('note', document.getElementById('payment_note').value);
    formData.append('payment_date', document.getElementById('payment_date').value);

    let url = editId
        ? 'index.php?page=payments&action=update_payment'
        : 'index.php?page=payments&action=save_payment';

    if (editId) formData.append('id', editId);

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        if (!data.success) {
            showPaymentError(data.message || "Save failed");
            return;
        }

        closePaymentModal();
        loadPayments();
    });
}

    /* ================= FINAL INVOICE ================= */

    function createFinalInvoice(orderId) {

        fetch('?page=invoices&action=CreateAutoFromOrder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    orderid: orderId
                })
            })
            .then(res => res.json())
            .then(data => {

                if (!data.success) {
                    alert(data.message || 'Invoice failed');
                    return;
                }

                openInvoicePreview(data.invoice_id);

            });

    }

    /* ================= PROFORMA ================= */

    function createProformaInvoice(orderId) {

        fetch('index.php?page=invoices&action=create_proforma', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    orderid: orderId
                })
            })
            .then(res => res.json())
            .then(data => {

                if (!data.success) {
                    alert(data.message || 'Proforma failed');
                    return;
                }

                openInvoicePreview(data.invoice_id);

            });

    }

    /* ================= PREVIEW ================= */

    function openInvoicePreview(invoiceId) {

        fetch('index.php?page=invoices&action=preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    invoice_id: invoiceId
                })
            })
            .then(res => res.json())
            .then(data => {

                if (!data.success) {
                    alert(data.message || 'Preview failed');
                    return;
                }

                document.getElementById('invoicePreviewContent').innerHTML = data.html;
                document.getElementById('invoicePreviewModal').classList.remove('hidden');

            });

    }

    /* ================= CLOSE ================= */

    function closePaymentModal() {
        document.getElementById("paymentModal").classList.add("hidden");
    }

    function closePreviewModal() {
        document.getElementById("invoicePreviewModal").classList.add("hidden");
    }

    function showPaymentError(msg) {
        let box = document.getElementById("payment_error_box");
        box.innerText = msg;
        box.classList.remove("hidden");
    }

    function clearPaymentError() {
        document.getElementById("payment_error_box").classList.add("hidden");
    }
</script>

<!-- ${p.payment_stage === 'final' ? `
<button onclick="createInvoiceFromPayment(${p.id})"
   class="text-green-700 text-xs font-semibold flex items-center">

    <svg width="15" height="15" viewBox="0 0 15 15" fill="none"
        xmlns="http://www.w3.org/2000/svg">
        <path d="M2.62925 10.3889C1.64271 9.68768 1 8.54159 1 7.24672C1 5.47783 2.3 3.84375 4.25 3.52778C4.86168 2.07349 6.30934 1 7.99783 1C10.1607 1 11.9284 2.67737 12.05 4.79167C13.1978 5.29352 14 6.52522 14 7.85887C14 8.98648 13.4266 9.98004 12.5556 10.5634M7.5 14V6.77778M7.5 14L5.33333 11.8333M7.5 14L9.66667 11.8333"
            stroke="black"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"/>
    </svg>

</button>
` : ``} -->