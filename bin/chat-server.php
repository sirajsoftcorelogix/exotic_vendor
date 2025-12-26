<?php
require __DIR__ . '/../vendor/autoload.php';

#ini_set('session.save_path', 'C:\\xampp\\tmp'); // <<< use exactly what phpinfo() shows

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use ChatModule\ChatServer;

$config = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$chat = new ChatServer($pdo);
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            $chat
        )
    ),
    $config['WS_PORT'],
    '127.0.0.1'
);

echo "Starting Ratchet WebSocket server on port ".$config['WS_PORT'];
$server->run();
