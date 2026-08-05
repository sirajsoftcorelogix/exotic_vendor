-- SQL script to add "Bulk Stock Adjustment" menu item under the Products module.
-- Adjust parent_id or sort_order if your modules table uses a different parent ID for Products.

-- Option A: Insert automatically under the existing "Products" parent module (parent_id = 0)
INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
SELECT id, 'Bulk Stock Adjustment', 'products', 'bulk_stock_adjustment', '<i class="fas fa-boxes-stacked mr-2"></i>', 1, 1, 50
FROM `modules` 
WHERE `parent_id` = 0 AND (`slug` = 'products' OR `module_name` LIKE '%Product%')
LIMIT 1;

-- Option B: Manual insert if you know your Products parent module ID (replace @products_parent_id with actual parent ID, e.g. 1)
/*
SET @products_parent_id := (SELECT id FROM `modules` WHERE `parent_id` = 0 AND `slug` = 'products' LIMIT 1);

INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
VALUES (@products_parent_id, 'Bulk Stock Adjustment', 'products', 'bulk_stock_adjustment', '<i class="fas fa-boxes-stacked mr-2"></i>', 1, 1, 50);
*/
