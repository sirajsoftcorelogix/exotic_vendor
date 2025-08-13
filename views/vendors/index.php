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
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Vendors</h2>
    <a href="index.php?page=vendors&action=add" class="btn btn-primary">Add New Vendor</a>
</div>
<div id="addUserMsg" style="margin-top:10px;"> </div>
<table class="table table-bordered">
  <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">Contact Name</th>
        <th scope="col">Vendor Email</th>
        <th scope="col">Vendor Phone</th>
        <th scope="col">Address</th>
        <th scope="col">Active</th>
        <th scope="col">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php
    if (!empty($data['vendors'])) {
        foreach ($data['vendors'] as $vendor) {
    ?>
    <tr data-id="<?= $vendor['id'] ?>">
    <td><?= $vendor['id'] ?></td>
    <td><?= htmlspecialchars($vendor['contact_name']) ?></td>
    <td><?= htmlspecialchars($vendor['vendor_email']) ?></td>
    <td><?= htmlspecialchars($vendor['vendor_phone']) ?></td>

    <td><?= htmlspecialchars($vendor['address']) ?></td>
    <td><?= $vendor['is_active'] == 1 ? 'Yes' : 'No' ?></td>
    <td>
        <a href="index.php?page=vendors&action=update&id=<?= $vendor['id'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fa fa-edit"></i></a>
        <button class="btn btn-sm btn-danger mt-0" onclick="deleteData(<?= $vendor['id'] ?>)" title="Delete"><i class="fa fa-trash"></i></button>
    </td>
    </tr>
    
    <script>
    function deleteData(id) {
    const msgDiv = document.getElementById('addUserMsg');
    if (confirm('Are you sure you want to delete this vendor?')) {
        fetch('?page=vendors&action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })  
        .then(response => response.json())  
        .then(data => {
            if (data.success) { 
                //alert(data.message);
                msgDiv.textContent = data.message;
                msgDiv.style.color = data.success ? 'green' : 'red';
                document.querySelector(`tr[data-id="${id}"]`).remove();
            } else {
                //alert(data.message); 
                msgDiv.textContent = data.message;
                msgDiv.style.color = data.success ? 'green' : 'red';   
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the vendor.');
        });
    }
}
</script>
    <?php
        }  
    } else {
    ?>
    <tr><td colspan="7" class="text-center">No vendors found.</td></tr>
    <?php }
    ?>
  </tbody>
</table>





        
