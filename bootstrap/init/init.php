<?php
// Start session (optional)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Load DB
require_once 'settings/database/database.php';
$conn = Database::getConnection();

$_SESSION['tenant_id']=1;
$_SESSION['currency_symbol']='₹';

$_SESSION['tenant_id'] = 1;
$_SESSION['tenant_Name'] = 'Exotic India Art';

// Load helpers
require_once 'helpers/html_helpers.php';
//require_once 'helpers/menu_helpers.php'; // ← we'll move menu functions here

// Optionally include more common setup like error reporting, timezone, etc.
$domain = "http://" . $_SERVER['SERVER_NAME'] . '/exotic_vendor';
$root_path = $_SERVER['DOCUMENT_ROOT'];
?>