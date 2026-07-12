-- Optional sidebar menu for Order Status Management (vp_order_status CRUD).
-- Run manually and adjust parent_id / user_id / sort_order as needed.
-- Assign permissions to roles via Admin → Roles if the item does not appear.

/*
INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
VALUES (0, 'Order Status', 'order_status', 'list', '<i class="fas fa-tags mr-2"></i>', 1, 1, 210);

SET @os_parent := LAST_INSERT_ID();

INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
VALUES
(@os_parent, 'Manage statuses', 'order_status', 'list', '<i class="fas fa-list mr-2"></i>', 1, 1, 1);
*/
