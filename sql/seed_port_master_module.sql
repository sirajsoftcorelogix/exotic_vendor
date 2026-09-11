-- Sidebar menu link for Port Master.
-- Safe to re-run: skips insert if slug 'ports' already exists.
-- Administrator (role_id = 1) sees all active modules; other roles need the permission rows below.

SET @ports_parent := COALESCE(
    (SELECT parent_id FROM modules WHERE slug IN ('sizes', 'languages', 'materials', 'account_groups') AND parent_id > 0 ORDER BY id ASC LIMIT 1),
    (SELECT id FROM modules WHERE parent_id = 0 AND slug IN ('materials', 'account_groups') LIMIT 1),
    0
);

INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
SELECT
    @ports_parent,
    'Port Master',
    'ports',
    'list',
    '<i class="fas fa-plane-departure mr-2"></i>',
    1,
    1,
    225
WHERE NOT EXISTS (SELECT 1 FROM modules WHERE slug = 'ports' LIMIT 1);

SET @ports_module_id := (SELECT id FROM modules WHERE slug = 'ports' LIMIT 1);

-- Create one permission per active access tier (View Access, Sr Emp Access, etc.).
INSERT INTO `vp_permissions` (`module_id`, `module_name`, `action_name`, `is_active`, `user_id`)
SELECT
    @ports_module_id,
    'Port Master',
    ra.access_name,
    1,
    1
FROM `vp_role_access` ra
WHERE ra.is_active = '1'
  AND @ports_module_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `vp_permissions` p
      WHERE p.module_id = @ports_module_id
        AND p.action_name = ra.access_name
  );

-- Grant those permissions to every active role.
INSERT INTO `vp_role_permissions` (`role_id`, `permission_id`, `user_id`)
SELECT
    r.id,
    p.id,
    1
FROM `vp_roles` r
INNER JOIN `vp_permissions` p ON p.module_id = @ports_module_id
WHERE r.is_active = '1'
  AND @ports_module_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `vp_role_permissions` rp
      WHERE rp.role_id = r.id
        AND rp.permission_id = p.id
  );

-- Switch existing menu icon to air cargo (plane departure).
UPDATE `modules`
SET `font_awesome_icon` = '<i class="fas fa-plane-departure mr-2"></i>'
WHERE `slug` = 'ports';
