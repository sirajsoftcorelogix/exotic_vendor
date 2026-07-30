<?php

/**
 * Build left navigation from modules + role permission tables.
 *
 * Schema chain:
 * modules → vp_permissions (module_id, action_name from vp_role_access)
 *         → vp_role_permissions (role_id, permission_id)
 *         → vp_roles
 * User roles: vp_users.role_id (+ vp_user_roles when present)
 */
class NavigationMenu
{
    private mysqli $conn;

    /** @var array{user_column:string,role_column:string}|null|false */
    private static $userRolesTableMeta = false;

    public function __construct(mysqli $db)
    {
        $this->conn = $db;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildMenuTreeForUser(int $userId, int $primaryRoleId = 0): array
    {
        $roleIds = $this->resolveActiveRoleIdsForUser($userId, $primaryRoleId);
        if ($roleIds === []) {
            return [];
        }

        if (in_array(1, $roleIds, true)) {
            return $this->buildTreeFromModuleIds($this->fetchAllActiveModuleIds());
        }

        $accessTiers = $this->fetchActiveAccessTierNames();
        if ($accessTiers === []) {
            return [];
        }

        $allowedModuleIds = $this->fetchAllowedModuleIdsForRoles($roleIds, $accessTiers);
        if ($allowedModuleIds === []) {
            return [];
        }

        return $this->buildTreeFromModuleIds($allowedModuleIds);
    }

    /**
     * @return int[]
     */
    public function resolveActiveRoleIdsForUser(int $userId, int $primaryRoleId = 0): array
    {
        $roleIds = [];

        if ($primaryRoleId > 0) {
            $roleIds[] = $primaryRoleId;
        }

        if ($userId > 0) {
            foreach ($this->fetchRoleIdsFromUserRolesPivot($userId) as $roleId) {
                $roleIds[] = $roleId;
            }
        }

        if ($userId > 0 && $primaryRoleId <= 0) {
            $stmt = $this->conn->prepare(
                'SELECT u.role_id
                 FROM vp_users u
                 INNER JOIN vp_roles r ON r.id = u.role_id AND r.is_active = 1
                 WHERE u.id = ? AND u.is_deleted = 0
                 LIMIT 1'
            );
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result ? $result->fetch_assoc() : null;
                $stmt->close();
                if ($row) {
                    $roleIds[] = (int) ($row['role_id'] ?? 0);
                }
            }
        }

        $roleIds = array_values(array_unique(array_filter(
            $roleIds,
            static fn(int $id): bool => $id > 0
        )));

        if ($roleIds === []) {
            return [];
        }

        return $this->filterActiveRoleIds($roleIds);
    }

    /**
     * @return string[]
     */
    private function fetchActiveAccessTierNames(): array
    {
        $tiers = [];
        $result = $this->conn->query(
            "SELECT access_name FROM vp_role_access WHERE is_active = '1' ORDER BY id ASC"
        );
        if (!$result) {
            return $tiers;
        }

        while ($row = $result->fetch_assoc()) {
            $name = trim((string) ($row['access_name'] ?? ''));
            if ($name !== '') {
                $tiers[] = $name;
            }
        }
        $result->free();

        return $tiers;
    }

    /**
     * @param int[] $roleIds
     * @param string[] $accessTiers
     * @return int[]
     */
    private function fetchAllowedModuleIdsForRoles(array $roleIds, array $accessTiers): array
    {
        $roleIds = array_values(array_filter(array_map('intval', $roleIds), static fn(int $id): bool => $id > 0));
        if ($roleIds === []) {
            return [];
        }

        $rolePlaceholders = implode(',', array_fill(0, count($roleIds), '?'));
        $tierPlaceholders = implode(',', array_fill(0, count($accessTiers), '?'));

        $sql = "SELECT DISTINCT p.module_id
                FROM vp_role_permissions rp
                INNER JOIN vp_permissions p ON p.id = rp.permission_id
                INNER JOIN vp_roles r ON r.id = rp.role_id AND r.is_active = 1
                WHERE rp.role_id IN ({$rolePlaceholders})
                  AND p.module_id IS NOT NULL
                  AND p.module_id > 0
                  AND p.action_name IN ({$tierPlaceholders})";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $types = str_repeat('i', count($roleIds)) . str_repeat('s', count($accessTiers));
        $params = array_merge($roleIds, $accessTiers);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $moduleIds = [];
        while ($row = $result->fetch_assoc()) {
            $moduleIds[] = (int) ($row['module_id'] ?? 0);
        }
        $stmt->close();

        return array_values(array_unique(array_filter(
            $moduleIds,
            static fn(int $id): bool => $id > 0
        )));
    }

    /**
     * @return int[]
     */
    private function fetchAllActiveModuleIds(): array
    {
        $result = $this->conn->query('SELECT id FROM modules WHERE active = 1');
        if (!$result) {
            return [];
        }

        $moduleIds = [];
        while ($row = $result->fetch_assoc()) {
            $moduleIds[] = (int) ($row['id'] ?? 0);
        }
        $result->free();

        return array_values(array_filter($moduleIds, static fn(int $id): bool => $id > 0));
    }

    /**
     * @param int[] $allowedModuleIds
     * @return array<int, array<string, mixed>>
     */
    private function buildTreeFromModuleIds(array $allowedModuleIds): array
    {
        $allowedModuleIds = array_values(array_unique(array_filter(
            array_map('intval', $allowedModuleIds),
            static fn(int $id): bool => $id > 0
        )));
        if ($allowedModuleIds === []) {
            return [];
        }

        $idsList = implode(',', $allowedModuleIds);
        $sql = "SELECT DISTINCT m.id, m.parent_id, m.module_name, m.slug, m.action, m.font_awesome_icon, m.sort_order
                FROM modules m
                WHERE m.active = 1
                  AND (
                    m.id IN ({$idsList})
                    OR m.id IN (
                        SELECT DISTINCT parent_id
                        FROM modules
                        WHERE id IN ({$idsList})
                          AND parent_id IS NOT NULL
                          AND parent_id > 0
                    )
                  )
                ORDER BY COALESCE(m.parent_id, 0), m.sort_order ASC, m.module_name ASC";

        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $items[$id] = [
                'id' => $id,
                'parent_id' => (int) ($row['parent_id'] ?? 0),
                'name' => (string) ($row['module_name'] ?? ''),
                'slug' => (string) ($row['slug'] ?? ''),
                'action' => (string) ($row['action'] ?? ''),
                'icon' => stripslashes((string) ($row['font_awesome_icon'] ?? '')),
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'children' => [],
            ];
        }
        $result->free();

        $allowedLookup = array_fill_keys($allowedModuleIds, true);
        $roots = [];

        foreach ($items as $id => &$item) {
            if ($item['parent_id'] === 0) {
                $roots[$id] = &$item;
                continue;
            }

            if (isset($items[$item['parent_id']])) {
                $items[$item['parent_id']]['children'][$id] = &$item;
            }
        }
        unset($item);

        $this->sortMenuNodes($roots);

        $menu = [];
        foreach ($roots as $rootId => $root) {
            $children = [];
            foreach ($root['children'] as $childId => $child) {
                if (!isset($allowedLookup[(int) $childId])) {
                    continue;
                }
                $children[] = $this->normalizeMenuNode($child);
            }

            if ($children === [] && !isset($allowedLookup[(int) $rootId])) {
                continue;
            }

            if ($children === [] && isset($allowedLookup[(int) $rootId])) {
                $children[] = $this->normalizeMenuNode($root);
            }

            if ($children === []) {
                continue;
            }

            usort($children, static function (array $a, array $b): int {
                return ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));
            });

            $menu[(int) $rootId] = [
                'id' => (int) ($root['id'] ?? 0),
                'parent_id' => (int) ($root['parent_id'] ?? 0),
                'name' => (string) ($root['name'] ?? ''),
                'slug' => (string) ($root['slug'] ?? ''),
                'action' => (string) ($root['action'] ?? ''),
                'icon' => (string) ($root['icon'] ?? ''),
                'sort_order' => (int) ($root['sort_order'] ?? 0),
                'children' => $children,
            ];
        }

        $this->sortMenuNodes($menu);

        return $menu;
    }

    /**
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    private function normalizeMenuNode(array $node): array
    {
        return [
            'id' => (int) ($node['id'] ?? 0),
            'parent_id' => (int) ($node['parent_id'] ?? 0),
            'name' => (string) ($node['name'] ?? ''),
            'slug' => (string) ($node['slug'] ?? ''),
            'action' => (string) ($node['action'] ?? ''),
            'icon' => (string) ($node['icon'] ?? ''),
            'sort_order' => (int) ($node['sort_order'] ?? 0),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $menu
     */
    private function sortMenuNodes(array &$menu): void
    {
        uasort($menu, static function (array $a, array $b): int {
            return ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));
        });

        foreach ($menu as &$item) {
            if (!empty($item['children']) && is_array($item['children'])) {
                $this->sortMenuNodes($item['children']);
            }
        }
        unset($item);
    }

    /**
     * @param int[] $roleIds
     * @return int[]
     */
    private function filterActiveRoleIds(array $roleIds): array
    {
        $roleIds = array_values(array_unique(array_filter(
            array_map('intval', $roleIds),
            static fn(int $id): bool => $id > 0
        )));
        if ($roleIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $sql = "SELECT id FROM vp_roles WHERE is_active = 1 AND id IN ({$placeholders})";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return $roleIds;
        }

        $types = str_repeat('i', count($roleIds));
        $stmt->bind_param($types, ...$roleIds);
        $stmt->execute();
        $result = $stmt->get_result();

        $active = [];
        while ($row = $result->fetch_assoc()) {
            $active[] = (int) ($row['id'] ?? 0);
        }
        $stmt->close();

        return array_values(array_filter($active, static fn(int $id): bool => $id > 0));
    }

    /**
     * @return int[]
     */
    private function fetchRoleIdsFromUserRolesPivot(int $userId): array
    {
        $meta = $this->resolveUserRolesTableMeta();
        if ($meta === null) {
            return [];
        }

        $userColumn = $meta['user_column'];
        $roleColumn = $meta['role_column'];
        $sql = "SELECT DISTINCT ur.`{$roleColumn}` AS resolved_role_id
                FROM vp_user_roles ur
                INNER JOIN vp_roles r ON r.id = ur.`{$roleColumn}` AND r.is_active = 1
                WHERE ur.`{$userColumn}` = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $roleIds = [];
        while ($row = $result->fetch_assoc()) {
            $roleIds[] = (int) ($row['resolved_role_id'] ?? 0);
        }
        $stmt->close();

        return array_values(array_filter($roleIds, static fn(int $id): bool => $id > 0));
    }

    /**
     * Detect vp_user_roles column names (varies by environment).
     *
     * @return array{user_column:string,role_column:string}|null
     */
    private function resolveUserRolesTableMeta(): ?array
    {
        if (self::$userRolesTableMeta !== false) {
            return self::$userRolesTableMeta;
        }

        $tableResult = $this->conn->query("SHOW TABLES LIKE 'vp_user_roles'");
        if (!$tableResult || $tableResult->num_rows === 0) {
            if ($tableResult) {
                $tableResult->free();
            }

            return self::$userRolesTableMeta = null;
        }
        $tableResult->free();

        $columnsResult = $this->conn->query('SHOW COLUMNS FROM vp_user_roles');
        if (!$columnsResult) {
            return self::$userRolesTableMeta = null;
        }

        $columns = [];
        while ($row = $columnsResult->fetch_assoc()) {
            $field = (string) ($row['Field'] ?? '');
            if ($field !== '' && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field) === 1) {
                $columns[] = $field;
            }
        }
        $columnsResult->free();

        if ($columns === []) {
            return self::$userRolesTableMeta = null;
        }

        $lowerMap = [];
        foreach ($columns as $column) {
            $lowerMap[strtolower($column)] = $column;
        }

        $userColumn = $this->pickColumn($lowerMap, ['user_id', 'vp_user_id', 'userid']);
        $roleColumn = $this->pickColumn($lowerMap, ['role_id', 'vp_role_id', 'roles_id', 'roleid']);

        if ($userColumn === null) {
            foreach ($lowerMap as $lower => $original) {
                if (str_contains($lower, 'user')) {
                    $userColumn = $original;
                    break;
                }
            }
        }

        if ($roleColumn === null) {
            foreach ($lowerMap as $lower => $original) {
                if (str_contains($lower, 'role')) {
                    $roleColumn = $original;
                    break;
                }
            }
        }

        if ($userColumn === null || $roleColumn === null || $userColumn === $roleColumn) {
            return self::$userRolesTableMeta = null;
        }

        return self::$userRolesTableMeta = [
            'user_column' => $userColumn,
            'role_column' => $roleColumn,
        ];
    }

    /**
     * @param array<string, string> $lowerMap
     * @param string[] $candidates
     */
    private function pickColumn(array $lowerMap, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (isset($lowerMap[strtolower($candidate)])) {
                return $lowerMap[strtolower($candidate)];
            }
        }

        return null;
    }
}
