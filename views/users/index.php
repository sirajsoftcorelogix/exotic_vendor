<div class="container">
    <h2 class="">Users</h2>
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
			foreach($data['users'] as $item):
		?> 
		<tr>
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
        <?php endforeach; ?>
			<?php }else{ ?>
			<tr>
				<td colspan="7" class="text-center">No purchase orders found.</td>
			</tr>
			<?php } ?>
  </tbody>
</table>