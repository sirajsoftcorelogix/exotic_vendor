-- Cancelled / returned are terminal: no outgoing workflow transitions.
-- Safe to re-run.

UPDATE vp_workflow_transition wt
INNER JOIN vp_order_status fs ON fs.id = wt.from_status_id
SET wt.is_active = 0
WHERE fs.slug IN ('cancelled', 'cancelled_returned', 'returned')
  AND wt.is_active = 1;
