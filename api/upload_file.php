<?php
require __DIR__ . '/../config.php';
$config = require __DIR__ . '/../config.php';

session_start();
$currentUser = $_SESSION["user"]['id'] ?? null;
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'no_file']);
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'upload_error']);
    exit;
}

$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'too_big']);
    exit;
}

// basic mime check
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'text/plain'
];

if (!in_array($mime, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'bad_type']);
    exit;
}

// Extract original filename safely
$originalName = $file['name'];
$ext = pathinfo($originalName, PATHINFO_EXTENSION);

// Generate safe random filename for storing
$safeName = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
$dest = $uploadDir . '/' . $safeName;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['error' => 'move_failed']);
    exit;
}

// Public path - adjust if needed
$publicPath = '/exotic_vendor/uploads/' . $safeName;

header('Content-Type: application/json');
echo json_encode([
    'path' => $publicPath,
    'original_name' => $originalName // <-- Return original filename
]);