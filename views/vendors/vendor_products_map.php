<!-- Custom Color Configuration -->
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'brand-orange': 'rgba(208, 103, 6, 1)',
                    'brand-purple': '#A855F7',
                    'brand-red': 'rgba(204, 0, 0, 1)',
                    'brand-gray': '#E5E5EB',
                },
                boxShadow: {
                    'card': '0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24)',
                }
            }
        }
    }
</script>

<style>
    /* Placeholder pattern for product images */
    .product-placeholder {
        background-color: #f3f4f6;
        background-image: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%239C92AC' fill-opacity='0.1' fill-rule='evenodd'%3E%3Cpath d='M0 40L40 0H20L0 20M40 40V20L20 40'/%3E%3C/g%3E%3C/svg%3E");
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
    }
</style>
<!-- Main Container -->
<div class="max-w-7xl mx-auto bg-white shadow-lg rounded-sm overflow-hidden min-h-[80vh] flex flex-col">

    <!-- Header Section -->
    <div class="px-6 py-5 md:px-8">
        <div class="flex items-center gap-4">
            <!-- Circular Icon -->
            <div class="w-12 h-12 md:w-14 md:h-14 bg-brand-orange rounded-full flex items-center justify-center shadow-sm">
                <i class="fas fa-link text-white text-xl md:text-2xl transform -rotate-45"></i>
            </div>
            <!-- Title -->
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Vendor ⇌ Product Mapping</h1>
        </div>
    </div>

    <!-- Purple Separator Line -->
    <div class="h-1 bg-brand-purple w-full"></div>
    <form id="vendorProductsForm" method="POST" action="?page=vendors&action=saveProductsMap">
        <input type="hidden" name="vendor_id" value="<?php echo htmlspecialchars($vendor['id']); ?>">
        <!-- Content Area -->
        <div class="p-6 md:p-8 flex-grow">

            <!-- Vendor Info Header -->
            <div class="mb-8">
                <h2 class="text-lg md:text-xl font-bold text-black break-words">
                    <?php echo $vendor["vendor_code"] . " - " . $vendor["vendor_name"] . " - " . $vendor["city"] . " - " . $vendor["state"] . " - " . $vendor["vendor_phone"]; ?>
                </h2>
            </div>

            <!-- Product Grid -->
            <!-- Responsive: 1 col on mobile, 2 on tablet, 3 on desktop -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-10" id="productGrid">

                <!-- Add New Product Card -->
                <div class="border border-gray-300 bg-gray-50 rounded-md p-6 flex flex-col justify-center h-full min-h-[150px]" id="addProductBox">
                    <label for="productId" class="font-bold text-gray-800 mb-2">Product:</label>
                    <div class="flex w-full">
                        <input type="text" id="productId" value="" placeholder="Enter Item Code"
                            class="border border-gray-400 rounded-l px-3 py-2 w-full text-gray-700 focus:outline-none focus:border-gray-600 focus:ring-1 focus:ring-gray-600">
                            
                        <button onclick="addProduct(event); return false;"
                                class="bg-black text-white px-5 py-2 rounded-r font-medium hover:bg-gray-800 transition-colors whitespace-nowrap">
                            + Add
                        </button>
                    </div>
                    <p id="errMsg" style="color: red;"></p>
                </div>

            </div>
        </div>

        <!-- Footer Actions -->
        <div class="border-t border-gray-200 p-6 md:px-8 flex justify-end items-center gap-4 bg-white mt-auto">
            <button class="px-6 py-2.5 bg-gray-500 text-white text-base md:text-lg font-bold rounded hover:bg-gray-600 transition-colors shadow-sm">
                Cancel
            </button>
            <button class="px-6 py-2.5 bg-brand-orange text-white text-base md:text-lg font-bold rounded hover:bg-orange-600 transition-colors shadow-sm">
                Save Mapping
            </button>
        </div>
    </form>
</div>
<script>
    // Interaction Logic
    function addProduct(e) {
        e.preventDefault();
        document.getElementById('errMsg').innerHTML = "";

        let code = document.getElementById('productId').value;
        if (!code) { document.getElementById('errMsg').innerHTML = "Enter item code"; return false; }

        fetch("?page=vendors&action=generateBlock", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "vendor_id=<?php echo $vendor['id']; ?>&item_code=" + code
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('errMsg').innerHTML = "";

            if (data.success === true) {
                // ✅ HTML string → use insertAdjacentHTML
                document
                    .getElementById('addProductBox')
                    .insertAdjacentHTML('beforebegin', data.html);

                document.getElementById('productId').value = '';
            } else {
                document.getElementById('errMsg').innerHTML = data.message;
            }
        });
    }

    function deleteCard(button) {
        const card = button.closest('.product-card');
        if (card) {
            // Confirm dialog (optional, mimicking real app behavior)
            // if(confirm("Are you sure you want to remove this mapping?")) {
            card.style.transition = "all 0.3s ease";
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            setTimeout(() => {
                card.remove();
            }, 300);
            // }
        }
    }
</script>