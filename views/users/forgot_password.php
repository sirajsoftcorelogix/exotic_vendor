<?php 
global $domain, $root_path;
?>


<div class="flex flex-col md:flex-row w-full min-h-screen">
  <!-- Left Panel -->
  <div class="w-full md:w-3/5 p-8 lg:p-12 text-white flex-col items-center justify-center left-panel-gradient left-panel-bg relative hidden md:flex">

      <!-- Decorative Large Circles -->
        <!-- <div class="decorative-circle" style="width: 461.33px; height: 461.33px; top: 490.69px; left: -166.08px; border-width: 1.5px;"></div>
        <div class="decorative-circle" style="width: 461.33px; height: 461.33px; top: 506.63px; left: -105.69px; border-width: 1.5px;"></div> -->

      <!-- Decorative Small Circle -->
      <div class="absolute border-white border-opacity-30 rounded-full" style="width: 24px; height: 24px; top: 501px; left: 9px; border-width: 3px;"></div>

      <!-- Decorative SVGs -->
      <div class="absolute" style="width: 157px; height: 42px; top: 10px; left: 10px;">
          <svg viewBox="0 0 271 75" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M1 58.0725C6.35323 62.4967 14.3831 71.677 27.7662 70.0179C51.8557 67.0316 87.5446 19.5818 73.2687 14.2725C57.209 8.29976 66.5771 74 96.0199 74C125.463 74 148.214 1 174.98 1C201.746 1 181.672 48.7818 208.438 50.1091C235.204 51.4364 229.851 15.6 270 22.2364" stroke="rgba(255, 255, 255, 0.3)" stroke-width="2"/>
          </svg>
      </div>
      <div class="absolute" style="width: 61.58px; height: 150.86px; top: 40px; right: 40px;">
          <svg viewBox="0 0 62 151" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="8.2196" height="8.2196" transform="matrix(0.696258 0.717792 -0.696258 0.717792 5.99072 0)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(0.696258 0.717792 -0.696258 0.717792 5.99072 38.9376)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(0.696258 0.717792 -0.696258 0.717792 5.99072 76.4849)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(0.696258 0.717792 -0.696258 0.717792 5.99072 115.423)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(0.696258 0.717792 -0.696258 0.717792 56.1321 0)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(0.696258 0.717792 -0.696258 0.717792 56.1321 38.9376)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(0.696258 0.717792 -0.696258 0.717792 56.1321 76.4849)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(0.696258 0.717792 -0.696258 0.717792 56.1321 115.423)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(0.696258 0.717792 -0.696258 0.717792 30.5027 23.641)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(0.696258 0.717792 -0.696258 0.717792 30.5027 62.5786)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(0.696258 0.717792 -0.696258 0.717792 30.5027 100.126)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(0.696258 0.717792 -0.696258 0.717792 30.5027 139.063)" fill="white" fill-opacity="0.3"/>
          </svg>
      </div>
      <div class="absolute" style="width: 150.86px; height: 61.58px; top: 595px; left: 27px;">
          <svg class="w-full h-full" viewBox="0 0 151 62" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="8.2196" height="8.2196" transform="matrix(-0.717792 0.696258 -0.717792 -0.696258 150.863 5.72302)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(-0.717792 0.696258 -0.717792 -0.696258 111.926 5.72302)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(-0.717792 0.696258 -0.717792 -0.696258 74.3784 5.72302)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(-0.717792 0.696258 -0.717792 -0.696258 35.4407 5.72302)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(-0.717792 0.696258 -0.717792 -0.696258 150.863 55.8645)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(-0.717792 0.696258 -0.717792 -0.696258 111.926 55.8645)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(-0.717792 0.696258 -0.717792 -0.696258 74.3784 55.8645)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(-0.717792 0.696258 -0.717792 -0.696258 35.4407 55.8645)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(-0.717792 0.696258 -0.717792 -0.696258 127.222 30.2349)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(-0.717792 0.696258 -0.717792 -0.696258 88.2847 30.2349)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(-0.717792 0.696258 -0.717792 -0.696258 50.7375 30.2349)" fill="white" fill-opacity="0.3"/>
              <rect width="8.2196" height="8.2196" transform="matrix(-0.717792 0.696258 -0.717792 -0.696258 11.7996 30.2349)" fill="white" fill-opacity="0.3"/>
          </svg>
      </div>
      <div class="absolute" style="width: 156px; height: 42px; bottom: 40px; right: 40px;">
          <svg viewBox="0 0 271 75" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M1 58.0725C6.35323 62.4967 14.3831 71.677 27.7662 70.0179C51.8557 67.0316 87.5446 19.5818 73.2687 14.2725C57.209 8.29976 66.5771 74 96.0199 74C125.463 74 148.214 1 174.98 1C201.746 1 181.672 48.7818 208.438 50.1091C235.204 51.4364 229.851 15.6 270 22.2364" stroke="rgba(207, 144, 84, 1)" stroke-width="2"/>
          </svg>
      </div>

      <!-- Main Illustration -->
      <div class="relative z-10 text-center">
          <img src="images/forgotpass.png" alt="Forgot password"/>
      </div>
  </div>

  <!-- Right Panel (Forgot Password Form) -->
  <div class="w-full md:w-2/5 p-8 lg:p-12 bg-white flex flex-col justify-center relative">
      <!-- Decorative elements -->
      <div class="absolute top-8 right-8 w-8 h-8 rounded-full border-2 border-gray-300"></div>

      <div class="max-w-sm mx-auto w-full relative pb-24">
          <div class="mb-8 text-center md:text-left">
              <div class="flex items-center justify-center md:justify-start space-x-2 mb-2">
                  <i class="fa-solid fa-lock text-2xl text-gray-800"></i>
                  <h3 class="text-2xl font-bold text-gray-800">Forgot Password</h3>
              </div>
              <p class="text-gray-500 text-sm">Enter your email for the verification process, we will send 6 digits code to your email.</p>
          </div>

          <form action="#" id="forgotPasswordForm">
              <div id="forgotPasswordMsg" style="margin-top:10px;"></div>
              <!-- Email Input -->
              <div class="relative mb-4">
                      <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                          <i class="fa-regular fa-envelope text-gray-400"></i>
                      </span>
                  <input required id="login" name="login" type="email" placeholder="Email Address" class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 transition duration-300">
              </div>

              <!-- CAPTCHA Input -->
              <div class="flex items-center space-x-4 mb-6">
                  <div class="relative flex-grow">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                              <i class="fa-solid fa-shield-halved text-gray-400"></i>
                          </span>
                      <input type="text" id="captchaInput" placeholder="Enter the text" class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 transition duration-300">
                  </div>
                  <div class="captcha-image1 flex items-center">
                      <span id="captchaToken">mkfxc</span>
                      <button type="button" onclick="updateCaptcha()" class="ml-2 text-gray-500 hover:text-orange-500">
                          <i class="fa-solid fa-arrows-rotate"></i>
                      </button>
                  </div>
              </div>
              <!-- Continue Button -->
              <button type="submit" class="w-full bg-[#D06706] text-white font-bold py-3 rounded-lg shadow-lg hover:bg-orange-700 transition duration-300">
                  Continue
              </button>
              <!-- Login Link -->
                <div class="text-center mt-3">                  
                    <a href="<?php echo $domain; ?>/?page=users&action=login" class="text-sm text-[#C2C2C2] hover:text-orange-600 transition duration-300">Login</a>
                </div>
          </form>
      </div>
      <!-- SVG squiggle-->
      <div class="absolute bottom-8 right-0 w-[269px] h-[73px]">
          <svg viewBox="0 0 271 75" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M1 58.0725C6.35323 62.4967 14.3831 71.677 27.7662 70.0179C51.8557 67.0316 87.5446 19.5818 73.2687 14.2725C57.209 8.29976 66.5771 74 96.0199 74C125.463 74 148.214 1 174.98 1C201.746 1 181.672 48.7818 208.438 50.1091C235.204 51.4364 229.851 15.6 270 22.2364" stroke="#424242" stroke-width="2"/>
          </svg>
      </div>
  </div>
</div>

<!-- Verification Popup -->
<div id="verificationPopup" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-white p-8 rounded-lg shadow-xl text-center max-w-md w-full m-4">
        <h2 class="text-2xl font-bold mb-2">Verification</h2>
        <p class="text-gray-500 mb-6">Enter your 6 digits code that you received on your email.</p>
        <div id="verifyPasswordMsg" style="margin-top:10px;"></div>
        <div class="flex justify-center space-x-2 mb-6" id="otp-inputs">
            <input type="text" maxlength="1" class="w-12 h-12 text-center text-xl border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400">
            <input type="text" maxlength="1" class="w-12 h-12 text-center text-xl border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400">
            <input type="text" maxlength="1" class="w-12 h-12 text-center text-xl border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400">
            <input type="text" maxlength="1" class="w-12 h-12 text-center text-xl border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400">
            <input type="text" maxlength="1" class="w-12 h-12 text-center text-xl border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400">
            <input type="text" maxlength="1" class="w-12 h-12 text-center text-xl border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400">
        </div>
        <div class="text-orange-500 font-semibold mb-6" id="timer"></div>
        <button id="continueButton" class="w-full bg-[#D06706] text-white font-bold py-3 rounded-lg shadow-lg hover:bg-orange-700 transition duration-300">
            Continue
        </button>
        <p class="text-gray-500 mt-4">If you didn't receive a code! <a href="#" id="resendLink" class="text-orange-500 font-semibold">Resend</a></p>
    </div>
</div>

<script>
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    const verificationPopup = document.getElementById('verificationPopup');
    const otpInputs = document.querySelectorAll('#otp-inputs input');
    const timerEl = document.getElementById('timer');
    const resendLink = document.getElementById('resendLink');
    let captchaVerified = false;

    let timerInterval;
    updateCaptcha();
    const captchaInput = document.getElementById('captchaInput');
    captchaInput.addEventListener('input', function() {
        const value = captchaInput.value.trim();
        if (value.length === 5) {            
            validateCaptcha(value);
        }
    });

    function updateCaptcha(){
      fetch('?page=users&action=updateCaptcha', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'token=' + encodeURIComponent(captchaToken)
      })
      .then(r => r.json())
      .then(data => {
          if (data.success) {
              document.getElementById('captchaToken').textContent = data.captcha;
          }
      });
    }
    function validateCaptcha(value) {
        fetch('?page=users&action=validateCaptcha', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'captcha=' + encodeURIComponent(value)
        })
        .then(r => r.json())
        .then(data => {
          var msgDiv = document.getElementById('forgotPasswordMsg');
            if (data.success) {
                msgDiv.textContent = 'Valid captcha.';
                captchaVerified = true;
                // Captcha is valid, proceed with form submission
            } else {                
                msgDiv.textContent = 'Invalid captcha.';
            }
          msgDiv.style.color = data.success ? 'green' : 'red';
        });
    }

    forgotPasswordForm.addEventListener('submit', function(e) {
      e.preventDefault();

      if (!captchaVerified) {
        var msgDiv = document.getElementById('forgotPasswordMsg');
        msgDiv.textContent = 'Please complete the captcha.';
        msgDiv.style.color = 'red';
        return;
      }

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
        if (data.success) {
          // Show verification popup
          verificationPopup.style.display = 'flex';
          startTimer();
          alert('For demo purpose only, your OTP is: ' + data.token); 
        }
      });
    });
    document.getElementById('continueButton').addEventListener('click', function() {
        var login = document.getElementById('login').value.trim();
        var msgDivVerify = document.getElementById('verifyPasswordMsg');
        msgDivVerify.textContent = '';
        let otp = '';
        otpInputs.forEach(input => {
            otp += input.value;
        });
        if (otp.length === 6) {
            // Here you would typically verify the OTP with the server
            // alert('OTP entered: ' + otp);
            fetch('?page=users&action=verifyResetToken', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'login=' + encodeURIComponent(login) + '&token=' + encodeURIComponent(otp)
            })
            .then(r => r.json())
            .then(data => {
              msgDivVerify.textContent = data.message;
              msgDivVerify.style.color = data.success ? 'green' : 'red';
              if (data.success) {
                  // Show verification popup
                  verificationPopup.style.display = 'none';
                  clearInterval(timerInterval);
                // Redirect to reset password page or show reset password form
                window.location.href = '?page=users&action=resetPassword&login=' + encodeURIComponent(login) +'&token=' + otp;      
              }else{
                //alert('Invalid OTP. Please try again.');
                msgDivVerify.textContent = 'Invalid OTP. Please try again.';
              }
            });
            
        } else {
            alert('Please enter the complete 6-digit code.');
        }
    });

    // Close popup when clicking outside of it
    verificationPopup.addEventListener('click', function(e) {
        if (e.target === verificationPopup) {
            verificationPopup.style.display = 'none';
            clearInterval(timerInterval);
        }
    });

    otpInputs.forEach((input, index) => {
        input.addEventListener('keyup', (e) => {
            const currentInput = input,
                nextInput = input.nextElementSibling,
                prevInput = input.previousElementSibling;

            if (nextInput && currentInput.value) {
                nextInput.focus();
            }

            if (e.key === 'Backspace' && prevInput) {
                prevInput.focus();
            }
        });
    });

    resendLink.addEventListener('click', function(e) {
        e.preventDefault();
        if (!resendLink.classList.contains('pointer-events-none')) {
            startTimer();
            // Resend OTP logic here
            var login = document.getElementById('login').value.trim();
            var msgDivVerify = document.getElementById('verifyPasswordMsg');
            fetch('?page=users&action=sendResetLink', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'login=' + encodeURIComponent(login)
            })
            .then(r => r.json())
            .then(data => {
                msgDivVerify.textContent = data.message;
                msgDivVerify.style.color = data.success ? 'green' : 'red';
                if (data.success) {
                    // Show verification popup
                    verificationPopup.style.display = 'flex';
                    startTimer();
                    alert('For demo purpose only, your OTP is: ' + data.token);
                }
            });
        }
    });

    function startTimer() {
        clearInterval(timerInterval);
        let timeLeft = 180;
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;

        resendLink.classList.add('text-gray-400', 'pointer-events-none');
        resendLink.classList.remove('text-orange-500');
        timerEl.style.display = 'block';

        timerEl.textContent = `${minutes < 10 ? '0' : ''}${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        //timerEl.textContent = `00:${timeLeft}`;
        timerInterval = setInterval(() => {
            timeLeft--;
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerEl.textContent = `${minutes < 10 ? '0' : ''}${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerEl.style.display = 'none';
                resendLink.classList.remove('text-gray-400', 'pointer-events-none');
                resendLink.classList.add('text-orange-500');
            }
        }, 1000);
    }
</script>