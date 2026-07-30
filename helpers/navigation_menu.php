<?php

/**
 * Access tier names from vp_role_access (ordered).
 *
 * @return string[]
 */
function resolveRoleAccessTierNames(): array
{
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}

	$cache = [];
	global $conn;
	if (!$conn instanceof mysqli) {
		return $cache;
	}

	$result = $conn->query("SELECT access_name FROM vp_role_access WHERE is_active = '1' ORDER BY id ASC");
	if (!$result) {
		return $cache;
	}

	while ($row = $result->fetch_assoc()) {
		$name = trim((string) ($row['access_name'] ?? ''));
		if ($name !== '') {
			$cache[] = $name;
		}
	}
	$result->free();

	return $cache;
}

/**
 * Permission action_name values that grant left-menu visibility for a module.
 * Elevation tiers (Sr Emp / Top Management) alone do not expose a module in the menu.
 *
 * @return string[]
 */
function resolveMenuPermissionActionNames(): array
{
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}

	$allTiers = resolveRoleAccessTierNames();
	if ($allTiers === []) {
		return $cache = [];
	}

	$elevationTiers = [];
	foreach (getAccessTiersIncluding('Sr Emp Access') as $tier) {
		$elevationTiers[] = $tier;
	}

	$menuTiers = array_values(array_filter(
		$allTiers,
		static fn(string $tier): bool => !in_array($tier, $elevationTiers, true)
	));

	return $cache = $menuTiers !== [] ? $menuTiers : $allTiers;
}

/**
 * Module ids the role may see in the left navigation.
 *
 * @return int[]
 */
function resolveAllowedMenuModuleIds(int $roleId): array
{
	if ($roleId === 1) {
		return resolveAllActiveMenuModuleIds();
	}

	$menuActions = resolveMenuPermissionActionNames();
	if ($menuActions === []) {
		return [];
	}

	global $conn;
	if (!$conn instanceof mysqli) {
		return [];
	}

	$actionPlaceholders = implode(',', array_fill(0, count($menuActions), '?'));
	$sql = "SELECT DISTINCT p.module_id
			FROM vp_role_permissions rp
			INNER JOIN vp_permissions p ON rp.permission_id = p.id
			INNER JOIN vp_roles r ON r.id = rp.role_id AND r.is_active = 1
			WHERE rp.role_id = ?
			  AND p.module_id IS NOT NULL
			  AND p.module_id > 0
			  AND p.action_name IN ({$actionPlaceholders})";

	$stmt = $conn->prepare($sql);
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
		$moduleIds[] = (int) ($row['module_id'] ?? 0);
	}
	$stmt->close();

	return array_values(array_unique(array_filter($moduleIds, static fn(int $id): bool => $id > 0)));
}

/**
 * @return int[]
 */
function resolveAllActiveMenuModuleIds(): array
{
	global $conn;
	if (!$conn instanceof mysqli) {
		return [];
	}

	$result = $conn->query('SELECT id FROM modules WHERE active = 1');
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
function sortNavigationMenu(array &$menu): void
{
	uasort($menu, static function (array $a, array $b): int {
		return ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));
	});

	foreach ($menu as &$item) {
		if (!empty($item['children']) && is_array($item['children'])) {
			sortNavigationMenu($item['children']);
		}
	}
	unset($item);
}

/**
 * Build left-nav menu tree for the current or given role.
 *
 * @return array<int, array<string, mixed>>
 */
function buildNavigationMenuTree(int $roleId): array
{
	$allowedModuleIds = resolveAllowedMenuModuleIds($roleId);
	if ($allowedModuleIds === []) {
		return [];
	}

	global $conn;
	if (!$conn instanceof mysqli) {
		return [];
	}

	$idsList = implode(',', array_map('intval', $allowedModuleIds));
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

	$result = $conn->query($sql);
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

	sortNavigationMenu($modules);

	$menu = [];
	foreach ($modules as $id => $module) {
		if ((int) ($module['parent_id'] ?? 0) !== 0) {
			continue;
		}

		$children = [];
		foreach ($module['children'] as $childId => $child) {
			if (!isset($allowedLookup[(int) $childId])) {
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

		if ($children === [] && !isset($allowedLookup[(int) $id])) {
			continue;
		}

		if ($children === [] && isset($allowedLookup[(int) $id])) {
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

	sortNavigationMenu($menu);

	return $menu;
}
