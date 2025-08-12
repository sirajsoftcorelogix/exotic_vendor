<?php 
global $domain, $root_path;
?>
<div class="login container">
<div class="right-panel">
    <div>
    <img src="<?php echo $domain;?>/images/logo.png" alt="Exotic India Logo" class="logo">
    </div> 
<h2>Forgot Password</h2>
<div class="">
<form id="forgotPasswordForm">
  <label for="login">Email or Phone:</label>
  <input type="text" id="login" name="login" required>
  <button type="submit">Send Reset Link</button>
</form>
<div id="forgotPasswordMsg" style="margin-top:10px;"></div>
</div>
<script>
document.getElementById('forgotPasswordForm').onsubmit = function(e) {
  e.preventDefault();
  var login = document.getElementById('login').value.trim();
  var msgDiv = document.getElementById('forgotPasswordMsg');
  msgDiv.textContent = '';
  fetch('?page=users&action=sendResetLink', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'login=' + encodeURIComponent(login)
  })
  .then(r => r.json())
  .then(data => {
    msgDiv.textContent = data.message;
    msgDiv.style.color = data.success ? 'green' : 'red';
  });
};
</script>
</div>
</div>