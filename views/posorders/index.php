<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Roboto:wght@400&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f9fafb;
    }

    .product-title {
        font-weight: 600;
        font-size: 13px;
        color: rgba(58, 58, 73, 1);
    }

    .item-code,
    .quantity {
        font-weight: 400;
        font-size: 13px;
        color: rgba(58, 58, 73, 1);
    }

    .heading-typography {
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        font-size: 13px;
        line-height: 1;
        letter-spacing: -0.02em;
        color: rgba(121, 122, 124, 1);
    }

    .data-typography {
        font-family: 'Roboto', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 18px;
        letter-spacing: 0.2px;
        color: rgba(0, 0, 0, 1);
    }

    .custom-checkbox:checked {
        background-color: rgba(208, 103, 6, 1);
        border-color: rgba(208, 103, 6, 1);
    }

    .custom-checkbox {
        appearance: none;
        -webkit-appearance: none;
        height: 14px;
        width: 14px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        cursor: pointer;
        display: inline-block;
        position: relative;
    }

    .custom-checkbox:checked::after {
        content: '✓';
        font-size: 10px;
        color: white;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .set-priority-title {
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        font-size: 13px;
        color: rgba(121, 122, 124, 1);
    }

    .urgent-text {
        font-family: 'Roboto', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 18px;
        letter-spacing: 0.2px;
        color: rgba(255, 0, 4, 1);
    }

    .download-invoice {
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        font-size: 13px;
        color: rgba(9, 9, 9, 1);
    }

    .note-heading {
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        font-size: 13px;
        line-height: 1;
        letter-spacing: -0.02em;
        color: rgba(121, 122, 124, 1);
    }

    .note-content {
        font-family: 'Inter', sans-serif;
        font-weight: 400;
        font-size: 12px;
        letter-spacing: 0.2px;
        color: rgba(121, 122, 124, 1);
    }

    .pending-text {
        font-family: 'Roboto', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 18px;
        letter-spacing: 0.2px;
        color: rgba(7, 7, 7, 1);
    }

    .date-field {
        position: relative;
        display: inline-block;
    }

    .date-field input {
        padding-right: 35px;
        /* space for icon */
        width: 375px;
        padding: 8px;
    }

    .date-field i {
        position: absolute;
        right: 10px;
        top: 70%;
        transform: translateY(-50%);
        color: #d06706;
        pointer-events: none;
        /* icon won't block clicks */
    }
</style>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<div class="container mx-auto pr-4 pt-2">
    <?php

    $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
    $page = $page < 1 ? 1 : $page;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Orders per page, default 50
    $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 50; // Only allow specific values
    $total_orders = isset($data['total_orders']) ? (int)$data['total_orders'] : 0;
    $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;

    // Prepare query string for pagination links
    $search_params = $_GET;
    unset($search_params['page_no'], $search_params['limit'], $search_params['sort']);
    $query_string = http_build_query($search_params);
    $query_string = $query_string ? '&' . $query_string : '';

    // Calculate start/end slot for 10 pages
    $slot_size = 10;
    $start = max(1, $page - floor($slot_size / 2));
    $end = min($total_pages, $start + $slot_size - 1);
    if ($end - $start < $slot_size - 1) {
        $start = max(1, $end - $slot_size + 1);
    }
    ?>
    <!-- Header Section -->
    <!-- Stats Section -->
   
    <?php
    // if (isset($_GET['order_from']) && !empty($_GET['order_from'])) {
    //     $from_date = $_GET['order_from'];
    // } else {
    //     $from_date = date('Y-m-d');
    // }
    // if (isset($_GET['order_till']) && !empty($_GET['order_till'])) {
    //     $till_date = $_GET['order_till'];
    // } else {
    //     $till_date = date('Y-m-d');
    // }
    $dtrange = '';
    if (empty($_GET['order_till'])) {
        $dtrange = htmlspecialchars($_GET['order_from'] ?? '');
    } else {
        $dtrange = htmlspecialchars($_GET['order_from'] ?? '') . ' - ' . htmlspecialchars($_GET['order_till'] ?? '');
    }

    ?>
    <!-- Advance Search Accordion -->
    <div class="mt-2 mb-8 bg-white rounded-xl p-4 ">
        <button id="accordion-button-search" class="w-full flex justify-between items-center mb-2">
            <h2 class="text-xl font-bold text-gray-900">Advance Search</h2>
            <svg id="accordion-icon-search" class="w-6 h-6 transition-transform transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div id="accordion-content-search" class="accordion-content  overflow-visible hidden ">
            <!-- Responsive Grid container -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6 items-end">
                <form method="GET" class="contents">
                    <!-- Orders From/Till -->
                    <div class="col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-2 flex items-end gap-0">

                        <div class="w-full date-field">
                            <label for="order-from" class="block text-sm font-medium text-gray-600 mb-1">Order Date Range</label>
                            <input type="text" autocomplete="off" value="<?= $dtrange ?>" name="daterange" id="daterange" placeholder="Select date range" class="w-full px-2 py-2 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500" />
                            <i class="fa fa-calendar"></i>
                            <input type="hidden" name="order_from" value="<?= htmlspecialchars($_GET['order_from'] ?? '') ?>" id="from_date">
                            <input type="hidden" name="order_till" value="<?= htmlspecialchars($_GET['order_till'] ?? '') ?>" id="to_date">

                        </div>
                        <script>
                            $(function() {
                                // Initialize date range picker: display format 'DD MMM YYYY' (e.g., 25 Dec 2015)
                                $('#daterange').daterangepicker({
                                    autoUpdateInput: false, // keep field blank until Apply
                                    showDropdowns: true, // optional: month/year dropdowns
                                    locale: {
                                        format: 'DD MMM YYYY'
                                    },
                                    alwaysShowCalendars: true, // ensures only calendars show
                                    drops: 'down', // position of calendar
                                    opens: 'right', // alignment
                                    autoApply: false
                                }, function(start, end) {
                                    // Update hidden fields whenever a range is selected (ISO for server)
                                    $('#from_date').val(start.format('YYYY-MM-DD'));
                                    $('#to_date').val(end.format('YYYY-MM-DD'));
                                });

                                // When user selects a range, update the input using 'DD MMM YYYY' format
                                $('#daterange').on('apply.daterangepicker', function(ev, picker) {
                                    $(this).val(picker.startDate.format('DD MMM YYYY') + ' - ' + picker.endDate.format('DD MMM YYYY'));
                                    // ensure hidden fields are updated too (in case callback didn't run)
                                    $('#from_date').val(picker.startDate.format('YYYY-MM-DD'));
                                    $('#to_date').val(picker.endDate.format('YYYY-MM-DD'));
                                });

                                // If user clears, reset to placeholder and clear hidden fields
                                $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
                                    $(this).val('');
                                    $('#from_date').val('');
                                    $('#to_date').val('');
                                });

                                // If page already has from/to values (e.g., after a search), format and show them
                                var existingFrom = $('#from_date').val();
                                var existingTo = $('#to_date').val();
                                if (existingFrom && existingTo) {
                                    try {
                                        var fromFormatted = moment(existingFrom, 'YYYY-MM-DD').format('DD MMM YYYY');
                                        var toFormatted = moment(existingTo, 'YYYY-MM-DD').format('DD MMM YYYY');
                                        $('#daterange').val(fromFormatted + ' - ' + toFormatted);
                                    } catch (e) {
                                        // ignore formatting errors
                                    }
                                }

                            });
                        </script>
                        <!-- <div class="w-1/2">
                        <label for="order-from" class="block text-sm font-medium text-gray-600 mb-1">Order From</label>
                        <input type="date" value="<?= htmlspecialchars($_GET['order_from'] ?? '') ?>" name="order_from" id="order-from" class="w-full px-2 py-2 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <span class="text-gray-500 pb-2">→</span>
                    <div class="w-1/2">
                        <label for="order-till" class="block text-sm font-medium text-gray-600 mb-1">Order To</label>
                        <input type="date" value="<?= htmlspecialchars($_GET['order_till'] ?? '') ?>" name="order_till" id="order-till" class="w-full px-2 py-2 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div> -->
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                        <select id="status" name="status[]" multiple="multiple" class="max-w-48 px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white advanced-multiselect">

                            <!-- <option value="all" disabled >Select Status</option> -->
                            <?php foreach ($status_list as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($_GET['status']) && $_GET['status'] === $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="payment_type" class="block text-sm font-medium text-gray-600 mb-1">Payment Type</label>
                        <select id="payment_type" name="payment_type[]" multiple="multiple" class="advanced-multiselect max-w-48 px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">

                            <!-- <option value="all" disabled >Select</option> -->
                            <?php foreach ($payment_types as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($_GET['payment_type']) && $_GET['payment_type'] === $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="">
                        <label for="category" class="block text-sm font-medium text-gray-600 mb-1">Category</label>
                        <select id="category" name="category[]" multiple="multiple" class="advanced-multiselect px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">

                            <!-- <option value="all" selected >All Categories</option> -->
                            <?php foreach (getCategories() as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] === $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- PO Date -->
                    <!-- <div>
                    <label for="order-date" class="block text-sm font-medium text-gray-600 mb-1">Order Date</label>
                    <input type="text" name="order_date" id="order-date" placeholder="Order Date" onfocus="(this.type='date')" onblur="(this.type='text')" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div> -->

                    <!-- Receipt Date -->
                    <!-- <div>
                    <label for="receipt-date" class="block text-sm font-medium text-gray-600 mb-1">Receipt Date</label>
                    <input type="text" name="receipt_date" id="receipt-date" placeholder="Receipt Date" onfocus="(this.type='date')" onblur="(this.type='text')" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div> -->

                    <!-- Order Number -->
                    <div>
                        <label for="order-number" class="block text-sm font-medium text-gray-600 mb-1">Order No</label>
                        <input type="text" value="<?= htmlspecialchars($_GET['order_number'] ?? '') ?>" name="order_number" id="order-number" placeholder="Order Number" class="w-full px-2 py-2 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-600 mb-1">Priority</label>
                        <select id="priority" name="priority" class="w-full px-3 py-2 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">
                            <option value="" selected>-Select-</option>
                            <option value="critical" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'critical') ? 'selected' : ''; ?>>Critical</option>
                            <option value="urgent" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                            <option value="high" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'high') ? 'selected' : ''; ?>>High</option>
                            <option value="medium" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="low" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'low') ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>
                    <div class="">
                        <label for="country" class="block text-sm font-medium text-gray-600 mb-1">Country</label>
                        <select id="country" name="country" class="w-full px-3 py-2 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">
                            <option value="" selected>All Country</option>
                            <optgroup label="Easy">
                                <option value="overseas">Overseas</option>
                                <option value="IN">India</option>
                            </optgroup>
                            <?php foreach ($country_list as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($_GET['country']) && $_GET['country'] === $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="">
                        <label for="staff_name" class="block text-sm font-medium text-gray-600 mb-1">PO Issued By</label>
                        <select id="staff_name" name="staff_name[]" multiple class="advanced-multiselect w-48 px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">
                            <!-- <option value="" >All Staff</option> -->
                            <?php foreach ($staff_list as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($_GET['staff_name']) && is_array($_GET['staff_name']) && in_array($key, $_GET['staff_name'])) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="relative">
                        <label for="vendor_autocomplete" class="block text-sm font-medium text-gray-600 mb-1">Vendor</label>
                        <input
                            type="text"
                            id="vendor_autocomplete"
                            class="w-full px-2 py-2 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500"
                            placeholder="Search vendor by name..."
                            autocomplete="off"
                            value="<?php echo isset($_GET['vendor_name']) ? htmlspecialchars($_GET['vendor_name']) : ''; ?>">
                        <input type="hidden" name="vendor_id" id="vendor_id" value="<?php echo isset($_GET['vendor_id']) ? htmlspecialchars($_GET['vendor_id']) : ''; ?>">
                        <div id="vendor_suggestions" class="absolute left-0 right-0 mt-1 z-50 bg-white border rounded-md shadow-lg max-h-48 overflow-auto " style="display:none; top:100%;"></div>
                    </div>
                    <!-- Min/Max Amount -->
                    <!-- <div class="col-span-1 sm:col-span-2 md:col-span-1 lg:col-span-2 flex items-end gap-2">
                    <div class="w-1/2">
                        <label for="min-amount" class="block text-sm font-medium text-gray-600 mb-1">Min Amount</label>
                        <input type="number" value="<?= htmlspecialchars($_GET['min_amount'] ?? '') ?>" name="min_amount" id="min-amount" placeholder="Min Amount" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <div class="w-1/2">
                        <label for="max-amount" class="block text-sm font-medium text-gray-600 mb-1">Max Amount</label>
                        <input type="number" value="<?= htmlspecialchars($_GET['max_amount'] ?? '') ?>" name="max_amount" id="max-amount" placeholder="Max Amount" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div> -->

                    <!-- Item Code -->
                    <div>
                        <label for="item-code" class="block text-sm font-medium text-gray-600 mb-1">Item Code</label>
                        <input type="text" value="<?= htmlspecialchars($_GET['item_code'] ?? '') ?>" name="item_code" id="item-code" placeholder="Item Code" class="w-full px-2 py-2 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <!-- PO No -->
                    <div>
                        <label for="po-no" class="block text-sm font-medium text-gray-600 mb-1">PO No</label>
                        <input type="text" value="<?= htmlspecialchars($_GET['po_no'] ?? '') ?>" name="po_no" id="po-no" placeholder="PO No" class="w-full px-2 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-xs">
                    </div>

                    <!-- Item Name -->
                    <div>
                        <label for="item-name" class="block text-sm font-medium text-gray-600 mb-1">Item Name</label>
                        <input type="text" value="<?= htmlspecialchars($_GET['item_name'] ?? '') ?>" name="item_name" id="item-name" placeholder="Item Name" class="w-full px-2 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-xs">
                    </div>
                    <div>
                        <label for="agent" class="block text-sm font-medium text-gray-600 mb-1">Agent</label>
                        <select id="agent" multiple name="agent[]" class="advanced-multiselect w-full px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">
                            <!-- <option value="" selected>-Select-</option> -->
                            <?php foreach ($staff_list as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($_GET['agent']) && is_array($_GET['agent']) && in_array($key, $_GET['agent'])) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Author filter -->
                    <div>
                        <label for="author" class="block text-sm font-medium text-gray-600 mb-1">Author</label>
                        <input type="text" value="<?= htmlspecialchars($_GET['author'] ?? '') ?>" name="author" id="author" placeholder="Author Name" class="w-full px-2 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-xs">
                    </div>
                    <!-- Publisher filter -->
                    <div>
                        <label for="publisher" class="block text-sm font-medium text-gray-600 mb-1">Publisher</label>
                        <input type="text" value="<?= htmlspecialchars($_GET['publisher'] ?? '') ?>" name="publisher" id="publisher" placeholder="Publisher Name" class="w-full px-2 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-xs">
                    </div>

                    <!-- Buttons -->
                    <div class="col-span-1 sm:col-span-1 md:col-span-1 flex items-center gap-2">
                        <button type="button" onclick="cancelSearch()" class="w-full bg-gray-600 text-white font-semibold py-2 px-2 rounded-md shadow-sm hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700 transition duration-150">Clear</button>
                        <!-- <button type="button" id="clear-button" onclick="clearFilters()" class="w-full bg-gray-800 text-white font-semibold py-2 px-2 rounded-md shadow-sm hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700 transition duration-150">Clear</button> -->
                        <button type="submit" class="w-full bg-amber-600 text-white font-semibold py-2 px-2 rounded-md shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition duration-150">Search</button>
                    </div>
                </form>

                <!-- Save Search Controls -->
                <div class="mt-4">
                    <?php if (!empty($query_string) && trim($query_string, '&') != ''): ?>
                        <a href="javascript:void(0);" id="saveSearchBtn" onclick="saveCurrentSearch()" class=""><img src="<?php echo base_url('images/save_search.jpeg'); ?>" alt="Save" class="ml-1 w-10 h-10"></a>
                        <!-- <button id="saveSearchBtn" onclick="saveCurrentSearch()" class="bg-green-600 text-white font-semibold py-2 px-3 rounded-md shadow-sm hover:bg-green-700 transition">Save Search</button> -->
                    <?php else: ?>
                        <!-- <button id="saveSearchBtn" onclick="saveCurrentSearch()" class="bg-green-400 text-white font-semibold py-2 px-3 rounded-md shadow-sm" disabled>Save Search</button> -->
                        <img src="<?php echo base_url('images/save_search.jpeg'); ?>" alt="Save" class="ml-1 w-10 h-10">
                    <?php endif; ?>
                </div>

                <div id="saved-searches" class="mt-4 w-7xl">
                    <?php if (!empty($saved_searches)): ?>
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-sm font-semibold mb-2">Saved Searches</h3>
                            <div class="ml-2 hover:text-orange-700 cursor-pointer" onclick="openSearchSetting();"> <i class="fas fa-cog"></i></div>
                        </div>
                        <div class="text-sm flex">
                            <?php foreach ($saved_searches as $s): ?>
                                <div id="saved-search-<?= $s['id'] ?>" class="items-center gap-2 bg-gray-200 px-4 rounded-md mr-4 min-w-max py-1 hover:bg-orange-300">
                                    <a class="" href="<?= base_url('?page=posorders&action=list') . '&' . htmlspecialchars($s['query']) ?>"><?= htmlspecialchars($s['name']) ?></a>
                                    <!-- <button onclick="deleteSavedSearch(<?= $s['id'] ?>)" class="text-red-600 hover:underline text-xs">Delete</button> -->
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>
                </div>

                <!-- clear filter -->
                <script>
                    // function clearFilters() {
                    //     const url = new URL(window.location.href);
                    //     //alert(url.search);
                    //     url.search = ''; // Clear all query parameters
                    //     const page = 'page=posorders&action=list';
                    //     window.location.href = url.toString() + '?' + page; // Redirect to the updated URL
                    // }
                    function cancelSearch() {
                        const url = new URL(window.location.href);
                        url.search = ''; // Clear all query parameters
                        const page = 'page=posorders&action=list';
                        window.location.href = url.toString() + '?' + page; // Redirect to the updated URL
                    }

                    function saveCurrentSearch() {
                        const currentQuery = '<?= trim($query_string, '&') ?>';
                        if (!currentQuery) {
                            alert('No filters to save.');
                            return;
                        }
                        let defaultName = 'Search - ' + new Date().toLocaleString();
                        let name = prompt('Enter a name for this search:', defaultName);
                        if (name === null) return; // cancelled
                        const formData = new URLSearchParams();
                        formData.append('name', name.trim());
                        formData.append('query', currentQuery);
                        fetch('<?= base_url('?page=posorders&action=saveSearch') ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: formData.toString()
                        }).then(r => r.json()).then(data => {
                            if (data.success) {
                                // add to list
                                const s = data.search;
                                const ul = document.querySelector('#saved-searches ul') || (() => {
                                    const div = document.getElementById('saved-searches');
                                    div.innerHTML = '<h3 class="text-sm font-semibold mb-2">Saved Searches</h3><ul class="space-y-2"></ul>';
                                    return document.querySelector('#saved-searches ul');
                                })();
                                const li = document.createElement('li');
                                li.id = 'saved-search-' + s.id;
                                li.className = 'flex items-center gap-2';
                                li.innerHTML = '<a class="text-indigo-600 hover:underline" href="<?= base_url('?page=posorders&action=list') ?>&' + encodeURI(s.query) + '">' + escapeHtml(s.name) + '</a> <button onclick="deleteSavedSearch(' + s.id + ')" class="text-red-600 hover:underline text-xs">Delete</button>';
                                ul.insertBefore(li, ul.firstChild);
                                alert('Search saved.');
                            } else {
                                alert('Unable to save search: ' + (data.message || ''));
                            }
                        }).catch(err => {
                            alert('Network error');
                        });
                    }

                    function deleteSavedSearch(id) {
                        if (!confirm('Delete this saved search?')) return;
                        const formData = new URLSearchParams();
                        formData.append('id', id);
                        fetch('<?= base_url('?page=posorders&action=deleteSearch') ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: formData.toString()
                        }).then(r => r.json()).then(data => {
                            if (data.success) {
                                const el = document.getElementById('saved-search-' + id);
                                if (el) el.remove();
                            } else {
                                alert('Unable to delete: ' + (data.message || ''));
                            }
                        }).catch(err => {
                            alert('Network error');
                        });
                    }

                    function escapeHtml(text) {
                        var map = {
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#039;'
                        };
                        return text.replace(/[&<>\"]/g, function(m) {
                            return map[m];
                        });
                    }
                </script>
            </div>
        </div>
    </div>

    <!-- End of Advance Search Accordion -->

    <!-- Orders Table Section -->
    <div class="mt-5">
        <form action="<?php echo base_url('?page=purchase_orders&action=create'); ?>" method="post" id="orders-form">
            <div class="flex  ">
            <div class="w-1/2">
            <!-- <button type="submit" onclick="checkPoItmes()" class="btn btn-success">Create PO</button> -->
             <!-- Actions dropdown for bulk operations -->
            <div class="relative inline-block text-left">
                <button id="bulk-action-toggle" type="button" class="btn btn-success inline-flex items-center px-4 py-2" aria-haspopup="true" aria-expanded="false">
                    Actions
                    <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="bulk-action-menu" class="hidden absolute left-0 mt-2 w-48 bg-white border rounded shadow z-50">
                    <a href="#" id="action-create-po" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Create PO</a>
                    <a href="#" id="action-update-status" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Update Status</a>
                    <a href="#" id="action-assign-to" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Assign To</a>
                    <a href="javascript:void(0)" id="action-add-to-purchase-list" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Add to purchase list</a>
                    <a href="javascript:void(0)" id="action-add-to-invoice" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Add to Invoice</a>
                </div>
            </div>
            </div>
            <div class="ml-auto flex items-center space-x-4">
            <span class="text-sm bg-white border border-gray-300 rounded-md px-2 py-1 cursor-pointer" onclick="clearSelectedOrders()" title="Clear selected orders">Clear All </span>
            <select id="sort-order" class="text-sm items-right pagination-select px-2 py-1.5 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white" onchange="location.href='?page=posorders&action=list&sort=' + this.value + '&<?= $query_string ?>';">
                    
                    <option value="desc" <?= (isset($_GET['sort']) && $_GET['sort'] === 'desc') ? 'selected' : '' ?>>Sort By New to Old</option>
                    <option value="asc" <?= (isset($_GET['sort']) && $_GET['sort'] === 'asc') ? 'selected' : '' ?>>Sort By Old to New</option>
            </select>
            <select id="rows-per-page" class="text-sm items-right pagination-select px-2 py-1.5 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white"
                onchange="location.href='?page=posorders&page_no=1&limit=' + this.value + '<?= $query_string ?>';">
                <?php foreach ([10, 20, 50, 100] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $opt === $limit ? 'selected' : '' ?>>
                        <?= $opt ?> Orders per page
                    </option>
                <?php endforeach; ?>
            </select>
            <!-- button refresh-->
            <span onclick="callImport()" title="Click to import" class="menu-button float-right text-orange-500 hover:bg-orange-200 font-semibold mr-2 cursor-pointer ">
                <!-- <img src="<?php //echo base_url('images/refresh2.jpg'); ?>" alt="Refresh" class="h-8 inline-block " /> -->
            <i class="fa-solid fa-download p-1 bg-white border border-orange-500"></i>
            </span>
            <!-- update imported orders -->
            <!-- <span onclick="callImportedUpdate()" title="Click to update" class="menu-button float-right text-blue-500 hover:bg-blue-200 font-semibold mr-2 cursor-pointer ">
                <i class="fas fa-edit p-1 bg-white border border-blue-500"></i>
            </span> -->
                </div>
            </div>
            <!-- Tabs -->
            <div class="relative border-b-[4px] border-white">
                <div id="tabsContainer" class="flex space-x-6" aria-label="Tabs">
                    <a href="<?php echo base_url('?page=posorders&action=list&status=all'); ?>" class="tab <?php echo (isset($_GET['status']) && $_GET['status'] === 'all') ? 'tab-active' : '';
                                                                                                        echo (!isset($_GET['status']) && !isset($_GET['options']) && !isset($_GET['agent'])) ? 'tab-active' : ''; ?> text-center relative py-4">
                        <span class="px-1 text-md">All Orders</span>
                        <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                    </a>
                    <a href="<?php echo base_url('?page=posorders&action=list&options=unshipped'); ?>" class="tab <?php echo (isset($_GET['options']) && $_GET['options'] === 'unshipped') ? 'tab-active' : ''; ?> text-gray-500 hover:text-gray-700 text-center relative py-4">
                        <span class="px-1 text-md">Unshipped</span>
                        <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                    </a>
                    <a href="<?php echo base_url('?page=posorders&action=list&options=express'); ?>" class="tab <?php echo (isset($_GET['options']) && $_GET['options'] === 'express') ? 'tab-active' : ''; ?> text-gray-500 hover:text-gray-700 text-center relative py-4">
                        <span class="px-1 text-md">Express Orders</span>
                        <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                    </a>
                    <a href="<?php echo base_url('?page=posorders&action=list&status=pending'); ?>" class="tab <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'tab-active' : ''; ?> text-gray-500 hover:text-gray-700 text-center relative py-4">
                        <span class="px-1 text-md">Unprocessed</span>
                        <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                    </a>
                    <a href="<?php echo base_url('?page=posorders&action=list&status=processed'); ?>" class="tab <?php echo (isset($_GET['status']) && $_GET['status'] === 'processed') ? 'tab-active' : ''; ?> text-gray-500 hover:text-gray-700 text-center relative py-4">
                        <span class="px-1 text-md">Processed</span>
                        <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                    </a>
                    <a href="<?php echo base_url('?page=posorders&action=list&status=dispatch'); ?>" class="tab <?php echo (isset($_GET['status']) && $_GET['status'] === 'dispatch') ? 'tab-active' : ''; ?> text-gray-500 hover:text-gray-700 text-center relative py-4">
                        <span class="px-1 text-md">Prep. for Dispatch</span>
                        <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                    </a>
                    <a href="<?php echo base_url('?page=posorders&action=list&agent=') . $_SESSION['user']['id']; ?>" class="tab <?php echo (isset($_GET['agent']) && $_GET['agent'] == $_SESSION['user']['id']) ? 'tab-active' : ''; ?> text-gray-500 hover:text-gray-700 text-center relative py-4">
                        <span class="px-1 text-md">My Orders</span>
                        <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                    </a>
                    <a href="<?php echo base_url('?page=posorders&action=list&status=shipped'); ?>" class="tab <?php echo (isset($_GET['status']) && $_GET['status'] === 'shipped') ? 'tab-active' : ''; ?> text-gray-500 hover:text-gray-700 text-center relative py-4">
                        <span class="px-1 text-md">Shipped</span>
                        <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                    </a>

                    <!--
                    <a href="#" class="tab text-gray-500 hover:text-gray-700 text-center relative py-4">
                        <span class="px-1 text-sm">Received</span>
                        <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                    </a>-->
                    <a href="<?php echo base_url('?page=posorders&action=list&status=cancelled'); ?>" class="tab <?php echo (isset($_GET['status']) && $_GET['status'] === 'cancelled') ? 'tab-active' : ''; ?> text-gray-500 hover:text-gray-700 text-center relative py-4">
                        <span class="px-1 text-md">Cancelled</span>
                        <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                    </a>
                    <a href="<?php echo base_url('?page=posorders&action=list&status=returned'); ?>" class="tab <?php echo (isset($_GET['status']) && $_GET['status'] === 'returned') ? 'tab-active' : ''; ?> text-gray-500 hover:text-gray-700 text-center relative py-4">
                        <span class="px-1 text-md">Returned</span>
                        <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                    </a>
                    
                </div>
                <div class="right-0 top-0 absolute p-4 size">
                    <!--<select id="category" class="px-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white"
                        onchange="location.href='?page=posorders&action=list&category=' + this.value;">
                        
                        <option value="all" selected >All Categories</option>
                        <?php // (getCategories() as $key => $value): 
                        ?>
                            <option value="<?php //echo $key; 
                                            ?>" <?php //echo (isset($_GET['category']) && $_GET['category'] === $key) ? 'selected' : ''; 
                                                ?>><?php //echo $value; 
                                                    ?></option>
                        <?php //endforeach; 
                        ?>                    
                    </select>-->
                </div>
            </div>

            <!-- Table h-96 overflow-y-scroll-->
            <div class="overflow-x-auto mt-4 ">
               
                <?php
                if (!empty($data['orders'])) {
                    foreach ($data['orders'] as $order) {

                        $options = $order['options'] ?? '';
                        $optionsArr = [];
                        $bordercolor = 'border border-gray-300';
                        $addontxt = [];
                        if (is_string($options)) {
                            $decoded = json_decode($options, true);
                            if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                                $optionsArr = $decoded;
                            } else {
                                // fallback: comma separated string
                                $optionsArr = array_filter(array_map('trim', explode(',', $options)));
                            }
                        } elseif (is_array($options)) {
                            $optionsArr = $options;
                        }
                        if (strtolower($order['payment_type']) == 'cod') {
                            $bordercolor = 'border-4 border-yellow-300';
                        }
                        if (!empty($optionsArr)) {
                            foreach ($optionsArr as $opt) {
                                $addon_css = '';
                                // normalize option value to a string to avoid warnings with strpos()
                                if (is_array($opt)) {
                                    $opt_text = implode(',', $opt);
                                } else {
                                    $opt_text = (string)$opt;
                                }
                                $opt_text = trim($opt_text);
                                if ($opt_text === '') {
                                    continue;
                                }

                                // Highlight Express Shipping specially, otherwise show default style
                                if (strpos($opt_text, 'Express') !== false) {
                                    $display = 'Express Shipping';
                                    $addon_css = 'bg-green-200 text-green-900';
                                    $bordercolor = 'border-4 border-green-300';
                                } else {
                                    $display = $opt_text;
                                    $addon_css = 'bg-gray-100 text-gray-800';
                                }
                                $addontxt[] = '<span class="inline-block text-sm px-2 py-1 rounded mr-2 mb-2 ' . $addon_css . '">' . htmlspecialchars($display) . '</span>';
                            }
                        } else {
                            $addontxt[] =  '<span class="data-typography mt-1 block">N/A</span>';
                        }

                ?>
                       
                       <div class="max-w-7xl mx-auto bg-white rounded-lg shadow-md  <?= $bordercolor ?>" style="margin: 0px 0px 10px 0px">
                            <div class="flex items-start p-4 gap-4">
                                <!-- Checkbox -->
                                <div class="flex-shrink-0 pt-1">
                                    <?php //if($order['status']=='pending'): 
                                    ?>
                                    <input type="checkbox" name="poitem[]" value="<?= $order['order_id'] ?>" class="custom-checkbox">
                                    <?php //endif; 
                                    ?>
                                </div>

                                <!-- Main two-column layout -->
                                <div class="grid grid-cols-[max-content,1fr] gap-x-4 w-full">

                                    <!-- COLUMN 1 -->
                                    <div class="flex flex-col gap-4 w-[500px]">
                                        <!-- Col 1, Row 1: Image and Title -->
                                        <div class="flex items-start gap-4 ">
                                            <div class="w-24 h-24 rounded-md flex-shrink-0 flex items-center justify-center bg-gray-50 overflow-hidden">
                                                <img src="<?= $order['image'] ?>" onclick="openImagePopup('<?= $order['image'] ?>')" alt="<?= $order['item_code'] . ' - ' . $order['title'] ?>" class="max-w-full max-h-full object-contain cursor-pointer">
                                            </div>
                                            <div class="pt-1 w-full max-w-xs">
                                                <h2 class="product-title mb-1 w-[300px]"><?= $order['title'] ?></h2>
                                                <p class="item-code">Item Code: <a href="http://exoticindiaart.com/book/details/<?= $order['item_code'] ?>" target="_blank" class="icon-link text-blue-600 hover:underline"><?= $order['item_code'] ?></a></p>
                                                <p class="quantity">Quantity: <?= $order['quantity'] ?> </p>
                                                <?php
                                                $dimensions = [];
                                                $Weight = '';
                                                // Weight
                                                if (!empty($order['product_weight']) && (float)$order['product_weight'] > 0) {
                                                    $unit = !empty($order['product_weight_unit']) ? $order['product_weight_unit'] : '';
                                                    $Weight = 'Weight: ' . $order['product_weight'] . ' ' . $unit;
                                                }

                                                // Length x Width x Height
                                                $length = (float)($order['prod_length'] ?? 0);
                                                $width  = (float)($order['prod_width'] ?? 0);
                                                $height = (float)($order['prod_height'] ?? 0);

                                                if ($length > 0 || $width > 0 || $height > 0) {
                                                    $dimParts = [];

                                                    if ($length > 0) $dimParts[] = 'L: ' . $length .' Inch';
                                                    if ($width > 0)  $dimParts[] = 'W: ' . $width .' Inch';
                                                    if ($height > 0) $dimParts[] = 'H: ' . $height .' Inch';

                                                    $dimensions[] = ' ' . implode(' × ', $dimParts);
                                                }
                                                ?>
                                                <?php if (!empty($dimensions)): ?>
                                                    <p class="quantity">
                                                        Dimensions: <?= implode(' | ', $dimensions) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if (!empty($Weight)): ?>
                                                    <p class="quantity">
                                                        <?= $Weight ?>
                                                    </p>
                                                    <?php endif; ?>
                                                 
                                            </div>
                                        </div>
                                        <!-- Col 1, Row 2: Order Details -->
                                        <div class="flex w-full"> <!-- Left padding to align under title (w-24 + gap-4 = 6rem + 1rem) -->
                                            <div class="w-1/2 pr-4 grid grid-cols-[max-content,1fr] items-center gap-x-2 pt-1">
                                                <span class="heading-typography ">Order Date</span>
                                                <p class="">: <span class="data-typography"><?= date("d M Y", strtotime($order['order_date'])) ?></span></p>

                                                <span class="heading-typography ">Order ID</span>
                                                <p class="">: <span class="data-typography"><a href="#" id="order-id-<?= $order['order_id'] ?>" class="order-detail-link text-blue-600 hover:underline" data-order='<?= htmlspecialchars(json_encode($order), ENT_QUOTES, 'UTF-8') ?>'><?= $order['order_number'] ?></a></span></p>
                                                <span class="heading-typography">Vendor Name</span>
                                                <p>: <span class="data-typography"><?= $order['vendor'] ?></span></p>
                                                <?php if (!empty($order['author'])) { ?>
                                                    <span class="heading-typography">Author</span>
                                                    <p>: <span class="data-typography"><?= $order['author'] ?? 'N/A' ?></span></p>
                                                <?php } else { ?>
                                                    <span class="heading-typography">Color</span>
                                                    <p>: <span class="data-typography"><?= $order['color'] ?? 'N/A' ?></span></p>
                                                <?php } ?>

                                            </div>
                                            <div class="w-1/2 pl-4 grid grid-cols-[max-content,1fr] items-center gap-x-2 pt-1">
                                                <span class="heading-typography">Staff Name</span>
                                                <p>: <span class="data-typography"><?= $order['staff_name'] ?? 'N/A' ?></span></p>
                                                <span class="heading-typography">Payment Type</span>
                                                <p>: <span class="data-typography uppercase "><?= $order['payment_type'] ?? 'N/A' ?></span></p>
                                                <span class="heading-typography">Agent</span>
                                                <p>: <span class="data-typography uppercase"><?= $order['agent_id'] ? $staff_list[$order['agent_id']] : 'N/A' ?></span></p>
                                                <?php if (!empty($order['publisher'])) { ?>
                                                    <span class="heading-typography">Publisher</span>
                                                    <p>: <span class="data-typography"><?= $order['publisher'] ?? 'N/A' ?></span></p>
                                                <?php } else { ?>
                                                    <span class="heading-typography">Size</span>
                                                    <p>: <span class="data-typography"><?= $order['size'] ?? 'N/A' ?></span></p>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- COLUMN 2 -->
                                    <div class="flex flex-col gap-4 ">
                                        <!-- Col 2, Row 1: Status Grid and Actions -->
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-grow">
                                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4 items-start text-center md:text-left">
                                                    <div class="">
                                                        <h4 class="set-priority-title mb-2">Priority</h4>
                                                        <?php
                                                        $priority_bg_class['critical'] = 'bg-red-700';
                                                        $priority_bg_class['urgent'] = 'bg-red-600';
                                                        $priority_bg_class['high'] = 'bg-red-500';
                                                        $priority_bg_class['medium'] = 'bg-orange-500';
                                                        $priority_bg_class['low'] = 'bg-yellow-400';
                                                        //$priority_span_bg_color = isset($order['priority']) ? $priority_bg_class[$order['priority']] : '';
                                                        if (isset($order['priority']) && $order['priority'] != '') {
                                                            $priority_span_bg_color = $priority_bg_class[$order['priority']];
                                                        } else {
                                                            $priority_span_bg_color = '';
                                                        }
                                                        if ($priority_span_bg_color != 'bg-yellow-400') {
                                                            $priority_span_text_color = 'text-white';
                                                        }
                                                        ?>
                                                        <span class="capitalize p-2 <?php echo $priority_span_bg_color . ' ' . $priority_span_text_color; ?>"><?= isset($order['priority']) ? $order['priority'] : '' ?></span>
                                                    </div>

                                                    <div>
                                                        <span class="heading-typography block mb-5">Local Stock</span>
                                                        <span class="data-typography mt-1 block font-semibold"><?= $order['local_stock'] ?? 'N/A' ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="heading-typography block mb-5">Location</span>
                                                        <span class="data-typography mt-1 block"><?= $order['location'] ?: 'N/A' ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="heading-typography block mb-5">Ship By Date</span>
                                                        <span class="data-typography mt-1 block"><?= $order['esd'] ? date("d M Y", strtotime($order['esd'])) : 'N/A' ?></span>
                                                    </div>

                                                    <div>
                                                        <span class="heading-typography block mb-5">Status</span>
                                                        <span class="data-typography mt-1 block font-semibold"><?= isset($status_list[$order['status']]) ? $status_list[$order['status']] : $order['status'] ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- <div class="w-auto flex flex-col items-center space-y-2">
                                            <span class="text-gray-500 hover:text-gray-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                </svg>
                                            </span>
                                            
                                            <span class="menu-button text-gray-500 hover:text-gray-700 font-semibold" style="position: relative; display: inline-block;" onclick="toggleMenu(<?= $order['order_id'] ?>)">
                                                <i class="fas fa-ellipsis-v"></i> 
                                            </span>
                                            <div id="menu-<?= $order['order_id'] ?>" style="display: none;" class="menu-popup-order">
                                                <a href="#" onclick="openStatusPopup(<?= $order['order_id'] ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Update status</a>
                                                <a href="<?php //echo base_url('?page=purchase_orders&action=create&order_id=' . $order['order_id']); 
                                                            ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Create PO</a>
                                                
                                            </div>
                                        </div> -->
                                            <div class="w-auto flex flex-col items-center space-y-2">
                                                <span class="text-gray-500 hover:text-gray-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                </span>
                                                <span class="menu-button text-gray-500 hover:text-gray-700 font-semibold relative inline-block" onclick="toggleMenu(<?= $order['order_id'] ?>)">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                    <div id="menu-<?= $order['order_id'] ?>" style="display: none;" class="menu-popup-order absolute right-0 mt-8 z-50 bg-white shadow rounded">
                                                        <a href="#" onclick="openStatusPopup(<?= $order['order_id'] ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Update Order</a>
                                                        <hr class="my-1 mx-2">
                                                        </hr>
                                                        <a href="#" onclick="SubmitCreatePo(<?= $order['order_id'] ?>); return false;" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Create PO</a>
                                                        <?php if (empty($order['invoice_id'])): ?>
                                                        <hr class="my-1 mx-2"></hr>
                                                        <a href="#" onclick="addOrderToInvoice(<?= $order['order_id'] ?>)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Add to Invoice</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </span>
                                            </div>
                                        </div>
                                        <!-- Col 2, Row 2: Note and Priority -->
                                        <div class="flex items-start gap-4">
                                            <div class="flex-grow">
                                                <h4 class="note-heading mb-2">Note:</h4>
                                                <div class="note-content bg-[#f3f3f3] p-4 rounded-[5px] w-full max-w-[452px] min-h-[100px]">
                                                    <?= isset($order['remarks']) ? $order['remarks'] : '' ?>
                                                </div>
                                            </div>
                                            <div class="w-auto flex flex-col justify-between text-left flex-shrink-0" style="min-height: calc(110px + 2.5rem );">
                                                <div class="mt-[20px] max-w-48">
                                                    <span class="heading-typography block mb-5">Addon</span>
                                                    <?= implode('', $addontxt) ?>
                                                </div>
                                                <div>
                                                    <?= $order['po_number'] ? '<a href="?page=purchase_orders&action=view&po_id=' . $order['po_id'] . '" target="_blank" class="mx-10 icon-link create-po-btn">' . $order['po_number'] . '</a>' : '' ?>
                                                    <?php if (!empty($order['invoice_id'])): ?>
                                                    <a href="<?= base_url("?page=invoices&action=generate_pdf&invoice_id=".$order['invoice_id']) ?>" target="_blank" class="download-invoice inline-flex items-center hover:text-blue-800 font-semibold">
                                                        <p class="mr-1">Download Invoice</p>
                                                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M2.62925 10.3889C1.64271 9.68768 1 8.54159 1 7.24672C1 5.47783 2.3 3.84375 4.25 3.52778C4.86168 2.07349 6.30934 1 7.99783 1C10.1607 1 11.9284 2.67737 12.05 4.79167C13.1978 5.29352 14 6.52522 14 7.85887C14 8.98648 13.4266 9.98004 12.5556 10.5634M7.5 14V6.77778M7.5 14L5.33333 11.8333M7.5 14L9.66667 11.8333" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-400">No Invoice</span>
                                                <?php endif;  ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="border-t mx-3  border-gray-200">
                            <!-- <h3 class="text-sm font-semibold ml-10 mt-4">Order Journey:</h3> -->
                            <div class="p-1 flex w-full">
                                <div class="grid p-4 rounded-lg grid grid-cols-8 gap-y-2">
                                    <!-- Step 1: Approved -->
                                    <div class="timeline-step completed">
                                        <div class="flex flex-col items-center text-center">
                                            <div class="relative w-full h-5 flex justify-center items-center">
                                                <div class="w-[18px] h-[18px] rounded-full bg-[rgba(39,174,96,1)] z-10"></div>
                                            </div>
                                            <p class="timeline-text mt-2">Created</p>
                                            <p class="timeline-date"><?php echo date('d M, Y', strtotime($order['order_date'])); ?></p>
                                        </div>
                                    </div>
                                    <!-- status log -->
                                    <?php if (!empty($order['status_log'])) {
                                        foreach ($order['status_log'] as $log) { ?>
                                            <div class="timeline-step completed min-w-[120px]">
                                                <div class="flex flex-col items-center text-center">
                                                    <div class="relative w-full h-5 flex justify-center items-center">
                                                        <div class="w-[18px] h-[18px] rounded-full bg-[rgba(39,174,96,1)] z-10"></div>
                                                    </div>
                                                    <p class="timeline-text mt-2"><?php echo ucfirst(str_replace('_', ' ', $log['status'])); ?></p>
                                                    <p class="timeline-date"><?php echo date('d M, Y', strtotime($log['change_date'])); ?></p>
                                                    <p><?php echo $log['changed_by_username']; ?></p>
                                                </div>
                                            </div>
                                        <?php }
                                        } ?>

                                </div>
                            </div>
                        </div>
                <?php
                    }
                } else {
                    echo "No orders found.";
                }
                ?>

            </div>
        </form>
        <!-- Pagination -->

        <div id="pagination-controls" class="flex justify-center items-center space-x-4 mt-8 bottom-0 border border-[rgba(226,228,230,1)] py-4">
            <div>
                <p class="text-sm text-gray-600">Showing <span class="font-medium"><?= count($orders) ?></span> of <span class="font-medium"><?= $total_orders ?></span> orders</p>
            </div>
            <?php
            //echo '****************************************  '.$query_string;
            if ($total_pages > 1): ?>
                <!-- Prev Button -->
                <a class="page-link px-2 py-1 rounded <?php if ($page <= 1) echo 'opacity-50 pointer-events-none'; ?>"
                    href="?page=posorders&action=list&page_no=<?= $page - $slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
                    &laquo; Prev
                </a>
                <!-- Page Slots -->
                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a class="page-link px-2 py-1 rounded <?= $i == $page ? 'bg-black text-white font-bold' : 'bg-gray-100 text-gray-700' ?>"
                        href="?page=posorders&action=list&page_no=<?= $i ?>&limit=<?= $limit ?><?= $query_string ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                <!-- Next Button -->
                <a class="page-link px-2 py-1 rounded <?php if ($page >= $total_pages) echo 'opacity-50 pointer-events-none'; ?>"
                    href="?page=posorders&action=list&page_no=<?= $page + $slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
                    Next &raquo;
                </a>
            <?php endif; ?>
            <select id="rows-per-page" class="pagination-select bg-transparent border-b border-gray-400 focus:outline-none focus:border-gray-800 text-gray-600"
                onchange="location.href='?page=posorders&page_no=1&limit=' + this.value + '<?= $query_string ?>';">
                <?php foreach ([10, 20, 50, 100] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $opt === $limit ? 'selected' : '' ?>>
                        <?= $opt ?>
                    </option>
                <?php endforeach; ?>
            </select>

        </div>

    </div>
</div>

<!-- Order Details Popup Modal -->
<!-- <div class="fixed inset-y-0 right-0 w-[400px] bg-white shadow-lg p-4" id="orderDetailOffcanvas" style="display: none; z-index: 1000;">
  Popup content goes here
  <h2 class="text-xl font-bold mb-4">Order Details</h2>
  <div id="orderDetailOffcanvasBody">
      Order details will be populated here
  </div>
  <button class="mt-4 bg-blue-500 text-white px-4 py-2 rounded">Close</button>
</div> -->
<!-- Right Side Popup Wrapper -->
<div id="popup-wrapper" class="hidden">
    <!-- Background Overlay -->
    <!-- <div id="popup-overlay" class="fixed inset-0 bg-black bg-opacity-25 z-40"></div> -->

    <!-- Sliding Container -->
    <div id="modal-slider" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: calc(45% + 61px); min-width: 661px;">

        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-vendor-popup-btn" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Popup Panel -->
        <!-- <div id="vendor-popup-panel" class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Order Details</h2>

                    <div class="flex items-start mb-6 pb-6 border-b">
                        <img src="https://placehold.co/100x80/e2e8f0/4a5568?text=Item" alt="Product Image" class="rounded-md h-36
                         object-cover">
                        <div class="ml-6 text-sm text-gray-600 space-y-1">
                            <p class="flex w-[400px]"><strong>Order Number:</strong> <span id="order_number"></span> 
                            <form action="<?php echo base_url('?page=purchase_orders&action=create'); ?>" method="post">
                            <input type="hidden" name="poitem[]" id="poitem_order_id">
                            <button type="submit" class="btn btn-success float-right">Create PO</button>
                            </form>
                            </p>
                            <p><strong>Order Date:</strong> <span id="order_date"></span></p>
                            <p><strong>HSN Code:</strong> <span id="hsn"></span></p>
                            <p><strong>Category:</strong> <span id="groupname"></span></p>
                            <p><strong>Quantity:</strong> <span id="quantity"></span></p>
                            <p><strong>Shipping Country:</strong> <span id="shipping_country"></span></p>  
                        </div>
                    </div>

                    <form id="detail-form" enctype="multipart/form-data">
                        <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                            <div>
                                <label class="text-sm font-bold text-gray-700 ">Title: </label>
                                <span class="text-gray-600" id="item"></span>
                            </div>                            
                            <div>
                                <label class="text-sm font-bold text-gray-700">Description: </label>
                                <span class="text-gray-600" id="description"></span>
                            </div>
                            <div>
                                <label class="text-sm font-bold text-gray-700">Sub Category: </label>
                                <span class="text-gray-600" id="sub_category"></span>
                            </div>
                            <div>
                                <label class="text-sm font-bold text-gray-700">Size: </label>
                                <span class="text-gray-600" id="size"></span>
                            </div>
                            <div>
                                <label class="text-sm font-bold text-gray-700">Color: </label>
                                <span class="text-gray-600" id="color"></span>
                            </div>
                            <div>
                                <label class="text-sm font-bold text-gray-700">Material: </label>
                                <span class="text-gray-600" id="material"></span>
                            </div>
                            <div>
                                <label for="item_price" class="text-sm font-bold text-gray-700">Item Price: </label>
                                <span class="text-gray-600" id="item_price">₹00</span>
                            </div>
                            <div>
                                <label for="final_price" class="text-sm font-bold text-gray-700">Final Price: </label>
                                <span class="text-gray-600" id="final_price">00</span>
                            </div>
                            <div>
                                <label for="cost_price" class="text-sm font-bold text-gray-700">Cost Price: </label>
                                <span class="text-gray-600" id="cost_price">00</span>
                            </div>
                            <div>
                                <label for="currency" class="text-sm font-bold text-gray-700">Currency: </label>
                                <span class="text-gray-600" id="currency">00</span>
                            </div>
                            <div>
                                <label class="text-sm font-bold text-gray-700">GST: </label>
                                <span class="text-gray-600" id="gst">18%</span>
                            </div>
                            <div>
                                <label for="marketplace" class="text-sm font-bold text-gray-700">Marketplace: </label>
                                <span class="text-gray-600" id="marketplace"></span>
                            </div>
                            <div>
                                <label for="local_stock" class="text-sm font-bold text-gray-700">Local Stock: </label>
                                <span class="text-gray-600" id="local_stock"></span>
                            </div>
                            <div>
                                <label for="location" class="text-sm font-bold text-gray-700">Location: </label>
                                <span class="text-gray-600" id="location"></span>
                            </div>
                            <div>
                                <label for="order_addons" class="text-sm font-bold text-gray-700">Order Addons: </label>
                                <span id="order_addons" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="backorder_status" class="text-sm font-bold text-gray-700">Backorder Status: </label>
                                <span id="backorder_status" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="backorder_percent" class="text-sm font-bold text-gray-700">Backorder Percentage: </label>
                                <span id="backorder_percent" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="backorder_delay" class="text-sm font-bold text-gray-700">Backorder Delay: </label>
                                <span id="backorder_delay" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="numsold" class="text-sm font-bold text-gray-700">Sold Quantity (numsold): </label>
                                <span id="numsold" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="po_number" class="text-sm font-bold text-gray-700">PO Number: </label>
                                <span id="po_number" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="po_date" class="text-sm font-bold text-gray-700">PO Date: </label>
                                <span id="po_date" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="expected_delivery_date" class="text-sm font-bold text-gray-700">Expected Delivery Date: </label>
                                <span id="expected_delivery_date" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="product_weight" class="text-sm font-bold text-gray-700">Product Weight: </label>
                                <span id="product_weight" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="product_weight_unit" class="text-sm font-bold text-gray-700">Product Weight Unit: </label>
                                <span id="product_weight_unit" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="prod_height" class="text-sm font-bold text-gray-700">Product Height: </label>
                                <span id="prod_height" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="prod_width" class="text-sm font-bold text-gray-700">Product Width: </label>
                                <span id="prod_width" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="prod_length" class="text-sm font-bold text-gray-700">Product Length: </label>
                                <span id="prod_length" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="length_unit" class="text-sm font-bold text-gray-700">Length Unit: </label>
                                <span id="length_unit" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="payment_type" class="text-sm font-bold text-gray-700">Payment Type: </label>
                                <span id="payment_type" class="text-gray-600"></span>
                            </div>
                            <div>
                                <label for="coupon" class="text-sm font-bold text-gray-700">Coupon: </label>
                                <span id="coupon" class="text-gray-600"></span>
                            </div>
                            <div> 
                                <label for="coupon_reduce" class="text-sm font-bold text-gray-700">Coupon Reduce: </label>
                                <span id="coupon_reduce" class="text-gray-600"></span>
                            </div>
                            <div> 
                                <label for="giftvoucher" class="text-sm font-bold text-gray-700">Gift Voucher: </label>
                                <span id="giftvoucher" class="text-gray-600"></span>
                            </div>
                            <div> 
                                <label for="giftvoucher_reduce" class="text-sm font-bold text-gray-700">Gift Voucher Reduce: </label>
                                <span id="giftvoucher_reduce" class="text-gray-600"></span>
                            </div>
                            <div> 
                                <label for="credit" class="text-sm font-bold text-gray-700">Credit: </label>
                                <span id="credit" class="text-gray-600"></span>
                            </div>
                            <div> 
                                <label for="vendor" class="text-sm font-bold text-gray-700">Vendor: </label>
                                <span id="vendor" class="text-gray-600"></span>
                            </div>
                            
                        </div>

                        
                    </form>
                </div>
            </div>
        </div> -->
    </div>
</div>
<!--popup for search settings-->
<div id="searchSettingsPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50">
    <div class="bg-white p-4 rounded-md max-w-6xl w-5/12 max-h-3xl relative flex flex-col items-center">
        <button onclick="closesearch();" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <div class="p-6 w-full overflow-y-auto">
            <h2 class="text-2xl font-bold mb-4">Search Settings</h2>
            <form id="searchSettingsForm" method="post" action="?page=posorders&action=save_search_settings">
                <div class="mb-4 w-full">
                    <label class="block text-gray-700 font-bold mb-2">Saved Search List:</label>
                    <ul class="space-y-2">
                        <?php if (isset($saved_searches)): ?>
                            <?php foreach ($saved_searches as $s): ?>
                                <li class="flex items-center justify-between bg-gray-50 p-3 rounded">
                                    <p><?= htmlspecialchars($s['name']) ?></p>
                                    <div class="flex gap-3">
                                        <a class="text-indigo-600 hover:underline text-sm" href="<?= base_url('?page=posorders&action=list') . '&' . htmlspecialchars($s['query']) ?>">Load</a>
                                        <button onclick="deleteSavedSearch(<?= $s['id'] ?>)" class="text-red-600 hover:underline text-sm">Delete</button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Image Popup -->
<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <img id="popupImage" class="max-w-full max-h-[80vh] rounded" src="" alt="Image Preview">
    </div>
</div>
<div id="importPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeImportPopup(event)">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center " onclick="event.stopPropagation();">
        <button onclick="closeImportPopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <div class="p-6">
            <h2 class="text-2xl font-bold mb-4">Import Orders</h2>
            <?php //echo date('Y-m-d H:i:s',1761382018);          

            ?>
            <div class="w-full bg-gray-200 rounded-full dark:bg-gray-700 mb-2">
                <div id="importProgress" class="bg-orange-600 text-xs font-medium text-orange-100 text-center p-0.5 leading-none rounded-full" style="width: 0%"> 0%</div>
            </div>
            <form id="importForm" enctype="multipart/form-data" method="post" action="?page=posorders&action=import_orders">
                <div class="mb-4 flex">
                    <label for="importType" class="block text-gray-700 font-bold mb-2 ">Import Type:</label>
                    <select id="importType" name="importType" class="border border-gray-300 rounded px-3 py-2 ml-12" onchange="toggleOrderNumberInput()">
                        <option value="all">Import All Orders</option>
                        <option value="specific">Import Specific Order</option>
                    </select>
                </div>
                <div class="mb-4 flex hidden" id="orderNumberDiv">
                    <label for="importOrderId" class="block text-gray-700 font-bold mb-2">Order Number:</label>
                    <input type="text" id="importOrderId" name="importOrderId" class="border border-gray-300 rounded px-3 py-2 ml-8">
                </div>
                <div class="mb-4" style="max-height:200px; overflow-y:auto;" id="importStatus">
                    <p class="text-gray-700 mb-2">Are you sure you want to import orders from Server?</p>
                </div>
                <div class="flex justify-end space-x-4">
                    <div id="errorMessage" class=""></div>
                    <button type="button" onclick="closeImportPopup()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--Status Popup -->
<div id="statusPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeStatusPopup(event)">
    <div class="bg-white p-4 rounded-md max-w-4xl max-h-3xl relative flex flex-col items-center " onclick="event.stopPropagation();">
        <button onclick="closeStatusPopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <div class="w-full flex">
            <div class="items-start mb-6 w-[40%]">
                <img src="https://placehold.co/100x80/e2e8f0/4a5568?text=Item" alt="Product Image" class="rounded-md border-2 m-6 h-36
                    object-cover">
                <p class="ml-6 text-sm text-gray-600 space-y-1">
                    <strong>Order Number:</strong> <span id="status_order_number"></span><br>
                    <strong>Item Code:</strong> <span id="status_item_code"></span><br>
                    <strong>Vendor Name:</strong> <span id="status_vendor_name"></span><br><br>
                    <span id="status_category"></span> /
                    <span id="status_sub_category"></span><br>
                    <span id="status_item" class="font-bold"></span><br>
                </p>
            </div>
            <div class="border-l pl-4 ml-4"></div>
            <div class="p-4 w-[59%]">
                <h2 class="text-2xl font-bold mb-4">Update Order</h2>
                <form id="statusForm" enctype="multipart/form-data" method="post" action="?page=posorders&action=update_status">
                    <input type="hidden" name="status_order_id" id="status_order_id">
                    <div class="mb-4">
                        <div class="mb-4 flex space-x-4">
                            <div>
                                <label for="orderStatus" class="block text-gray-700 font-bold mb-2 ">Order Status:</label>
                                <select id="orderStatus" name="orderStatus" class="border border-gray-300 rounded px-3 py-2 w-full">
                                    <?php
                                    echo '<option value="">-- Order Status --</option>';

                                    // Find the "Procurement" parent id (by slug or title, case-insensitive)
                                    $procurement_id = null;
                                    $sorder_id = null;
                                    $parent = [];
                                    foreach ($order_status_list as $s) {
                                        if ((isset($s['slug']) && strtolower($s['slug']) === 'procurement') ||
                                            (isset($s['title']) && strtolower($s['title']) === 'procurement')
                                        ) {
                                            $procurement_id = $s['id'] ?? null;
                                            //break;
                                        }
                                        if ($s['parent_id'] === 0 && strtolower($s['slug']) === 'order') {
                                            //$parent[$s['id']] = $s['title'];
                                            $sorder_id = $s['id'] ?? null;
                                        }
                                    }

                                    // Partition statuses into procurement children and others
                                    $procurement_children = [];
                                    $other_statuses = [];
                                    foreach ($order_status_list as $status) {
                                        // skip the procurement parent itself from listing
                                        if ($procurement_id !== null && isset($status['id']) && $status['id'] == $procurement_id) {
                                            continue;
                                        }
                                        if ($sorder_id !== null &&  $status['id'] == $sorder_id) {
                                            continue;
                                        }
                                        if ($procurement_id !== null && isset($status['parent_id']) && $status['parent_id'] == $procurement_id) {
                                            $procurement_children[] = $status;
                                        } else {
                                            $other_statuses[] = $status;
                                        }
                                    }
                                    // Output remaining statuses
                                    if (!empty($other_statuses)) {
                                        echo '<optgroup label="Order">';
                                        foreach ($other_statuses as $st) {
                                            $value = htmlspecialchars($st['slug'] ?? '');
                                            $label = htmlspecialchars($st['title'] ?? $st['slug'] ?? '');
                                            echo "<option value=\"{$value}\">{$label}</option>";
                                        }
                                    }
                                    // Output optgroup for Procurement (if any)
                                    if (!empty($procurement_children)) {
                                        echo '<optgroup label="Procurement">';
                                        foreach ($procurement_children as $st) {
                                            $value = htmlspecialchars($st['slug'] ?? '');
                                            $label = htmlspecialchars($st['title'] ?? $st['slug'] ?? '');
                                            echo "<option value=\"{$value}\">{$label}</option>";
                                        }
                                        echo '</optgroup>';
                                    }
                                    ?>

                                </select>
                                <input type="hidden" id="previousStatus" name="previousStatus" value="">
                            </div>
                            <div>
                                <label for="statusESD" class="block text-gray-700 font-bold mb-2">Ship By Date:</label>
                                <input type="date" id="statusESD" name="esd" class="border border-gray-300 rounded px-2 py-1.5 w-full">
                                <input type="hidden" id="previousESD" name="previous_esd" value="">
                            </div>
                        </div>
                        <div class="mb-4 flex space-x-4">
                            <div style="min-width: 100px;">
                                <label for="orderPriority" class="block text-gray-700 font-bold mb-2 ">Assign agent:</label>
                                <select name="agent_id" id="agentId" class="border border-gray-300 rounded px-3 py-2 w-full">
                                    <option value="">Select User</option>
                                    <?php foreach ($staff_list as $id => $name): ?>
                                        <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" id="agentName" name="agent_name" value="">
                                <input type="hidden" id="previousAgent" name="previous_agent" value="">
                            </div>
                            <div style="min-width: 100px;">
                                <label for="orderPriority" class="block text-gray-700 font-bold mb-2 ">Priority:</label>
                                <select id="orderPriority" name="orderPriority" class="border border-gray-300 rounded px-3 py-2 w-full">
                                    <option value="">-Select-</option>
                                    <option value="critical">Critical</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="high">High</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="low">Low</option>
                                </select>
                                <input type="hidden" id="previousPriority" name="previous_priority" value="">
                            </div>
                        </div>
                        <!-- Remarks field -->
                        <div class="mb-4">
                            <label for="orderRemarks" class="block text-gray-700 font-bold mb-2">Notes:</label>
                            <textarea id="orderRemarks" name="orderRemarks" class="border border-gray-300 rounded px-3 py-2 w-full" rows="4"></textarea>
                            <input type="hidden" id="previousRemarks" name="previous_remarks" value="">
                        </div>
                        <div id="orderStatusError" class="text-red-500 text-sm mt-1 hidden">Please select a status.</div>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeStatusPopup()" class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-600">Cancel</button>
                        <button type="submit" class="px-4 py-2 btn-success text-white rounded hover:bg-blue-700">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Details Modal -->
<div id="details-modal" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden">
    <div id="details-modal-slider" class="fixed top-0 right-0 h-full w-full max-w-3xl flex transform translate-x-full">

        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-details-modal"
                class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center -ml-[61px]"
                style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="h-full bg-white shadow-xl p-8 overflow-y-auto flex flex-col w-full">
            <!-- Modal Content -->
            <div class="" id="details-modal-content">
                <!-- Dynamic content will be loaded here -->

            </div>
        </div>
    </div>
</div>
<!-- image popup close -->

<!-- Bulk Update Status Modal -->
<div id="bulkStatusPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeBulkStatusPopup(event)">
    <div class="bg-white p-4 rounded-md max-w-2xl w-full relative" onclick="event.stopPropagation();">
        <button onclick="closeBulkStatusPopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <h2 class="text-xl font-bold mb-4">Bulk Update Status</h2>
        <form id="bulkStatusForm" method="post" action="?page=posorders&action=bulk_update_status">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Order Status</label>
                <select id="bulkOrderStatus" name="orderStatus" class="border rounded px-3 py-2 w-full">
                    <option value="">-- Select Status --</option>
                    <?php
                    // reuse status options logic
                    $procurement_id = null;
                    $sorder_id = null;
                    foreach ($order_status_list as $s) {
                        if ((isset($s['slug']) && strtolower($s['slug']) === 'procurement') ||
                            (isset($s['title']) && strtolower($s['title']) === 'procurement')
                        ) {
                            $procurement_id = $s['id'] ?? null;
                        }
                        if ($s['parent_id'] === 0 && strtolower($s['slug']) === 'order') {
                            $sorder_id = $s['id'] ?? null;
                        }
                    }
                    $procurement_children = [];
                    $other_statuses = [];
                    foreach ($order_status_list as $status) {
                        if ($procurement_id !== null && isset($status['id']) && $status['id'] == $procurement_id) continue;
                        if ($sorder_id !== null &&  $status['id'] == $sorder_id) continue;
                        if ($procurement_id !== null && isset($status['parent_id']) && $status['parent_id'] == $procurement_id) {
                            $procurement_children[] = $status;
                        } else {
                            $other_statuses[] = $status;
                        }
                    }
                    if (!empty($other_statuses)) {
                        echo '<optgroup label="Order">';
                        foreach ($other_statuses as $st) {
                            $value = htmlspecialchars($st['slug'] ?? '');
                            $label = htmlspecialchars($st['title'] ?? $st['slug'] ?? '');
                            echo "<option value=\"{$value}\">{$label}</option>";
                        }
                        echo '</optgroup>';
                    }
                    if (!empty($procurement_children)) {
                        echo '<optgroup label="Procurement">';
                        foreach ($procurement_children as $st) {
                            $value = htmlspecialchars($st['slug'] ?? '');
                            $label = htmlspecialchars($st['title'] ?? $st['slug'] ?? '');
                            echo "<option value=\"{$value}\">{$label}</option>";
                        }
                        echo '</optgroup>';
                    }
                    ?>
                </select>
            </div>
            <div class="mb-4">
                <!-- <label class="block text-sm font-bold mb-2">Notes</label>
                <textarea id="bulkStatusNotes" name="notes" class="border rounded px-3 py-2 w-full" rows="3"></textarea> -->
                <!--list selected item image-->
                <div id="bulkStatusSelectedItems" class="flex flex-wrap gap-2 max-h-48 overflow-y-auto border p-2">
                    <!-- Selected item images will be displayed here -->
                </div>
            </div>
            <div id="bulkStatusError" class="text-red-500 text-sm hidden mb-2"></div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeBulkStatusPopup()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Update Status</button>
            </div>
        </form>
    </div>
</div>
<!-- Bulk Assign To Modal -->
<div id="bulkAssignPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeBulkAssignPopup(event)">
    <div class="bg-white p-4 rounded-md max-w-2xl w-full relative" onclick="event.stopPropagation();">
        <button onclick="closeBulkAssignPopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <h2 class="text-xl font-bold mb-4">Assign Orders To Agent</h2>
        <form id="bulkAssignForm" method="post" action="?page=posorders&action=bulk_assign_agent">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Assign To</label>
                <select id="bulkAssignAgent" name="agent_id" class="border rounded px-3 py-2 w-full">
                    <option value="">-- Select Agent --</option>
                    <?php foreach ($staff_list as $id => $name): ?>
                        <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <!-- <label class="block text-sm font-bold mb-2">Notes</label>
                <textarea id="bulkStatusNotes" name="notes" class="border rounded px-3 py-2 w-full" rows="3"></textarea> -->
                <!--list selected item image-->
                <div id="bulkAssignSelectedItems" class="flex flex-wrap gap-2 max-h-48 overflow-y-auto border p-2">
                    <!-- Selected item images will be displayed here -->
                </div>
            </div>
            <div id="bulkAssignError" class="text-red-500 text-sm hidden mb-2"></div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeBulkAssignPopup()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Assign</button>
            </div>
        </form>
    </div>
</div>
<!--bulk add to To Purchase-->
<div id="bulkAddToPurchasePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeBulkAddToPurchasePopup(event)">
    <div class="bg-white p-4 rounded-md max-w-2xl w-full relative" onclick="event.stopPropagation();">
        <button onclick="closeBulkAddToPurchasePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <h2 class="text-xl font-bold mb-4">Create Purchase List</h2>
        <form id="bulkAddToPurchaseForm" method="post" action="">
            <div class="mb-4 flex gap-4 w-full items-end">
                <!-- Assign To -->
                <div class="w-1/2">
                    <label class="block text-sm font-bold mb-2">Assign To</label>
                    <select id="bulkAddToPurchaseAgent"
                        name="agent_id"
                        class="border rounded px-3 py-2 w-full">
                        <option value="">-- Select Agent --</option>
                        <?php foreach ($staff_list as $id => $name): ?>
                            <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date Purchased -->
                <div class="w-1/2">
                    <label class="block text-sm font-bold mb-2">Purchased By</label>
                    <input type="date"
                        id="bulkAddToPurchaseDatePurchased"
                        name="date_purchased"
                        class="border rounded px-3 py-2 w-full">
                </div>
            </div>

            <div class="mb-4">
                <!-- <label class="block text-sm font-bold mb-2">Notes</label>
                <textarea id="bulkStatusNotes" name="notes" class="border rounded px-3 py-2 w-full" rows="3"></textarea> -->
                <!--list selected item image-->
                <div id="bulkAddToPurchaseSelectedItems" class="flex flex-wrap gap-2 max-h-48 overflow-y-auto border p-2">
                    <!-- Selected item images will be displayed here -->
                </div>
            </div>
            <div id="bulkAddToPurchaseError" class="text-red-500 text-sm hidden mb-2"></div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeBulkAddToPurchasePopup()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Create</button>
            </div>
        </form>
    </div>
</div>
<!-- ...success popup... -->
<script>
    function closeImagePopup(event) {
        document.getElementById('imagePopup').classList.add('hidden');
        document.getElementById('popupImage').src = '';
    }
</script>
<script>
    // Popup functionality

    function checkPoItmes() {

        const checkedRows = document.querySelectorAll('input[name="poitem[]"]:checked');
        if (checkedRows.length === 0) {
            alert("Please select at least one order to create a Purchase Order.");
            event.preventDefault(); // Prevent form submission
            return false;
        }
        return true; // Allow form submission if at least one item is checked
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Accordion functionality
        const accordionButton = document.getElementById('accordion-button-search');
        const accordionContent = document.getElementById('accordion-content-search');
        const accordionIcon = document.getElementById('accordion-icon-search');
        //accordionContent fade in effect on load for 3 seconds

        setTimeout(() => {
            accordionContent.classList.remove('hidden');
        }, 300);
        accordionButton.addEventListener('click', () => {
            const isExpanded = accordionButton.getAttribute('aria-expanded') === 'true';
            accordionButton.setAttribute('aria-expanded', !isExpanded);
            if (accordionContent.classList.contains('hidden')) {
                accordionContent.classList.remove('hidden');
                accordionIcon.classList.add('rotate-180');
            } else {
                accordionContent.classList.add('hidden');
                accordionIcon.classList.remove('rotate-180');
            }
        });

        // Tab functionality
        // const tabs = document.querySelectorAll('.tab');
        // tabs.forEach(tab => {
        //     tab.addEventListener('click', function (event) {
        //         event.preventDefault();
        //         tabs.forEach(t => t.classList.remove('tab-active'));
        //         this.classList.add('tab-active');
        //     });
        // });

        // Date validation and clear functionality
        const fromDateInput = document.getElementById('from_date');
        const toDateInput = document.getElementById('to_date');
        const clearButton = document.getElementById('clear-button');

        fromDateInput.addEventListener('input', function() {
            if (fromDateInput.value) {
                toDateInput.min = fromDateInput.value;
            } else {
                toDateInput.min = null;
            }
        });

        function clearFilters() {
            fromDateInput.value = '';
            toDateInput.value = '';
            toDateInput.min = null;
        }

        //clearButton.addEventListener('click', clearFilters);
    });
    // Image popup functionality
    function openImagePopup(imageUrl) {
        popupImage.src = imageUrl;
        document.getElementById('imagePopup').classList.remove('hidden');
    }

    // Key for localStorage
    const STORAGE_KEY = 'selected_po_orders';

    // Save checked IDs to localStorage (merge with previous selections)
    function saveCheckedOrders() {
        // Get all previously checked IDs
        let checked = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        // Get all checkboxes on current page
        const allCbs = Array.from(document.querySelectorAll('input[name="poitem[]"]'));
        // Get checked on current page
        const checkedOnPage = allCbs.filter(cb => cb.checked).map(cb => cb.value);
        // Get unchecked on current page
        const uncheckedOnPage = allCbs.filter(cb => !cb.checked).map(cb => cb.value);

        // Remove unchecked from previous selection
        checked = checked.filter(id => !uncheckedOnPage.includes(id));
        // Add newly checked
        checkedOnPage.forEach(id => {
            if (!checked.includes(id)) checked.push(id);
        });

        localStorage.setItem(STORAGE_KEY, JSON.stringify(checked));
    }

    // Restore checked IDs from localStorage
    function restoreCheckedOrders() {
        const checked = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        document.querySelectorAll('input[name="poitem[]"]').forEach(cb => {
            cb.checked = checked.includes(cb.value);
        });
    }

    // Listen for checkbox changes
    document.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'poitem[]') {
            saveCheckedOrders();
        }
    });

    // Restore on page load
    document.addEventListener('DOMContentLoaded', restoreCheckedOrders);

    // Optional: Clear storage on successful PO creation
    // document.querySelector('form[action*="purchase_orders&action=create"]').addEventListener('submit', function() {
    //     localStorage.removeItem(STORAGE_KEY);
    // });

    // On form submit, add hidden inputs for all selected IDs
    document.querySelector('form[action*="purchase_orders&action=create"]').addEventListener('submit', function(e) {
        // Remove previous hidden inputs (if any)
        document.querySelectorAll('input.poitem_hidden').forEach(el => el.remove());

        // Get all selected IDs from localStorage
        const checked = JSON.parse(localStorage.getItem('selected_po_orders') || '[]');

        // Get all visible checked checkboxes on the current page
        const visibleChecked = Array.from(document.querySelectorAll('input[name="poitem[]"]:checked')).map(cb => cb.value);

        // For each ID in localStorage, add a hidden input ONLY if it's not already checked and visible
        checked.forEach(id => {
            if (!visibleChecked.includes(id)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'poitem[]';
                input.value = id;
                input.className = 'poitem_hidden';
                this.appendChild(input);
            }
        });

        // Optionally clear localStorage after submit
        localStorage.removeItem('selected_po_orders');
    });

    //call import function
    function callImport() {
        //open import popup to display import status and call ajax to import
        document.getElementById('importPopup').classList.remove('hidden');

    }

    function closeImportPopup(e) {
        // If called from button or outside click
        document.getElementById('importPopup').classList.add('hidden');
        location.reload();
    }
    //import calling through ajax
    document.getElementById('importForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        //loading image and submit button disable
        const orderId = document.getElementById('importOrderId').value;
        const submitButton = document.querySelector('#importForm button[type="submit"]');
        const loadingImage = document.createElement('img');
        loadingImage.src = 'images/loading-crop.gif'; // Path to your loading image
        loadingImage.alt = 'Loading...';
        loadingImage.style.height = '50px';
        loadingImage.classList.add('loading-image');
        submitButton.parentNode.insertBefore(loadingImage, submitButton);
        submitButton.disabled = true;
        document.getElementById('errorMessage').textContent = 'Import in progress...';
        let progress = 0;
        const interval = setInterval(() => {
            progress += 10;
            document.getElementById('importProgress').style.width = `${progress}%`;
            document.getElementById('importProgress').textContent = `${progress}%`;
            if (progress >= 45) {
                clearInterval(interval);
            }
        }, 100);
        const form = this;
        const formData = new FormData(form);

        fetch('index.php?page=posorders&action=import_orders&secret_key=b2d1127032446b78ce2b8911b72f6b155636f6898af2cf5d3aafdccf46778801&orderid=' + orderId, {
            method: 'GET',
        })
        .then(response => response.text())
        .then(text => {
            // Try to parse JSON; if parsing fails, treat response as HTML/text
            try {
                return JSON.parse(text);
            } catch (e) {
                return {
                    message: null,
                    html: text
                };
            }
        })
        .then(data => {
            // Remove loading image and enable submit button
            loadingImage.remove();
            submitButton.disabled = false;
            //increment gradually to show activity

            const interval = setInterval(() => {
                progress += 10;
                document.getElementById('importProgress').style.width = `${progress}%`;
                document.getElementById('importProgress').textContent = `${progress}%`;
                if (progress >= 100) {
                    clearInterval(interval);
                }
            }, 100);

            document.getElementById('errorMessage').classList.add('text-green-500');
            document.getElementById('errorMessage').textContent = 'Import completed successfully.';
            if (data && data.message) {
                alert(data.message || 'Import completed.');
            } else if (data && data.html) {
                // Server returned HTML; log it for debugging and show a generic success message
                document.getElementById('importStatus').innerHTML = data.html;
                //console.log('Server response (HTML):', data.html);
                //alert('Import completed. Server returned HTML response.');
            } else {
                alert('Import completed.');
            }
            //closeImportPopup();
            // Optionally, refresh the page or update the order list
            //location.reload();
        })
        .catch(error => {
            console.error('Error during import:', error);
            alert('An error occurred during import.');
            closeImportPopup();
        });
    });
    //toggle order number input
    function toggleOrderNumberInput() {
        const importType = document.getElementById('importType').value;
        const orderNumberDiv = document.getElementById('orderNumberDiv');
        if (importType === 'specific') {
            orderNumberDiv.classList.remove('hidden');
        } else {
            orderNumberDiv.classList.add('hidden');
            document.getElementById('importOrderId').value = '';
        }
    }
    // Toggle menu visibility
    function toggleMenu(orderId) {
        const menu = document.getElementById('menu-' + orderId);
        menu.style.display = 'block';
    }
    // Close menus when clicking outside
    document.addEventListener('click', function(event) {
        // don't close if click is inside a menu or on the menu button (handles inner elements like <i>)
        if (event.target.closest('.menu-popup-order') || event.target.closest('.menu-button')) {
            return;
        }
        document.querySelectorAll('.menu-popup-order').forEach(function(menu) {
            menu.style.display = 'none';
        });
    });

    function openStatusPopup(orderId) {
        document.getElementById('status_order_id').value = orderId;
        document.getElementById('statusPopup').classList.remove('hidden');
        document.getElementById('orderStatusError').textContent = '';
        document.getElementById('orderStatusError').classList.add('hidden');
        document.getElementById('orderRemarks').value = '';
        document.getElementById('orderPriority').value = '';


        // Close the menu
        const menu = document.getElementById('menu-' + orderId);
        if (menu) {
            menu.style.display = 'none';
        }
        // update fields with order data
        const orderData = JSON.parse(document.querySelector('#order-id-' + orderId).getAttribute('data-order'));
        document.getElementById('orderRemarks').value = orderData.remarks || '';
        document.getElementById('orderStatus').value = orderData.status || '';
        document.getElementById('status_order_number').textContent = orderData.order_number || 'N/A';
        document.getElementById('status_item_code').textContent = orderData.item_code || 'N/A';
        document.getElementById('status_vendor_name').textContent = orderData.vendor_name || 'N/A';
        document.getElementById('status_category').textContent = orderData.groupname || 'N/A';
        document.getElementById('status_sub_category').textContent = orderData.subcategories || 'N/A';
        document.getElementById('status_item').textContent = orderData.title || 'N/A';
        document.getElementById('orderPriority').value = orderData.priority || '';
        document.getElementById('previousStatus').value = orderData.status || '';
        document.getElementById('previousAgent').value = orderData.agent_id || '';
        document.getElementById('agentId').value = orderData.agent_id || '';
        document.getElementById('previousPriority').value = orderData.priority || '';
        document.getElementById('previousRemarks').value = orderData.remarks || '';
        document.getElementById('previousESD').value = orderData.esd || '';
        // display ESD in dd-mm-yyyy format while keeping the date input usable
        (function() {
            const statusESD = document.getElementById('statusESD');
            const raw = orderData.esd || '';

            if (!statusESD) return;

            // expect raw in yyyy-mm-dd
            const m = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (m) {
                const formatted = `${m[3]}-${m[2]}-${m[1]}`; // dd-mm-yyyy

                if (statusESD.type === 'date') {
                    // keep the actual input value in yyyy-mm-dd so the native picker works
                    statusESD.value = raw;
                } else {
                    // if it's a text field, set the formatted value directly
                    statusESD.value = formatted;
                }
            } else {
                // fallback: if format unknown, set raw value
                statusESD.value = raw || '';
            }
        })();
        //console.log(orderData.esd);
        //image
        const imgElem = document.querySelector('#statusPopup img');
        imgElem.src = orderData.image || 'default-image.png';
    }

    function closeStatusPopup() {
        document.getElementById('statusPopup').classList.add('hidden');
    }
    //agent name set on selection
    document.getElementById('agentId').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById('agentName').value = selectedOption.text;
    });
    // submit status form with validation
    document.getElementById('statusForm').addEventListener('submit', function(e) {
        const statusSelect = document.getElementById('orderStatus');
        const errorDiv = document.getElementById('orderStatusError');
        if (statusSelect.value === '') {
            e.preventDefault();
            errorDiv.classList.remove('hidden');
        } else {
            errorDiv.classList.add('hidden');
        }
        // Ajax submit the form if validation passes       
        if (statusSelect.value !== '') {
            e.preventDefault();
            const formData = new FormData(document.getElementById('statusForm'));
            fetch('?page=posorders&action=update_status', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        errorDiv.classList.remove('text-red-500');
                        errorDiv.classList.add('text-green-500');
                        errorDiv.textContent = 'Order status updated successfully.';
                        errorDiv.classList.remove('hidden');
                        //closeStatusPopup();
                        setTimeout(() => {
                            closeStatusPopup();
                            location.reload();
                        }, 2000);
                    } else {
                        errorDiv.textContent = 'Error updating order status.';
                        errorDiv.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating order status.');
                });
        }

    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Modal functionality
        const openModalBtn = document.getElementById('open-details-modal');
        const closeModalBtn = document.getElementById('close-details-modal');
        const modal = document.getElementById('details-modal');
        const modalSlider = document.getElementById('details-modal-slider');

        const openModal = () => {
            if (!modal || !modalSlider) return;
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalSlider.classList.remove('translate-x-full');
            }, 10);
        };

        const closeModal = () => {
            if (!modal || !modalSlider) return;
            modalSlider.classList.add('translate-x-full');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        };

        if (openModalBtn) openModalBtn.addEventListener('click', openModal);
        if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);

        if (modal) {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
        }

        // Accordion functionality
        // Initialize accordion triggers inside a given root (document or a container element).
        // Call initAccordionTriggers() on load and after injecting dynamic HTML.
        function initAccordionTriggers(root = document) {
            const accordionTriggers = root.querySelectorAll('.accordion-trigger');
            accordionTriggers.forEach(trigger => {
                // Remove previous handler if stored to avoid duplicate handlers
                if (trigger.__accordionClick__) {
                    trigger.removeEventListener('click', trigger.__accordionClick__);
                }

                const handler = function() {
                    const content = this.nextElementSibling;
                    const isOpening = !content.classList.contains('open');

                    // Open or close the clicked one
                    if (isOpening) {
                        content.classList.add('open');
                        this.classList.add('active');
                    } else {
                        content.classList.remove('open');
                        this.classList.remove('active');
                    }
                };

                // store the handler reference so it can be removed later
                trigger.__accordionClick__ = handler;
                trigger.addEventListener('click', handler);
            });
        }

        // initialize for existing DOM
        //initAccordionTriggers();

        // Load dynamic content into the modal when an order detail link is clicked
        const orderDetailLinks = document.querySelectorAll('.order-detail-link');
        orderDetailLinks.forEach(link => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                openModal(); // Open the modal first
                const modalContentDiv = document.getElementById('details-modal-content');
                const orderData = JSON.parse(link.getAttribute('data-order'));
                //console.log('Fetching details for order:', orderData.order_number);
                //loadingImage.classList.remove('hidden');
                modalContentDiv.innerHTML = '<p>Loading...</p>'; // Show loading indicator

                fetch(`?page=posorders&action=get_order_details_html&type=inner&order_number=${encodeURIComponent(orderData.order_number)}`)
                    .then(response => response.text())
                    .then(html => {
                        modalContentDiv.innerHTML = html; // Insert the fetched HTML

                        // Initialize accordion triggers inside the newly injected content so they work.
                        if (typeof initAccordionTriggers === 'function') {
                            initAccordionTriggers(modalContentDiv);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading order details:', error);
                        modalContentDiv.innerHTML = '<p>Error loading order details.</p>';
                    });
            });
        });

    });
</script>
<script>
    //call Imported Update Popup
    function callImportedUpdate() {
        //open import popup to display import status and call ajax to import
        document.getElementById('importedPopup').classList.remove('hidden');
    }
    //vendor auto complete
    document.getElementById('vendor_autocomplete').addEventListener('input', function() {
        const query = this.value;
        const suggestionsBox = document.getElementById('vendor_suggestions');
        const vendorIdInput = document.getElementById('vendor_id');
        // close suggestions if clicked outside
        document.addEventListener('click', function(event) {
            if (!suggestionsBox.contains(event.target) && event.target !== document.getElementById('vendor_autocomplete')) {
                suggestionsBox.style.display = 'none';
            }
            if (event.target === document.getElementById('vendor_autocomplete')) {
                if (suggestionsBox.children.length > 0) {
                    suggestionsBox.style.display = 'block';
                }
            }
        });

        if (query.length < 2) {
            suggestionsBox.style.display = 'none';
            return;
        }

        fetch('<?php echo base_url("?page=purchase_orders&action=vendor_search&query="); ?>' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                suggestionsBox.innerHTML = '';
                if (Array.isArray(data.data) && data.data.length > 0) {
                    data.data.forEach(vendor => {
                        const div = document.createElement('div');
                        div.className = 'p-2 hover:bg-gray-200 cursor-pointer';
                        div.textContent = vendor.vendor_name;
                        div.addEventListener('click', function() {
                            document.getElementById('vendor_autocomplete').value = vendor.vendor_name;
                            vendorIdInput.value = vendor.id;
                            suggestionsBox.style.display = 'none';
                        });
                        suggestionsBox.appendChild(div);
                    });
                    suggestionsBox.style.display = 'block';
                } else {
                    suggestionsBox.style.display = 'none';
                }
            });
    });
    //advanced multiselect Initialize Select2 for status 
    document.addEventListener('DOMContentLoaded', function() {
        const statusSelect = document.querySelector('.advanced-multiselect');
        if (statusSelect) {
            // Initialize Select2
            $(statusSelect).select2({
                placeholder: "Select Status",
                allowClear: true,
                width: '100%'
            });

            // Preselect values if any
            const preselectedStatus = <?php echo json_encode(isset($_GET['status']) ? (is_array($_GET['status']) ? $_GET['status'] : [$_GET['status']]) : []); ?>;
            if (preselectedStatus.length > 0) {
                $(statusSelect).val(preselectedStatus).trigger('change');
            }
        }

    });
    //payment_type multiselect Initialize Select2 for payment_type 
    document.addEventListener('DOMContentLoaded', function() {
        const paymentTypeSelect = document.querySelector('#payment_type');
        if (paymentTypeSelect) {
            // Initialize Select2
            $(paymentTypeSelect).select2({
                placeholder: "Select Payment Type",
                allowClear: true,
                width: '100%'
            });

            // Preselect values if any
            const preselectedPaymentTypes = <?php echo json_encode(isset($_GET['payment_type']) ? (is_array($_GET['payment_type']) ? $_GET['payment_type'] : [$_GET['payment_type']]) : []); ?>;
            if (preselectedPaymentTypes.length > 0) {
                $(paymentTypeSelect).val(preselectedPaymentTypes).trigger('change');
            }
        }

    });
    //category multiselect Initialize Select2 for category 
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.querySelector('#category');
        if (categorySelect) {
            // Initialize Select2
            $(categorySelect).select2({
                placeholder: "Select Category",
                allowClear: true,
                width: '100%'
            });

            // Preselect values if any
            const preselectedCategories = <?php echo json_encode(isset($_GET['category']) ? (is_array($_GET['category']) ? $_GET['category'] : [$_GET['category']]) : []); ?>;
            if (preselectedCategories.length > 0) {
                $(categorySelect).val(preselectedCategories).trigger('change');
            }
        }

    });
    //staff multiselect Initialize Select2 for staff 
    document.addEventListener('DOMContentLoaded', function() {
        const staffSelect = document.querySelector('#staff_name');
        if (staffSelect) {
            // Initialize Select2
            $(staffSelect).select2({
                placeholder: "Select Staff",
                allowClear: true,
                width: '100%'
            });

            // Preselect values if any
            const preselectedStaff = <?php echo json_encode(isset($_GET['staff']) ? (is_array($_GET['staff']) ? $_GET['staff'] : [$_GET['staff']]) : []); ?>;
            if (preselectedStaff.length > 0) {
                $(staffSelect).val(preselectedStaff).trigger('change');
            }
        }

    });
    //agent multiselect Initialize Select2 for agent 
    document.addEventListener('DOMContentLoaded', function() {
        const agentSelect = document.querySelector('#agent');
        if (agentSelect) {
            // Initialize Select2
            $(agentSelect).select2({
                placeholder: "Select Agent",
                allowClear: true,
                width: '100%'
            });

            // Preselect values if any
            const preselectedAgents = <?php echo json_encode(isset($_GET['agent']) ? (is_array($_GET['agent']) ? $_GET['agent'] : [$_GET['agent']]) : []); ?>;
            if (preselectedAgents.length > 0) {
                $(agentSelect).val(preselectedAgents).trigger('change');
            }
        }

    });
</script>

<script>
    // Helper to get selected order IDs (visible checked + persisted selections)
    function getSelectedOrderIds() {
        const visibleChecked = Array.from(document.querySelectorAll('input[name="poitem[]"]:checked')).map(cb => cb.value);
        const stored = JSON.parse(localStorage.getItem('selected_po_orders') || '[]');
        // merge and deduplicate
        const merged = Array.from(new Set([...stored, ...visibleChecked]));
        return merged;
    }

    // Toggle bulk actions menu
    document.getElementById('bulk-action-toggle').addEventListener('click', function(e) {
        e.stopPropagation();
        const menu = document.getElementById('bulk-action-menu');
        menu.classList.toggle('hidden');
    });
    // close menu on outside click
    document.addEventListener('click', function() {
        document.getElementById('bulk-action-menu').classList.add('hidden');
    });

    // Create PO action: submit the purchase_orders form after validation
    document.getElementById('action-create-po').addEventListener('click', function(e) {
        e.preventDefault();
        // reuse checkPoItmes validation
        const ids = getSelectedOrderIds();
        if (ids.length === 0) {
            alert('Please select at least one order to create a Purchase Order.');
            return;
        }
        // submit the enclosing form (the one with action create purchase_orders)
        const form = document.querySelector('form[action*="purchase_orders&action=create"]');
        if (!form) {
            alert('Create PO form not found.');
            return;
        }
        // ensure hidden inputs are added (like existing submit handler expects)
        // remove previous hidden inputs
        //form.querySelectorAll('input.poitem_hidden').forEach(el => el.remove());
        // append hidden inputs for ids that are not visible checked (server will receive duplicates but it's okay)
        // ids.forEach(id => {
        //     const input = document.createElement('input');
        //     input.type = 'hidden';
        //     input.name = 'poitem[]';
        //     input.value = id;
        //     input.className = 'poitem_hidden';
        //     form.appendChild(input);
        // });
        // clear persisted selection in localStorage
        try { localStorage.removeItem('selected_po_orders'); } catch(e){}
        // submit the form
        form.submit();
    });

    // Bulk Update Status handlers
    document.getElementById('action-update-status').addEventListener('click', function(e) {
        e.preventDefault();
        const ids = getSelectedOrderIds();
        if (ids.length === 0) {
            alert('Please select at least one order to update.');
            return;
        }
        // prepare form: remove previous hidden inputs
        // const form = document.getElementById('bulkStatusForm');
        // form.querySelectorAll('input.poitem_hidden').forEach(el => el.remove());
        // ids.forEach(id => {
        //     const input = document.createElement('input');
        //     input.type = 'hidden';
        //     input.name = 'poitem[]';
        //     input.value = id;
        //     input.className = 'poitem_hidden';
        //     form.appendChild(input);
        // });

        // populate selected items list
        const selectedItemsContainer = document.getElementById('bulkStatusSelectedItems');
        selectedItemsContainer.innerHTML = '';
        ids.forEach(id => {
            const element = document.querySelector('#order-id-' + id);
            if (!element) return; // Skip if element not found on current page
            const orderData = JSON.parse(element.getAttribute('data-order'));
            const itemText = 'Order:' + (orderData.order_number || ' ID ' + id);
            const image = orderData.image || 'default-image.png';
            const div = document.createElement('div');
            div.classList.add('rounded-md', 'flex-shrink-0', 'flex', 'flex-col', 'items-center', 'justify-start', 'bg-gray-50', 'overflow-hidden', 'w-32', 'h-32', 'm-2', 'mb-2', 'text-center');
            div.textContent = itemText;
            // const label = document.createElement('p');
            // label.classList.add('text-sm', 'font-medium', 'mt-2', 'px-1', 'break-words');
            // label.textContent = itemText;
            // div.appendChild(label);

            // Optionally, add image preview
            const img = document.createElement('img');
            img.src = image;
            img.classList.add('max-w-full', 'h-24', 'object-contain');
            //img.style.height = '145px';
            //selectedItemsContainer.appendChild(img);
            div.prepend(img);
            //append hidden order_id
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'order_ids[]';
            hiddenInput.value = id;
            div.appendChild(hiddenInput);
            selectedItemsContainer.appendChild(div);
        });
        document.getElementById('bulkStatusError').classList.add('hidden');
        document.getElementById('bulkStatusPopup').classList.remove('hidden');
    });

    function closeBulkStatusPopup(e) {
        document.getElementById('bulkStatusPopup').classList.add('hidden');
    }
    // Bulk Assign handlers
    document.getElementById('action-assign-to').addEventListener('click', function(e) {
        e.preventDefault();
        const ids = getSelectedOrderIds();
        if (ids.length === 0) {
            alert('Please select at least one order to assign.');
            return;
        }
        const form = document.getElementById('bulkAssignForm');
        form.querySelectorAll('input.poitem_hidden').forEach(el => el.remove());
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'poitem[]';
            input.value = id;
            input.className = 'poitem_hidden';
            form.appendChild(input);
        });
        // populate selected items list
        const selectedItemsContainer = document.getElementById('bulkAssignSelectedItems');
        selectedItemsContainer.innerHTML = '';
        ids.forEach(id => {
            const element = document.querySelector('#order-id-' + id);
            if (!element) return; // Skip if element not found on current page
            const orderData = JSON.parse(element.getAttribute('data-order'));
            const itemText = 'Order' + (orderData.order_number || ' ID ' + id);
            const image = orderData.image || 'default-image.png';
            const div = document.createElement('div');
            div.classList.add('rounded-md', 'flex-shrink-0', 'flex', 'flex-col', 'items-center', 'justify-start', 'bg-gray-50', 'overflow-hidden', 'w-32', 'h-32', 'm-2', 'mb-2', 'text-center');
            div.textContent = itemText;

            // Optionally, add image preview
            const img = document.createElement('img');
            img.src = image;
            img.classList.add('max-w-full', 'h-24', 'object-contain');
            //img.style.width = '145px';
            //img.style.height = '145px';
            //selectedItemsContainer.appendChild(img);
            div.prepend(img);
            //append hidden order_id
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'order_ids[]';
            hiddenInput.value = id;
            div.appendChild(hiddenInput);
            selectedItemsContainer.appendChild(div);
        });
        document.getElementById('bulkAssignError').classList.add('hidden');
        document.getElementById('bulkAssignPopup').classList.remove('hidden');
    });

    function closeBulkAssignPopup(e) {
        document.getElementById('bulkAssignPopup').classList.add('hidden');
    }

    // simple validation for bulk forms
    document.getElementById('bulkStatusForm').addEventListener('submit', function(e) {
        const status = document.getElementById('bulkOrderStatus').value;
        if (!status) {
            e.preventDefault();
            document.getElementById('bulkStatusError').textContent = 'Please select a status.';
            document.getElementById('bulkStatusError').classList.remove('hidden');
            return;
        }
        //ajax submit
        document.getElementById('bulkStatusError').textContent = 'Processing..'
        document.getElementById('bulkStatusError').classList.remove('hidden');
        e.preventDefault();
        const formData = new FormData(this);
        fetch('index.php?action=bulk_update_status', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    //alert(data.message);
                    document.getElementById('bulkStatusError').classList.remove('text-red-500');
                    document.getElementById('bulkStatusError').classList.add('text-green-500');
                    document.getElementById('bulkStatusError').textContent = 'Order statuses updated successfully.';
                    //poitem clear from localStorage
                    localStorage.removeItem('selected_po_orders');

                    //timeout to close popup and reload
                    setTimeout(() => {
                        closeBulkStatusPopup();
                        location.reload();
                    }, 3000);
                    //bulkStatusError.classList.remove('hidden');
                    //location.reload();
                } else {
                    alert(data.message);
                }
            });

    });
    document.getElementById('bulkAssignForm').addEventListener('submit', function(e) {
        const agent = document.getElementById('bulkAssignAgent').value;
        if (!agent) {
            e.preventDefault();
            document.getElementById('bulkAssignError').textContent = 'Please select an agent.';
            document.getElementById('bulkAssignError').classList.remove('hidden');
            return;
        }
        //ajax submit
        document.getElementById('bulkAssignError').textContent = 'Processing..'
        document.getElementById('bulkAssignError').classList.remove('hidden');
        e.preventDefault();
        const formData = new FormData(this);
        fetch('index.php?action=bulk_assign_order', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    //alert(data.message);
                    document.getElementById('bulkAssignError').classList.remove('text-red-500');
                    document.getElementById('bulkAssignError').classList.add('text-green-500');
                    document.getElementById('bulkAssignError').textContent = 'Agent Assigned successfully.';
                    //poitem clear from localStorage
                    localStorage.removeItem('selected_po_orders');

                    //timeout to close popup and reload
                    setTimeout(() => {
                        closeBulkStatusPopup();
                        location.reload();
                    }, 3000);
                    //bulkStatusError.classList.remove('hidden');
                    //location.reload();
                } else {
                    alert(data.message);
                }
            });
    });

    function SubmitCreatePo(id) {
        const form = document.getElementById('orders-form');
        const orderIdInput = document.createElement('input');
        orderIdInput.type = 'hidden';
        orderIdInput.name = 'poitem[]';
        orderIdInput.value = id;
        form.appendChild(orderIdInput);
        form.submit();
    }

    function openSearchSetting() {
        //open popup or modal for search settings
        document.getElementById('searchSettingsPopup').classList.remove('hidden');
    }

    function closesearch() {
        //if (event && event.target === event.currentTarget) {
        document.getElementById('searchSettingsPopup').classList.add('hidden');
        //}
    }

    // Bulk add to purchase list handlers
    document.getElementById('action-add-to-purchase-list').addEventListener('click', function(e) {
        e.preventDefault();

        const oids = getSelectedOrderIds();
        //console.log('Selected orders to create purchase list:', oids);

        if (oids.length === 0) {
            showAlert('Please select at least one order to create purchase list.', 'warning');
            return;
        }

        const form = document.getElementById('bulkAddToPurchaseForm');

        // remove old hidden inputs (order ids + sku + qty) from previous open
        form.querySelectorAll('input.poitem_hidden').forEach(el => el.remove());

        // append hidden order_ids[] for backend
        oids.forEach(orderId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'order_ids[]';
            input.value = orderId;
            input.className = 'poitem_hidden';
            form.appendChild(input);
        });

        // populate selected items list with image + sku + quantity input
        const selectedItemsContainer = document.getElementById('bulkAddToPurchaseSelectedItems');
        selectedItemsContainer.innerHTML = '';

        oids.forEach(orderId => {
            const element = document.querySelector('#order-id-' + orderId);
            if (!element) return;

            const orderData = JSON.parse(element.getAttribute('data-order'));
            const image = orderData.image || 'default-image.png';
            const skuOrCode = orderData.sku || orderData.item_code || ('ID ' + (orderId || ''));

            // Card container (slightly taller to accommodate qty)
            const div = document.createElement('div');
            div.className = 'border rounded-lg p-2 bg-gray-50 flex flex-col items-center gap-2 w-32';

            // image
            const img = document.createElement('img');
            img.src = image;
            img.className = 'w-full h-20 object-contain';
            div.appendChild(img);

            // sku / item code label
            const label = document.createElement('div');
            label.className = 'text-xs text-gray-700 text-center break-words leading-snug';
            label.textContent = skuOrCode;
            div.appendChild(label);

            // quantity input wrapper
            const qtyWrap = document.createElement('div');
            qtyWrap.className = 'w-full';

            const qtyLabel = document.createElement('label');
            qtyLabel.className = 'block text-[11px] text-gray-600 mb-1';
            qtyLabel.textContent = 'Quantity';
            qtyWrap.appendChild(qtyLabel);

            const qtyInput = document.createElement('input');
            qtyInput.type = 'number';
            qtyInput.min = '1';
            qtyInput.value = '';
            qtyInput.name = `quantity[]`; // ✅ quantity per order id
            qtyInput.className = 'border rounded px-2 py-1 w-full text-sm';
            qtyWrap.appendChild(qtyInput);

            div.appendChild(qtyWrap);

            // keep your existing hidden sku[] (if backend expects it)
            const hiddenSku = document.createElement('input');
            hiddenSku.type = 'hidden';
            hiddenSku.name = 'sku[]';
            hiddenSku.value = (orderData.sku || orderData.item_code || '');
            hiddenSku.className = 'poitem_hidden';
            div.appendChild(hiddenSku);

            selectedItemsContainer.appendChild(div);
        });

        document.getElementById('bulkAddToPurchaseError').classList.add('hidden');
        document.getElementById('bulkAddToPurchasePopup').classList.remove('hidden');
    });


    function closeBulkAddToPurchasePopup(e) {
        document.getElementById('bulkAddToPurchasePopup').classList.add('hidden');
    }

//bulk AddToPurchase submit
document.getElementById('bulkAddToPurchaseForm').addEventListener('submit', function(e){
    const agent = document.getElementById('bulkAddToPurchaseAgent').value;
    if (!agent) {
        e.preventDefault();
        document.getElementById('bulkAddToPurchaseError').textContent = 'Please select an agent.';
        document.getElementById('bulkAddToPurchaseError').classList.remove('hidden');
        return;
    }
    //ajax submit
    document.getElementById('bulkAddToPurchaseError').textContent = 'Processing..'
    document.getElementById('bulkAddToPurchaseError').classList.remove('hidden');
    e.preventDefault();
    const formData = new FormData(this);
    fetch('index.php?page=products&action=create_purchase_list', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const msgEl = document.getElementById('bulkAddToPurchaseError');
        if (data.success) {

            let html = [];

            // ✅ Created (GREEN)
            if (data.created && data.created > 0) {
                html.push(
                    `<span class="text-green-500 font-semibold">
                        ✅ ${data.created} item(s) added to Purchase List
                    </span>`
                );
            }

            // ⚠️ Failed (RED)
            if (Array.isArray(data.failed) && data.failed.length > 0) {
                html.push(
                    `<span class="text-red-500 font-semibold">
                        ⚠️ ${data.failed.length} item(s) failed:
                    </span>`
                );

                data.failed.forEach(f => {
                    html.push(
                        `<span class="text-red-500 ml-2 block">
                            • Order #${f.order_id} (SKU: ${f.sku}): ${f.message}
                        </span>`
                    );
                });
            }

            msgEl.classList.remove('hidden');
            msgEl.innerHTML = html.join('');

            // clear localStorage
            localStorage.removeItem('selected_po_orders');

            // auto close
            setTimeout(() => {
                closeBulkAddToPurchasePopup();
                location.reload();
            }, 3500);

        } else {
            msgEl.classList.remove('hidden');
            msgEl.classList.add('text-red-500');
            msgEl.textContent = data.message || 'Something went wrong.';
        }
    });
});

// Add to Invoice handler
document.getElementById('action-add-to-invoice').addEventListener('click', function(e){
    e.preventDefault();
    const oids = getSelectedOrderIds();
    if (oids.length === 0) {
        showAlert('Please select at least one order to create invoice.', 'warning');
        return;
    }
    
    // Validate customer_id for all selected orders
    let validationPromise = Promise.resolve();
    let customerId = null;
    const visibleOrderIds = [];
    const hiddenOrderIds = [];
    
    // Separate visible and hidden orders
    for (const id of oids) {
        const element = document.querySelector('#order-id-' + id);
        if (element) {
            visibleOrderIds.push(id);
        } else {
            hiddenOrderIds.push(id);
        }
    }
    
    // First, validate visible orders
    for (const id of visibleOrderIds) {
        const element = document.querySelector('#order-id-' + id);
        const orderData = JSON.parse(element.getAttribute('data-order'));
        //console.log('Order', id, 'customer_id:', orderData.customer_id);
        if (customerId === null) {
            customerId = orderData.customer_id;
        } else if (customerId !== orderData.customer_id) {
            showAlert('Selected orders belong to different customers. Please select orders for the same customer to create an invoice.', 'error');
            return;
        }

        //invoice created orders check
        const Inv = orderData.invoice_id;
        if (Inv && Inv !== '' && Inv !== '0') {
            showAlert('One or more selected orders are already invoiced. Cannot create invoice.', 'error');
            return;
        }
    }
    
    // If there are hidden orders, fetch their data via AJAX
    if (hiddenOrderIds.length > 0) {
        validationPromise = fetch('index.php?page=posorders&action=get_orders_customer_id', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ order_ids: hiddenOrderIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.orders) {
                for (const orderData of data.orders) {
                    console.log('Fetched Order', orderData.order_id, 'customer_id:', orderData.customer_id);
                    if (customerId === null) {
                        customerId = orderData.customer_id;
                    } else if (customerId !== orderData.customer_id) {
                        throw new Error('Different customers');
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error fetching order data:', error);
            showAlert('Different customers. Cannot create invoice.', 'error');
            throw error;
        });
    }
    
    validationPromise.then(() => {
        // Validation passed, proceed with form submission
        const form = document.getElementById('orders-form');
        form.querySelectorAll('input[name="poitem[]"]').forEach(el => {
            if (!oids.includes(parseInt(el.value))) {
                el.checked = false;
            }
        });
        const hiddenInputs = form.querySelectorAll('input[name="invoice_order_ids[]"]');
        hiddenInputs.forEach(el => el.remove());
        
        oids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'poitem[]';
            input.value = id;
            form.appendChild(input);
        });
        
        form.action = '<?php echo base_url('?page=invoices&action=create'); ?>';
        form.method = 'POST';
        form.submit();
    })
    .catch(error => {
        // Error handling already done in AJAX catch block
        console.error('Validation failed:', error);
    });
});

//clear selected orders from localStorage on page unload
function clearSelectedOrders() {
    localStorage.removeItem('selected_po_orders');
    window.location.reload();
}
//addOrderToInvoice function
function addOrderToInvoice(id) {
    const form = document.getElementById('orders-form');
    // Uncheck visible checkboxes to avoid duplicates
    document.querySelectorAll('input[type="checkbox"][name="poitem[]"]').forEach(cb => cb.checked = false);

    // Remove previously added hidden inputs for poitem[] (including poitem_hidden)
    form.querySelectorAll('input[name="poitem[]"]').forEach(el => {
        if (el.type === 'hidden' || el.classList.contains('poitem_hidden')) el.remove();
    });
    form.querySelectorAll('input.poitem_hidden').forEach(el => el.remove());

    // Clear persisted selections in localStorage
    try { localStorage.removeItem('selected_po_orders'); } catch(e){}

    // Add the selected order as a single hidden input and submit
    const orderIdInput = document.createElement('input');
    orderIdInput.type = 'hidden';
    orderIdInput.name = 'poitem[]';
    orderIdInput.value = id;
    form.appendChild(orderIdInput);

    form.action = '<?php echo base_url('?page=invoices&action=create'); ?>';
    form.method = 'POST';
    form.submit();
}
</script>