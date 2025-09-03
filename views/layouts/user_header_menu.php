<style>

    @font-face {
        font-family: 'Canva Sans';
        src: url('https://d153v3y111u672.cloudfront.net/static/fonts/canva-sans.woff2') format('woff2');
        font-weight: normal;
        font-style: normal;
    }

    .icon-svg {
        width: 24px;
        height: 24px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .popup-container {
        border: 1px solid #000;
        border-radius: 11px;
    }
</style>
<div id="popup" class="w-full max-w-xs bg-white shadow-xl overflow-hidden popup-container">
    <!-- User Profile Section -->
    <div id="profileHeader" class="flex items-center space-x-4 pb-4 border-b border-black p-4">
        <img class="w-12 h-12 rounded-full border border-gray-300" src="https://placehold.co/48x48/F3F4F6/9CA3AF?text=User" alt="User Avatar">
        <div>
            <div class="font-bold" style="font-size: 16.5px; font-family: 'Roboto', sans-serif;"><?php echo($userDetails["name"]);?></div>
            <div class="text-sm text-gray-500" style="font-size: 13px; font-family: 'Canva Sans', sans-serif;"><?php echo(ucfirst($userDetails["role"]));?></div>
        </div>
    </div>

    <!-- Menu Items -->
    <nav id="menu" class="hidden">
        <ul>
            <li class="pl-4">
                <a href="#" class="flex items-center space-x-3 p-3 rounded-md hover:bg-gray-100 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon-svg" viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span class="font-medium text-gray-700" style="font-size: 15px; font-family: 'Canva Sans', sans-serif;">Profile</span>
                </a>
            </li>
            <li class="pl-4">
                <a href="#" class="flex items-center space-x-3 p-3 rounded-md hover:bg-gray-100 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon-svg" viewBox="0 0 24 24">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="font-medium text-gray-700" style="font-size: 15px; font-family: 'Canva Sans', sans-serif;">Notifications</span>
                </a>
            </li>
            <li class="pl-4">
                <a href="#" class="flex items-center space-x-3 p-3 rounded-md hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-cog text-2xl text-gray-700"></i>
                    <span class="font-medium text-gray-700" style="font-size: 15px; font-family: 'Canva Sans', sans-serif;">Settings</span>
                </a>
            </li>
            <li class="pl-4">
                <a href="?page=users&action=logout" class="flex items-center space-x-3 p-3 rounded-md hover:bg-gray-100 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon-svg" viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span class="font-bold text-gray-700" style="font-size: 15px; font-family: 'Canva Sans', sans-serif;">Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</div>
<script> document.addEventListener('DOMContentLoaded', () => { 
	const popup = document.getElementById('popup'); 
	const profile = document.getElementById('profileHeader'); 
	const menu = document.getElementById('menu'); 
	// Show on hover over the profile header 
	profile.addEventListener('mouseenter', () => { menu.classList.remove('hidden'); }); 
	// Show on click on the profile header (name/avatar area) 
	profile.addEventListener('click', () => { menu.classList.remove('hidden'); }); 
	// Hide when the mouse leaves the whole card (profile + menu) 
	popup.addEventListener('mouseleave', () => { menu.classList.add('hidden'); }); 
	// Optional: hide if clicking outside the card 
	document.addEventListener('click', (e) => { if (!popup.contains(e.target)) { menu.classList.add('hidden'); } }); }); 
</script>