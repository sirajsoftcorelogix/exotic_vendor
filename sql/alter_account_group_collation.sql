-- Align account_group with legacy category table (category.name uses utf8mb4_general_ci).
-- Fixes: Illegal mix of collations when joining account_group.item_group to category.name.

ALTER TABLE account_group
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
