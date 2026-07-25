<?php
require_once __DIR__ . '/../../../helpers/order_list_filters.php';

$warehouses = $warehouses ?? [];
$stockAvailable = normalizeStockAvailableFilter($_GET['stock_available'] ?? '');
$selectedWarehouseId = (int) ($_GET['stock_warehouse_id'] ?? 0);
if ($selectedWarehouseId <= 0) {
    $selectedWarehouseId = (int) ($default_warehouse_id ?? resolveOrderListDefaultWarehouseId());
}
$warehouseDisabled = $stockAvailable === '';
?>
<div>
    <label for="stock-available" class="block text-sm font-medium text-gray-600 mb-1">Stock Available</label>
    <select
        id="stock-available"
        name="stock_available"
        class="w-full px-3 py-2 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">
        <option value="" <?php echo $stockAvailable === '' ? 'selected' : ''; ?>>Any</option>
        <option value="yes" <?php echo $stockAvailable === 'yes' ? 'selected' : ''; ?>>Yes</option>
        <option value="no" <?php echo $stockAvailable === 'no' ? 'selected' : ''; ?>>No</option>
    </select>
</div>
<div>
    <label for="stock-warehouse-id" class="block text-sm font-medium text-gray-600 mb-1">Warehouse</label>
    <select
        id="stock-warehouse-id"
        name="stock_warehouse_id"
        class="w-full px-3 py-2 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white disabled:bg-gray-100 disabled:text-gray-500"
        <?php echo $warehouseDisabled ? 'disabled' : ''; ?>>
        <option value="">Select warehouse</option>
        <?php foreach ($warehouses as $warehouse): ?>
            <?php
            $warehouseId = (int) ($warehouse['id'] ?? 0);
            $warehouseLabel = (string) ($warehouse['address_title'] ?? $warehouse['display_name'] ?? ('Warehouse #' . $warehouseId));
            ?>
            <option value="<?php echo $warehouseId; ?>" <?php echo $selectedWarehouseId === $warehouseId ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($warehouseLabel); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stockAvailable = document.getElementById('stock-available');
    const warehouseSelect = document.getElementById('stock-warehouse-id');
    if (!stockAvailable || !warehouseSelect) {
        return;
    }

    function syncStockWarehouseField() {
        const requiresWarehouse = stockAvailable.value === 'yes' || stockAvailable.value === 'no';
        warehouseSelect.disabled = !requiresWarehouse;
        warehouseSelect.classList.toggle('bg-gray-100', !requiresWarehouse);
        warehouseSelect.classList.toggle('text-gray-500', !requiresWarehouse);
    }

    stockAvailable.addEventListener('change', syncStockWarehouseField);
    syncStockWarehouseField();
});
</script>
