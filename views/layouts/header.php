<?php
require_once 'models/user/user.php';
$usersModel = new User($conn);
$userDetails = $usersModel->getUserById($_SESSION['user']['id']);
unset($usersModel);
?>
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

                <!-- Search Bar -->
                <div class="relative hidden md:block">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.41211 12.0996C5.62826 12.0996 4.88997 11.9538 4.19727 11.6621C3.51367 11.3613 2.91439 10.9535 2.39941 10.4385C1.88444 9.9235 1.47656 9.31966 1.17578 8.62695C0.875 7.93424 0.724609 7.19596 0.724609 6.41211C0.724609 5.62826 0.875 4.88997 1.17578 4.19727C1.47656 3.51367 1.88444 2.91439 2.39941 2.39941C2.91439 1.88444 3.51367 1.47656 4.19727 1.17578C4.88997 0.875 5.62826 0.724609 6.41211 0.724609C7.19596 0.724609 7.93424 0.875 8.62695 1.17578C9.31966 1.47656 9.9235 1.88444 10.4385 2.39941C10.9535 2.91439 11.3613 3.51367 11.6621 4.19727C11.9538 4.88997 12.0996 5.62826 12.0996 6.41211C12.0996 7.19596 11.9538 7.93424 11.6621 8.62695C11.3613 9.31966 10.9535 9.9235 10.4385 10.4385C9.9235 10.9535 9.31966 11.3613 8.62695 11.6621C7.93424 11.9538 7.19596 12.0996 6.41211 12.0996ZM6.41211 1.59961C5.75586 1.59961 5.13151 1.72721 4.53906 1.98242C3.95573 2.23763 3.44759 2.58171 3.01465 3.01465C2.58171 3.44759 2.23763 3.95573 1.98242 4.53906C1.72721 5.13151 1.59961 5.75586 1.59961 6.41211C1.59961 7.07747 1.72721 7.70182 1.98242 8.28516C2.23763 8.86849 2.58171 9.37891 3.01465 9.81641C3.44759 10.2539 3.95573 10.6003 4.53906 10.8555C5.13151 11.1016 5.75586 11.2246 6.41211 11.2246C7.07747 11.2246 7.70182 11.1016 8.28516 10.8555C8.86849 10.6003 9.37891 10.2539 9.81641 9.81641C10.2539 9.37891 10.6003 8.86849 10.8555 8.28516C11.1016 7.70182 11.2246 7.07747 11.2246 6.41211C11.2246 5.75586 11.1016 5.13151 10.8555 4.53906C10.6003 3.95573 10.2539 3.44759 9.81641 3.01465C9.37891 2.58171 8.86849 2.23763 8.28516 1.98242C7.70182 1.72721 7.07747 1.59961 6.41211 1.59961ZM11.7578 13.2891C11.7396 13.2891 11.7191 13.2891 11.6963 13.2891C11.6735 13.2891 11.6484 13.2891 11.6211 13.2891C11.4844 13.2708 11.3112 13.1934 11.1016 13.0566C10.8919 12.9199 10.7188 12.6419 10.582 12.2227C10.5091 12.0039 10.484 11.7897 10.5068 11.5801C10.5296 11.3704 10.6003 11.1836 10.7188 11.0195C10.8372 10.8555 10.9945 10.7279 11.1904 10.6367C11.3864 10.5456 11.5983 10.5 11.8262 10.5C12.1178 10.5 12.3753 10.5547 12.5986 10.6641C12.8219 10.7734 12.9883 10.9284 13.0977 11.1289C13.1979 11.3294 13.2344 11.5527 13.207 11.7988C13.1797 12.0449 13.084 12.291 12.9199 12.5371C12.7103 12.8561 12.5007 13.0612 12.291 13.1523C12.0814 13.2435 11.9036 13.2891 11.7578 13.2891ZM11.416 11.9492C11.4616 12.1042 11.5163 12.2204 11.5801 12.2979C11.6439 12.3753 11.6986 12.4141 11.7441 12.4141C11.7897 12.4232 11.8535 12.3981 11.9355 12.3389C12.0176 12.2796 12.1042 12.1862 12.1953 12.0586C12.2773 11.931 12.3252 11.8239 12.3389 11.7373C12.3525 11.6507 12.3503 11.5846 12.332 11.5391C12.3138 11.5026 12.2614 11.4661 12.1748 11.4297C12.0882 11.3932 11.972 11.375 11.8262 11.375C11.735 11.375 11.6553 11.3887 11.5869 11.416C11.5186 11.4434 11.4661 11.4844 11.4297 11.5391C11.3932 11.5846 11.3727 11.6439 11.3682 11.7168C11.3636 11.7897 11.3796 11.8672 11.416 11.9492Z" fill="#90979F"/></svg>
                        </span>
                    <input type="text" placeholder="Search" class="search-input w-[200px] h-[36px] pl-4 pr-10 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>

                <!-- Flag Icon -->
                <div class="flex-shrink-0">
                    <svg width="24" height="24" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_92_3098)"><g clip-path="url(#clip1_92_3098)"><path d="M16.5 8.48493C16.5 9.41902 16.3493 10.323 16.048 11.1365C14.9633 14.2401 11.9802 16.4699 8.51507 16.4699C5.04991 16.4699 2.06685 14.2702 0.951977 11.1667C0.650659 10.323 0.5 9.44915 0.5 8.51507C0.5 7.58098 0.650659 6.67702 0.951977 5.86346C2.06685 2.72976 5.01977 0.5 8.48493 0.5C11.9501 0.5 14.9331 2.72976 16.0179 5.83333C16.3192 6.67702 16.4699 7.55085 16.4699 8.48493H16.5Z" fill="white"/><path d="M16.0178 5.83333H0.951904C2.06678 2.72976 5.0197 0.5 8.48486 0.5C11.95 0.5 14.9331 2.72976 16.0178 5.83333Z" fill="#FF9933"/><path d="M16.0178 11.1667C14.9331 14.2703 11.95 16.5001 8.48486 16.5001C5.0197 16.5001 2.06678 14.2703 0.951904 11.1667H16.0178Z" fill="#128807"/><path d="M8.78609 8.72582V8.69569H8.81622L9.08741 8.90662L10.1119 9.41886L9.14767 8.81622L8.81622 8.66556L8.84635 8.63543V8.6053L9.1778 8.75596L10.2927 8.96688L9.20793 8.63543L8.84635 8.57517L8.87648 8.54503V8.5149L9.23807 8.54503L10.3529 8.48477L9.23807 8.42451L8.87648 8.48477V8.45464V8.42451H8.84635L9.20793 8.36424L10.2927 8.00266L9.1778 8.24372L8.84635 8.39437V8.36424V8.33411H8.81622L9.14767 8.18345L10.1119 7.55068L9.08741 8.06292L8.81622 8.30398V8.27385H8.78609V8.24372H8.75596L9.05727 8.03279L9.81057 7.1891L8.96688 7.9424L8.75596 8.21358H8.72582V8.18345H8.69569L8.90662 7.88213L9.41886 6.88778L8.81622 7.82187L8.66556 8.15332H8.63543H8.6053L8.75596 7.79174L8.96688 6.70699L8.63543 7.76161L8.57517 8.12319H8.54503H8.5149L8.54503 7.76161L8.48477 6.64673L8.42451 7.76161L8.48477 8.12319H8.45464H8.42451L8.36424 7.76161L8.00266 6.70699L8.24372 7.79174L8.36424 8.15332H8.33411L8.18345 7.82187L7.55068 6.88778L8.06292 7.88213L8.30398 8.18345H8.27385V8.21358H8.24372L8.03279 7.9424L7.1891 7.1891L7.9424 8.03279L8.21358 8.24372V8.27385H8.18345V8.30398L7.88213 8.06292L6.88778 7.55068L7.82187 8.18345L8.15332 8.33411V8.36424V8.39437L7.79174 8.24372L6.70699 8.00266L7.76161 8.36424L8.12319 8.42451V8.45464V8.48477L7.76161 8.42451L6.64673 8.48477L7.76161 8.54503L8.12319 8.5149V8.54503V8.57517L7.76161 8.63543L6.70699 8.96688L7.79174 8.75596L8.15332 8.6053V8.63543V8.66556L7.82187 8.81622L6.88778 9.41886L7.88213 8.90662L8.18345 8.69569V8.72582H8.21358V8.75596L7.9424 8.96688L7.1891 9.81057L8.03279 9.05727L8.24372 8.78609H8.27385V8.81622H8.30398L8.06292 9.08741L7.55068 10.1119L8.18345 9.14767L8.33411 8.81622V8.84635H8.36424L8.24372 9.1778L8.00266 10.2927L8.36424 9.20793L8.42451 8.84635V8.87648H8.45464H8.48477L8.42451 9.23807L8.48477 10.3529L8.54503 9.23807L8.5149 8.87648H8.54503H8.57517V8.84635L8.63543 9.20793L8.96688 10.2927L8.75596 9.1778L8.6053 8.84635H8.63543H8.66556V8.81622L8.81622 9.14767L9.41886 10.1119L8.90662 9.08741L8.69569 8.81622V8.78609H8.72582H8.75596L8.96688 9.05727L9.81057 9.81057L9.05727 8.96688L8.75596 8.75596H8.78609V8.72582Z" fill="#000088"/><path d="M8.48482 6.34546C7.30968 6.34546 6.34546 7.30968 6.34546 8.48482C6.34546 9.65996 7.30968 10.6242 8.48482 10.6242C9.65996 10.6242 10.6242 9.65996 10.6242 8.48482C10.6242 7.30968 9.65996 6.34546 8.48482 6.34546ZM10.3229 8.8464C10.3229 8.9368 10.2927 9.05732 10.2325 9.14772C10.2023 9.14772 10.1421 9.14772 10.1119 9.17785C10.1119 9.23811 10.1119 9.26825 10.1421 9.29838C10.1119 9.38877 10.0517 9.47917 9.99141 9.56956C9.96128 9.53943 9.90101 9.56956 9.87088 9.56956C9.84075 9.5997 9.84075 9.65996 9.87088 9.69009C9.81062 9.78049 9.72022 9.84075 9.65996 9.90101C9.65996 9.87088 9.56956 9.84075 9.53943 9.90101C9.47917 9.90101 9.47917 9.99141 9.5093 10.0215C9.41891 10.0818 9.32851 10.1421 9.23811 10.1722C9.23811 10.1421 9.17785 10.1119 9.11759 10.1119C9.05732 10.1119 9.02719 10.1722 9.05732 10.2325C8.96693 10.2626 8.87653 10.2927 8.75601 10.3229C8.75601 10.2626 8.72587 10.2325 8.66561 10.2325C8.60535 10.2325 8.57521 10.2626 8.57521 10.3229C8.51495 10.3229 8.48482 10.3229 8.42456 10.3229C8.36429 10.3229 8.33416 10.3229 8.2739 10.3229C8.2739 10.2626 8.2739 10.2325 8.21363 10.2023C8.15337 10.2023 8.12324 10.2023 8.0931 10.2626C8.00271 10.2626 7.88218 10.2325 7.79179 10.1722C7.79179 10.1421 7.79179 10.0818 7.76165 10.0517C7.70139 10.0517 7.67126 10.0517 7.64113 10.0818C7.55073 10.0517 7.46034 9.99141 7.36994 9.93115C7.36994 9.90101 7.36994 9.84075 7.36994 9.81062C7.33981 9.78049 7.27955 9.78049 7.24941 9.81062C7.15902 9.75036 7.09875 9.65996 7.03849 9.5997C7.06862 9.5997 7.09875 9.5093 7.03849 9.47917C7.03849 9.41891 6.9481 9.41891 6.91796 9.44904C6.8577 9.35864 6.79744 9.26825 6.7673 9.17785C6.79744 9.17785 6.82757 9.11759 6.82757 9.05732C6.82757 8.99706 6.7673 8.96693 6.70704 8.99706C6.67691 8.90666 6.64678 8.81627 6.61665 8.69574C6.67691 8.69574 6.70704 8.66561 6.70704 8.60535C6.70704 8.54508 6.67691 8.51495 6.61665 8.51495C6.61665 8.45469 6.61665 8.42455 6.61665 8.36429C6.61665 8.30403 6.61665 8.2739 6.61665 8.21363C6.67691 8.21363 6.70704 8.21363 6.73717 8.15337C6.73717 8.0931 6.73717 8.06297 6.67691 8.03284C6.67691 7.94245 6.70704 7.82192 6.7673 7.73152C6.79744 7.73152 6.8577 7.73152 6.88783 7.70139C6.88783 7.64113 6.88783 7.611 6.8577 7.58086C6.88783 7.49047 6.9481 7.40007 7.00836 7.30968C7.03849 7.30968 7.09875 7.30968 7.12889 7.30968C7.15902 7.27955 7.15902 7.21928 7.12889 7.18915C7.18915 7.09875 7.27955 7.03849 7.33981 6.97823C7.33981 7.00836 7.4302 7.03849 7.46034 6.97823C7.49047 6.97823 7.5206 6.88783 7.49047 6.8577C7.58086 6.79744 7.67126 6.73717 7.76165 6.70704C7.76165 6.73717 7.82192 6.7673 7.88218 6.7673C7.94245 6.7673 7.97258 6.70704 7.94245 6.64678C8.03284 6.61665 8.12324 6.58651 8.24376 6.55638C8.24376 6.61665 8.2739 6.64678 8.33416 6.64678C8.39442 6.64678 8.42456 6.61665 8.42456 6.55638C8.48482 6.55638 8.51495 6.55638 8.57521 6.55638C8.63548 6.55638 8.66561 6.55638 8.72587 6.55638C8.72587 6.61665 8.72587 6.64678 8.78614 6.67691C8.8464 6.67691 8.87653 6.67691 8.90666 6.61665C8.99706 6.61665 9.11759 6.64678 9.20798 6.70704C9.20798 6.73717 9.20798 6.79744 9.23811 6.82757C9.29838 6.82757 9.32851 6.82757 9.35864 6.79744C9.44904 6.82757 9.53943 6.88783 9.62983 6.9481C9.62983 6.97823 9.62983 7.03849 9.62983 7.06862C9.65996 7.09875 9.72022 7.09875 9.75036 7.06862C9.84075 7.12889 9.90101 7.21928 9.96128 7.27955C9.93115 7.27955 9.90101 7.36994 9.96128 7.40007C9.96128 7.46034 10.0517 7.46034 10.0818 7.4302C10.1421 7.5206 10.2023 7.611 10.2325 7.70139C10.2023 7.70139 10.1722 7.76165 10.1722 7.82192C10.1722 7.88218 10.2325 7.91231 10.2927 7.88218C10.3229 7.97258 10.353 8.06297 10.3831 8.1835C10.3229 8.1835 10.2927 8.24376 10.2927 8.2739C10.2927 8.33416 10.3229 8.36429 10.3831 8.36429C10.3831 8.42455 10.3831 8.45469 10.3831 8.51495C10.3831 8.57521 10.3831 8.60535 10.3831 8.66561C10.3229 8.66561 10.2927 8.66561 10.2626 8.72587C10.2626 8.78614 10.2626 8.81627 10.3229 8.8464Z" fill="#000088"/><g style="mix-blend-mode:multiply"><path d="M14.1497 2.85028C17.2533 5.95386 17.2533 11.0461 14.1497 14.1497C11.0461 17.2533 5.95386 17.2533 2.85028 14.1497C-0.283427 11.016 -0.283427 5.95386 2.85028 2.85028C5.95386 -0.283427 11.0461 -0.283427 14.1497 2.85028Z" fill="url(#paint0_linear_92_3098)"/><path d="M8.48486 0.951904C12.6431 0.951904 16.0479 4.32667 16.0479 8.51499C16.0479 12.7033 12.6732 16.0781 8.48486 16.0781C4.29654 16.0781 0.951904 12.6732 0.951904 8.48486C0.951904 4.29654 4.32667 0.951904 8.48486 0.951904Z" fill="url(#paint1_linear_92_3098)"/></g></g></g><defs><linearGradient id="paint0_linear_92_3098" x1="14.153" y1="14.1584" x2="2.83932" y2="2.4471" gradientUnits="userSpaceOnUse"><stop stop-color="#939598"/><stop offset="0.13" stop-color="#A8A9AC"/><stop offset="0.39" stop-color="#CDCED0"/><stop offset="0.63" stop-color="#E8E9E9"/><stop offset="0.84" stop-color="#F9F9F9"/><stop offset="1" stop-color="white"/></linearGradient><linearGradient id="paint1_linear_92_3098" x1="3.15153" y1="3.1214" x2="13.6977" y2="13.6675" gradientUnits="userSpaceOnUse"><stop stop-color="#999999"/><stop offset="0.1" stop-color="#A8A8A8"/><stop offset="0.36" stop-color="#CDCDCD"/><stop offset="0.61" stop-color="#E8E8E8"/><stop offset="0.83" stop-color="#F9F9F9"/><stop offset="1" stop-color="white"/></linearGradient><clipPath id="clip0_92_3098"><rect x="0.5" y="0.5" width="17" height="17" rx="8" fill="white"/></clipPath><clipPath id="clip1_92_3098"><rect width="16" height="16" fill="white" transform="translate(0.5 0.5)"/></clipPath></defs></svg>
                </div>

                <!-- Notification Bell -->
                <div class="relative flex-shrink-0">
                    <svg class="w-6 h-6" width="17" height="19" viewBox="0 0 17 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8.29199 0.441406C11.3016 0.441539 13.8305 2.70512 14.1631 5.69629L14.3955 7.78809C14.5028 8.75395 14.8165 9.68618 15.3164 10.5195L15.8506 11.4082C16.0546 11.7482 16.2321 12.0433 16.3535 12.2891C16.4726 12.5302 16.59 12.8239 16.583 13.1533C16.5702 13.7407 16.2636 14.2825 15.7666 14.5957C15.4876 14.7715 15.1754 14.8218 14.9072 14.8438C14.6341 14.8661 14.2901 14.8652 13.8936 14.8652H2.69043C2.29386 14.8652 1.94995 14.8661 1.67676 14.8438C1.40852 14.8218 1.09544 14.7715 0.816406 14.5957C0.319686 14.2824 0.0128073 13.7405 0 13.1533C-0.00698554 12.8239 0.11134 12.5302 0.230469 12.2891C0.351902 12.0433 0.529398 11.7482 0.733398 11.4082L1.2666 10.5195C1.76657 9.68616 2.08115 8.75399 2.18848 7.78809L2.4209 5.69629C2.75349 2.70506 5.28229 0.441467 8.29199 0.441406ZM8.29199 1.94141C6.04677 1.94147 4.1605 3.62997 3.91211 5.86133L3.67969 7.9541C3.54852 9.13431 3.16364 10.2727 2.55273 11.291L2.01953 12.1807C1.80226 12.5428 1.66303 12.7754 1.5752 12.9531C1.52381 13.0571 1.50623 13.111 1.50098 13.1299C1.5054 13.2063 1.54479 13.2755 1.60742 13.3193C1.62351 13.324 1.67727 13.3387 1.79883 13.3486C1.99645 13.3648 2.26763 13.3652 2.69043 13.3652H13.8936C14.3162 13.3652 14.5876 13.3648 14.7852 13.3486C14.9034 13.339 14.957 13.3243 14.9746 13.3193C15.0376 13.2758 15.0773 13.2073 15.082 13.1309C15.077 13.1126 15.0609 13.0585 15.0088 12.9531C14.921 12.7755 14.7817 12.5427 14.5645 12.1807L14.0303 11.291C13.4194 10.2728 13.0355 9.13422 12.9043 7.9541L12.6719 5.86133C12.4235 3.63 10.5372 1.94154 8.29199 1.94141Z" fill="#5D6772"/>
                        <path d="M5.61676 14.7296C5.77455 15.6129 6.12224 16.3935 6.60591 16.9501C7.08957 17.5068 7.68219 17.8086 8.29184 17.8086C8.90148 17.8086 9.4941 17.5068 9.97777 16.9502C10.4614 16.3935 10.8091 15.6129 10.9669 14.7296" stroke="#5D6772" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <span class="absolute top-0.5 right-0.5 block h-[5px] w-[5px] rounded-full bg-[#27ae60] ring-2 ring-white"></span>
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
                            <a href="#" class="flex items-center space-x-3 px-3 py-1 hover:bg-gray-100 rounded-md transition-colors">
                                <svg class="w-[17px] h-[17px] text-gray-400" width="16" height="19" viewBox="0 0 16 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1.33675 0.229666C0.854722 0.341576 0.388082 0.734877 0.156662 1.21691L0.0139718 1.51768L0.00238189 9.38558C-0.00540811 14.6539 0.00618192 17.315 0.0331619 17.4385C0.141082 17.9475 0.522982 18.4144 1.02819 18.6534L1.32516 18.7961L7.51536 18.8077C11.646 18.8155 13.7671 18.8039 13.8906 18.7769C14.5115 18.6458 15.0823 18.0826 15.2252 17.4617C15.2522 17.3498 15.2676 15.4677 15.2676 12.1239C15.2676 7.15251 15.2638 6.94807 15.1942 6.80158C15.1017 6.60873 8.78437 0.287426 8.6223 0.225864C8.48721 0.175705 1.54898 0.179504 1.33675 0.229666ZM7.36108 3.84347C7.36108 6.56636 7.36488 6.60113 7.58091 6.97524C7.75058 7.26442 8.06294 7.5422 8.39088 7.70028L8.69165 7.84297L11.403 7.85456L14.1105 7.86615V12.6061C14.1105 16.2007 14.0989 17.3692 14.0641 17.4463C13.9638 17.67 14.3457 17.6584 7.63107 17.6584C0.916472 17.6584 1.29818 17.67 1.19805 17.4463C1.12471 17.292 1.1325 1.67215 1.20584 1.54105C1.31775 1.34041 1.22902 1.3482 4.40316 1.34439H7.36127V3.84347H7.36108ZM11.1793 6.69746C8.61071 6.70525 8.63769 6.70905 8.55675 6.48922C8.53357 6.43127 8.51818 5.44023 8.51818 4.03252V1.67215L11.025 4.17901L13.5319 6.68587L11.1793 6.69746Z" fill="#5D6772"/>
                                    <path d="M3.81281 10.3306V10.9092H7.63105H11.4493V10.3306V9.75208H7.63105H3.81281V10.3306Z" fill="#5D6772"/>
                                    <path d="M3.81281 12.8375V13.416H7.63105H11.4493V12.8375V12.2589H7.63105H3.81281V12.8375Z" fill="#5D6772"/>
                                    <path d="M3.81281 15.2672V15.8457H5.6256H7.4382V15.2672V14.6886H5.6256H3.81281V15.2672Z" fill="#5D6772"/>
                                </svg>
                                <span class="menu-text">Reports</span>
                            </a>
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
                                <button id="notification-toggle" class="relative inline-flex items-center h-5 w-9 rounded-full bg-gray-200 transition-colors duration-200 ease-in-out focus:outline-none">
                                    <span id="notification-toggle-thumb" class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform duration-200 ease-in-out"></span>
                                </button>
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
    <div id="modal-slider-edit" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: 35%; min-width: 400px;">
        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-vendor-popup-btn-edit" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
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
                        <input type="hidden" id="editUserId" name="id" value="">
                        <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                            <div>
                                <label for="name" class="text-sm font-medium text-gray-700">Name:</label>
                                <input type="text" id="editName" name="name" class="form-input w-full mt-1" required>
                            </div>

                            <div>
                                <label for="email" class="text-sm font-medium text-gray-700">Email:</label>
                                <input type="email" id="editEmail" name="email" class="form-input w-full mt-1" readonly>
                            </div>

                            <div>
                                <label for="phone" class="text-sm font-medium text-gray-700">Phone:</label>
                                <input type="number" id="EditPhone" name="phone" class="form-input w-full mt-1" required>
                            </div>

                            <div>
                                <label for="password" class="text-sm font-medium text-gray-700">Password:</label>
                                <input type="password" id="editPassword" name="password" class="form-input w-full mt-1">
                            </div>

                        </div>

                        <div class="flex justify-center items-center gap-4 pt-6 border-t">
                            <button type="button" id="cancel-vendor-btn-edit" class="action-btn cancel-btn">Back</button>
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
    const toggleButton = document.getElementById('notification-toggle');
    const toggleThumb = document.getElementById('notification-toggle-thumb');

    let isToggleOn = false;

    // Toggle popup on button click
    profileMenuButton.addEventListener('click', (event) => {
        event.stopPropagation();
        logoutPopup.classList.toggle('hidden');
    });

    // Toggle switch functionality
    toggleButton.addEventListener('click', (event) => {
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
    });

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

    // Edit User Modal Logic    
    const popupWrapperEdit = document.getElementById('editUserProfileModal');
    const modalSliderEdit = document.getElementById('modal-slider-edit');
    const cancelVendorBtnEdit = document.getElementById('cancel-vendor-btn-edit');
    const closeVendorPopupBtnEdit = document.getElementById('close-vendor-popup-btn-edit');

    function openUserProfileEditModal(id) {
        fetch("?page=users&action=userDetails&id=" + id)
        .then(res => res.json())
        .then(user => {
            if (user.status === "error") {
                alert(user.message);
                return;
            }
            document.getElementById("editUserId").value   = user.id;
            document.getElementById("editName").value = user.name;
            document.getElementById("editEmail").value= user.email;
            document.getElementById("EditPhone").value= user.phone;

            popupWrapperEdit.classList.remove('hidden');
            setTimeout(() => {
                modalSliderEdit.classList.remove('translate-x-full');
            }, 10);
            //document.getElementById('editUserModal').show();
        });
    }

    function closeVendorPopupEdit() {
        modalSliderEdit.classList.add('translate-x-full');
    }

    closeVendorPopupBtnEdit.addEventListener('click', closeVendorPopupEdit);
    cancelVendorBtnEdit.addEventListener('click', closeVendorPopupEdit);

    document.getElementById('editUserProfile').onsubmit = function(e) {
        e.preventDefault();
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
                closeVendorPopupEdit();
                location.reload();
            }, 1000); // redirect after 1 sec
        });
    };
</script>