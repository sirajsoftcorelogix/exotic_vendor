<div class="container">
<?php
if (isset($data['message'])) {  
    $message = $data['message'];
    if (is_array($message)) {
        $message = implode('<br>', $message);
    }
    echo "<div class='alert alert-success'>$message</div>";
}
?>
<div class="container my-4">
  <h5 class="mb-4">Manage Orders</h5>
  <div class="row g-3">
    
    <div class="col-md">
      <div class="stat-card">
        <div class="icon-circle float-start"><i class="fa-solid fa-list-check"></i></div>
        <h6 class="mb-1 ">PO Pending</h6>
        <h3 class="mb-1">56</h3>
        <div class="percent-up"><i class="fa-solid fa-arrow-up"></i> 16% this month</div>
      </div>
    </div>

    <div class="col-md">
      <div class="stat-card ">
        <div class="icon-circle float-start"><i class="fa-solid fa-file-signature"></i></div>
        <h6 class="mb-1">PO Sent</h6>
        <h3 class="mb-1">26</h3>
        <div class="percent-down"><i class="fa-solid fa-arrow-down"></i> 1% this month</div>
      </div>
    </div>

    <div class="col-md">
      <div class="stat-card">
        <div class="icon-circle float-start"><i class="fa-solid fa-calendar-days"></i></div>
        <h6 class="mb-1">Due Receipt</h6>
        <h3 class="mb-1">16</h3>
        <div class="percent-down"><i class="fa-solid fa-arrow-down"></i> 6% this month</div>
      </div>
    </div>

    <div class="col-md">
      <div class="stat-card">
        <div class="icon-circle float-start"><i class="fa-solid fa-hand-holding"></i></div>
        <h6 class="mb-1">Received</h6>
        <h3 class="mb-1">42</h3>
        <div class="percent-up"><i class="fa-solid fa-arrow-up"></i> 16% this month</div>
      </div>
    </div>

    <div class="col-md">
      <div class="stat-card">
        <div class="icon-circle float-start"><i class="fa-solid fa-truck"></i></div>
        <h6 class="mb-1">Shipped</h6>
        <h3 class="mb-1">25</h3>
        <div class="percent-down"><i class="fa-solid fa-arrow-down"></i> 6% this month</div>
      </div>
    </div>

  </div>
</div>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-0 p-0 ">
  <div class="container-fluid">
    <div class="collapse navbar-collapse">
        <div class="container">
            <div class="row">
                <div class="col ">
                <ul class="navbar-nav">
                    <li class="nav-item">
                    <a class="nav-link navbar-active-bottom" aria-current="page" href="#">New 0</a>
                    </li>
                    <li class="nav-item">
                    <a class="nav-link" href="#">Completed</a>
                    </li>
                </ul>
                </div>
                <div class="col">
                  <?php
                    $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
                    $page = $page < 1 ? 1 : $page;
                    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Orders per page, default 20
                    $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 20; // Only allow specific values
                    $total_orders = isset($data['total_orders']) ? (int)$data['total_orders'] : 0;
                    $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;
                    ?>
                    <div class="dropdown ms-auto btn border-light-grey">
                        <span class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= $limit ?> Orders per page
                        </span>
                        <ul class="dropdown-menu">
                            <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                <li>
                                    <a class="dropdown-item<?= $limit == $opt ? ' active' : '' ?>"
                                      href="?page=orders&page_no=1&limit=<?= $opt ?>">
                                      <?= $opt ?> Orders per page
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>               
                 </div>
               
            </div>
        </div>
        
    </div>
  </div>
</nav>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-0 p-0">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Navbar</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item ">
          <a class="nav-link navbar-active-bottom" aria-current="page" href="#">All Orders</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">No. PO</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">PO Sent</a>
        </li>
        <li class="nav-item">
          <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<form action="<?php echo base_url('?page=purchase_orders&action=create'); ?>" method="post">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Orders</h2>   
    <button type="submit" onclick="checkPoItmes()" class="btn btn-success">Create PO</button>
    
</div>

<div class="row">
    <!-- Order List Table (left column) -->
<div class="col-md-12">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Order List</h5>
        </div>
        <div class="card-body scrollable-column">
            <table class="table table-striped">
                <thead>
                    <tr>  
                        <th scope="col">#</th>
                        <th scope="col">Order Number</th>
                        <th scope="col">Title</th>
                        <th scope="col">Item Code</th>
                        <th scope="col">Size</th>
                        <th scope="col">Color</th>
                        <th scope="col">Marketplace Vendor</th>
                        <th scope="col">Quantity</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr> 
                </thead>
                <tbody>
                    <?php 
                    if (!empty($data['orders'])) {
                        foreach ($data['orders'] as $order) { 
                    ?>  
                    <tr data-id="<?= $order['id'] ?>">
                        <td><input type="checkbox" name="poitem[]" value="<?=$order['id']?>">  <?= $order['id'] ?></td>
                        <td>
                            <a href="#" class="order-detail-link" 
                               data-order='<?= htmlspecialchars(json_encode($order), ENT_QUOTES, 'UTF-8') ?>'>
                               <?= htmlspecialchars($order['order_number']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($order['title']) ?></td> 
                        <td><?= htmlspecialchars($order['item_code']) ?></td>
                        <td><?= htmlspecialchars($order['size']) ?></td>
                        <td><?= htmlspecialchars($order['color']) ?></td>
                        <td><?= htmlspecialchars($order['marketplace_vendor']) ?></td>
                        <td><?= htmlspecialchars($order['quantity']) ?></td>
                        <td><?= htmlspecialchars($order['status']) ?></td>
                        <td>
                            <a href="index.php?page=orders&action=update&id=<?= $order['id'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fa fa-edit"></i></a>
                            <button class="btn btn-sm btn-danger mt-0" onclick="deleteData(<?= $order['id'] ?>)" title="Delete"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='10' class='text-center'>No orders found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

  </div>
</form>
</div>

<!-- Order Details Popup Modal -->
<!-- <div class="modal fade" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="orderDetailModalLabel">Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="orderDetailModalBody">
               
          <div class="text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>  
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="deleteData(<?php // echo $order['id']; ?>)">Delete Order</button>
      </div>
    </div>
  </div>
</div> -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="orderDetailOffcanvas" aria-labelledby="orderDetailOffcanvasLabel" style="width:600px; max-width:100vw;">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="orderDetailOffcanvasLabel">Order Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body" id="orderDetailOffcanvasBody">
    <!-- Order details will be injected here -->
    <div class="text-center">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>     
  </div>
  <div class=" m-3">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">Close</button>
      <button type="button" class="btn btn-danger" onclick="deleteData(<?= $order['id'] ?>)">Delete Order</button>
    </div> 
</div>

<!-- Paging controls -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Order pagination">
  <ul class="pagination justify-content-center">
    <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
      <a class="page-link" href="?page=orders&page_no=<?= $page-1 ?>&limit=<?= $limit ?>" tabindex="-1">Previous</a>
    </li>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <li class="page-item<?= $i == $page ? ' active' : '' ?>">
        <a class="page-link" href="?page=orders&page_no=<?= $i ?>&limit=<?= $limit ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
    <li class="page-item<?= $page >= $total_pages ? ' disabled' : '' ?>">
      <a class="page-link" href="?page=orders&page_no=<?= $page+1 ?>&limit=<?= $limit ?>">Next</a>
    </li>
  </ul>
</nav>
<?php endif; ?>
</div>
<script>
function checkPoItmes() {
    const checkedRows = document.querySelectorAll('input[name="poitem[]"]:checked');
    if (checkedRows.length === 0) {
        alert("Please select at least one order to create a Purchase Order.");
        event.preventDefault(); // Prevent form submission
        return false;
    }
    return true; // Allow form submission if at least one item is checked
}
function deleteData(id) {
    if (confirm('Are you sure you want to delete this order?')) {
        fetch('?page=orders&action=delete', {       
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })  
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                document.querySelector(`tr[data-id="${id}"]`).remove();
            } else {    
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the order.');
        });

    }
  }
// Popup for order detail
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.order-detail-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const order = JSON.parse(this.getAttribute('data-order'));
            let html = `
                <p><strong>Order Number:</strong> ${order.order_number}</p>
                <p><strong>Title:</strong> ${order.title}</p>
                <p><strong>Item Code:</strong> ${order.item_code}</p>
                <p><strong>Size:</strong> ${order.size}</p>
                <p><strong>Color:</strong> ${order.color}</p>
                <p><strong>Marketplace Vendor:</strong> ${order.marketplace_vendor}</p>
                <p><strong>Quantity:</strong> ${order.quantity}</p>
                <p><strong>Status:</strong> ${order.status}</p>
            `;
            document.getElementById('orderDetailOffcanvasBody').innerHTML = html;
            var offcanvas = new bootstrap.Offcanvas(document.getElementById('orderDetailOffcanvas'));
            offcanvas.show();
        });
    });
});


// document.addEventListener('DOMContentLoaded', function() {
//     document.querySelectorAll('.order-detail-link').forEach(function(link) {
//         link.addEventListener('click', function(e) {
//             e.preventDefault();
//             const order = JSON.parse(this.getAttribute('data-order'));
//             let html = `
//                 <p><strong>Order Number:</strong> ${order.order_number}</p>
//                 <p><strong>Title:</strong> ${order.title}</p>
//                 <p><strong>Item Code:</strong> ${order.item_code}</p>
//                 <p><strong>Size:</strong> ${order.size}</p>
//                 <p><strong>Color:</strong> ${order.color}</p>
//                 <p><strong>Marketplace Vendor:</strong> ${order.marketplace_vendor}</p>
//                 <p><strong>Quantity:</strong> ${order.quantity}</p>
//                 <p><strong>Status:</strong> ${order.status}</p>
//             `;
//             document.getElementById('orderDetailModalBody').innerHTML = html;
//             var modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
//             modal.show();
//         });
//     });
// });
</script>
