<div class="container">

<?php 
if (isset($data['user']['id'])) {
  //echo "<h3>Edit User: " . htmlspecialchars($data['user']['name']) . "</h3>";
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
<?php 
}/*else {?>
<h3>Add New User</h3> 
<form id="addUserForm">
  <div class="row mb-3">
    <div class="col-md-6">
      <label for="inputEmail4" class="form-label">Email</label> 
      <input type="email" id="email" name="email" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label for="inputName4" class="form-label">Name</label>
      <input type="text" id="name" name="name" class="form-control" required>
    </div>
  </div>
  <div class="row mb-3">
    <div class="col-md-6">
      <label for="inputPhone4" class="form-label">Phone</label>
      <input type="text" id="phone" name="phone" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label for="inputPassword4" class="form-label">Password</label>
      <input type="password" id="password" name="password" class="form-control" required>
    </div>
  </div>
  <div class="row mb-3">  
    <div class="col-md-6">
      <label for="inputRole4" class="form-label">Role</label>
      <select id="role" name="role" class="form-select">
        <option value="admin">Admin</option>
        <option value="user">User</option>
      </select>
    </div>
    <div class="col-md-6">
      <label for="inputActive4" class="form-label">Active</label>
      <select id="is_active" name="is_active" class="form-select">
        <option value="1">Yes</option>
        <option value="0">No</option>
      </select>
    </div>
  </div>  
  <input type="hidden" name="action" value="addPost">
  <input type="hidden" name="page" value="users"> 

  <button type="submit" class=" ">Add User</button>
</form>
<div id="addUserMsg" style="margin-top:10px;"> </div>
<?php }*/ ?>
<script>
  document.getElementById('addUserForm').onsubmit = function(e) {
    e.preventDefault();
    var form = new FormData(this);
    var params = new URLSearchParams(form).toString();
    var msgDiv = document.getElementById('addUserMsg');
    msgDiv.textContent = '';
    fetch('?page=users&action=addUser', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: params
    })
    .then(r => r.json())
    .then(data => {
      console.log("Success:", data);
      msgDiv.textContent = data.message;
      msgDiv.style.color = data.success ? 'green' : 'red';
      if (data.success) { 
          setTimeout(() => {
            location.reload();
          }, 1000); // refresh after 1 sec
      }
    });
  };
</script>
</div>