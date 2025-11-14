<?php
// Start session (optional)
session_start();
// Load DB
require_once 'settings/database/database.php';
$conn = Database::getConnection();

$_SESSION['tenant_id']=1;
$_SESSION['currency_symbol']='₹';
$_SESSION['tenant_is'] = 1;
$_SESSION['tenant_Name'] = 'ExoticIndiaArt';

// Load helpers
require_once 'helpers/html_helpers.php';
//require_once 'helpers/menu_helpers.php'; // ← we'll move menu functions here
// Optionally include more common setup like error reporting, timezone, etc.
?>