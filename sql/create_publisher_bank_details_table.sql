-- Encrypted bank details for publisher master (mirrors vendor_bank_details)
CREATE TABLE IF NOT EXISTS publisher_bank_details (
    publisher_id INT UNSIGNED NOT NULL,
    account_holder_name VARBINARY(512) NULL,
    account_number VARBINARY(512) NULL,
    ifsc_code VARBINARY(512) NULL,
    bank_name VARBINARY(512) NULL,
    branch_name VARBINARY(512) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (publisher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
