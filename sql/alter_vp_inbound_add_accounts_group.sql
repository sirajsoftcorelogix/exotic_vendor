-- Link inbound line to account_group (filtered by item group on desktop form)
ALTER TABLE vp_inbound
    ADD COLUMN accounts_group INT UNSIGNED NULL DEFAULT NULL AFTER group_name,
    ADD INDEX idx_vp_inbound_accounts_group (accounts_group);
