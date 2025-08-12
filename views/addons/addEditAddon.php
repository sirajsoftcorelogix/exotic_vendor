 <div class="" id="dataModal" tabindex="-1" aria-labelledby="datatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="dataForm" method="POST" action="index.php?page=products&action=addEditAddon"  >
			<input type="hidden" name="id" id="id">
			<div class="modal-header mb-5">
				<h5 class="modal-title" id="datatModalLabel">Add New Addons</h5>
			</div>
			<?php if (isset($data['message'])): ?>
				<div class="alert alert-<?= $data['message']['success'] ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
					<?= $data['message']['success'] ? $data['message']['message'] : $data['message']['error']; ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>
			<?php endif; ?>
			<div class="modal-body row g-3">
				<div class="col-md-6">
					<label class="form-label">Addon Name <span class="text-danger">*</span></label>
					<input type="text" class="form-control" id="addon_name" name="addon_name" required>
				</div>
				<div class="col-md-6 mb-3">
					<label for="active" class="form-label d-block">Active</label>
					<select name="active" id="active" class="form-control w-100" required>
						<option value="1">Yes</option>
						<option value="0">No</option>
					</select>
				</div>
				<div class="col-md-12">
					<label class="form-label">Description</label>
					<textarea class="form-control"  id="description" name="description"></textarea>
				</div>
			</div>
			<div class="mt-5">
				<button type="button" class="btn btn-secondary pr-4" data-bs-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-success" id="savedataBtn">Save Addon</button>
			</div>
        </form>
      </div>
    </div>
  </div>
  <script>
    // Parse tenant JSON data from PHP
    const addonData = <?= $data['addon'] ? $data['addon'] : 'null' ?>;
	//console.log('Tenant JSON:', addonData);
    window.onload = function() {
         try {
			if (addonData) {
				document.getElementById('id').value = addonData.id || '';
				document.getElementById('addon_name').value = addonData.addon_name || '';
				document.getElementById('description').value = addonData.description || '';
				document.getElementById('active').value = addonData.active ?? '';
			}
		} catch (e) {
			console.error("Error filling form:", e);
		}
    }
</script> 