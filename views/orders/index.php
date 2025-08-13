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
                    <div class="dropdown ms-auto btn border-light-grey" >
                        <span class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            50 Orders per page
                        </span>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">10 Orders per page</a></li>
                            <li><a class="dropdown-item" href="#">20 Orders per page</a></li>
                            <li><a class="dropdown-item" href="#">50 Orders per page</a></li>
                            <li><a class="dropdown-item" href="#">100 Orders per page</a></li>
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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Orders</h2>
    <!-- <a href="index.php?page=orders&action=add" class="btn btn-primary">Add New Order</a> -->
</div>
<table class="table table-bordered">
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
    </th>
  </thead>
  <tbody>
    <?php   
    if (!empty($data['orders'])) {
        foreach ($data['orders'] as $order) { 
    ?>  
    <tr data-id="<?= $order['id'] ?>">
        <td><?= $order['id'] ?></td>
        <td><?= htmlspecialchars($order['order_number']) ?></td>
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
    <script>
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
</script>
    <?php
        }
    }
    ?>
  </tbody>
</table>
</div>