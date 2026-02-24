-- Create table for storing saved searches per user
CREATE TABLE IF NOT EXISTS `saved_searches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `page` VARCHAR(100) NOT NULL DEFAULT 'orders',
  `name` VARCHAR(255) NOT NULL,
  `query` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_page` (`user_id`, `page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- invoice related tables
CREATE TABLE vp_order_info (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED DEFAULT NULL,

    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    company VARCHAR(150) DEFAULT NULL,

    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) DEFAULT NULL,
    state_iso VARCHAR(10) DEFAULT NULL,
    state_code VARCHAR(10) DEFAULT NULL,
    country CHAR(2) NOT NULL,
    zipcode VARCHAR(20) NOT NULL,

    mobile VARCHAR(20) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    gstin VARCHAR(20) DEFAULT NULL,

    shipping_first_name VARCHAR(100) DEFAULT NULL,
    shipping_last_name VARCHAR(100) DEFAULT NULL,
    shipping_company VARCHAR(150) DEFAULT NULL,
    shipping_address_line1 VARCHAR(255) DEFAULT NULL,
    shipping_address_line2 VARCHAR(255) DEFAULT NULL,
    shipping_city VARCHAR(100) DEFAULT NULL,
    shipping_state VARCHAR(100) DEFAULT NULL,
    shipping_state_iso VARCHAR(10) DEFAULT NULL,
    shipping_state_code VARCHAR(10) DEFAULT NULL,
    shipping_country CHAR(2) DEFAULT NULL,
    shipping_zipcode VARCHAR(20) DEFAULT NULL,
    shipping_mobile VARCHAR(20) DEFAULT NULL,
    shipping_email VARCHAR(150) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
  

CREATE TABLE IF NOT EXISTS `vp_customers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_email_phone` (`email`, `phone`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE vp_invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    invoice_number VARCHAR(100) NOT NULL,
    invoice_date DATE NOT NULL,

    customer_id BIGINT UNSIGNED NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'INR',

    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,

    status ENUM('draft','final') 
           NOT NULL DEFAULT 'draft',

    notes TEXT NULL,

    
    created_by BIGINT UNSIGNED DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_invoice_number (invoice_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_invoice_date (invoice_date)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
  
ALTER TABLE `vp_invoices` ADD `vp_order_info_id` INT NULL AFTER `customer_id`;

CREATE TABLE vp_invoice_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    invoice_id BIGINT UNSIGNED NOT NULL,
    order_number VARCHAR(100) NULL,

    item_code VARCHAR(100) NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT NULL,

    box_no VARCHAR(50) NULL,

    quantity DECIMAL(12,3) NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,

    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,

    cgst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    sgst DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    igst DECIMAL(15,2) NOT NULL DEFAULT 0.00,

    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_invoice_id (invoice_id),

    CONSTRAINT fk_invoice_items_invoice
        FOREIGN KEY (invoice_id)
        REFERENCES vp_invoices(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO vp_po_invoice_map (po_id, invoice_id)
SELECT i.po_id, i.id
FROM vp_po_invoice i
LEFT JOIN vp_po_invoice_map m
    ON m.invoice_id = i.id
WHERE m.invoice_id IS NULL
  AND i.po_id IS NOT NULL;


CREATE TABLE global_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_prefix VARCHAR(20) NOT NULL,
    invoice_series INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO global_settings (invoice_prefix, invoice_series) VALUES ('INV', 1);

CREATE TABLE firm_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firm_name VARCHAR(150) NOT NULL,
    pan VARCHAR(20),
    gst VARCHAR(20),
    address TEXT,
    phone VARCHAR(20),
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100),
    pin VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Add customer_id column to vp_orders table not updated in test
ALTER TABLE `vp_orders` ADD `customer_id` INT NULL AFTER `vendor_id`;
ALTER TABLE `vp_invoice_items` ADD `hsn` VARCHAR(50) NULL AFTER `item_code`;
ALTER TABLE `vp_invoices` CHANGE `vp_address_info_id` `vp_order_info_id` INT(11) NULL DEFAULT NULL;
--ALTER TABLE `vp_invoice_items` CHANGE `item_code` `sku` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

INSERT INTO `firm_details` (`id`, `firm_name`, `pan`, `gst`, `address`, `phone`, `city`, `state`, `country`, `pin`, `created_at`, `updated_at`) VALUES
(1, 'EXOTIC INDIA ART PVT LTD', 'AADCE1400C', '07AADCE1400C1ZJ', 'A-16/1, Wazirpur Industrial Estate', NULL, NULL, 'Delhi', 'India', '110052', '2026-01-18 10:21:05', '2026-01-18 10:21:05');


INSERT INTO `global_settings` (`id`, `invoice_prefix`, `invoice_series`, `terms_and_conditions`, `created_at`, `updated_at`) VALUES
(1, 'inv/2025-26/', 10017, NULL, '2026-01-18 10:21:56', '2026-01-21 21:02:44');

--new column for storing invoice number in orders table
ALTER TABLE `vp_orders` ADD `invoice_no` VARCHAR(100) NULL AFTER `customer_id`;

--invoice id
ALTER TABLE `vp_orders` CHANGE `invoice_no` `invoice_id` INT NULL DEFAULT NULL;

CREATE TABLE currency_master (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    currency_code CHAR(3) NOT NULL,        -- USD, INR, EUR
    currency_name VARCHAR(50) NOT NULL,    -- US Dollar, Indian Rupee
    currency_unit VARCHAR(20) NOT NULL,    -- 1 USD, 100 JPY, etc.

    rate_import DECIMAL(12,6) NOT NULL DEFAULT 0.000000,
    rate_export DECIMAL(12,6) NOT NULL DEFAULT 0.000000,

    is_active TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_currency_code (currency_code)
);

CREATE TABLE currency_rate_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    currency_code CHAR(3) NOT NULL,
    rate_import DECIMAL(12,6) NOT NULL,
    rate_export DECIMAL(12,6) NOT NULL,
    rate_date DATE NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_currency_date (currency_code, rate_date)
);

ALTER TABLE `vp_orders` ADD `agent_assign_date` DATETIME NULL AFTER `agent_id`;

ALTER TABLE `vp_invoices` ADD `exchange_text` VARCHAR(100) NULL AFTER `currency`,
ADD `converted_amount` DECIMAL(15,2) NULL DEFAULT 0.00 AFTER `exchange_text`;


INSERT INTO `currency_master`
(`id`, `currency_code`, `currency_name`, `currency_unit`, `rate_import`, `rate_export`, `is_active`, `created_at`, `updated_at`)
VALUES
(1, 'AUD', 'Australian Dollar', '1 AUD', 54.20, 54.80, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(2, 'BHD', 'Bahraini Dinar', '1 BHD', 220.50, 222.00, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(3, 'CAD', 'Canadian Dollar', '1 CAD', 61.80, 62.40, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(4, 'CNY', 'Chinese Yuan', '1 CNY', 11.40, 11.60, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(5, 'DKK', 'Danish Krone', '1 DKK', 12.10, 12.30, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(6, 'EUR', 'Euro', '1 EUR', 89.50, 90.20, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(7, 'HKD', 'Hong Kong Dollar', '1 HKD', 10.60, 10.80, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(8, 'KWD', 'Kuwaiti Dinar', '1 KWD', 270.00, 272.00, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(9, 'NZD', 'New Zealand Dollar', '1 NZD', 50.10, 50.70, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(10, 'NOK', 'Norwegian Krone', '1 NOK', 7.70, 7.90, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(11, 'GBP', 'Pound Sterling', '1 GBP', 104.20, 105.00, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(12, 'QAR', 'Qatari Riyal', '1 QAR', 22.70, 23.00, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(13, 'SAR', 'Saudi Arabian Riyal', '1 SAR', 22.10, 22.40, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(14, 'SGD', 'Singapore Dollar', '1 SGD', 61.90, 62.50, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(15, 'ZAR', 'South African Rand', '1 ZAR', 4.40, 4.60, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(16, 'SEK', 'Swedish Kroner', '1 SEK', 7.90, 8.10, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(17, 'CHF', 'Swiss Franc', '1 CHF', 94.50, 95.20, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(18, 'TRY', 'Turkish Lira', '1 TRY', 2.60, 2.80, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(19, 'AED', 'UAE Dirham', '1 AED', 22.60, 22.90, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(20, 'USD', 'US Dollar', '1 USD', 83.25, 83.50, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(21, 'JPY', 'Japanese Yen', '100 JPY', 55.20, 55.80, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16'),
(22, 'KRW', 'Korean Won', '100 KRW', 6.20, 6.50, 1, '2026-01-28 14:26:16', '2026-01-28 14:26:16');

Create table vp_invoices_international (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    invoice_id BIGINT UNSIGNED NOT NULL,

    pre_carriage_by VARCHAR(100) NULL,
    port_of_loading VARCHAR(100) NULL,
    port_of_discharge VARCHAR(100) NULL,
    country_of_origin VARCHAR(100) NULL,
    country_of_final_destination VARCHAR(100) NULL,
    final_destination VARCHAR(255) NULL,
    usd_export_rate DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    ap_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    freight_charge DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    insurance_charge DECIMAL(15,2) NOT NULL DEFAULT 0.00,   

    irn VARCHAR(200) NULL,    
    ack_number VARCHAR(100) NULL,
    ack_date TIMESTAMP NULL,
    signed_invoice TEXT NULL,
    qrcode_string TEXT NULL,
    irn_status VARCHAR(50) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_invoice_id (invoice_id),

    CONSTRAINT fk_invoices_international_invoice
        FOREIGN KEY (invoice_id)
        REFERENCES vp_invoices(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

ALTER table `vp_order_info` ADD `total` DECIMAL(15,2) NULL DEFAULT 0 AFTER `shipping_email`;

ALTER TABLE `vp_products` ADD `notes` TEXT NULL AFTER `discount_india`;

--create dispatch details table
CREATE TABLE vp_dispatch_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(100) NOT NULL,
    invoice_id BIGINT UNSIGNED NULL, 
    box_no VARCHAR(50) NULL,
    length DECIMAL(15,2) NULL DEFAULT 0.00,
    width DECIMAL(15,2) NULL DEFAULT 0.00,
    height DECIMAL(15,2) NULL DEFAULT 0.00,
    weight DECIMAL(15,2) NULL DEFAULT 0.00,
    volumetric_weight DECIMAL(15,2) NULL DEFAULT 0.00,
    billing_weight DECIMAL(15,2) NULL DEFAULT 0.00,
    shipping_charges DECIMAL(15,2) NULL DEFAULT 0.00,      
    dispatch_date DATE NOT NULL,
    courier_name VARCHAR(255) NOT NULL,
    tracking_number VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
-- Add foreign key constraint to link dispatch details with invoices
ALTER TABLE vp_dispatch_details
    ADD CONSTRAINT fk_dispatch_details_invoice
        FOREIGN KEY (invoice_id)
        REFERENCES vp_invoices(id)
        ON DELETE CASCADE;
-- Add index on order_number for faster lookups
CREATE INDEX idx_order_number ON vp_dispatch_details(order_number);
-- Add index on invoice_id for faster lookups
CREATE INDEX idx_invoice_id ON vp_dispatch_details(invoice_id);
ALTER TABLE vp_dispatch_details ADD `shiprocket_order_id` VARCHAR(100) NULL AFTER `tracking_number`,
ADD `shiprocket_shipment_id` VARCHAR(100) NULL AFTER `shiprocket_order_id`,
ADD `shiprocket_tracking_url` VARCHAR(255) NULL AFTER `shiprocket_shipment_id`,
ADD `awb_code` VARCHAR(100) NULL AFTER `shiprocket_tracking_url`,
ADD `shipment_status` VARCHAR(50) NULL AFTER `awb_code`,
ADD `label_url` VARCHAR(255) NULL AFTER `shipment_status`;

ALTER TABLE `vp_invoice_items` ADD `image_url` VARCHAR(255) NULL AFTER `item_code`;
ALTER TABLE `vp_dispatch_details` ADD `box_items` TEXT NULL AFTER `weight`;
ALTER TABLE `vp_dispatch_details` ADD `created_by` INT NULL AFTER `label_url`;

ALTER TABLE `vp_order_info` ADD `giftvoucher` VARCHAR(100) NULL AFTER `updated_at`, ADD `giftvoucher_reduce` VARCHAR(100) NULL AFTER `giftvoucher`, ADD `transid` VARCHAR(100) NULL AFTER `giftvoucher_reduce`, ADD `currency` VARCHAR(100) NULL AFTER `transid`, ADD `payment_type` VARCHAR(100) NULL AFTER `currency`, ADD `coupon` VARCHAR(100) NULL AFTER `payment_type`, ADD `coupon_reduce` VARCHAR(100) NULL AFTER `coupon`;
ALTER TABLE `vp_order_info` ADD `credit` DECIMAL(10,0) NULL AFTER `coupon_reduce`;
ALTER TABLE `vp_vendors` CHANGE `vendor_code` `vendor_code` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;


CREATE TABLE `shiprocket_api_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `token` text NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shiprocket_api_tokens`
--

INSERT INTO `shiprocket_api_tokens` (`id`, `token`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjI1NjAxMjUsInNvdXJjZSI6InNyLWF1dGgtaW50IiwiZXhwIjoxNzcyNDQxODU3LCJqdGkiOiJ0MHQ5OGVra0hVcDkxQWxuIiwiaWF0IjoxNzcxNTc3ODU3LCJpc3MiOiJodHRwczovL3NyLWF1dGguc2hpcHJvY2tldC5pbi9hdXRob3JpemUvdXNlciIsIm5iZiI6MTc3MTU3Nzg1NywiY2lkIjoyOTg1MDcsInRjIjozNjAsInZlcmJvc2UiOmZhbHNlLCJ2ZW5kb3JfaWQiOjAsInZlbmRvcl9jb2RlIjoiIn0.DcsXO_szP7se17CuZE1nHMNIfvjOxLotI7zqSNHYLZM', '2026-03-02 14:27:27', '2026-02-20 18:36:06', '2026-02-21 12:22:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `shiprocket_api_tokens`
--
ALTER TABLE `shiprocket_api_tokens`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `shiprocket_api_tokens`
--
ALTER TABLE `shiprocket_api_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;