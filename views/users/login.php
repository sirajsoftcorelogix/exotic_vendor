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
            <h2 class="portal-title mb-1">Exotic India Central</h2>
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
                <div id="loginError" style="color:red; margin-bottom:10px;"></div>
                <div id="loginSuccess" style="color:green; margin-bottom:10px;"></div>
                
                <!-- Email Input -->
                <div class="relative mb-4" id="emailContainer">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fa-regular fa-envelope text-gray-400"></i>
                    </span>
                    <input type="email" id="login" name="login" placeholder="Email Address" class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 transition duration-300" required>
                </div>

                <!-- OTP Input -->
                <div class="relative mb-6" id="otpContainer" style="display: none;">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fa-solid fa-key text-gray-400"></i>
                    </span>
                    <input type="text" id="otp" name="otp" placeholder="Enter OTP" class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 transition duration-300">
                </div>

                <!-- Action Button -->
                <button type="button" id="sendOtpBtn" class="w-full bg-[#D06706] text-white font-bold py-3 rounded-lg shadow-lg hover:bg-orange-700 transition duration-300">
                    Send OTP
                </button>
                
                <!-- Resend OTP Timer -->
                <div id="resendTimerContainer" style="display: none; text-align: center; margin-top: 10px;">
                    <p class="text-sm text-gray-600">Resend OTP in <span id="timerCount">10:00</span></p>
                </div>
                
                <button type="submit" id="loginBtn" class="w-full bg-[#D06706] text-white font-bold py-3 rounded-lg shadow-lg hover:bg-orange-700 transition duration-300" style="display: none;">
                    Login
                </button>
            </form>
        </div>
    </div>
</div>
<script>
    let otpTimer = null;
    let remainingTime = 0;

    function startOtpTimer() {
        remainingTime = 600; // 10 minutes in seconds
        const timerDisplay = document.getElementById('timerCount');
        const timerContainer = document.getElementById('resendTimerContainer');
        const sendOtpBtn = document.getElementById('sendOtpBtn');

        sendOtpBtn.disabled = true;
        sendOtpBtn.style.opacity = '0.5';
        timerContainer.style.display = 'block';

        otpTimer = setInterval(() => {
            remainingTime--;
            const minutes = Math.floor(remainingTime / 60);
            const seconds = remainingTime % 60;
            timerDisplay.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

            if (remainingTime <= 0) {
                clearInterval(otpTimer);
                sendOtpBtn.disabled = false;
                sendOtpBtn.style.opacity = '1';
                timerContainer.style.display = 'none';
                sendOtpBtn.textContent = 'Resend OTP';
                sendOtpBtn.style.display = 'block';
                document.getElementById('loginBtn').style.display = 'none';
            }
        }, 1000);
    }

    document.getElementById('sendOtpBtn').addEventListener('click', function(e) {
        var login = document.getElementById('login').value.trim();
        var errorDiv = document.getElementById('loginError');
        var successDiv = document.getElementById('loginSuccess');
        var btn = document.getElementById('sendOtpBtn');
        errorDiv.textContent = '';
        successDiv.textContent = '';
        
        if (!login) {
            errorDiv.textContent = 'Please enter your email.';
            return;
        }
        
        btn.textContent = 'Sending...';
        btn.disabled = true;

        var controller = new AbortController();
        var timeoutId = setTimeout(function() { controller.abort(); }, 30000);

        fetch('?page=users&action=sendLoginOtp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'login=' + encodeURIComponent(login),
                signal: controller.signal
            })
            .then(function(response) {
                clearTimeout(timeoutId);
                return response.text().then(function(text) {
                    var data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error(text ? text.substring(0, 200) : 'Invalid server response.');
                    }
                    if (!response.ok && data && (data.smtp_error || data.message)) {
                        throw new Error(data.smtp_error || data.message);
                    }
                    return data;
                });
            })
            .then(data => {
                if (data.success) {
                    successDiv.textContent = data.message || 'OTP sent to your email.';
                    document.getElementById('login').readOnly = true;
                    document.getElementById('login').classList.add('bg-gray-100');
                    document.getElementById('sendOtpBtn').style.display = 'none';
                    document.getElementById('otpContainer').style.display = 'block';
                    document.getElementById('loginBtn').style.display = 'block';
                    document.getElementById('otp').required = true;
                    startOtpTimer();
                } else {
                    btn.textContent = 'Send OTP';
                    btn.disabled = false;
                    errorDiv.textContent = data.smtp_error || data.message || 'Could not send OTP.';
                }
            })
            .catch(function(err) {
                clearTimeout(timeoutId);
                btn.textContent = 'Send OTP';
                btn.disabled = false;
                if (err.name === 'AbortError') {
                    errorDiv.textContent = 'Request timed out. The mail server may be unreachable from this environment.';
                } else {
                    errorDiv.textContent = err.message || 'Failed to send OTP. Please try again.';
                }
            });
    });

    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var login = document.getElementById('login').value.trim();
        var otp = document.getElementById('otp').value.trim();
        var errorDiv = document.getElementById('loginError');
        var successDiv = document.getElementById('loginSuccess');
        errorDiv.textContent = '';
        successDiv.textContent = '';

        var btn = document.getElementById('loginBtn');
        btn.textContent = 'Verifying...';
        btn.disabled = true;

        fetch('?page=users&action=loginProcess', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'login=' + encodeURIComponent(login) + '&otp=' + encodeURIComponent(otp)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (otpTimer) clearInterval(otpTimer);
                    successDiv.textContent = 'Login successful! Redirecting...';
                    var redirect = <?php echo json_encode(isset($_SESSION['redirect_after_login']) && $_SESSION['redirect_after_login'] ? $_SESSION['redirect_after_login'] : '?page=orders&action=list'); ?>;
                    window.location.href = redirect;
                } else {
                    btn.textContent = 'Login';
                    btn.disabled = false;
                    errorDiv.textContent = data.message;
                }
            })
            .catch(() => {
                btn.textContent = 'Login';
                btn.disabled = false;
                errorDiv.textContent = 'Login failed. Please try again.';
            });
    });
</script>