-- GST numeric state codes (Place of Supply / Stcd) for Indian states.
-- Existing state_code column keeps the 2-letter abbreviation (AP, AS, ...).
-- Run once against the vendor portal database.

ALTER TABLE `states`
    ADD COLUMN `gst_state_code` CHAR(2) NULL DEFAULT NULL COMMENT 'GST department numeric state code' AFTER `state_code`;

CREATE INDEX `idx_states_country_gst_code` ON `states` (`country_id`, `gst_state_code`);

-- India (country_id = 105). Adjust country_id if your India row differs.
UPDATE `states` SET `gst_state_code` = '37' WHERE `country_id` = 105 AND `name` IN ('Andhra Pradesh');
UPDATE `states` SET `gst_state_code` = '12' WHERE `country_id` = 105 AND `name` IN ('Arunachal Pradesh');
UPDATE `states` SET `gst_state_code` = '18' WHERE `country_id` = 105 AND `name` IN ('Assam');
UPDATE `states` SET `gst_state_code` = '10' WHERE `country_id` = 105 AND `name` IN ('Bihar');
UPDATE `states` SET `gst_state_code` = '22' WHERE `country_id` = 105 AND `name` IN ('Chhattisgarh');
UPDATE `states` SET `gst_state_code` = '30' WHERE `country_id` = 105 AND `name` IN ('Goa');
UPDATE `states` SET `gst_state_code` = '24' WHERE `country_id` = 105 AND `name` IN ('Gujarat');
UPDATE `states` SET `gst_state_code` = '06' WHERE `country_id` = 105 AND `name` IN ('Haryana');
UPDATE `states` SET `gst_state_code` = '02' WHERE `country_id` = 105 AND `name` IN ('Himachal Pradesh');
UPDATE `states` SET `gst_state_code` = '20' WHERE `country_id` = 105 AND `name` IN ('Jharkhand');
UPDATE `states` SET `gst_state_code` = '29' WHERE `country_id` = 105 AND `name` IN ('Karnataka');
UPDATE `states` SET `gst_state_code` = '32' WHERE `country_id` = 105 AND `name` IN ('Kerala');
UPDATE `states` SET `gst_state_code` = '23' WHERE `country_id` = 105 AND `name` IN ('Madhya Pradesh');
UPDATE `states` SET `gst_state_code` = '27' WHERE `country_id` = 105 AND `name` IN ('Maharashtra');
UPDATE `states` SET `gst_state_code` = '14' WHERE `country_id` = 105 AND `name` IN ('Manipur');
UPDATE `states` SET `gst_state_code` = '17' WHERE `country_id` = 105 AND `name` IN ('Meghalaya');
UPDATE `states` SET `gst_state_code` = '15' WHERE `country_id` = 105 AND `name` IN ('Mizoram');
UPDATE `states` SET `gst_state_code` = '13' WHERE `country_id` = 105 AND `name` IN ('Nagaland');
UPDATE `states` SET `gst_state_code` = '21' WHERE `country_id` = 105 AND `name` IN ('Odisha', 'Orissa');
UPDATE `states` SET `gst_state_code` = '03' WHERE `country_id` = 105 AND `name` IN ('Punjab');
UPDATE `states` SET `gst_state_code` = '08' WHERE `country_id` = 105 AND `name` IN ('Rajasthan');
UPDATE `states` SET `gst_state_code` = '11' WHERE `country_id` = 105 AND `name` IN ('Sikkim');
UPDATE `states` SET `gst_state_code` = '33' WHERE `country_id` = 105 AND `name` IN ('Tamil Nadu');
UPDATE `states` SET `gst_state_code` = '36' WHERE `country_id` = 105 AND `name` IN ('Telangana');
UPDATE `states` SET `gst_state_code` = '16' WHERE `country_id` = 105 AND `name` IN ('Tripura');
UPDATE `states` SET `gst_state_code` = '09' WHERE `country_id` = 105 AND `name` IN ('Uttar Pradesh');
UPDATE `states` SET `gst_state_code` = '05' WHERE `country_id` = 105 AND `name` IN ('Uttarakhand', 'Uttaranchal');
UPDATE `states` SET `gst_state_code` = '19' WHERE `country_id` = 105 AND `name` IN ('West Bengal');

-- Union Territories
UPDATE `states` SET `gst_state_code` = '35' WHERE `country_id` = 105 AND `name` IN ('Andaman and Nicobar Islands', 'Andaman & Nicobar Islands');
UPDATE `states` SET `gst_state_code` = '04' WHERE `country_id` = 105 AND `name` IN ('Chandigarh');
UPDATE `states` SET `gst_state_code` = '26' WHERE `country_id` = 105 AND `name` IN (
    'Dadra and Nagar Haveli and Daman and Diu',
    'Dadra & Nagar Haveli and Daman & Diu',
    'Dadra and Nagar Haveli',
    'Daman and Diu'
);
UPDATE `states` SET `gst_state_code` = '07' WHERE `country_id` = 105 AND `name` IN ('Delhi', 'NCT of Delhi', 'New Delhi');
UPDATE `states` SET `gst_state_code` = '01' WHERE `country_id` = 105 AND `name` IN ('Jammu and Kashmir', 'Jammu & Kashmir');
UPDATE `states` SET `gst_state_code` = '38' WHERE `country_id` = 105 AND `name` IN ('Ladakh');
UPDATE `states` SET `gst_state_code` = '31' WHERE `country_id` = 105 AND `name` IN ('Lakshadweep');
UPDATE `states` SET `gst_state_code` = '34' WHERE `country_id` = 105 AND `name` IN ('Puducherry', 'Pondicherry');

-- Legacy Andhra Pradesh row (pre-2014), if still present
UPDATE `states` SET `gst_state_code` = '28' WHERE `country_id` = 105 AND `name` IN ('Andhra Pradesh (Old)');

-- Optional: back-fill by abbreviation when name variants differ
UPDATE `states` SET `gst_state_code` = '37' WHERE `country_id` = 105 AND `gst_state_code` IS NULL AND `state_code` = 'AP' AND `name` LIKE '%Andhra%';
UPDATE `states` SET `gst_state_code` = '18' WHERE `country_id` = 105 AND `gst_state_code` IS NULL AND `state_code` = 'AS';
UPDATE `states` SET `gst_state_code` = '12' WHERE `country_id` = 105 AND `gst_state_code` IS NULL AND `state_code` = 'AR';
UPDATE `states` SET `gst_state_code` = '10' WHERE `country_id` = 105 AND `gst_state_code` IS NULL AND `state_code` = 'BR';
