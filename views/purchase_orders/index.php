<div class="container">
    <h2 class="">Purchase orders</h2>
</div>
<table class="table table-bordered">
  <thead>
    <tr>
        <th scope="col">#</th>  
        <th scope="col">Vendor</th>
        <th scope="col">PO Number</th>
        <th scope="col">Expected Delivery Date</th>
        <th scope="col">Delivery Address</th>
        <th scope="col">Total GST</th>
        <th scope="col">Grand Total</th>
        <th scope="col">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($purchaseOrders)): ?>
        <?php foreach ($purchaseOrders as $order): ?>
        <tr>
            <td><?= $order['id'] ?></td>
            <td><?= htmlspecialchars($order['vendor_id']) ?></td>
                
            <td><?= htmlspecialchars($order['po_number']) ?></td>
            <td><?= htmlspecialchars($order['expected_delivery_date']) ?></td>
            <td><?= htmlspecialchars($order['delivery_address']) ?></td>    
            <td><?= htmlspecialchars($order['total_gst']) ?></td>
            <td><?= htmlspecialchars($order['total_cost']) ?></td>
            <td>
                <a href="index.php?page=purchase_orders&action=view&id=<?= $order['id'] ?>" class="btn btn-sm btn-info" title="View"><i class="fa fa-eye"></i></a>
                <a href="index.php?page=purchase_orders&action=edit&id<?= $order['id'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fa fa-edit"></i></a>   
                <!-- <button class="btn btn-sm btn-danger mt-0" onclick="deleteData(<?= $order['id'] ?>)" title="Delete"><i class="fa fa-trash"></i></button> -->
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="8" class="text-center">No purchase orders found.</td>
        </tr>
    <?php endif;
    ?>
  </tbody>
</table>
<!-- <div class="text-end">
    <a href="index.php?page=purchase_orders&action=create" class="btn btn-primary">Create Purchase Order</a>
</div> -->
    