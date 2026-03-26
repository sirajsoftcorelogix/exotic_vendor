<div class="min-h-screen">
  <header class="border-b bg-white">
    <div class="mx-auto flex max-w-[1500px] items-center gap-3 px-4 py-3">
      <a href="?page=pos_register&action=list" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Back to POS</a>
      <h1 class="text-base font-semibold text-slate-800">Stock Report</h1>
      <div class="ml-auto flex items-center gap-2 border rounded-xl px-3 py-2">
        <div class="h-8 w-8 rounded-full bg-slate-300"></div>
        <div class="text-xs">
          <div class="font-semibold"><?= htmlspecialchars($warehouse_name ?? 'No Warehouse') ?></div>
          <div class="text-slate-500">Sales Terminal</div>
        </div>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-[1500px] px-4 py-5">
    <div class="rounded-2xl bg-white border p-4">
      <form method="get" action="index.php" class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-5">
        <input type="hidden" name="page" value="pos_register">
        <input type="hidden" name="action" value="stock-report">

        <input
          type="text"
          name="search"
          value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
          placeholder="Search by item code, sku, title"
          class="rounded-xl border border-slate-200 px-4 py-2 text-sm focus:border-orange-500 outline-none md:col-span-2"
        >

        <select name="category" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-orange-500 outline-none">
          <?php foreach (($categories ?? []) as $slug => $label): ?>
            <option value="<?= htmlspecialchars($slug) ?>" <?= (($filters['category'] ?? 'allProducts') === $slug) ? 'selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select name="stock_status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-orange-500 outline-none">
          <option value="all" <?= (($filters['stock_status'] ?? 'all') === 'all') ? 'selected' : '' ?>>All Stock</option>
          <option value="out" <?= (($filters['stock_status'] ?? 'all') === 'out') ? 'selected' : '' ?>>Out of Stock</option>
          <option value="low" <?= (($filters['stock_status'] ?? 'all') === 'low') ? 'selected' : '' ?>>Low Stock (1-5)</option>
          <option value="in" <?= (($filters['stock_status'] ?? 'all') === 'in') ? 'selected' : '' ?>>In Stock</option>
        </select>

        <button type="submit" class="rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
          Apply
        </button>
      </form>

      <div class="overflow-x-auto rounded-xl border">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="px-3 py-2 text-left">Image</th>
              <th class="px-3 py-2 text-left">Item Code</th>
              <th class="px-3 py-2 text-left">SKU</th>
              <th class="px-3 py-2 text-left">Title</th>
              <th class="px-3 py-2 text-left">Category</th>
              <th class="px-3 py-2 text-left">Stock</th>
              <th class="px-3 py-2 text-left">Sell Price</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="7" class="px-3 py-8 text-center text-slate-400">No stock records found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($rows as $r): ?>
                <?php $qty = (int)($r['stock_qty'] ?? 0); ?>
                <tr class="border-t">
                  <td class="px-3 py-2">
                    <img src="<?= htmlspecialchars($r['image'] ?: 'https://dummyimage.com/64x64/e5e7eb/6b7280&text=No+Image') ?>" class="h-10 w-10 rounded object-cover bg-slate-100">
                  </td>
                  <td class="px-3 py-2 font-semibold text-slate-700"><?= htmlspecialchars($r['item_code'] ?? '') ?></td>
                  <td class="px-3 py-2"><?= htmlspecialchars($r['sku'] ?? '') ?></td>
                  <td class="px-3 py-2"><?= htmlspecialchars($r['title'] ?? '') ?></td>
                  <td class="px-3 py-2"><?= htmlspecialchars($r['groupname'] ?? '') ?></td>
                  <td class="px-3 py-2">
                    <?php if ($qty <= 0): ?>
                      <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">Out (0)</span>
                    <?php elseif ($qty <= 5): ?>
                      <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">Low (<?= $qty ?>)</span>
                    <?php else: ?>
                      <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">In (<?= $qty ?>)</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-3 py-2"><?= number_format((float)($r['sell_price'] ?? 0), 2) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
