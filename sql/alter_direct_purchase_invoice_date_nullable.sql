-- Allow direct purchases without invoice date.

ALTER TABLE `vp_direct_purchases`
  MODIFY `invoice_date` DATE NULL DEFAULT NULL;
