
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
    <h2>Users</h2>
    <a href="index.php?page=users&action=add" class="btn btn-primary">Add New User</a>
</div>
<table class="table table-bordered">
  <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">Name</th>
      <th scope="col">Email</th>
      <th scope="col">Phone</th>
      <th scope="col">Role</th>
      <th scope="col">Active</th>
    </tr>
  </thead>
  <tbody>
  <?php //print_r($data);
  if (!empty($data)){
    $i=0;
  foreach($data['users'] as $item)
  { 
  ?>  
   <tr data-id="<?= $item['id'] ?>" >
      <td><?= $item['id'] ?></td>
      <td><?= htmlspecialchars($item['name']) ?></td>      
      <td><?= htmlspecialchars($item['email']) ?></td>
      <td><?= htmlspecialchars($item['phone']) ?></td>
      <td><?= htmlspecialchars($item['role']) ?></td>
      <td><?= $item['is_active'] == 1 ? 'Yes' : 'No' ?></td>
      <td>
          <a href="index.php?page=users&action=update&id=<?= $item['id'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fa fa-edit"></i></a>
          <button class="btn btn-sm btn-danger mt-0" onclick="deleteData(<?= $item['id'] ?>)" title="Delete"><i class="fa fa-trash"></i></button>
      </td>
  </tr>
<script>
function deleteData(id) {
    if (confirm('Are you sure you want to delete this user?')) {
        fetch('?page=users&action=delete', {  
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
            alert('An error occurred while deleting the user.');
        });
      
  }
}
</script>

<?php 
$i++;
} ?>
<?php }else{ ?>
<tr><td colspan="8" class="text-center">No item found.</td></tr>
<?php } ?>
   
  </tbody>
</table>
</div>