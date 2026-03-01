<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<div class="max-w-7xl mx-auto p-4 space-y-6">

    <!-- PRODUCT HEADER -->
    <div class="bg-white rounded-lg p-4 grid grid-cols-1 md:grid-cols-1 gap-4">
        <!-- STOCK TRANSACTIONS -->
        <div class="bg-white rounded-lg p-4 overflow-x-auto">
            <h3 class="font-semibold mb-3">Stock Transactions</h3>
            <!--search fileds-->
            <div class="flex flex-wrap gap-4 mb-4">
                <!-- <input type="text" id="searchRefId" placeholder="Search by Ref ID" class="border rounded p-2 text-sm"> -->
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-600 mb-1">Date Range</label>
                    <input type="text" id="dateRange" name="dateRange" class="border rounded p-2 text-sm" placeholder="Select date range">
                </div>
                <script>
                    $(function() {
                        // Initialize date range picker: display format 'DD MMM YYYY' (e.g., 25 Dec 2015)
                        $('#dateRange').daterangepicker({
                            autoUpdateInput: false,
                            locale: {
                                cancelLabel: 'Clear',
                                format: 'DD MMM YYYY'
                            }
                        });
                        $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
                            $(this).val(picker.startDate.format('DD MMM YYYY') + ' - ' + picker.endDate.format('DD MMM YYYY'));
                        });
                        $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
                            $(this).val('');
                        });
                    });
                </script>

                <div>
                    <label for="searchType" class="block text-sm font-medium text-gray-600 mb-1">Transaction Type</label>
                    <select id="searchType" class="border rounded p-2 text-sm">
                        <option value="">All Types</option>
                        <option value="IN">Purchase</option>
                        <option value="OUT">Sale</option>
                        <option value="TRANSFER_IN">Transfer In</option>
                        <option value="TRANSFER_OUT">Transfer Out</option>
                    </select>
                </div>
                <div>
                    <label for="searchWarehouse" class="block text-sm font-medium text-gray-600 mb-1">Warehouse</label>
                    <select id="searchWarehouse" class="border rounded p-2 text-sm">
                        <option value="">All Warehouses</option>
                        <?php
                        if (!empty($products['warehouses'])) {
                            foreach ($products['warehouses'] as $warehouse) {
                                echo '<option value="' . htmlspecialchars($warehouse['id']) . '">' . htmlspecialchars($warehouse['name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="flex items-end">
                    <label for="searchType" class="block text-sm font-medium text-gray-600 mb-1 invisible"> </label>
                    <button class="px-4 py-2 bg-orange-500 hover:bg-orange-700 text-white rounded text-sm" onclick="filterStockHistory()">Search</button>
                </div>
            </div>
            <table id="stockHistoryTable" class="min-w-full text-sm border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border">Date</th>
                        <th class="p-2 border">Ref ID</th>
                        <th class="p-2 border">Type</th>
                        <th class="p-2 border">Stock In</th>
                        <th class="p-2 border">Stock Out</th>
                        <th class="p-2 border">Balance</th>
                        <th class="p-2 border">Warehouse</th>
                    </tr>
                </thead>
                <tbody>
                    <?php //print_r($products['warehouses']);
                    if (!empty($stock_history)) {
                        foreach ($stock_history as $history) {
                            $type = ['IN' => 'Purchase', 'OUT' => 'Sale', 'TRANSFER_IN' => 'Transfer In', 'TRANSFER_OUT' => 'Transfer Out'];
                            $fontawesomeIcon = ['IN' => 'fa-arrow-up', 'OUT' => 'fa-arrow-down', 'TRANSFER_IN' => 'fa-exchange-alt', 'TRANSFER_OUT' => 'fa-exchange-alt'];
                            $textColor = ['IN' => 'text-green-600', 'OUT' => 'text-red-600', 'TRANSFER_IN' => 'text-blue-600', 'TRANSFER_OUT' => 'text-blue-600'];
                    ?>
                            <tr class="text-center">
                                <td class="p-2 border"><?php echo htmlspecialchars(date('d M Y', strtotime($history['created_at'] ?? ''))); ?></td>
                                <td class="p-2 border"><?php echo htmlspecialchars($history['ref_id'] ?? ''); ?></td>
                                <td class="p-2 border <?php echo htmlspecialchars($textColor[$history['movement_type']] ?? ''); ?>">
                                    <i class="fas <?php echo htmlspecialchars($fontawesomeIcon[$history['movement_type']] ?? ''); ?>"></i>
                                    <?php echo htmlspecialchars($type[$history['movement_type']] ?? ''); ?>
                                </td>
                                <td class="p-2 border"><?php echo htmlspecialchars($history['movement_type'] == 'IN' ? $history['quantity'] : ''); ?></td>
                                <td class="p-2 border"><?php echo htmlspecialchars($history['movement_type'] == 'OUT' ? $history['quantity'] : ''); ?></td>
                                <td class="p-2 border"><?php echo htmlspecialchars($history['running_stock'] ?? '0'); ?></td>
                                <td class="p-2 border"><?php echo htmlspecialchars($history['warehouse_name'] ?? ''); ?></td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="8" class="p-4 text-center text-gray-500">No stock transactions found.</td></tr>';
                    }
                    ?>

                </tbody>
            </table>
            <!-- Pagination -->
            <div id="paginationContainer" class="flex justify-center items-center gap-2 mt-4">
                <button id="prevBtn" class="px-3 py-1 bg-gray-300 text-gray-700 rounded disabled:opacity-50" onclick="previousPage()">Previous</button>
                <span id="pageInfo" class="text-sm text-gray-600">Page 1</span>
                <button id="nextBtn" class="px-3 py-1 bg-gray-300 text-gray-700 rounded disabled:opacity-50" onclick="nextPage()">Next</button>
            </div>
        </div>
    </div>
    <script>
        let currentPage = 1;
        const itemsPerPage = 10;
        let lastFilterParams = {};

        function filterStockHistory(page = 1) {
            const dateRange = $('#dateRange').val();
            let startDate = '';
            let endDate = '';
            if (dateRange) {
                const dates = dateRange.split(' - ');
                startDate = moment(dates[0], 'DD MMM YYYY').format('YYYY-MM-DD');
                endDate = moment(dates[1], 'DD MMM YYYY').format('YYYY-MM-DD');
            }
            const type = $('#searchType').val();
            const warehouse = $('#searchWarehouse').val();
            $.ajax({
                url: `<?php echo base_url('?page=products&action=get_filtered_stock_history'); ?>`,
                method: 'GET',
                data: {
                    product_id: <?php echo htmlspecialchars($products['id'] ?? 0); ?>,
                    sku: '<?php echo htmlspecialchars($products['sku'] ?? ''); ?>',
                    start_date: startDate,
                    end_date: endDate,
                    type: type,
                    warehouse: warehouse,
                    page_no: page,
                    limit: itemsPerPage
                },
                dataType: 'json'
            }).done(function(response) {
                if (response.success) {
                    const history = response.data.stock_history;
                    const totalPages = response.data.total_pages;
                    $('#stockHistoryTable tbody').empty();
                    if (history.length > 0) {
                        history.forEach(function(history) {
                            const typeText = {
                                'IN': 'Purchase',
                                'OUT': 'Sale',
                                'TRANSFER_IN': 'Transfer In',
                                'TRANSFER_OUT': 'Transfer Out'
                            };
                            const fontawesomeIcon = {
                                'IN': 'fa-arrow-up',
                                'OUT': 'fa-arrow-down',
                                'TRANSFER_IN': 'fa-exchange-alt',
                                'TRANSFER_OUT': 'fa-exchange-alt'
                            };
                            const textColor = {
                                'IN': 'text-green-600',
                                'OUT': 'text-red-600',
                                'TRANSFER_IN': 'text-blue-600',
                                'TRANSFER_OUT': 'text-blue-600'
                            };
                            const row = `<tr class="text-center"> <td class="p-2 border">${history.created_at ?moment(history.created_at).format('DD MMM YYYY') : ''}</td> <td class="p-2 border">${history.ref_id ? history.ref_id : ''}</td> <td class="p-2 border ${textColor[history.movement_type] ?? ''}"> <iclass="fas ${fontawesomeIcon[history.movement_type] ?? ''}"></i> ${typeText[history.movement_type] ?? ''} </td> <td class="p-2 border">${history.movement_type === 'IN' ? history.quantity : ''}</td> <td class="p-2 border">${history.movement_type === 'OUT' ? history.quantity :''}</td> <td class="p-2 border">${history.running_stock ?? '0'}</td> <td class="p-2 border">${history.warehouse_name ?? ''}</td> </tr>`;
                            $('#stockHistoryTable tbody').append(row);
                        });
                    } else {
                        $('#stockHistoryTable tbody').append('<tr><td colspan="8" class="p-4 text-centertext-gray-500">No stock transactions found.</td></tr>');
                    }
                    $('#pageInfo').text(`Page ${page} of ${totalPages}`);
                    $('#prevBtn').prop('disabled', page <= 1);
                    $('#nextBtn').prop('disabled', page >= totalPages);
                    currentPage = page;
                } else {
                    alert('Failed to fetch stock history: ' + response.message);
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                alert('Failed to fetchstock history: ' + errorThrown);
            });
        }

        function previousPage() {
            if (currentPage > 1) {
                filterStockHistory(currentPage - 1);
            }
        }

        function nextPage() {
            filterStockHistory(currentPage + 1);
        }
    </script>