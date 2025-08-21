<?php
// Start session (optional)
session_start();
// Load DB
require_once 'settings/database/database.php';
$conn = Database::getConnection();

$_SESSION['tenant_id']=1;
$_SESSION['currency_symbol']='₹';
$_SESSION['tenant_Name'] = 'Exotic India Art';

$_SESSION['store_id']=1;
$_SESSION['store_code']='DL001';

$_SESSION['user_id']=1;
$_SESSION['user_full_name']='Siraj Ali';

// Load helpers
require_once 'helpers/html_helpers.php';
//require_once 'helpers/menu_helpers.php'; // ← we'll move menu functions here

// Optionally include more common setup like error reporting, timezone, etc.

$domain = "http://" . $_SERVER['SERVER_NAME'].'/exotic_vendor';
$root_path = $_SERVER['DOCUMENT_ROOT'].'/exotic_vendor';
?>
