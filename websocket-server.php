<?php
/**
 * LIVE WEBSOCKET SERVER
 * ----------------------
 * Production-ready Ratchet WebSocket Server
 * Supports:
 *  - Real-time chat messaging
 *  - Session-based authentication
 *  - Group chat
 *  - Presence
 *  - Read receipts
 */

use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/ChatServer.php';

/******************************************
 * 1. CONFIGURATION (EDIT FOR LIVE SERVER)
 ******************************************/

$DB_HOST = "localhost";
$DB_NAME = "vendor_portal_test";
$DB_USER = "vendor_user";
$DB_PASS = "eXotic@123";

// WebSocket Port (Change only if needed)
$WS_PORT = 8888;

/******************************************
 * 2. DATABASE CONNECTION
 ******************************************/

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    file_put_contents(__DIR__ . "/ws_error.log",
        "[" . date('Y-m-d H:i:s') . "] DB ERROR: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    exit("Database connection failed. Check ws_error.log");
}

/******************************************
 * 3. SESSION PATH FIX FOR LIVE SERVERS
 ******************************************/

$sessionPath = ini_get("session.save_path");
if (!$sessionPath || !is_dir($sessionPath)) {
    // Shared hosting fallback
    $sessionPath = sys_get_temp_dir();
}

// Export path to ChatServer dynamically
ChatModule\ChatServer::$SESSION_PATH = $sessionPath;

/******************************************
 * 4. START THE WEBSOCKET SERVER
 ******************************************/

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatModule\ChatServer($pdo)
        )
    ),
    $WS_PORT
);

echo "-------------------------------------------\n";
echo " WebSocket Server LIVE MODE\n";
echo " Running on port: {$WS_PORT}\n";
echo " Session Path: {$sessionPath}\n";
echo "-------------------------------------------\n";

$server->run();
