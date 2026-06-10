-- Remove unused vp_dispatch_details columns (run once on existing databases).
-- awb_code is the canonical waybill field; re-dispatch uses shipment_status only.
ALTER TABLE vp_dispatch_details
  DROP COLUMN tracking_number,
  DROP COLUMN pickup_date,
  DROP COLUMN is_re_dispatch,
  DROP COLUMN re_dispatch_count;
