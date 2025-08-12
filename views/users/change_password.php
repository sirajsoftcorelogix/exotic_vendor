<form id="changePasswordForm">
  <label>Current Password:</label>
  <input type="password" name="current_password" required>
  <label>New Password:</label>
  <input type="password" name="new_password" required>
  <button type="submit">Change Password</button>
</form>
<div id="changePasswordError"></div>
<script>

document.getElementById('changePasswordForm').onsubmit = function(e) {
  e.preventDefault();
  var form = new FormData(this);
  fetch('?page=users&action=changePasswordProcess', {
    method: 'POST',
    body: new URLSearchParams(form)
  })
  .then(r => r.json())
  .then(data => {
    document.getElementById('changePasswordError').textContent = data.message;
  });
    .catch(() => {  
    document.getElementById('changePasswordError').textContent = 'An error occurred. Please try again.';
  });
};
</script>