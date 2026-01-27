<?php

require __DIR__ . '/../vendor/autoload.php';

use React\EventLoop\Loop;
use React\Socket\SocketServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;
use ChatModule\ChatServer;

// Load config
$config = require __DIR__ . '/../config.php';

// Create event loop (NEW API)
$loop = Loop::get();

// Create PDO (NON-persistent)
$dsn = sprintf(
    "mysql:host=%s;port=%d;dbname=%s;charset=%s",
    $config['db']['host'],
    $config['db']['port'] ?? 3306,
    $config['db']['name'],
    $config['db']['charset']
);

$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_PERSISTENT         => false,
]);

// Chat server
$chatServer = new ChatServer($pdo, $loop);

// Socket (bind to all interfaces)
$socket = new SocketServer(
    '0.0.0.0:' . $config['WS_PORT'],
    [],
    $loop
);

// Ratchet server
$server = new IoServer(
    new HttpServer(
        new WsServer($chatServer)
    ),
    $socket,
    $loop
);

echo "✅ WebSocket server running on port {$config['WS_PORT']}\n";

// Run loop (ONLY ONCE)
$loop->run();
