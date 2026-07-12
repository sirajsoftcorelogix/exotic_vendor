-- Optional sidebar menu for Picklist module.
-- Run manually and adjust parent_id / user_id / sort_order as needed.
-- Assign permissions to roles via Admin → Roles if the item does not appear.

/*
INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
VALUES (0, 'Picklist', 'picklist', 'list', '<i class="fas fa-clipboard-list mr-2"></i>', 1, 1, 205);

SET @pl_parent := LAST_INSERT_ID();

INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
VALUES
(@pl_parent, 'Picklists', 'picklist', 'list', '<i class="fas fa-list mr-2"></i>', 1, 1, 1);
*/
