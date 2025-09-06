<div class="bg-white p-4 md:p-8">
    <form action="<?php echo base_url('purchase_orders/create_post'); ?>" id="create_po" method="post">
    <div class="flex flex-col md:flex-row justify-between mb-8">
        <!-- Left Column -->
        <div class="space-y-2 w-full md:w-auto">
            <div class="flex items-center">
                <label for="vendor" class="block text-gray-700 form-label">Vendor :</label>
                <select id="vendor" name="vendor" class="mt-1 block pl-3 pr-10 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md form-input w-full md:w-[300px]">
                    <option value="">Select Vendor</option>
                    <?php foreach ($vendors as $vendor): ?>
                        <option value="<?= $vendor['id'] ?>"><?= htmlspecialchars($vendor['contact_name']) ?></option>
                    <?php endforeach; ?>  
                </select>
                
            </div>
            <div class="flex items-center">
                <label for="delivery-address" class="block text-gray-700 form-label">Delivery Address :</label>
                <select id="delivery_address" name="delivery_address" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md form-input px-3 w-full md:w-[300px]">
                    <option value="">Select Delivery Address</option>
                    <?php foreach ($exotic_address as $address): ?>
                        <option value="<?= $address['id'] ?>"><?= htmlspecialchars($address['address']) ?></option>
                    <?php endforeach; ?>
                </select>   
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-2 w-full md:w-auto mt-4 md:mt-0">
            <div class="flex items-center">
                <label for="delivery-due-date" class="block text-gray-700 form-label">Delivery Due Date :</label>
                <input type="date" id="delivery_due_date" name="delivery_due_date" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md form-input px-3 w-full md:w-[150px]" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>
            <!-- <div class="flex items-center">
                <label for="order-id" class="block text-gray-700 form-label">Order ID</label>
                <input type="text" name="order_id" id="order_id" placeholder="2142086" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md bg-white form-input px-3 placeholder-gray-400 w-full md:w-[150px]">
            </div> -->
            <div class="flex items-center">
                <label for="employee-name" class="block text-gray-700 form-label">Employee Name</label>
                <select name="user_id" id="employee_name" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md bg-white form-input px-3 w-full md:w-[150px]">
                    <option value="">Select Employee</option>
                    <?php foreach ($users as $id => $name): ?>
                        <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Item Table -->
    <div class="bg-[rgba(245,245,245,1)] p-4 rounded-lg">
        <table class="w-full border-separate" id="poTable" style="border-spacing: 0 5px;">
            <thead class="table-header">
            <tr>
                <th class="p-2 text-left w-1/12">S.No</th>
                <th class="p-2 text-left w-3/12">Item Summary</th>
                <th class="p-2 text-left w-1/12">HSN</th>
                <th class="p-2 text-left w-1/12">Image</th>
                <th class="p-2 text-left w-1/12">GST %</th>
                <th class="p-2 text-left w-2/12">Quantity</th>
                <th class="p-2 text-left w-1/12">Unit</th>
                <th class="p-2 text-left w-2/12">Rate</th>
                <th class="p-2 text-left w-1/12">Amount</th>
                <th class="p-2 text-right w-1/12"></th>
            </tr>
            </thead>
            <tbody class="table-row-text">
                <?php foreach ($data as $index => $item): ?>
            <tr class="bg-white">
                <td class="p-4 rounded-l-lg"><input type="hidden" name="orderid[]" value="<?= $item['id'] ?>"><input type="hidden" name="ordernumber[]" value="<?= $item['order_number'] ?>"><?php echo $index + 1; ?></td>
                <td class="p-4"><input type="hidden" name="title[]" value="<?= $item['title'] ?>" ><?php echo $item['title']; ?></td>
                <td class="p-4"><input type="hidden" name="hsn[]" value="<?= $item['hsn'] ?>"><?php echo $item['hsn']; ?></td>
                <td class="p-4"><input type="hidden" name="img[]" value="<?= $item['image'] ?>"><img src="<?php echo $item['image']; ?>" class="rounded-lg"></td>
                <td class="p-4"><input type="number" name="gst[]" class="gst w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="<?php echo $item['gst']; ?>" oninput="calculateTotals()" required></td>
                <td class="p-4">
                    <div class="flex items-center space-x-2">
                        <input type="number" name="quantity[]" class="quantity w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="<?php echo $item['quantity'];  ?>" oninput="calculateTotals()" required>
                        <!-- <button class="text-[#D06706]">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_427_430)"><path d="M12.0465 8.20171C10.6474 9.47037 9.33829 11.0991 7.90075 12.3041C7.56581 12.5845 7.25417 12.7388 6.8125 12.7978C6.09762 12.8939 5.09165 12.9659 4.36744 12.9883C3.50508 13.0154 2.73585 12.5712 2.75448 11.6359C2.76884 10.909 2.86781 9.93098 2.95164 9.19835C2.992 8.84595 3.04983 8.53545 3.24582 8.2299L11.1585 0.415632C11.9227 -0.178697 12.8029 -0.120026 13.5279 0.491828C14.0922 0.968052 15.0966 1.93688 15.5631 2.49426C16.1484 3.19335 16.1422 4.07837 15.5631 4.77785C14.5839 5.96041 13.1029 7.05649 12.0461 8.20209L12.0465 8.20171ZM12.2572 1.03396C12.1435 1.04272 11.9914 1.11244 11.8971 1.17873C11.5144 1.44732 11.1364 2.00355 10.7525 2.30224L13.6765 5.13787C14.091 4.59726 15.3764 3.97665 14.7694 3.19678C14.2393 2.51559 13.2993 1.87897 12.7319 1.19664C12.6112 1.0972 12.416 1.02139 12.2568 1.03396H12.2572ZM3.89279 11.8744C3.9382 11.9216 4.10004 11.9635 4.17145 11.962C4.89643 11.9464 5.93228 11.858 6.65687 11.7692C6.78689 11.7532 6.92699 11.7174 7.03916 11.6492L12.8693 5.94022L9.99496 3.04591L4.13652 8.79985C4.00651 8.99529 3.98516 9.58505 3.96032 9.84602C3.9153 10.323 3.85631 10.8968 3.84195 11.368C3.83846 11.4842 3.82022 11.7989 3.8924 11.8744H3.89279Z" fill="currentColor"/><path d="M2.04958 2.33194C3.16732 2.2085 4.46941 2.40014 5.60695 2.32394C6.18289 2.447 6.14176 3.26687 5.56736 3.34687C4.59787 3.48174 3.31946 3.26344 2.30922 3.34878C1.6281 3.4063 1.1127 3.92444 1.04788 4.58696V13.695C1.10687 14.4322 1.64634 14.9138 2.38684 14.9713H11.5488C13.652 14.8079 12.6526 11.8801 12.8886 10.5337C13.0523 9.99611 13.7703 9.99839 13.9326 10.5337C13.8247 12.6089 14.6599 15.6335 11.7045 16.0003H2.2316C1.06845 15.9165 0.137389 15.0174 0 13.8859L0.00620967 4.36409C0.140494 3.35906 1.00791 2.447 2.04997 2.33194H2.04958Z" fill="currentColor"/></g><defs><clipPath id="clip0_427_430"><rect width="16" height="16" fill="white"/></clipPath></defs></svg>
                        </button> -->
                    </div>
                </td>
                <td class="p-4">Nos</td>
                <td class="p-4">
                    <div class="flex items-center space-x-2">
                        <input type="number" name="rate[]" value="" oninput="calculateTotals()" required class="amount w-[105px] h-[25px] text-center border rounded-md focus:ring-0 form-input">
                        <input type="checkbox" id="gst_inclusive" name="gst_inclusive[]" class="gst_inclusive" value="1" onchange="calculateTotals()">
                        <label for="gst_inclusive">GST inclusive</label>
                        <!-- <button class="text-[#D06706]">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_427_430)"><path d="M12.0465 8.20171C10.6474 9.47037 9.33829 11.0991 7.90075 12.3041C7.56581 12.5845 7.25417 12.7388 6.8125 12.7978C6.09762 12.8939 5.09165 12.9659 4.36744 12.9883C3.50508 13.0154 2.73585 12.5712 2.75448 11.6359C2.76884 10.909 2.86781 9.93098 2.95164 9.19835C2.992 8.84595 3.04983 8.53545 3.24582 8.2299L11.1585 0.415632C11.9227 -0.178697 12.8029 -0.120026 13.5279 0.491828C14.0922 0.968052 15.0966 1.93688 15.5631 2.49426C16.1484 3.19335 16.1422 4.07837 15.5631 4.77785C14.5839 5.96041 13.1029 7.05649 12.0461 8.20209L12.0465 8.20171ZM12.2572 1.03396C12.1435 1.04272 11.9914 1.11244 11.8971 1.17873C11.5144 1.44732 11.1364 2.00355 10.7525 2.30224L13.6765 5.13787C14.091 4.59726 15.3764 3.97665 14.7694 3.19678C14.2393 2.51559 13.2993 1.87897 12.7319 1.19664C12.6112 1.0972 12.416 1.02139 12.2568 1.03396H12.2572ZM3.89279 11.8744C3.9382 11.9216 4.10004 11.9635 4.17145 11.962C4.89643 11.9464 5.93228 11.858 6.65687 11.7692C6.78689 11.7532 6.92699 11.7174 7.03916 11.6492L12.8693 5.94022L9.99496 3.04591L4.13652 8.79985C4.00651 8.99529 3.98516 9.58505 3.96032 9.84602C3.9153 10.323 3.85631 10.8968 3.84195 11.368C3.83846 11.4842 3.82022 11.7989 3.8924 11.8744H3.89279Z" fill="currentColor"/><path d="M2.04958 2.33194C3.16732 2.2085 4.46941 2.40014 5.60695 2.32394C6.18289 2.447 6.14176 3.26687 5.56736 3.34687C4.59787 3.48174 3.31946 3.26344 2.30922 3.34878C1.6281 3.4063 1.1127 3.92444 1.04788 4.58696V13.695C1.10687 14.4322 1.64634 14.9138 2.38684 14.9713H11.5488C13.652 14.8079 12.6526 11.8801 12.8886 10.5337C13.0523 9.99611 13.7703 9.99839 13.9326 10.5337C13.8247 12.6089 14.6599 15.6335 11.7045 16.0003H2.2316C1.06845 15.9165 0.137389 15.0174 0 13.8859L0.00620967 4.36409C0.140494 3.35906 1.00791 2.447 2.04997 2.33194H2.04958Z" fill="currentColor"/></g><defs><clipPath id="clip0_427_430"><rect width="16" height="16" fill="white"/></clipPath></defs></svg>
                        </button> -->
                    </div>
                </td>
                <td class="p-4 rowTotal"></td>
                <td class="p-2 align-top text-right">
                        <button type="button" class="remove-row text-red-500 hover:text-red-700" title="Remove Item"> <span class="text-lg">&times;</span> </button>
                    </td>
                <!-- <td class="p-4 text-right rounded-r-lg"><button>
                    <svg width="14" height="16" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.2198 2.46658L13.5732 2.48141C14.142 2.57241 14.1239 3.51406 13.6281 3.62287C13.4664 3.65814 13.1578 3.57143 13.1049 3.74156L11.7041 14.1792C11.4162 15.0615 10.6479 15.653 9.72717 15.7357C8.33059 15.861 5.74347 15.8501 4.33736 15.739C3.36304 15.6622 2.57373 15.0773 2.28587 14.1287L0.898821 3.74156L0.80254 3.64496C-0.0761549 3.87476 -0.309794 2.57241 0.488063 2.47482C0.982945 2.41415 3.62001 2.56813 3.78366 2.4669C4.1494 1.59977 4.1402 0.663395 5.11879 0.234443C5.84468 -0.083726 8.27177 -0.0863637 8.96973 0.277635C9.90232 0.763627 9.85106 1.60867 10.2194 2.4669L10.2198 2.46658ZM8.92636 2.47746C8.78341 2.05774 8.80214 1.41876 8.28689 1.2849C7.98818 1.20742 5.94721 1.21467 5.67216 1.30402C5.19601 1.45898 5.21934 2.07059 5.07738 2.47746H8.92636ZM11.9413 3.63605H2.06242L3.47148 13.9045C3.60687 14.2458 3.90985 14.4762 4.27558 14.5135C6.0057 14.4096 7.8919 14.6516 9.60263 14.5161C10.2805 14.4624 10.5135 14.1409 10.642 13.4993L11.9417 3.63605H11.9413Z" fill="#DF0000"/><path d="M5.82431 5.84744C5.9058 5.92921 5.96857 6.05846 5.9781 6.17616C5.81445 8.00275 6.18709 10.19 5.97678 11.9731C5.89627 12.6556 4.98209 12.6978 4.82436 12.0325L4.81812 6.17649C4.86741 5.69479 5.5003 5.52334 5.82464 5.84777L5.82431 5.84744Z" fill="#DF0000"/><path d="M9.03183 5.84744C9.11332 5.92921 9.17609 6.05846 9.18562 6.17616C9.02197 8.00275 9.39461 10.19 9.1843 11.9731C9.10379 12.6556 8.18961 12.6978 8.03188 12.0325L8.02563 6.17649C8.07493 5.69479 8.70782 5.52334 9.03216 5.84777L9.03183 5.84744Z" fill="#DF0000"/></svg>
                </button></td> -->
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Item Button and Totals -->
    <div class="mt-4 flex justify-between items-start">
        <!-- Add Item Button -->
        <div>
            <button type="button" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Add Item</button>
        </div>
        <!-- Totals Section -->
        <div class="w-1/3">
            <div class="bg-[rgba(245,245,245,1)] p-4 rounded-lg">
                <div class="space-y-1">
                    <div class="flex justify-between subtotal-text">
                        <span>Subtotal :</span>
                        <span id="subtotal_view"></span>
                    </div>
                    <div class="flex justify-between subtotal-text">
                        <span>Shipping :</span> 
                        <input type="text" name="shipping_cost" id="shipping_cost" class="w-[100px] h-[25px] text-right border rounded-md focus:ring-0 form-input" value="0" oninput="calculateTotals()" required>
                    </div>
                    <div class="flex justify-between subtotal-text">
                        <span>GST :</span>
                        <span id="total_gst_view"></span>
                    </div>
                </div>
                <div class="mt-1 border-t border-gray-300 pt-1">
                    <div class="flex justify-between final-total-text">
                        <span>Grand Total :</span>
                        <span id="grand_total_view"></span>
                    </div>
                </div>
                <input type="hidden" name="subtotal" id="subtotal" class="form-control" value="" >
                <input type="hidden" name="total_gst" id="total_gst" class="form-control" value="" >
                <input type="hidden" name="grand_total" id="grandTotal" class="form-control" value="" >
            </div>
        </div>
    </div>

    <hr class="my-8 border-gray-200">

    <!-- Notes and Terms -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <div class="flex justify-between items-center mb-1" style="height: 37px;">
                <label for="notes" class="block text-sm font-medium text-gray-700 notes-label">Add Note:</label>
            </div>
            <textarea id="notes" name="notes" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" placeholder="Important note to remember" style="min-height: 148px;"></textarea>
        </div>
        <div>
            <div class="flex justify-between items-center mb-1">
                <label for="terms" class="block text-sm font-medium text-gray-700 notes-label">Terms & Conditions:</label>
                <button type="button" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Load Template</button>
            </div>
            <textarea id="terms" name="terms" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" placeholder="Important terms & conditions to remember" style="min-height: 148px;"></textarea>
        </div>  
    </div>

    <!-- Action Buttons -->
    <div class="mt-8 flex justify-end space-x-4">
        <button type="submit" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Create</button>
        <button type="button" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Preview</button>
        <button type="button" class="bg-black text-white font-semibold py-2 px-4 rounded-md action-button">Cancel</button>
    </div>
    </form>
</div>
<!-- Order Item Modal -->
<div id="orderModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl p-6 relative">
        <button type="button" class="absolute top-2 right-3 text-2xl font-bold text-gray-500 hover:text-black" id="closeOrderModal">&times;</button>
        <h2 class="text-xl font-bold mb-4">Select Order Item</h2>
        <input type="text" id="orderSearch" class="border p-2 w-full mb-4" placeholder="Search with order id, item code, or title...">
        <div class="max-h-72 overflow-y-auto">
            <table class="w-full border">
                <thead>
                    <tr>
                        <th class="p-2 text-left">Title</th>
                        <th class="p-2 text-left">Order ID</th>
                        <th class="p-2 text-left">Order Date</th>
                        <th class="p-2 text-left">Image</th>
                        <th class="p-2 text-left">Action</th>
                    </tr>
                </thead>
                <tbody id="orderList">
                    <!-- Dynamic rows here -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function calculateTotals() {
    let subtotal = 0;
    let totalGST = 0;
    let grandTotal = 0;
    

    document.querySelectorAll("#poTable tbody tr").forEach(tr => {
        const amount = parseFloat(tr.querySelector(".amount").value) || 0;
        const gstPercent = parseFloat(tr.querySelector(".gst").value) || 0;
        const quantity = parseFloat(tr.querySelector(".quantity").value) || 0;        

        const lineSubtotal = amount * quantity;
        const gstAmount = (lineSubtotal * gstPercent) / 100;
        const rowTotal = lineSubtotal + gstAmount;

        tr.querySelector(".rowTotal").innerText = rowTotal.toFixed(2);

        subtotal += lineSubtotal;
        totalGST += gstAmount;
        grandTotal += rowTotal;
    });

    const shipping_cost = parseFloat(document.getElementById("shipping_cost").value) || 0;
    grandTotal += shipping_cost;

    document.getElementById("subtotal").value = subtotal.toFixed(2);
    document.getElementById("total_gst").value = totalGST.toFixed(2);
    document.getElementById("grandTotal").value = grandTotal.toFixed(2);
    document.getElementById("subtotal_view").innerText = "₹" + subtotal.toFixed(2);
    document.getElementById("total_gst_view").innerText = "₹" + totalGST.toFixed(2);
    document.getElementById("grand_total_view").innerText = "₹" + grandTotal.toFixed(2);
}
document.querySelectorAll(".gst, .quantity, .amount").forEach(input => {
    input.addEventListener("input", calculateTotals);
});
document.addEventListener('DOMContentLoaded', function () {
    const itemTable = document.querySelector('#poTable tbody');
    itemTable.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row')) {
                const row = e.target.closest('tr');
                if (row) {
                    row.remove();
                    calculateTotals();
                }
            }
        });
    });

// function openPOPopup() {
//     // Show modal
//     new bootstrap.Modal(document.getElementById("createPOModal")).show();
// }
document.getElementById("create_po").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent default form submission
    // Disable the button and change text
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Processing...';
    const formData = new FormData(this);
    
    fetch(<?php echo "'".base_url('?page=purchase_orders&action=create_post')."'"; ?>, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())  
    .then(data => {
        if (data.success) {
            alert("Purchase Order created successfully!");
            window.location.href = "<?php echo base_url('?page=purchase_orders&acton=list'); ?>"; // Redirect to the list page
        } else {
            alert("Error: " + data.message);
            // Re-enable the button and restore text
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("An error occurred while creating the Purchase Order.");
    });
});

// add item
// Show modal and fetch order items
document.querySelector('.action-button').addEventListener('click', function(e) {
    if (e.target.textContent.trim() === 'Add Item') {
        document.getElementById('orderModal').style.display = 'flex';
        document.getElementById('orderSearch').value = '';
        fetchOrderItems('');
    }
});

// Close modal
document.getElementById('closeOrderModal').onclick = function() {
    document.getElementById('orderModal').style.display = 'none';
};

// Search filter (fetches filtered items)
document.getElementById('orderSearch').addEventListener('input', function() {
    if (this.value.length < 3 && this.value.length > 0) return; // Minimum 3 characters to search
    fetchOrderItems(this.value);
});

// Fetch order items dynamically
function fetchOrderItems(query) {
    fetch('?page=purchase_orders&action=order_items&search=' + encodeURIComponent(query))
        .then(r => r.json())
        .then(data => {
            console.log(data);
            const tbody = document.getElementById('orderList');
            tbody.innerHTML = '';
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(item => {
                    console.log(item);
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="p-2">${item.title}</td>
                        <td class="p-2">${item.order_number}</td>
                        <td class="p-2">${item.order_date}</td>
                        <td class="p-2"><img src="${item.image}" alt="" class="w-10 h-10 rounded"></td>
                        <td class="p-2">
                            <button type="button" title="Select" class="select-order bg-blue-500 text-white px-3 py-1 rounded"
                                data-id="${item.id}"
                                data-title="${item.title.replace(/"/g, '&quot;')}"
                                data-hsn="${item.item_code.replace(/"/g, '&quot;')}"
                                data-image="${item.image.replace(/"/g, '&quot;')}"
                                >+</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                    //alert('Item added to the list.');
                });
            } else {
                //alert('No items found.');
                tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-gray-500">No items found.</td></tr>';
            }
            addSelectOrderListeners();
        });
}

// Insert selected order into poTable
function addSelectOrderListeners() {
    document.querySelectorAll('.select-order').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            const hsn = this.getAttribute('data-hsn');
            const image = this.getAttribute('data-image');
            const poTable = document.querySelector('#poTable tbody');
            const rowCount = poTable.querySelectorAll('tr').length + 1;

            // Prevent duplicate items
            let exists = false;
            poTable.querySelectorAll('input[name="orderid[]"]').forEach(function(input) {
                if (input.value == id) exists = true;
            });
            if (exists) {
                alert('This item is already added.');
                return;
            }

            const tr = document.createElement('tr');
            tr.className = 'bg-white';
            tr.innerHTML = `
                <td class="p-4 rounded-l-lg"><input type="hidden" name="orderid[]" value="${id}">${rowCount}</td>
                <td class="p-4"><input type="hidden" name="title[]" value="${title}">${title}</td>
                <td class="p-4"><input type="hidden" name="hsn[]" value="${hsn}">${hsn}</td>
                <td class="p-4"><input type="hidden" name="img[]" value="${image}"><img src="${image}" class="rounded-lg" style="width:40px;height:40px;"></td>
                <td class="p-4"><input type="number" name="gst[]" class="gst w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="18" oninput="calculateTotals()" required></td>
                <td class="p-4">
                    <div class="flex items-center space-x-2">
                        <input type="number" name="quantity[]" class="quantity w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="1" oninput="calculateTotals()" required>
                    </div>
                </td>
                <td class="p-4">Nos</td>
                <td class="p-4">
                    <div class="flex items-center space-x-2">
                        <input type="number" name="rate[]" value="" oninput="calculateTotals()" required class="amount w-[105px] h-[25px] text-center border rounded-md focus:ring-0 form-input">
                        <input type="checkbox" name="gst_inclusive[]" class="gst_inclusive" value="1" onchange="calculateTotals()">
                        <label>GST inclusive</label>
                    </div>
                </td>
                <td class="p-4 rowTotal"></td>
                <td class="p-2 align-top text-right">
                    <button type="button" class="remove-row text-red-500 hover:text-red-700" title="Remove Item"><span class="text-lg">&times;</span></button>
                </td>
            `;
            poTable.appendChild(tr);

            // Add event listeners for new inputs
            tr.querySelectorAll('.gst, .quantity, .amount').forEach(input => {
                input.addEventListener('input', calculateTotals);
            });
            tr.querySelector('.remove-row').addEventListener('click', function() {
                tr.remove();
                calculateTotals();
            });

            calculateTotals();
            document.getElementById('orderModal').style.display = 'none';
        });
    });
}
</script>