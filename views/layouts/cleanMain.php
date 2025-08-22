<?php $loginUser = getloginUser();
global $domain, $root_path;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vendor Onboarding Portal</title>
    <link rel="icon" type="image/x-icon" href="images/EXOTIC_FAV_ICO.png">
    <!-- Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Instrument+Sans:wght@600&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap 5 CSS -->
	<!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet" /> -->
	<!-- Bootstrap 5 JS (includes Popper) -->
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script> -->
	<!-- Font Awesome (icons) -->
	<!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" /> -->
	<!-- jQuery (required for AJAX, DOM manipulation, etc.) -->
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script> -->
<link rel="stylesheet" href="style/style.css" />
</head>
<body class="bg-white">
    <!-- Main container for the two panels, now taking full screen height -->
    <div class="flex flex-col md:flex-row w-full min-h-screen">
        <?= $content ?? '' ?>
    </div>
</body>
</html>