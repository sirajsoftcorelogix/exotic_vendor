<?php 
global $domain, $root_path;
?>
<!-- <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Exotic India Vendor Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
  <link rel="icon" href="<?php //echo $root_path;?>/images/favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="<?php //echo $root_path;?>/style/login.css">
</head>
<body> -->
  
  <div class="login container">
    <div class="left-panel">
      <img src="<?php echo $domain;?>/images/yantra.png" alt="Yantra Image">
    </div>
    <div class="right-panel">
      <div>
        <img src="<?php echo $domain;?>/images/logo.png" alt="Exotic India Logo" class="logo">
      </div>      
      <h2>Vendor Portal</h2>
      <p class="tagline">Flurish Together. Grow Stronger.</p>
      <div class="login-form">
        <form id="loginForm">
          <label for="login">Login Name (Email or Phone):</label>
          <input type="text" id="login" name="login" placeholder="Enter email or phone">

          <label for="password">Password:</label>
          <input type="password" id="password" name="password" placeholder="Enter password">

          <button type="submit">Login</button>
        </form>
        <div class="links">
          <a href="<?php echo $domain; ?>/?page=users&action=forgotPassword">Forgot Password?</a>
        </div>
        <div id="loginError" style="color:red;margin-top:10px;"></div>
      
<!-- </body>
</html> -->
<script>
  document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var login = document.getElementById('login').value.trim();
    var password = document.getElementById('password').value;
    var errorDiv = document.getElementById('loginError');
    errorDiv.textContent = '';
    fetch("<?php echo $domain.'/?page=users&action=loginProcess';?>", {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'login=' + encodeURIComponent(login) + '&password=' + encodeURIComponent(password)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        //alert('Login successful!');
        window.location.href = "<?php echo $domain; ?>/?page=dashboard&action=list";
      } else {
        //alert('Login failed: ' + data.message);
        errorDiv.textContent = data.message;
      }
    })
    .catch(() => {
      errorDiv.textContent = 'Login failed. Please try again.';
    });
  });
</script>
    </div>
    
  </div>
</div>

