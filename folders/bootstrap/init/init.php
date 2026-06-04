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


$domain = "https://" . $_SERVER['SERVER_NAME'];
$root_path = $_SERVER['DOCUMENT_ROOT'];
define('EXPECTED_SECRET_KEY', 'b2d1127032446b78ce2b8911b72f6b155636f6898af2cf5d3aafdccf46778801');

$secretKey = "d71b3895474bad65c349de861e60acfbb0f38f104196a93368b4e4d97e9191e2"; //bank details encryption

// SMTP — used for login OTP, password reset, and other portal mail (see helpers/mail_helper.php)
define('smtpHost', 'glacier.mxrouting.net');
define('smtpPort', 587);           // 587 = STARTTLS, 465 = SSL
define('smtpSecure', 'tls');      // tls | ssl | none  (omit to auto-pick from port)
define('smtpUser', 'vendor-admin@exoticindia.com');
define('smtpPass', 'yXuF9tpZjeuHM8PdEGGc');
define('smtpFrom', 'vendor-admin@exoticindia.com');


// Load helpers
require_once 'helpers/html_helpers.php';
//require_once 'helpers/menu_helpers.php'; // ← we'll move menu functions here

// Optionally include more common setup like error reporting, timezone, etc.
$domain = "http://" . $_SERVER['SERVER_NAME'] . ':8080/';
$root_path = $_SERVER['DOCUMENT_ROOT'];
