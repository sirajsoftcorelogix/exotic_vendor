-- Speed up payments list filtered by order_number (POS receipt → Payment History links).
-- Safe to run more than once: ignore "Duplicate key name" if the index already exists.

CREATE INDEX idx_pos_payments_order_number ON pos_payments (order_number);
