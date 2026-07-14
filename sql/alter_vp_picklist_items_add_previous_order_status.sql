-- Store order status at time of picklist add for accurate restore on remove.
ALTER TABLE `vp_picklist_items`
  ADD COLUMN `previous_order_status` VARCHAR(64) DEFAULT NULL AFTER `order_id`;
