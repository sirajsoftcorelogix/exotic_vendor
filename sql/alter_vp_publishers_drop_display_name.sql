-- Remove unused display_name from publisher master (website remains)
ALTER TABLE vp_publishers DROP COLUMN display_name;
