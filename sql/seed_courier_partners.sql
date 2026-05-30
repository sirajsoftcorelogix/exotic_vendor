-- Seed common courier partners (safe to re-run).
INSERT INTO courier_partners (partner_code, partner_name, supports_domestic, supports_international, is_active, notes)
VALUES
    ('ARAMEX', 'Aramex', 1, 1, 1, 'International express; SOAP API'),
    ('DHL', 'DHL Express', 0, 1, 1, 'International express; MyDHL REST API'),
    ('DELHIVERY', 'Delhivery', 1, 0, 1, 'Domestic bulk dispatch; implement DelhiveryAdapter'),
    ('BLUEDART', 'Blue Dart', 1, 0, 1, 'Domestic bulk dispatch; implement BlueDartAdapter'),
    ('FEDEX', 'FedEx', 0, 1, 1, 'International single dispatch; FedEx REST API'),
    ('UPS', 'UPS', 0, 1, 1, 'International single dispatch; UPS REST API'),
    ('SHIPROCKET', 'Shiprocket', 1, 0, 1, 'Domestic aggregator (legacy fallback)')
ON DUPLICATE KEY UPDATE
    partner_name = VALUES(partner_name),
    supports_domestic = VALUES(supports_domestic),
    supports_international = VALUES(supports_international),
    is_active = VALUES(is_active),
    notes = VALUES(notes),
    updated_at = CURRENT_TIMESTAMP;
