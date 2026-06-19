<?php

/**
 * Step-by-step timing logs for inbound module requests (502 / overload diagnosis).
 *
 * Storage (EXOTIC_INBOUND_PROFILER_STORAGE):
 *   both (default) — database + JSON files when the log dir is writable
 *   db             — database only
 *   file           — JSON files only
 *   auto           — files first, database if file write fails
 *
 * Files: logs/inbound_profiler/ (or EXOTIC_INBOUND_PROFILER_DIR)
 * Database: vp_inbound_profiler_logs (auto-created on first write)
 */

if (!defined('INBOUND_PROFILER_ENABLED')) {
    $env = getenv('EXOTIC_INBOUND_PROFILER');
    define('INBOUND_PROFILER_ENABLED', $env === false || $env === '' || $env === '1' || strtolower((string) $env) === 'true');
}

if (!defined('INBOUND_PROFILER_STORAGE')) {
    $storage = strtolower(trim((string) (getenv('EXOTIC_INBOUND_PROFILER_STORAGE') ?: 'both')));
    if (!in_array($storage, ['both', 'db', 'file', 'auto'], true)) {
        $storage = 'both';
    }
    define('INBOUND_PROFILER_STORAGE', $storage);
}

if (!defined('INBOUND_PROFILER_DIR')) {
    $envDir = getenv('EXOTIC_INBOUND_PROFILER_DIR');
    if ($envDir !== false && trim((string) $envDir) !== '') {
        define('INBOUND_PROFILER_DIR', rtrim((string) $envDir, DIRECTORY_SEPARATOR));
    } else {
        define('INBOUND_PROFILER_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'inbound_profiler');
    }
}

if (!defined('INBOUND_PROFILER_MAX_FILES')) {
    define('INBOUND_PROFILER_MAX_FILES', 200);
}

if (!defined('INBOUND_PROFILER_MAX_ROWS')) {
    define('INBOUND_PROFILER_MAX_ROWS', 200);
}

if (!defined('INBOUND_PROFILER_DB_TABLE')) {
    define('INBOUND_PROFILER_DB_TABLE', 'vp_inbound_profiler_logs');
}

function inbound_profiler_ensure_dir(string $dir): bool
{
    if (is_dir($dir)) {
        return is_writable($dir);
    }

    if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }

    return is_writable($dir);
}

/**
 * Primary log directory plus fallback when the app tree is not writable (common on production).
 *
 * @return list<string>
 */
function inbound_profiler_log_dirs(): array
{
    $dirs = [];
    $primary = INBOUND_PROFILER_DIR;
    if ($primary !== '') {
        $dirs[] = $primary;
    }

    $fallback = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'exotic_inbound_profiler';
    if (!in_array($fallback, $dirs, true)) {
        $dirs[] = $fallback;
    }

    return $dirs;
}

function inbound_profiler_note_write_error(string $dir, string $message): void
{
    $payload = [
        'timestamp' => date('Y-m-d H:i:s'),
        'dir' => $dir,
        'message' => $message,
        'user' => function_exists('posix_getpwuid') && function_exists('posix_geteuid')
            ? (posix_getpwuid(posix_geteuid())['name'] ?? null)
            : null,
        'php_sapi' => PHP_SAPI,
    ];
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return;
    }

    @file_put_contents($dir . DIRECTORY_SEPARATOR . '_profiler_write_error.json', $json . "\n", LOCK_EX);
    error_log('[inbound_profiler] ' . $message . ' (dir=' . $dir . ')');
}

function inbound_profiler_prune(string $dir, int $maxFiles = INBOUND_PROFILER_MAX_FILES): void
{
    if ($maxFiles < 1 || !is_dir($dir)) {
        return;
    }
    $pattern = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'inbound_*.json';
    $files = glob($pattern) ?: [];
    if (count($files) <= $maxFiles) {
        return;
    }
    usort($files, static function ($a, $b) {
        return (filemtime($a) ?: 0) <=> (filemtime($b) ?: 0);
    });
    foreach (array_slice($files, 0, count($files) - $maxFiles) as $file) {
        @unlink($file);
    }
}

function inbound_profiler_db_connection(): ?mysqli
{
    static $resolved = null;
    static $loaded = false;

    if ($loaded) {
        return $resolved;
    }
    $loaded = true;

    global $conn;
    if (isset($conn) && $conn instanceof mysqli) {
        $resolved = $conn;
        return $resolved;
    }

    $dbFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.php';
    if (!is_file($dbFile)) {
        return null;
    }

    require_once $dbFile;
    if (!class_exists('Database')) {
        return null;
    }

    try {
        $resolved = Database::getConnection();
    } catch (Throwable $e) {
        error_log('[inbound_profiler] DB connection failed: ' . $e->getMessage());
        $resolved = null;
    }

    return $resolved;
}

function inbound_profiler_db_table_ready(mysqli $conn): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }

    $table = INBOUND_PROFILER_DB_TABLE;
    $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        run_id VARCHAR(16) NOT NULL,
        action VARCHAR(80) NOT NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'ok',
        total_ms INT UNSIGNED NOT NULL DEFAULT 0,
        memory_peak_mb DECIMAL(8,2) NULL,
        user_id INT UNSIGNED NULL,
        inbound_id INT UNSIGNED NULL,
        request_uri VARCHAR(512) NULL,
        payload_json MEDIUMTEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_action (action),
        INDEX idx_total_ms (total_ms),
        INDEX idx_inbound_id (inbound_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $ready = (bool) $conn->query($sql);
    if (!$ready) {
        error_log('[inbound_profiler] CREATE TABLE failed: ' . $conn->error);
    }

    return $ready;
}

function inbound_profiler_prune_db(mysqli $conn, int $maxRows = INBOUND_PROFILER_MAX_ROWS): void
{
    if ($maxRows < 1) {
        return;
    }

    $table = INBOUND_PROFILER_DB_TABLE;
    $maxRows = (int) $maxRows;
    $sql = "DELETE FROM `{$table}` WHERE id NOT IN (
        SELECT id FROM (
            SELECT id FROM `{$table}` ORDER BY id DESC LIMIT {$maxRows}
        ) AS keep_rows
    )";
    $conn->query($sql);
}

/**
 * @param array<string, mixed> $payload
 */
function inbound_profiler_write_db(array $payload, string $runId): bool
{
    $conn = inbound_profiler_db_connection();
    if (!$conn instanceof mysqli || !inbound_profiler_db_table_ready($conn)) {
        return false;
    }

    $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
    $inboundId = isset($meta['inbound_id']) ? (int) $meta['inbound_id'] : null;
    if ($inboundId !== null && $inboundId <= 0) {
        $inboundId = null;
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }

    $table = INBOUND_PROFILER_DB_TABLE;
    $sql = "INSERT INTO `{$table}`
        (run_id, action, status, total_ms, memory_peak_mb, user_id, inbound_id, request_uri, payload_json, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('[inbound_profiler] INSERT prepare failed: ' . $conn->error);
        return false;
    }

    $runIdShort = substr($runId, 0, 16);
    $action = (string) ($payload['action'] ?? 'unknown');
    $status = (string) ($payload['status'] ?? 'ok');
    $totalMs = (int) ($payload['total_ms'] ?? 0);
    $memoryPeak = (float) ($payload['memory_peak_mb'] ?? 0);
    $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : null;
    if ($userId !== null && $userId <= 0) {
        $userId = null;
    }
    $requestUri = (string) ($payload['request_uri'] ?? '');
    if (strlen($requestUri) > 512) {
        $requestUri = substr($requestUri, 0, 512);
    }
    $createdAt = (string) ($payload['timestamp'] ?? date('Y-m-d H:i:s'));

    $stmt->bind_param(
        'sssidiisss',
        $runIdShort,
        $action,
        $status,
        $totalMs,
        $memoryPeak,
        $userId,
        $inboundId,
        $requestUri,
        $json,
        $createdAt
    );

    $ok = $stmt->execute();
    if (!$ok) {
        error_log('[inbound_profiler] INSERT failed: ' . $stmt->error);
    }
    $stmt->close();

    if ($ok) {
        inbound_profiler_prune_db($conn);
    }

    return $ok;
}

/**
 * @param array<string, mixed> $payload
 */
function inbound_profiler_write_file(array $payload, string $runId): bool
{
    $dir = null;
    foreach (inbound_profiler_log_dirs() as $candidate) {
        if (inbound_profiler_ensure_dir($candidate)) {
            $dir = $candidate;
            break;
        }
        inbound_profiler_note_write_error(
            $candidate,
            'Profiler directory is missing or not writable'
        );
    }
    if ($dir === null) {
        return false;
    }

    $action = (string) ($payload['action'] ?? 'unknown');
    $slug = preg_replace('/[^a-z0-9_]+/i', '_', $action) ?: 'inbound';
    $file = $dir . DIRECTORY_SEPARATOR . sprintf(
        'inbound_%s_%s_%s.json',
        date('Ymd_His'),
        substr($runId, 0, 8),
        substr($slug, 0, 40)
    );

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        inbound_profiler_note_write_error($dir, 'json_encode failed for profiler payload');
        return false;
    }

    $written = @file_put_contents($file, $json . "\n", LOCK_EX);
    if ($written === false) {
        inbound_profiler_note_write_error($dir, 'file_put_contents failed for ' . basename($file));
        return false;
    }

    inbound_profiler_prune($dir);
    return true;
}

/**
 * @return list<array<string, mixed>>
 */
function inbound_profiler_fetch_db_rows(int $limit = 30): array
{
    $conn = inbound_profiler_db_connection();
    if (!$conn instanceof mysqli || !inbound_profiler_db_table_ready($conn)) {
        return [];
    }

    $limit = max(1, $limit);
    $table = INBOUND_PROFILER_DB_TABLE;
    $sql = "SELECT id, run_id, action, status, total_ms, memory_peak_mb, payload_json, created_at
            FROM `{$table}`
            ORDER BY id DESC
            LIMIT {$limit}";

    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
        if (!is_array($payload)) {
            $payload = [];
        }
        $rows[] = [
            'source' => 'db',
            'file' => 'db#' . ($row['id'] ?? ''),
            'action' => $payload['action'] ?? ($row['action'] ?? '?'),
            'total_ms' => (int) ($payload['total_ms'] ?? ($row['total_ms'] ?? 0)),
            'memory_mb' => $payload['memory_peak_mb'] ?? ($row['memory_peak_mb'] ?? '?'),
            'status' => $payload['status'] ?? ($row['status'] ?? '?'),
            'meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            'steps' => is_array($payload['steps'] ?? null) ? $payload['steps'] : [],
            'created_at' => $row['created_at'] ?? null,
        ];
    }
    $result->free();

    return $rows;
}

function inbound_profiler_db_diagnostics(): array
{
    $conn = inbound_profiler_db_connection();
    if (!$conn instanceof mysqli) {
        return [
            'available' => false,
            'table_ready' => false,
            'row_count' => 0,
            'error' => 'Database connection unavailable',
        ];
    }

    if (!inbound_profiler_db_table_ready($conn)) {
        return [
            'available' => true,
            'table_ready' => false,
            'row_count' => 0,
            'error' => 'Could not create or access ' . INBOUND_PROFILER_DB_TABLE,
        ];
    }

    $table = INBOUND_PROFILER_DB_TABLE;
    $countResult = $conn->query("SELECT COUNT(*) AS cnt FROM `{$table}`");
    $rowCount = 0;
    if ($countResult && ($countRow = $countResult->fetch_assoc())) {
        $rowCount = (int) ($countRow['cnt'] ?? 0);
        $countResult->free();
    }

    return [
        'available' => true,
        'table_ready' => true,
        'table' => $table,
        'row_count' => $rowCount,
        'error' => null,
    ];
}

/**
 * @param array<string, mixed> $meta
 */
function inbound_profiler_start(string $action, array $meta = []): string
{
    $runId = bin2hex(random_bytes(8));
    if (!isset($GLOBALS['_inbound_profiler_runs']) || !is_array($GLOBALS['_inbound_profiler_runs'])) {
        $GLOBALS['_inbound_profiler_runs'] = [];
    }
    $GLOBALS['_inbound_profiler_runs'][$runId] = [
        'action' => $action,
        'started_at' => microtime(true),
        'last_mark' => microtime(true),
        'meta' => $meta,
        'steps' => [],
        'memory_peak_mb' => 0,
    ];

    return $runId;
}

/**
 * @param array<string, mixed> $extra
 */
function inbound_profiler_step(string $runId, string $label, array $extra = []): void
{
    if ($runId === '' || empty($GLOBALS['_inbound_profiler_runs'][$runId])) {
        return;
    }
    $run = &$GLOBALS['_inbound_profiler_runs'][$runId];
    $now = microtime(true);
    $run['steps'][] = [
        'step' => $label,
        'ms_since_start' => (int) round(($now - $run['started_at']) * 1000),
        'ms_since_prev' => (int) round(($now - $run['last_mark']) * 1000),
        'extra' => $extra,
    ];
    $run['last_mark'] = $now;
    $run['memory_peak_mb'] = round(memory_get_peak_usage(true) / 1048576, 2);
}

/**
 * @param array<string, mixed> $extra
 */
function inbound_profiler_finish(string $runId, string $status = 'ok', array $extra = []): void
{
    if (!INBOUND_PROFILER_ENABLED || $runId === '' || empty($GLOBALS['_inbound_profiler_runs'][$runId])) {
        unset($GLOBALS['_inbound_profiler_runs'][$runId]);
        return;
    }

    $run = $GLOBALS['_inbound_profiler_runs'][$runId];
    unset($GLOBALS['_inbound_profiler_runs'][$runId]);

    $totalMs = (int) round((microtime(true) - $run['started_at']) * 1000);
    $action = (string) ($run['action'] ?? 'unknown');

    $alwaysLog = in_array($action, [
        'inbound_product_publish',
        'updatedesktopform',
        'syncVendorReferenceCache',
        'download_photos',
    ], true);
    $slowThresholdMs = ($action === 'list') ? 3000 : 1500;
    if (!$alwaysLog && $totalMs < $slowThresholdMs) {
        return;
    }

    $payload = [
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => $status,
        'action' => $action,
        'total_ms' => $totalMs,
        'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
        'user_id' => $_SESSION['user']['id'] ?? null,
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        'meta' => $run['meta'] ?? [],
        'steps' => $run['steps'] ?? [],
        'extra' => $extra,
    ];

    $storage = INBOUND_PROFILER_STORAGE;
    $fileOk = false;
    $dbOk = false;

    if ($storage === 'file' || $storage === 'both' || $storage === 'auto') {
        $fileOk = inbound_profiler_write_file($payload, $runId);
    }
    if ($storage === 'db' || $storage === 'both' || ($storage === 'auto' && !$fileOk)) {
        $dbOk = inbound_profiler_write_db($payload, $runId);
    }

    if (!$fileOk && !$dbOk) {
        error_log('[inbound_profiler] Failed to persist profiler run for action=' . $action . ' storage=' . $storage);
    }
}
