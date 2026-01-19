<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Invoices</h1>
        <a href="<?php echo base_url('?page=invoices&action=create'); ?>" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">+ Create Invoice</a>
    </div>

    <!-- Invoices Table -->
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="w-full">
            <thead class="bg-gray-100">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Invoice Number</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Customer</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Total Amount</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Actions</th>
            </tr>
            </thead>
            <tbody>
                <?php if (!empty($invoices)): ?>
                    <?php foreach ($invoices as $invoice): ?>
            <tr class="border-t hover:bg-gray-50">
                <td class="px-6 py-4 text-sm"><?= htmlspecialchars($invoice['invoice_number']) ?></td>
                <td class="px-6 py-4 text-sm"><?= date('d M Y', strtotime($invoice['invoice_date'])) ?></td>
                <td class="px-6 py-4 text-sm"><?= $invoice['customer_id'] ?></td>
                <td class="px-6 py-4 text-sm font-semibold"><?= $invoice['currency'] ?> <?= number_format($invoice['total_amount'], 2) ?></td>
                <td class="px-6 py-4 text-sm">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        <?php 
                            if ($invoice['status'] === 'draft') echo 'bg-gray-100 text-gray-800';
                            elseif ($invoice['status'] === 'sent') echo 'bg-blue-100 text-blue-800';
                            elseif ($invoice['status'] === 'paid') echo 'bg-green-100 text-green-800';
                        ?>
                    "><?= ucfirst($invoice['status']) ?></span>
                </td>
                <td class="px-6 py-4 text-right text-sm space-x-2">
                    <a href="<?php echo base_url('?page=invoices&action=view&id=' . $invoice['id']); ?>" class="text-indigo-600 hover:text-indigo-900">View</a>
                </td>
            </tr>
                    <?php endforeach; ?>
                <?php else: ?>
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">No invoices found.</td>
            </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-6 flex justify-center space-x-2">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?php echo base_url('?page=invoices&action=list&page_no=' . $i . '&limit=' . $limit); ?>" 
               class="px-4 py-2 rounded <?= $i == $page_no ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
