<?php
//is_login();
require_once 'models/user/user.php';
$usersModel = new User($conn);
$userDetails = $usersModel->getUserById($_SESSION['user']['id']);
unset($usersModel);
require_once 'controllers/NotificationController.php';
$notificationController = new NotificationController();
$msgCnt = $notificationController->getUnreadCount();
?>
<style>
.bell-container {
  position: relative;
  display: inline-block;
}

.bell {
  width: 60px;
}

/* Badge that supports 1–3 digits */
.notification-badge {
  position: absolute;
  top: -12px;
  right: -12px;
  background: red;
  color: white;
  padding: 4px 4px;
  min-width: 20px;
  height: 20px;
  border-radius: 12px; /* pill shape */
  font-size: 14px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
}
</style>
<!-- Header Component -->
<header class="bg-white border-b border-[rgba(226,228,230,1)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-3">

            <!-- Left Side: Breadcrumbs -->
            <div class="flex items-center space-x-2">
                <!-- Home Icon SVG -->
                <svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.377 13.7754H3.62305C3.22201 13.7754 2.84831 13.6979 2.50195 13.543C2.14648 13.388 1.83887 13.1807 1.5791 12.9209C1.31934 12.6611 1.11198 12.3535 0.957031 11.998C0.802083 11.6426 0.724609 11.2689 0.724609 10.877V6.54297C0.724609 6.15104 0.833984 5.73633 1.05273 5.29883C1.27148 4.86133 1.53581 4.51953 1.8457 4.27344L4.99023 1.8125C5.46419 1.44792 6.03385 1.25879 6.69922 1.24512C7.36458 1.23145 7.94336 1.39323 8.43555 1.73047L12.0312 4.25977C12.3776 4.50586 12.6715 4.85677 12.9131 5.3125C13.1546 5.76823 13.2754 6.20573 13.2754 6.625V10.877C13.2754 11.278 13.1979 11.6517 13.043 11.998C12.888 12.3535 12.6807 12.6611 12.4209 12.9209C12.1611 13.1807 11.8535 13.388 11.498 13.543C11.1517 13.6979 10.778 13.7754 10.377 13.7754ZM5.52344 2.50977L2.39258 4.95703C2.18294 5.12109 1.99837 5.36263 1.83887 5.68164C1.67936 6.00065 1.59961 6.28776 1.59961 6.54297V10.877C1.59961 11.4329 1.79785 11.9092 2.19434 12.3057C2.59082 12.7021 3.06706 12.9004 3.62305 12.9004H10.377C10.9329 12.9004 11.4092 12.7021 11.8057 12.3057C12.2021 11.9092 12.4004 11.4329 12.4004 10.877V6.625C12.4004 6.35156 12.3138 6.0485 12.1406 5.71582C11.9674 5.38314 11.7669 5.13932 11.5391 4.98438L7.92969 2.45508C7.59245 2.2181 7.18685 2.10645 6.71289 2.12012C6.23893 2.13379 5.84245 2.26367 5.52344 2.50977ZM7 11.4375C6.88151 11.4375 6.77897 11.3942 6.69238 11.3076C6.60579 11.221 6.5625 11.1185 6.5625 11V9.25C6.5625 9.13151 6.60579 9.02897 6.69238 8.94238C6.77897 8.85579 6.88151 8.8125 7 8.8125C7.11849 8.8125 7.22103 8.85579 7.30762 8.94238C7.39421 9.02897 7.4375 9.13151 7.4375 9.25V11C7.4375 11.1185 7.39421 11.221 7.30762 11.3076C7.22103 11.3942 7.11849 11.4375 7 11.4375Z" fill="#5D6772"/>
                </svg>
                <a href="#" class="breadcrumb-home">Home</a>
                <span class="text-gray-500">/</span>
                <span class="breadcrumb-current">Onboarding Dashboard</span>
            </div>

            <!-- Right Side: Search, Flag, Notifications, Profile -->
            <div class="flex items-center space-x-4 md:space-x-6">

                <!-- Notification Bell -->
                <div class="relative flex-shrink-0">
                    <div class="bell-container">
                        <div class="notification-badge"><?php echo $msgCnt;?></div>
                        <a href="index.php?page=notifications&amp;action=list" class="focus:outline-none">
                        <svg class="w-6 h-6" width="17" height="19" viewBox="0 0 17 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.29199 0.441406C11.3016 0.441539 13.8305 2.70512 14.1631 5.69629L14.3955 7.78809C14.5028 8.75395 14.8165 9.68618 15.3164 10.5195L15.8506 11.4082C16.0546 11.7482 16.2321 12.0433 16.3535 12.2891C16.4726 12.5302 16.59 12.8239 16.583 13.1533C16.5702 13.7407 16.2636 14.2825 15.7666 14.5957C15.4876 14.7715 15.1754 14.8218 14.9072 14.8438C14.6341 14.8661 14.2901 14.8652 13.8936 14.8652H2.69043C2.29386 14.8652 1.94995 14.8661 1.67676 14.8438C1.40852 14.8218 1.09544 14.7715 0.816406 14.5957C0.319686 14.2824 0.0128073 13.7405 0 13.1533C-0.00698554 12.8239 0.11134 12.5302 0.230469 12.2891C0.351902 12.0433 0.529398 11.7482 0.733398 11.4082L1.2666 10.5195C1.76657 9.68616 2.08115 8.75399 2.18848 7.78809L2.4209 5.69629C2.75349 2.70506 5.28229 0.441467 8.29199 0.441406ZM8.29199 1.94141C6.04677 1.94147 4.1605 3.62997 3.91211 5.86133L3.67969 7.9541C3.54852 9.13431 3.16364 10.2727 2.55273 11.291L2.01953 12.1807C1.80226 12.5428 1.66303 12.7754 1.5752 12.9531C1.52381 13.0571 1.50623 13.111 1.50098 13.1299C1.5054 13.2063 1.54479 13.2755 1.60742 13.3193C1.62351 13.324 1.67727 13.3387 1.79883 13.3486C1.99645 13.3648 2.26763 13.3652 2.69043 13.3652H13.8936C14.3162 13.3652 14.5876 13.3648 14.7852 13.3486C14.9034 13.339 14.957 13.3243 14.9746 13.3193C15.0376 13.2758 15.0773 13.2073 15.082 13.1309C15.077 13.1126 15.0609 13.0585 15.0088 12.9531C14.921 12.7755 14.7817 12.5427 14.5645 12.1807L14.0303 11.291C13.4194 10.2728 13.0355 9.13422 12.9043 7.9541L12.6719 5.86133C12.4235 3.63 10.5372 1.94154 8.29199 1.94141Z" fill="#5D6772"/>
                            <path d="M5.61676 14.7296C5.77455 15.6129 6.12224 16.3935 6.60591 16.9501C7.08957 17.5068 7.68219 17.8086 8.29184 17.8086C8.90148 17.8086 9.4941 17.5068 9.97777 16.9502C10.4614 16.3935 10.8091 15.6129 10.9669 14.7296" stroke="#5D6772" stroke-width="1.5" stroke-linecap="round"/>
                        </svg></a>
                    </div>
                </div>
                    <div class="relative flex-shrink-0">
                        <a href="chat.php" title="Chat" class="flex items-center space-x-3 p-3 rounded-md hover:bg-gray-100 transition-colors duration-200" target="_blank">
                            <span class="font-medium text-gray-700" style="font-size: 15px; font-family: 'Canva Sans', sans-serif;"><i class="fas fa-comments"></i></span>
                        </a>
                    </div>
                <!-- User Profile with Logout Popup -->
                <div class="relative">
                    <!-- Profile Button -->
                    <button id="profile-menu-button" class="flex-shrink-0 focus:outline-none">
                        <div class="relative">
                            <img class="h-10 w-10 rounded-full object-cover"
                                 src="images/user_pic.png"
                                 alt="User profile"
                                 onerror="this.onerror=null;this.src='https://placehold.co/40x40/E0E0E0/000000?text=U'">
                            <span class="absolute bottom-0 right-0 block h-[5px] w-[5px] rounded-full bg-[#27ae60] ring-2 ring-white"></span>
                        </div>
                    </button>

                    <!-- UPDATED Profile Dropdown -->
                    <div id="logout-popup" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg z-10 p-2">
                        <!-- Profile details -->
                        <div class="p-2 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="relative flex-shrink-0">
                                    <img class="h-10 w-10 rounded-full object-cover"
                                         src="images/user_pic.png"
                                         alt="User profile"
                                         onerror="this.onerror=null;this.src='https://placehold.co/40x40/E0E0E0/000000?text=U'">
                                    <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-green-500 ring-2 ring-white"></span>
                                </div>
                                <div>
                                    <p class="profile-name text-gray-800"><?php echo($userDetails["name"]);?></p>
                                    <p class="profile-title"><?= ($userDetails['role'] != "") ? ucwords(str_replace("_", " ", $userDetails['role'])) : '' ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Menu Items -->
                        <div class="mt-2">
                            <!-- Profile Setting -->
                            <a href="#" onclick="openUserProfileEditModal(<?php echo $userDetails['id']; ?>)" class="flex items-center space-x-3 px-3 py-1 hover:bg-gray-100 rounded-md transition-colors">
                                <svg class="w-[17px] h-[17px] text-gray-400" width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9.5 18C14.1944 18 18 14.1944 18 9.5C18 4.80558 14.1944 1 9.5 1C4.80558 1 1 4.80558 1 9.5C1 14.1944 4.80558 18 9.5 18Z" stroke="#5D6772" stroke-width="1.5" stroke-miterlimit="10" stroke-linejoin="round"/>
                                    <path d="M15.1666 15.7333C15.1666 12.6036 12.6297 10.0667 9.49998 10.0667C6.37028 10.0667 3.83331 12.6036 3.83331 15.7333" stroke="#5D6772" stroke-width="1.5" stroke-miterlimit="10" stroke-linejoin="round"/>
                                    <path d="M9.49998 10.0666C11.3777 10.0666 12.9 8.54437 12.9 6.6666C12.9 4.78883 11.3777 3.2666 9.49998 3.2666C7.62221 3.2666 6.09998 4.78883 6.09998 6.6666C6.09998 8.54437 7.62221 10.0666 9.49998 10.0666Z" stroke="#5D6772" stroke-width="1.5" stroke-miterlimit="10" stroke-linejoin="round"/>
                                </svg>
                                <span class="menu-text">Profile Setting</span>
                            </a>
                            <!-- Reports -->
                            <!-- <a href="#" class="flex items-center space-x-3 px-3 py-1 hover:bg-gray-100 rounded-md transition-colors">
                                <svg class="w-[17px] h-[17px] text-gray-400" width="16" height="19" viewBox="0 0 16 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1.33675 0.229666C0.854722 0.341576 0.388082 0.734877 0.156662 1.21691L0.0139718 1.51768L0.00238189 9.38558C-0.00540811 14.6539 0.00618192 17.315 0.0331619 17.4385C0.141082 17.9475 0.522982 18.4144 1.02819 18.6534L1.32516 18.7961L7.51536 18.8077C11.646 18.8155 13.7671 18.8039 13.8906 18.7769C14.5115 18.6458 15.0823 18.0826 15.2252 17.4617C15.2522 17.3498 15.2676 15.4677 15.2676 12.1239C15.2676 7.15251 15.2638 6.94807 15.1942 6.80158C15.1017 6.60873 8.78437 0.287426 8.6223 0.225864C8.48721 0.175705 1.54898 0.179504 1.33675 0.229666ZM7.36108 3.84347C7.36108 6.56636 7.36488 6.60113 7.58091 6.97524C7.75058 7.26442 8.06294 7.5422 8.39088 7.70028L8.69165 7.84297L11.403 7.85456L14.1105 7.86615V12.6061C14.1105 16.2007 14.0989 17.3692 14.0641 17.4463C13.9638 17.67 14.3457 17.6584 7.63107 17.6584C0.916472 17.6584 1.29818 17.67 1.19805 17.4463C1.12471 17.292 1.1325 1.67215 1.20584 1.54105C1.31775 1.34041 1.22902 1.3482 4.40316 1.34439H7.36127V3.84347H7.36108ZM11.1793 6.69746C8.61071 6.70525 8.63769 6.70905 8.55675 6.48922C8.53357 6.43127 8.51818 5.44023 8.51818 4.03252V1.67215L11.025 4.17901L13.5319 6.68587L11.1793 6.69746Z" fill="#5D6772"/>
                                    <path d="M3.81281 10.3306V10.9092H7.63105H11.4493V10.3306V9.75208H7.63105H3.81281V10.3306Z" fill="#5D6772"/>
                                    <path d="M3.81281 12.8375V13.416H7.63105H11.4493V12.8375V12.2589H7.63105H3.81281V12.8375Z" fill="#5D6772"/>
                                    <path d="M3.81281 15.2672V15.8457H5.6256H7.4382V15.2672V14.6886H5.6256H3.81281V15.2672Z" fill="#5D6772"/>
                                </svg>
                                <span class="menu-text">Reports</span>
                            </a> -->
                            <!-- Notifications -->
                            <div class="flex items-center justify-between space-x-3 px-3 py-1">
                                <div class="flex items-center space-x-3">
                                    <svg class="w-[17px] h-[17px] text-gray-400" width="17" height="19" viewBox="0 0 17 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8.29199 0.441406C11.3016 0.441539 13.8305 2.70512 14.1631 5.69629L14.3955 7.78809C14.5028 8.75395 14.8165 9.68618 15.3164 10.5195L15.8506 11.4082C16.0546 11.7482 16.2321 12.0433 16.3535 12.2891C16.4726 12.5302 16.59 12.8239 16.583 13.1533C16.5702 13.7407 16.2636 14.2825 15.7666 14.5957C15.4876 14.7715 15.1754 14.8218 14.9072 14.8438C14.6341 14.8661 14.2901 14.8652 13.8936 14.8652H2.69043C2.29386 14.8652 1.94995 14.8661 1.67676 14.8438C1.40852 14.8218 1.09544 14.7715 0.816406 14.5957C0.319686 14.2824 0.0128073 13.7405 0 13.1533C-0.00698554 12.8239 0.11134 12.5302 0.230469 12.2891C0.351902 12.0433 0.529398 11.7482 0.733398 11.4082L1.2666 10.5195C1.76657 9.68616 2.08115 8.75399 2.18848 7.78809L2.4209 5.69629C2.75349 2.70506 5.28229 0.441467 8.29199 0.441406ZM8.29199 1.94141C6.04677 1.94147 4.1605 3.62997 3.91211 5.86133L3.67969 7.9541C3.54852 9.13431 3.16364 10.2727 2.55273 11.291L2.01953 12.1807C1.80226 12.5428 1.66303 12.7754 1.5752 12.9531C1.52381 13.0571 1.50623 13.111 1.50098 13.1299C1.5054 13.2063 1.54479 13.2755 1.60742 13.3193C1.62351 13.324 1.67727 13.3387 1.79883 13.3486C1.99645 13.3648 2.26763 13.3652 2.69043 13.3652H13.8936C14.3162 13.3652 14.5876 13.3648 14.7852 13.3486C14.9034 13.339 14.957 13.3243 14.9746 13.3193C15.0376 13.2758 15.0773 13.2073 15.082 13.1309C15.077 13.1126 15.0609 13.0585 15.0088 12.9531C14.921 12.7755 14.7817 12.5427 14.5645 12.1807L14.0303 11.291C13.4194 10.2728 13.0355 9.13422 12.9043 7.9541L12.6719 5.86133C12.4235 3.63 10.5372 1.94154 8.29199 1.94141Z" fill="#5D6772"/>
                                        <path d="M5.61676 14.7296C5.77455 15.6129 6.12224 16.3935 6.60591 16.9501C7.08957 17.5068 7.68219 17.8086 8.29184 17.8086C8.90148 17.8086 9.4941 17.5068 9.97777 16.9502C10.4614 16.3935 10.8091 15.6129 10.9669 14.7296" stroke="#5D6772" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                    <span class="menu-text">Notifications</span>
                                </div>
                                <!-- NEW Toggle Switch -->
                                <!-- <button id="notification-toggle" class="relative inline-flex items-center h-5 w-9 rounded-full bg-gray-200 transition-colors duration-200 ease-in-out focus:outline-none">
                                    <span id="notification-toggle-thumb" class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform duration-200 ease-in-out"></span>
                                </button> -->
                            </div>
                        </div>

                        <!-- Sign Out -->
                        <div class="border-t border-gray-100 mt-2 pt-2">
                            <a href="?page=users&action=logout" class="flex items-center space-x-3 px-3 py-1 text-sm font-medium hover:bg-gray-100 rounded-md transition-colors">
                                <svg class="w-[17px] h-[17px]" width="19" height="15" viewBox="0 0 19 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M14.8046 0H10.3319C8.36877 0 6.78046 1.58831 6.78046 3.55146V3.83736C6.78046 3.94536 6.8694 4.03431 6.97741 4.03431H8.20994C8.31794 4.03431 8.40689 3.94536 8.40689 3.83736V3.55146C8.40689 2.49047 9.26457 1.62643 10.3319 1.62643H14.8046C15.8656 1.62643 16.7296 2.48412 16.7296 3.55146V11.4485C16.7296 12.5095 15.8719 13.3736 14.8046 13.3736H10.3319C9.27093 13.3736 8.40689 12.5159 8.40689 11.4485V11.1626C8.40689 11.0546 8.31794 10.9657 8.20994 10.9657H6.97741C6.8694 10.9657 6.78046 11.0546 6.78046 11.1626V11.4485C6.78046 13.4117 8.36877 15 10.3319 15H14.8046C16.7678 15 18.3561 13.4117 18.3561 11.4485V3.55146C18.3561 1.58831 16.7678 0 14.8046 0Z" fill="#EC4040"/>
                                    <path d="M10.1223 8.7356V6.26419C10.1223 5.93382 9.85546 5.66698 9.52509 5.66698H4.8237V4.822C4.8237 4.51069 4.56321 4.28833 4.29003 4.28833C4.18837 4.28833 4.08037 4.3201 3.98507 4.38998L2.21887 5.64792L0.223952 7.07105C-0.0746506 7.28071 -0.0746506 7.72543 0.223952 7.93509L2.21887 9.35822L3.98507 10.6162C4.08037 10.686 4.18837 10.7178 4.29003 10.7178C4.56321 10.7178 4.8237 10.5018 4.8237 10.1841V9.33916H9.52509C9.85546 9.33916 10.1223 9.07232 10.1223 8.74195V8.7356Z" fill="#EC4040"/>
                                </svg>
                                <span class="menu-text !text-[rgba(236,64,64,1)]">Sign Out</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Edit User Modal -->
<div class="modal fade hidden" id="editUserProfileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- Sliding Container -->
    <div id="modal-slider-edit-profile" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: 35%; min-width: 400px;">
        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-vendor-popup-btn-edit-profile" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Popup Panel -->
        <div class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Edit Profile</h2>
                    <div id="editUserProfileMsg" style="margin-top:10px;"></div>
                    <form id="editUserProfile">
                        <input type="hidden" id="editUserProfileId" name="id" value="">
                        <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                            <div>
                                <label for="name" class="text-sm font-medium text-gray-700">Name:</label>
                                <input type="text" id="editProfileName" name="name" class="form-input w-full mt-1" required>
                            </div>

                            <div>
                                <label for="email" class="text-sm font-medium text-gray-700">Email:</label>
                                <input type="email" id="editProfileEmail" name="email" class="form-input w-full mt-1" readonly>
                            </div>

                            <div>
                                <label for="phone" class="text-sm font-medium text-gray-700">Phone:</label>
                                <input type="number" id="EditProfilePhone" name="phone" class="form-input w-full mt-1" required>
                            </div>
                            <div>
                                <label for="password" class="text-sm font-medium text-gray-700">Password:</label>
                                <input type="password" id="editProfilePassword" name="password" class="form-input w-full mt-1">
                            </div>
                        </div><div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
<div></div>
                            <div>
                                <label for="password" class="text-sm font-medium text-gray-700">Confirm Password:</label>
                                <input type="password" id="editProfileConfirmPassword" name="confirm_password" class="form-input w-full mt-1" onfocus="clearField();">
                            </div>

                        </div>

                        <div class="flex justify-center items-center gap-4 pt-6 border-t">
                            <button type="submit" class="action-btn save-btn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
  </div>
</div>
<!-- End Model Popup -->

<!-- JavaScript for Popup Toggle -->
<script>
    const profileMenuButton = document.getElementById('profile-menu-button');
    const logoutPopup = document.getElementById('logout-popup');
    //const toggleButton = document.getElementById('notification-toggle');
    //const toggleThumb = document.getElementById('notification-toggle-thumb');

    let isToggleOn = false;
    let typingTimer;
    let doneTypingInterval = 500; // wait 0.5s after user stops typing

    // Toggle popup on button click
    profileMenuButton.addEventListener('click', (event) => {
        event.stopPropagation();
        logoutPopup.classList.toggle('hidden');
    });

    // Toggle switch functionality
    /*toggleButton.addEventListener('click', (event) => {
        event.stopPropagation();
        isToggleOn = !isToggleOn;
        if (isToggleOn) {
            toggleButton.classList.add('toggle-bg-active');
            toggleButton.classList.remove('bg-gray-200');
            toggleThumb.classList.add('translate-x-[18px]');
        } else {
            toggleButton.classList.remove('toggle-bg-active');
            toggleButton.classList.add('bg-gray-200');
            toggleThumb.classList.remove('translate-x-[18px]');
        }
    });*/

    // Close popup if clicked outside
    window.addEventListener('click', () => {
        if (!logoutPopup.classList.contains('hidden')) {
            logoutPopup.classList.add('hidden');
        }
    });

    // Prevent popup from closing when clicking inside it
    logoutPopup.addEventListener('click', (event) => {
        event.stopPropagation();
    });
    function clearField() {
        let message = document.getElementById("editUserProfileMsg");
        message.innerHTML = '';
    }

    // Edit User Modal Logic    
    const popupWrapperEditProfile = document.getElementById('editUserProfileModal');
    const modalSliderEditProfile = document.getElementById('modal-slider-edit-profile');
    const closeVendorPopupBtnEditProfile = document.getElementById('close-vendor-popup-btn-edit-profile');

    function openUserProfileEditModal(id) {
        fetch("?page=users&action=userDetails&id=" + id)
        .then(res => res.json())
        .then(user => {
            if (user.status === "error") {
                alert(user.message);
                return;
            }
            document.getElementById("editUserProfileId").value   = user.id;
            document.getElementById("editProfileName").value = user.name;
            document.getElementById("editProfileEmail").value= user.email;
            document.getElementById("EditProfilePhone").value= user.phone;

            popupWrapperEditProfile.classList.remove('hidden');
            setTimeout(() => {
                modalSliderEditProfile.classList.remove('translate-x-full');
            }, 10);
        });
    }

    function closeVendorPopupEditProfile() {
        modalSliderEditProfile.classList.add('translate-x-full');
    }

    closeVendorPopupBtnEditProfile.addEventListener('click', closeVendorPopupEditProfile);

    document.getElementById('editUserProfile').onsubmit = function(e) {
        e.preventDefault();
        
        let password = document.getElementById("editProfilePassword").value;
        let confirmPassword = document.getElementById("editProfileConfirmPassword").value;
        let message = document.getElementById("editUserProfileMsg");
        message.innerHTML = '';
        if (password.length > 0) {
            if (password !== confirmPassword) {
                confirmPassword.value="";
                message.innerHTML = `<div style="color: red; padding: 10px; background: #ffe0e0; border: 1px solid #a00;"> ❌ Passwords do not match </div>`;
                return false;
            }
        }

        var form = new FormData(this);
        var params = new URLSearchParams(form).toString();
        fetch('?page=users&action=updateProfile', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(r => r.json())
        .then(data => {
            var msgBox = document.getElementById('editUserProfileMsg');
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
                msgBox.innerHTML = '';
                closeVendorPopupEditProfile();
                location.reload();
            }, 1500); // redirect after 1 sec
        });
    };
    // Notification Logic
    if (!("Notification" in window)) {
        alert("This browser does not support desktop notification");
    } else if (Notification.permission !== "granted") {
        Notification.requestPermission();
    }

    let processing = false;

    function checkNewNotification() {
        if (processing) return;

        $.get("index.php?page=notifications&action=fetch_notifications", function(data) {
            let notifs = JSON.parse(data);

            if (notifs.length > 0) {
                processing = true;
                showNotificationsQueue(notifs);
            }
        });
    }

    function playNotificationSound() {
        const audio = new Audio("images/sounds/notify.mp3");
        audio.play().catch(error => {
            console.log("Autoplay blocked:", error);
        });
    }

    function showNotificationsQueue(notifs) {
        if (notifs.length === 0) {
            processing = false;
            return;
        }

        let notif = notifs.shift();

        let notification = new Notification(notif.title, {
            body: notif.message,
            icon: "images/bell-icons.ico",
            data: { link: notif.link }
        });

        //playNotificationSound();

        let notifyAudio = new Audio("images/sounds/notify.mp3");
        let audioUnlocked = false;

        document.addEventListener("click", function() {
            if (!audioUnlocked) {
                notifyAudio.play().catch(() => {});
                notifyAudio.pause();
                notifyAudio.currentTime = 0;
                audioUnlocked = true;
                console.log("Audio unlocked!");
            }
        });

        notification.onclick = function(event) {
            event.preventDefault();

            // Mark as read
            $.post("index.php?page=notifications&action=mark_as_read", { ids: [notif.id] });

            // Open link
            /*if (notification.data.link) {
                window.open(notification.data.link, "_blank");
            }*/
        };

        // Mark as read
        $.post("index.php?page=notifications&action=is_display", { ids: [notif.id] });

        // Show next after 1 second
        setTimeout(() => showNotificationsQueue(notifs), 5000);
    }

    setInterval(checkNewNotification, 5000);
</script>