-- Add Not Available / Partially Available item statuses to picklists
ALTER TABLE `vp_picklist_items`
  MODIFY COLUMN `status` ENUM('pending', 'picked', 'not_available', 'partially_available') NOT NULL DEFAULT 'pending';
