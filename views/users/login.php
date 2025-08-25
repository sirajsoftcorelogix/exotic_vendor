<?php 
global $domain, $root_path;
?>
  
<div class="flex flex-col md:flex-row w-full min-h-screen">  
 <!-- Left Panel -->
    <div class="w-full md:w-3/5 p-8 lg:p-12 text-white flex flex-col justify-center left-panel-bg left-panel-gradient">
        <div class="relative z-10 max-w-md mx-auto">
            <!-- Logo and Brand Name -->
            <div class="mb-4">
                <div class="flex items-center space-x-4">
                    <!-- Logo image updated -->
                    <img src="<?= $domain ?>/images/logo.png" alt="Company Logo">
                </div>
            </div>

            <!-- Main Content -->
            <h2 class="portal-title mb-1">Vendor Onboarding Portal</h2>
            <p class="portal-subtitle text-orange-100 mb-3">Flurish Together. Grow Stronger.</p>
            <button class="bg-white text-black w-[110px] h-[40px] rounded-[5px] shadow-md hover:bg-gray-100 transition duration-300 flex items-center justify-center read-more-btn">
                Read More
            </button>
        </div>
    </div>

    <!-- Right Panel (Login Form) -->
    <div class="w-full md:w-2/5 p-8 lg:p-12 bg-white flex flex-col justify-center">
        <div class="max-w-sm mx-auto w-full">
            <h3 class="text-2xl font-bold text-gray-800 mb-1">Hello Again!</h3>
            <p class="text-gray-500 mb-8">Welcome Back</p>

            <form id="loginForm">
                <!-- Email Input -->
                <div class="relative mb-4">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fa-regular fa-envelope text-gray-400"></i>
                        </span>
                    <input type="email" id="login" name="login" placeholder="Email Address" class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 transition duration-300">
                </div>

                <!-- Password Input -->
                <div class="relative mb-6">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fa-solid fa-lock text-gray-400"></i>
                        </span>
                    <input type="password" id="password" name="password" placeholder="Password" class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 transition duration-300">
                </div>

                <!-- Login Button -->
                <button type="submit" class="w-full bg-[#D06706] text-white font-bold py-3 rounded-lg shadow-lg hover:bg-orange-700 transition duration-300">
                    Login
                </button>

                <!-- Forgot Password Link -->
                <div class="text-center mt-3">                  
                    <a href="<?php echo $domain; ?>/?page=users&action=forgotPassword" class="text-sm text-[#C2C2C2] hover:text-orange-600 transition duration-300">Forgot Password</a>
                </div>
                <div id="loginError" style="color:red;margin-top:10px;"></div>
            </form>
        </div>
    </div>
</div>
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