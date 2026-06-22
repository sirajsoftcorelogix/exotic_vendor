-- Performance indexes for inbound list (getAll), batch logs/images, and filter dropdowns.
-- Safe to run via: php scripts/apply_inbound_list_indexes.php --execute
-- (script skips indexes that already exist)

-- inbound_logs: batch fetch by inbound id, stat filters, published date range
ALTER TABLE `inbound_logs` ADD INDEX `idx_inbound_logs_i_id` (`i_id`);
ALTER TABLE `inbound_logs` ADD INDEX `idx_inbound_logs_i_id_stat` (`i_id`, `stat`);
ALTER TABLE `inbound_logs` ADD INDEX `idx_inbound_logs_stat_created_at` (`stat`, `created_at`);
ALTER TABLE `inbound_logs` ADD INDEX `idx_inbound_logs_stat_i_id` (`stat`, `i_id`);

-- item_images: list thumbnails + gallery ORDER BY display_order, id per item_id
ALTER TABLE `item_images` ADD INDEX `idx_item_images_item_display_id` (`item_id`, `display_order`, `id`);

-- vp_inbound: list filters and sort columns
ALTER TABLE `vp_inbound` ADD INDEX `idx_vp_inbound_vendor_code` (`vendor_code`);
ALTER TABLE `vp_inbound` ADD INDEX `idx_vp_inbound_received_by` (`received_by_user_id`);
ALTER TABLE `vp_inbound` ADD INDEX `idx_vp_inbound_updated_by` (`updated_by_user_id`);
ALTER TABLE `vp_inbound` ADD INDEX `idx_vp_inbound_group_name` (`group_name`);
ALTER TABLE `vp_inbound` ADD INDEX `idx_vp_inbound_created_at` (`created_at`);
ALTER TABLE `vp_inbound` ADD INDEX `idx_vp_inbound_modified_at` (`modified_at`);
ALTER TABLE `vp_inbound` ADD INDEX `idx_vp_inbound_assigned_user_at` (`assigned_to_user_id`, `assigned_at`, `id`);
ALTER TABLE `vp_inbound` ADD INDEX `idx_vp_inbound_marketplace` (`Marketplace`);
