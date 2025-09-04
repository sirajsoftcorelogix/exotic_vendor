<div class="container">
<?php
if (isset($data['user']['id'])) {
?>
<div class="h-full w-full overflow-y-auto">
    <div class="p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Edit User</h2>
        <form id="addUserForm" class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md border border-gray-200">
            <input type="hidden" name="id" value="<?= $data['user']['id'] ?>">
            <div id="addUserMsg"></div>
            <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                <div>
                    <label for="name" class="text-sm font-medium text-gray-700">Name:</label>
                    <input type="text" id="name" name="name" class="form-input w-full mt-1" required value="<?= htmlspecialchars($data['user']['name']) ?>">
                </div>

                <div>
                    <label for="email" class="text-sm font-medium text-gray-700">Email:</label>
                    <input type="email" id="email" name="email" class="form-input w-full mt-1" required value="<?= htmlspecialchars($data['user']['email']) ?>">
                </div>

                <div>
                    <label for="phone" class="text-sm font-medium text-gray-700">Phone:</label>
                    <input type="number" id="phone" name="phone" class="form-input w-full mt-1" required value="<?= htmlspecialchars($data['user']['phone']) ?>">
                </div>

                <div>
                    <label for="password" class="text-sm font-medium text-gray-700">Password:</label>
                    <input type="password" id="password" name="password" class="form-input w-full mt-1">
                </div>

                <div>
                    <label for="role" class="text-sm font-medium text-gray-700">Role:</label>
                    <select id="role" name="role" class="form-select w-full mt-1" required>
                        <option value="admin" <?= $data['user']['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="user" <?= $data['user']['role'] == 'user' ? 'selected' : '' ?>>User</option>
                    </select>
                </div>

                <div>
                    <label for="is_active" class="text-sm font-medium text-gray-700">Active:</label>
                    <select id="is_active" name="is_active" class="form-select w-full mt-1" required>
                        <option value="1" <?= $data['user']['is_active']==1 ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= $data['user']['is_active']==0 ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-center items-center gap-4 pt-6 border-t">
                <button type="button" id="cancel-vendor-btn" class="action-btn cancel-btn" onclick="window.history.back(-1)">Back</button>
                <button type="submit" class="action-btn save-btn">Update</button>
            </div>
        </form>
    </div>
</div>
<script>
  document.getElementById('addUserForm').onsubmit = function(e) {
    e.preventDefault();
    var form = new FormData(this);
    var params = new URLSearchParams(form).toString();
    fetch('?page=users&action=addUser', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: params
    })
    .then(r => r.json())
    .then(data => {
      var msgBox = document.getElementById('addUserMsg');
      msgBox.innerHTML = '';
      if (data.success) {
        msgBox.innerHTML = `<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">
                            ✅ ${data.message}
        </div>`;
      } else {
        msgBox.innerHTML = `<div style="color: red; padding: 10px; background: #ffe0e0; border: 1px solid #a00;">
            ❌ ${data.message}
        </div>`;
      }
      setTimeout(() => {
        window.location.href = '?page=users&action=list';
      }, 1000); // redirect after 1 sec
    });
  };
</script>
<?php 
} else {
  echo "<h3>User not found.</h3>";
}
?>
</div>