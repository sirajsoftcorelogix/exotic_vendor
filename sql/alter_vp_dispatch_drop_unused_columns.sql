-- Remove unused vp_dispatch_details columns (run once on existing databases).
-- awb_code is the canonical waybill field; tracking_url is the dispatch-level tracking link.
-- Direct courier rows also store tracking_url on courier_shipments.
ALTER TABLE vp_dispatch_details
  DROP COLUMN tracking_number,
  DROP COLUMN pickup_date,
  DROP COLUMN is_re_dispatch,
  DROP COLUMN re_dispatch_count,
  DROP COLUMN shiprocket_tracking_url;
