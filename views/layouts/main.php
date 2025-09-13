<?php $loginUser = getloginUser();?>
<?php //$root_path = $_SERVER['DOCUMENT_ROOT'].'/exotic_vendor';
//$domain = "http://".$_SERVER['SERVER_NAME']."/exotic_vendor";
global $domain, $root_path, $page, $action, $conn;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vendor Portal</title>
    <link rel="icon" type="image/x-icon" href="images/EXOTIC_FAV_ICO.png">
	<script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
	<!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet" /> -->
	<!-- Bootstrap 5 JS (includes Popper) -->
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script> -->
	<!-- Font Awesome (icons) -->
	<!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" /> -->
	<!-- jQuery (required for AJAX, DOM manipulation, etc.) -->
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script> -->
	<link rel="stylesheet" href="style/style.css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
	<script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ96j3p6QhL6pG6L7Jz4uB4pB7I9fX6p5z5w2k4t5N6g9/5o5e+n8t1t2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
</head>
<body class="bg-gray-100">
<div class="flex h-screen ">
<!-- Header Component -->
<!-- Updated with bottom border -->

<?php include 'views/layouts/left_menu.php'; ?>
<div class="flex-1 flex flex-col overflow-hidden">
	<?php require_once 'header.php'; ?>
	<!-- End Top Navbar -->
	<main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
	<?= $content ?? '' ?>
	</main>
	
	<!-- Footer Component -->
	<footer class="w-full bg-gray-100">
		<div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
			<!-- Top border line -->
			<div class="border-t border-gray-300"></div>

			<!-- Footer content -->
			<div class="flex items-center justify-between pt-4">
				<!-- Left side: Copyright information -->
				<p class="text-sm text-gray-600">
					&copy; 2025 <span class="text-gray-800 font-medium">Exotic India Pvt. Ltd.</span>, All Rights Reserved
				</p>

				<!-- Right side: Version number -->
				<p class="text-sm text-gray-600">
					Version : 1.3.8
				</p>
			</div>
		</div>
	</footer>
</div>
</div>
</body>
</html>