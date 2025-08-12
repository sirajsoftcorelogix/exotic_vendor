<!-- views/layouts/header.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vendor Portal</title>
    <link rel="icon" type="image/x-icon" href="systemImages/SPINSUIT_FAV_ICO.png">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap 5 JS (includes Popper) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome (icons) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <!-- jQuery (required for AJAX, DOM manipulation, etc.) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>

    <link rel="stylesheet" href="style/style.css" />
</head>

<body>
<div id="app">
    <!-- Sidebar -->
    <?php include 'views/layouts/left_menu.php'; ?>

    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom sticky-top">
        <div class="container-fluid">
            <button class="btn btn-outline-secondary d-md-none me-2" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <button id="toggleSidebar" class="btn btn-outline-secondary d-none d-md-block me-2">
                <i class="fas fa-bars"></i>
            </button>
            <span class="navbar-brand">Say Sir Laundry Services - DLDWK07</span>

            <ul class="navbar-nav ms-auto">
                <button class="btn btn-primary" onclick="document.location='index.php?page=orders&action=addEditorder'">New Order</button>

                <!-- Notification Bell -->
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <li><h6 class="dropdown-header d-flex justify-content-between">Unread Notifications <span class="badge rounded-pill bg-danger">3</span></h6></li>
                        <li><a class="dropdown-item" href="#">🧺 New laundry order received</a></li>
                        <li><a class="dropdown-item" href="#">📦 Pickup scheduled at 5 PM</a></li>
                        <li><a class="dropdown-item" href="#">📄 Invoice #234 generated</a></li>
                    </ul>
                </li>

                <!-- User Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle fa-lg"></i> Admin
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="login.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Offcanvas Sidebar -->
    <div class="offcanvas offcanvas-start bg-dark text-white d-flex flex-column" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel" style="width: 260px;">
        <div class="offcanvas-body p-0 d-flex flex-column" style="height: 100vh;">
            <!-- Logo -->
            <div class="text-center p-3 border-bottom position-relative">
                <img src="systemImages/SpinSuiteLogo.png" alt="SpinSuite Logo" style="max-width: 160px;" id="sidebarLogo">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>

            <!-- Sidebar Menu -->
            <?php include 'views/layouts/left_menu.php'; ?>

            <!-- Footer -->
            <div class="p-3 border-top text-center small text-white mt-auto">
                © 2025 SpinSuite<br>Version 1.0.3
            </div>
        </div>
    </div>

    <!-- Main content wrapper starts -->
    <div class="container-fluid py-4">
