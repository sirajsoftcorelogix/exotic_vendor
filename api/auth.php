<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check user login
if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../config.php';
$config = require __DIR__ . '/../config.php';

// Database connection (adjust credentials)
$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
    $config['db']['user'],
    $config['db']['pass'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
