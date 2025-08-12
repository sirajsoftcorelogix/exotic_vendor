<div class="container">

<?php 
if (isset($data['user']['id'])) {
  echo "<h3>Edit User: " . htmlspecialchars($data['user']['name']) . "</h3>";
?>
  
<form id="addUserForm">
  <div class="row mb-3">
    <div class="col-md-6">
      <label for="inputEmail4" class="form-label">Email</label>
      <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($data['user']['email']) ?>" required>
    </div>
    <div class="col-md-6">
      <label for="inputName4" class="form-label">Name</label>
      <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($data['user']['name']) ?>" required>
    </div>
  </div>
  <div class="row mb-3">
    <div class="col-md-6">
      <label for="inputPhone4" class="form-label">Phone</label>
      <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($data['user']['phone']) ?>" required>
    </div>
    <div class="col-md-6">
      <label for="inputPassword4" class="form-label">Password</label>
      <input type="password" id="password" name="password" class="form-control" placeholder="Enter new password (leave blank to keep current)">
    </div>
  </div>
  <input type="hidden" name="id" value="<?= $data['user']['id'] ?>">
  <button type="submit" class="btn btn-secondary">Update User</button>
</form>

<?php 
}else {?>
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
  <button type="submit" class=" ">Add User</button>
</form>
<?php } ?>
<div id="addUserMsg" style="margin-top:10px;"></div>
<script>
document.getElementById('addUserForm').onsubmit = function(e) {
  e.preventDefault();
  var form = new FormData(this);
  var params = new URLSearchParams(form).toString();
  var msgDiv = document.getElementById('addUserMsg');
  msgDiv.textContent = '';
  fetch('?page=users&action=addPost', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: params
  })
  .then(r => r.json())
  .then(data => {
    msgDiv.textContent = data.message;
    msgDiv.style.color = data.success ? 'green' : 'red';
    if (data.success) document.getElementById('addUserForm').reset();
  });
};
</script>
</div>