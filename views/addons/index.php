<?php
	$sort_by = $_GET['sort_by'] ?? 'id';
	$sort_order = $_GET['sort_order'] ?? 'desc';
	$search = $_GET['search'] ?? '';
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb" style="margin-bottom: 0;">
                <li><a href="index.php?page=addons&action=list"><h4>Addons</h4></a></li>
            </ol>
        </nav>
    </div>

    <div class="more-link" style="display: flex; align-items: center; gap: 8px;">
        <form action="index.php" method="get" class="d-flex">
            <div class="input-group">
                <input type="hidden" name="page" value="addons" />
                <input type="hidden" name="action" value="list" />
                <input class="form-control" type="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search">
                <button class="btn btn-primary px-3" type="submit">
                    <i class="fa fa-search"></i>
                </button>
            </div>
        </form>
        <a href="index.php?page=products&action=addEditAddon" class="btn btn-primary text-white" style="text-decoration:none;" >
            <i class="fa fa-plus me-1"></i> Add
        </a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th><?= sort_link('id', 'ID', $sort_by, $sort_order, 'addons', $search) ?></th>
                <th><?= sort_link('addon_name', 'Addon Name', $sort_by, $sort_order, 'addons', $search) ?></th>
                <th><?= sort_link('description ', 'Description', $sort_by, $sort_order, 'addons', $search) ?></th>
                <th><?= sort_link('active', 'Active', $sort_by, $sort_order, 'addons', $search) ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php 
			$addons = $data['addons']['addons'];
			
			if (!empty($addons)){
				$i=0;
				foreach ($addons as $addon){
				//print_r($userdata);
			?>
				
                <tr data-id="<?= $addon['id'] ?>" data-addon_name="<?= htmlspecialchars($addon['addon_name']) ?>" data-description="<?= htmlspecialchars($addon['description']) ?>" " data-active="<?= htmlspecialchars($addon['active']) ?>">
                    <td><?= $addon['id'] ?></td>
                    <td><?= htmlspecialchars($addon['addon_name']) ?></td>
                    <td><?= htmlspecialchars($addon['description']) ?></td>
                    <td><?= $addon['active'] == 1 ? 'Yes' : 'No' ?></td>
                    <td>
                        <a href="index.php?page=products&action=addEditAddon&id=<?= $addon['id'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fa fa-edit"></i></a>
						<button class="btn btn-sm btn-danger" onclick="deleteData(<?= $addon['id'] ?>)" title="Delete"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
				<?php 
						$i++;
					} ?>
			<?php }else{ ?>
            <tr><td colspan="8" class="text-center">No addon found.</td></tr>
			<?php } ?>
        </tbody>
    </table>
	<?php if ($data['total_pages'] > 1): ?>
		<nav aria-label="Page navigation" class="mt-3">
		<ul class="pagination justify-content-center">
		<!-- Previous Button -->
		  <li class="page-item <?= $page_no <= 1 ? 'disabled' : '' ?>">
			<a class="page-link" href="?page=products&action=addEditAddon&page_no=<?= $page_no - 1 ?>&search=<?= urlencode($search) ?>&sort_by=<?= $sort_by ?>&sort_order=<?= $sort_order ?>" aria-label="Previous">
			  <span aria-hidden="true">&laquo;</span>
			</a>
		  </li>
		<?php for ($i = 1; $i <= $total_pages; $i++): ?>
			<li class="page-item <?= $i == $page_no ? 'active' : '' ?>">
			<a class="page-link" href="index.php?page=products&action=addEditAddon&page_no=<?= $i ?>" 
			   class="<?= ($page_no == $i) ? 'active' : '' ?>">
			   <?= $i ?>
			</a>
			</li>
		<?php endfor; ?>
			 <!-- Next Button -->
		  <li class="page-item <?= $page_no >= $total_pages ? 'disabled' : '' ?>">
			<a class="page-link" href="?page=products&action=addEditAddon&page_no=<?= $page_no + 1 ?>&search=<?= urlencode($search) ?>&sort_by=<?= $sort_by ?>&sort_order=<?= $sort_order ?>" aria-label="Next">
			  <span aria-hidden="true">&raquo;</span>
			</a>
		  </li>
			</ul>
		</nav>
	<?php endif; ?>
</div>
<script>
	function deleteData(dataId) {
		if (!confirm("Are you sure you want to delete this addon?")) return;
		try {
			fetch('index.php?page=products&action=delete_addons', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: `id=${encodeURIComponent(dataId)}`
			})
			.then(response => response.json())
			.then(result => {
				if (result.success) {
					// Remove row from DOM
					document.querySelector(`tr[data-id='${dataId}']`).remove();
					alert(result.message);
				} else {
					alert(result.message || "Failed to delete.");
				}
			})
		} catch (error) {
			//console.error('Fetch error:', error);
			alert('An error occurred while submitting the form. Check console for details.');
		}
	}
</script>