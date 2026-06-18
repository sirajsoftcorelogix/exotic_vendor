<?php
/**
 * Virtual verification for inbound desktopform accounts_group feature.
 * Run: php scripts/virtual_test_inbound_accounts_group.php
 * Optional DB checks if bootstrap/database is available.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$failures = 0;
$passes = 0;

function assert_true(bool $cond, string $label): void
{
    global $failures, $passes;
    if ($cond) {
        $passes++;
        echo "PASS: {$label}\n";
    } else {
        $failures++;
        echo "FAIL: {$label}\n";
    }
}

echo "=== Inbound accounts_group virtual test ===\n\n";

// --- Logic: API publish value from joined row ---
function resolveAccountGroupApiValueFromJoin(array $row): string
{
    $name = trim((string) ($row['account_group_name'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    return '';
}

assert_true(
    resolveAccountGroupApiValueFromJoin(['account_group_name' => 'WOOD FURNITURE', 'accounts_group' => 5]) === 'WOOD FURNITURE',
    'Publish uses account_group_name from JOIN when present'
);
assert_true(
    resolveAccountGroupApiValueFromJoin(['account_group_name' => '', 'accounts_group' => 0]) === '',
    'Empty accounts_group yields no publish account_group'
);

// --- Logic: save POST mapping (InboundingController updatedesktopform) ---
$mapSave = static function (array $post): ?int {
    return trim((string) ($post['accounts_group'] ?? '')) === '' ? null : (int) $post['accounts_group'];
};
assert_true($mapSave(['accounts_group' => '12']) === 12, 'Save casts accounts_group id to int');
assert_true($mapSave(['accounts_group' => '']) === null, 'Empty accounts_group saves as NULL');

// --- Static wiring checks ---
$index = file_get_contents($root . '/index.php');
assert_true(strpos($index, "case 'fetchAccountGroups':") !== false, 'Route fetchAccountGroups registered');

$controller = file_get_contents($root . '/controllers/InboundingController.php');
assert_true(strpos($controller, "'accounts_group'") !== false, 'Controller saves accounts_group');
assert_true(strpos($controller, "\$API_data['account_group']") !== false, 'Publish sets API account_group');
assert_true(strpos($controller, 'fetchAccountGroupsAjax') !== false, 'AJAX endpoint exists');
assert_true(strpos($controller, 'getActiveByGroupCategoryCode') !== false, 'Loads groups by group category code');

$model = file_get_contents($root . '/models/inbounding/Inbounding.php');
assert_true(strpos($model, 'ensureInboundAccountsGroupColumn') !== false, 'Column ensure on save');
assert_true(strpos($model, 'LEFT JOIN account_group ag ON vi.accounts_group = ag.id') !== false, 'Publish query joins account_group');
assert_true(strpos($model, 'resolveAccountGroupApiValue') !== false, 'API value resolver exists');

$accountGroupModel = file_get_contents($root . '/models/account_group/AccountGroup.php');
assert_true(strpos($accountGroupModel, 'getActiveByGroupCategoryCode') !== false, 'Filter by category.category');
assert_true(strpos($accountGroupModel, 'getActiveByItemGroup') !== false, 'Filter by item_group slug');

$view = file_get_contents($root . '/views/inbounding/desktopform.php');
assert_true(strpos($view, 'id="accounts_group_select"') !== false, 'Accounts Group dropdown in form');
assert_true(strpos($view, 'name="accounts_group"') !== false, 'Form field name accounts_group');
assert_true(strpos($view, "getVal('accounts_group')") !== false, 'Client validation for accounts_group');
assert_true(strpos($view, 'reloadAccountsGroupsForGroup') !== false, 'Reload dropdown when Group changes');
assert_true(strpos($view, 'fetchAccountGroups') !== false, 'AJAX URL for account groups');

// --- Optional live DB ---
echo "\n--- Optional DB checks ---\n";
$dbBootstrap = $root . '/bootstrap/init/settings/database/database.php';
if (!is_file($dbBootstrap)) {
    echo "SKIP: database bootstrap not found (no local DB config)\n";
} else {
    try {
        require_once $dbBootstrap;
        $conn = Database::getConnection();
        require_once $root . '/models/account_group/AccountGroup.php';
        require_once $root . '/models/inbounding/Inbounding.php';

        $colRes = $conn->query("SHOW COLUMNS FROM vp_inbound LIKE 'accounts_group'");
        assert_true($colRes && $colRes->num_rows > 0, 'vp_inbound.accounts_group column exists');

        $agTable = $conn->query("SHOW TABLES LIKE 'account_group'");
        assert_true($agTable && $agTable->num_rows > 0, 'account_group table exists');

        $agModel = new AccountGroup($conn);
        $inboundModel = new Inbounding($conn);

        // Sample group -4 (Home & Living) if category data exists
        $groups = $agModel->getActiveByGroupCategoryCode('-4');
        assert_true(is_array($groups), 'getActiveByGroupCategoryCode returns array');
        if (count($groups) > 0) {
            echo "INFO: Found " . count($groups) . " account groups for category -4\n";
            assert_true(isset($groups[0]['id']) && isset($groups[0]['account_group_name']), 'Group row has id and name');
        } else {
            echo "WARN: No account groups for -4 (check account_group.item_group vs category.name)\n";
        }

        $slug = $inboundModel->resolveItemGroupSlugFromGroupName('-4');
        if ($slug !== '') {
            echo "INFO: group -4 resolves to item_group slug: {$slug}\n";
            $bySlug = $agModel->getActiveByItemGroup($slug);
            assert_true(is_array($bySlug), 'getActiveByItemGroup returns array');
        }

        $inboundId = 3450;
        $stmt = $conn->prepare('SELECT id, group_name, accounts_group FROM vp_inbound WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $inboundId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                echo "INFO: inbound #{$inboundId} group_name={$row['group_name']} accounts_group=" . ($row['accounts_group'] ?? 'NULL') . "\n";
            } else {
                echo "SKIP: inbound #{$inboundId} not in this database\n";
            }
        }
    } catch (Throwable $e) {
        echo "SKIP DB: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Summary: {$passes} passed, {$failures} failed ===\n";
exit($failures > 0 ? 1 : 0);
