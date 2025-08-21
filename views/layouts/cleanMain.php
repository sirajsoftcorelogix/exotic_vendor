<?php $loginUser = getloginUser();
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
<link rel="stylesheet" href="style/login.css" />
</head>
<body>
<div id="app" class="viewport">
    <!-- Main Content -->
   <!--div class="w-100" style="margin-left: 275px;"-->
	<div id="mainContent" class="container-fluid" >
        <?= $content ?? '' ?>
    </div>
</div>
</body>
</html>