<?php

header('Content-Type: application/json');

// Load database connection
require_once __DIR__ . '/../bootstrap/init/init.php';
require_once __DIR__ . '/CurrencyController.php';

if (!isset($_GET['code'])) {
    echo json_encode(['error' => 'Currency code not provided']);
    exit;
}

$currencyCode = strtoupper($_GET['code']);
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 30;

$controller = new CurrencyController($conn);
$history = $controller->getRateHistory($currencyCode, $limit);

echo json_encode($history);
exit;
?>
