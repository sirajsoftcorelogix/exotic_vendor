<?php $loginUser = getloginUser();?>
<?php //$root_path = $_SERVER['DOCUMENT_ROOT'].'/VendorPortal';
//$domain = "http://".$_SERVER['SERVER_NAME']."/VendorPortal";
global $domain, $root_path;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vendor Portal</title>
    <link rel="icon" type="image/x-icon" href="images/EXOTIC_FAV_ICO.png">
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
	<div class="container-fluid">
  		<div class="row">
    		<div class="col-lg-2 d-none d-md-block">
    			<?php include 'views/layouts/left_menu.php'; ?>
			</div>
			<div class="col-lg-10">
				<!-- Top Navbar -->
				<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
					<div class="container-fluid">
						<button class="btn btn-outline-secondary d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
							<i class="fas fa-bars"></i>
						</button>
						<button id="toggleSidebar" class="btn btn-outline-secondary d-none d-md-block me-2">
							<i class="fas fa-bars"></i>
						</button>
					
						<span class="navbar-brand"><?php //echo $loginUser['tenant_Name'].' - '.$loginUser['store_code']; ?></span>
						
					
						
						<div class="navbar-nav ms-auto">
							<!-- <button class="btn btn-primary" onclick="document.location='index.php?page=orders&action=addEditorder'">New Order</button>
							-->
							<!-- Notifications -->
							<!-- <div class="dropdown mx-2">
								<button class="btn btn-outline-secondary position-relative" type="button" data-bs-toggle="dropdown">
								<i class="fas fa-bell"></i>
								<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>
								</button>
								<ul class="dropdown-menu dropdown-menu-end">
								<li><h6 class="dropdown-header">Notifications</h6></li>
								<li><a class="dropdown-item" href="#">New invoice created</a></li>
								<li><a class="dropdown-item" href="#">Customer feedback received</a></li>
								<li><a class="dropdown-item" href="#">Monthly report ready</a></li>
								</ul>
							</div> -->
							<!-- User Dropdown -->
							<div class="dropdown">
								<button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
								<i class="fas fa-user-circle"></i>
								</button>
								<ul class="dropdown-menu dropdown-menu-end bg-light border-0 shadow">
								<li><a class="dropdown-item" href="#">Profile</a></li>
								<li><a class="dropdown-item" href="#">Settings</a></li>
								<li><hr class="dropdown-divider"></li>
								<li><a class="dropdown-item" href="<?php echo $domain."/?page=users&action=logout"?>">Logout</a></li>
								</ul>
							</div>
						</div>
					</div>
				</nav>
				<!-- End Top Navbar -->
				<div id="mainContent" class="container mt-3">
					<?= $content ?? '' ?>
					<div class="text-center mt-5">
						© 2025 <a href="http://hexionit.com" target="_blank"> www.hexionit.com</a> | All rights reserved | SpinSuite Version 1.0.3
					</div>	
				</div>
			</div>
		</div>
	</div>

    <!-- Main Content -->
   <!--div class="w-100" style="margin-left: 275px;"-->
	<!-- <div  class="content">
    </div> -->
	
</div>
</body>
</html>