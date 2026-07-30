<?php

/**
 * Permission action_name values that grant left-nav visibility.
 * Uses explicit Sr Emp Access when present; elevation-only tiers do not expand menu access.
 *
 * @return string[]
 */
function resolveNavigationMenuPermissionActions(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    global $conn;
    if (!$conn instanceof mysqli) {
        return $cache = [];
    }

    $allTiers = [];
    $result = $conn->query("SELECT access_name FROM vp_role_access WHERE is_active = '1' ORDER BY id ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $name = trim((string) ($row['access_name'] ?? ''));
            if ($name !== '') {
                $allTiers[] = $name;
            }
        }
        $result->free();
    }

    if (in_array('Sr Emp Access', $allTiers, true)) {
        return $cache = ['Sr Emp Access'];
    }

    if (!function_exists('getAccessTiersIncluding')) {
        require_once __DIR__ . '/html_helpers.php';
    }

    $elevationTiers = getAccessTiersIncluding('Sr Emp Access');
    $baseTiers = array_values(array_filter(
        $allTiers,
        static fn(string $tier): bool => !in_array($tier, $elevationTiers, true)
    ));

    if ($baseTiers !== []) {
        return $cache = $baseTiers;
    }

    return $cache = $allTiers !== [] ? [$allTiers[0]] : [];
}

/**
 * @param string[] $moduleNames
 */
function userHasNavigationMenuAccess(int $userId, array $moduleNames): bool
{
    if ($userId <= 0 || $moduleNames === []) {
        return false;
    }
    if (isAdministratorUser()) {
        return true;
    }

    foreach ($moduleNames as $moduleName) {
        $moduleName = trim((string) $moduleName);
        if ($moduleName === '') {
            continue;
        }
        foreach (resolveNavigationMenuPermissionActions() as $actionName) {
            if (hasPermission($userId, $moduleName, $actionName)) {
                return true;
            }
        }
    }

    return false;
}
