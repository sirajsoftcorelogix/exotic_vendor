<?php

require_once __DIR__ . '/../../helpers/navigation_menu.php';

class NavigationMenu
{
    private mysqli $conn;

    public function __construct(mysqli $db)
    {
        $this->conn = $db;
    }

    /**
     * @return int[]
     */
    public function getAllowedModuleIdsForRole(int $roleId): array
    {
        if ($roleId === 1) {
            return $this->getAllActiveModuleIds();
        }

        $menuActions = resolveNavigationMenuPermissionActions();
        if ($menuActions === []) {
            return [];
        }

        $actionPlaceholders = implode(',', array_fill(0, count($menuActions), '?'));
        $sql = "SELECT DISTINCT COALESCE(NULLIF(p.module_id, 0), m.id) AS resolved_module_id
                FROM vp_role_permissions rp
                INNER JOIN vp_permissions p ON rp.permission_id = p.id
                INNER JOIN vp_roles r ON r.id = rp.role_id AND r.is_active = 1
                LEFT JOIN modules m
                    ON m.module_name COLLATE utf8mb4_unicode_ci = p.module_name COLLATE utf8mb4_unicode_ci
                   AND m.active = 1
                WHERE rp.role_id = ?
                  AND p.action_name COLLATE utf8mb4_unicode_ci IN ({$actionPlaceholders})
                  AND COALESCE(NULLIF(p.module_id, 0), m.id) IS NOT NULL
                  AND COALESCE(NULLIF(p.module_id, 0), m.id) > 0";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $types = 'i' . str_repeat('s', count($menuActions));
        $params = array_merge([$roleId], $menuActions);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $moduleIds = [];
        while ($row = $result->fetch_assoc()) {
            $moduleIds[] = (int) ($row['resolved_module_id'] ?? 0);
        }
        $stmt->close();

        return array_values(array_unique(array_filter($moduleIds, static fn(int $id): bool => $id > 0)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildMenuTree(int $roleId): array
    {
        $allowedModuleIds = $this->getAllowedModuleIdsForRole($roleId);
        if ($allowedModuleIds === []) {
            return [];
        }

        $idsList = implode(',', array_map('intval', $allowedModuleIds));
        if ($roleId === 1) {
            $sql = "SELECT DISTINCT m.id, m.parent_id, m.module_name, m.slug, m.action, m.font_awesome_icon, m.sort_order
                    FROM modules m
                    WHERE m.active = 1
                    ORDER BY COALESCE(m.parent_id, 0), m.sort_order ASC, m.module_name ASC";
            $result = $this->conn->query($sql);
        } else {
            $sql = "SELECT DISTINCT m.id, m.parent_id, m.module_name, m.slug, m.action, m.font_awesome_icon, m.sort_order
                    FROM modules m
                    WHERE m.active = 1
                      AND (
                        m.id IN ({$idsList})
                        OR m.id IN (
                            SELECT DISTINCT parent_id
                            FROM modules
                            WHERE id IN ({$idsList}) AND parent_id IS NOT NULL AND parent_id > 0
                        )
                      )
                    ORDER BY COALESCE(m.parent_id, 0), m.sort_order ASC, m.module_name ASC";
            $result = $this->conn->query($sql);
        }

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
        $modules = [];

        foreach ($items as $id => &$item) {
            if ($item['parent_id'] === 0) {
                $modules[$id] = &$item;
                continue;
            }

            if (isset($items[$item['parent_id']])) {
                $items[$item['parent_id']]['children'][$id] = &$item;
            }
        }
        unset($item);

        $this->sortMenu($modules);

        $menu = [];
        foreach ($modules as $id => $module) {
            if ((int) ($module['parent_id'] ?? 0) !== 0) {
                continue;
            }

            $children = [];
            foreach ($module['children'] as $childId => $child) {
                if (!isset($allowedLookup[(int) $childId]) && $roleId !== 1) {
                    continue;
                }
                $children[] = [
                    'id' => (int) ($child['id'] ?? 0),
                    'parent_id' => (int) ($child['parent_id'] ?? 0),
                    'name' => (string) ($child['name'] ?? ''),
                    'slug' => (string) ($child['slug'] ?? ''),
                    'action' => (string) ($child['action'] ?? ''),
                    'icon' => (string) ($child['icon'] ?? ''),
                    'sort_order' => (int) ($child['sort_order'] ?? 0),
                ];
            }

            if ($children === [] && !isset($allowedLookup[(int) $id]) && $roleId !== 1) {
                continue;
            }

            if ($children === [] && (isset($allowedLookup[(int) $id]) || $roleId === 1)) {
                $children[] = [
                    'id' => (int) ($module['id'] ?? 0),
                    'parent_id' => (int) ($module['parent_id'] ?? 0),
                    'name' => (string) ($module['name'] ?? ''),
                    'slug' => (string) ($module['slug'] ?? ''),
                    'action' => (string) ($module['action'] ?? ''),
                    'icon' => (string) ($module['icon'] ?? ''),
                    'sort_order' => (int) ($module['sort_order'] ?? 0),
                ];
            }

            if ($children === []) {
                continue;
            }

            usort($children, static function (array $a, array $b): int {
                return ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));
            });

            $menu[$id] = [
                'id' => (int) ($module['id'] ?? 0),
                'parent_id' => (int) ($module['parent_id'] ?? 0),
                'name' => (string) ($module['name'] ?? ''),
                'slug' => (string) ($module['slug'] ?? ''),
                'action' => (string) ($module['action'] ?? ''),
                'icon' => (string) ($module['icon'] ?? ''),
                'sort_order' => (int) ($module['sort_order'] ?? 0),
                'children' => $children,
            ];
        }

        $this->sortMenu($menu);

        return $menu;
    }

    /**
     * @return int[]
     */
    private function getAllActiveModuleIds(): array
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
     * @param array<int, array<string, mixed>> $menu
     */
    private function sortMenu(array &$menu): void
    {
        uasort($menu, static function (array $a, array $b): int {
            return ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));
        });

        foreach ($menu as &$item) {
            if (!empty($item['children']) && is_array($item['children'])) {
                $this->sortMenu($item['children']);
            }
        }
        unset($item);
    }
}
