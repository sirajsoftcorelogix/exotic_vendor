<?php

/**
 * BusyAccounting Model
 * Responsible for retrieving sales invoices, sales returns, KPI metrics,
 * and voucher line-item breakdowns for accounting/bookkeeping review and BUSY integration.
 * BUSY integration operates organization-wide across all sales and returns (sharing company GSTIN).
 */
class BusyAccounting
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Fetch unified list of organization-wide accounting vouchers (Sales + Sales Returns)
     */
    public function getAccountingVouchers(array $filters): array
    {
        $startDate   = !empty($filters['start_date']) ? trim($filters['start_date']) : '';
        $endDate     = !empty($filters['end_date']) ? trim($filters['end_date']) : '';
        $voucherType = !empty($filters['voucher_type']) ? trim($filters['voucher_type']) : 'all'; // all, sales, sales_return
        $search      = !empty($filters['search']) ? trim($filters['search']) : '';
        $page        = max(1, (int)($filters['page'] ?? 1));
        $limit       = min(500, max(10, (int)($filters['limit'] ?? 25)));
        $offset      = ($page - 1) * $limit;

        // 1. Fetch Invoices if requested
        $invoices = [];
        if ($voucherType === 'all' || $voucherType === 'sales') {
            $invoices = $this->fetchInvoices($startDate, $endDate, $search);
        }

        // 2. Fetch Sales Returns if requested
        $returns = [];
        if ($voucherType === 'all' || $voucherType === 'sales_return') {
            $returns = $this->fetchSalesReturns($startDate, $endDate, $search);
        }

        // 3. Merge & Sort chronologically
        $all = array_merge($invoices, $returns);
        usort($all, function ($a, $b) {
            $dateCmp = strcmp($b['voucher_date'], $a['voucher_date']);
            if ($dateCmp !== 0) {
                return $dateCmp;
            }
            return $b['id'] <=> $a['id'];
        });

        $totalRecords = count($all);
        $totalPages   = ceil($totalRecords / $limit);
        $pagedResults = array_slice($all, $offset, $limit);

        return [
            'vouchers'      => $pagedResults,
            'total_records' => $totalRecords,
            'total_pages'   => $totalPages,
            'current_page'  => $page,
            'limit'         => $limit
        ];
    }

    /**
     * Compute financial KPI summary metrics for given filters (Organization-wide)
     */
    public function getKPIs(array $filters): array
    {
        $startDate = !empty($filters['start_date']) ? trim($filters['start_date']) : '';
        $endDate   = !empty($filters['end_date']) ? trim($filters['end_date']) : '';
        $search    = !empty($filters['search']) ? trim($filters['search']) : '';

        $invoices = $this->fetchInvoices($startDate, $endDate, $search);
        $returns  = $this->fetchSalesReturns($startDate, $endDate, $search);

        $grossSales = 0.0;
        $salesTaxable = 0.0;
        $salesTax = 0.0;
        $salesCount = count($invoices);

        foreach ($invoices as $inv) {
            if (strtolower($inv['status']) !== 'cancelled') {
                $grossSales   += (float)$inv['total_amount'];
                $salesTaxable += (float)$inv['taxable_amount'];
                $salesTax     += (float)$inv['tax_amount'];
            }
        }

        $returnTotal = 0.0;
        $returnTaxable = 0.0;
        $returnTax = 0.0;
        $returnCount = count($returns);

        foreach ($returns as $ret) {
            if (strtolower($ret['status']) !== 'cancelled') {
                $returnTotal   += (float)$ret['total_amount'];
                $returnTaxable += (float)$ret['taxable_amount'];
                $returnTax     += (float)$ret['tax_amount'];
            }
        }

        return [
            'gross_sales'     => $grossSales,
            'sales_taxable'   => $salesTaxable,
            'sales_tax'       => $salesTax,
            'sales_count'     => $salesCount,
            'sales_returns'   => $returnTotal,
            'return_taxable'  => $returnTaxable,
            'return_tax'      => $returnTax,
            'return_count'    => $returnCount,
            'net_revenue'     => $grossSales - $returnTotal,
            'net_tax'         => $salesTax - $returnTax
        ];
    }

    /**
     * Helper to fetch invoice vouchers (Organization-wide)
     */
    private function fetchInvoices(string $startDate, string $endDate, string $search): array
    {
        $where = ["1=1"];
        $params = [];
        $types = "";

        if ($startDate !== '' && $endDate !== '') {
            $where[] = "DATE(i.invoice_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        } elseif ($startDate !== '') {
            $where[] = "DATE(i.invoice_date) >= ?";
            $params[] = $startDate;
            $types .= "s";
        } elseif ($endDate !== '') {
            $where[] = "DATE(i.invoice_date) <= ?";
            $params[] = $endDate;
            $types .= "s";
        }

        if ($search !== '') {
            $s = "%" . $search . "%";
            $where[] = "(i.invoice_number LIKE ? OR CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) LIKE ? OR c.gstin LIKE ?)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $types .= "sss";
        }

        $whereSql = implode(" AND ", $where);

        $sql = "SELECT i.id, i.invoice_number, i.invoice_date, i.subtotal, i.tax_amount, i.discount_amount, 
                       i.total_amount, i.currency, i.status,
                       c.first_name, c.last_name, c.gstin
                FROM vp_invoices i
                LEFT JOIN vp_order_info c ON i.customer_id = c.customer_id AND c.id = (SELECT MAX(id) FROM vp_order_info WHERE customer_id = i.customer_id)
                WHERE {$whereSql}
                ORDER BY i.invoice_date DESC, i.id DESC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $customerName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if ($customerName === '') {
                $customerName = 'Walk-in Customer';
            }

            $rows[] = [
                'id'                 => (int)$r['id'],
                'voucher_type'       => 'sales',
                'voucher_type_label' => 'Sales Invoice',
                'voucher_no'         => (string)$r['invoice_number'],
                'ref_order_no'       => '—',
                'voucher_date'       => (string)$r['invoice_date'],
                'customer_name'     => $customerName,
                'gstin'              => (string)($r['gstin'] ?? '—'),
                'taxable_amount'     => (float)($r['subtotal'] ?? 0),
                'tax_amount'         => (float)($r['tax_amount'] ?? 0),
                'discount_amount'    => (float)($r['discount_amount'] ?? 0),
                'total_amount'       => (float)($r['total_amount'] ?? 0),
                'currency'           => (string)($r['currency'] ?? 'INR'),
                'status'             => (string)($r['status'] ?? 'final')
            ];
        }
        $stmt->close();

        return $rows;
    }

    /**
     * Helper to fetch sales return vouchers (Organization-wide)
     */
    private function fetchSalesReturns(string $startDate, string $endDate, string $search): array
    {
        $where = ["1=1"];
        $params = [];
        $types = "";

        if ($startDate !== '' && $endDate !== '') {
            $where[] = "DATE(sr.return_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        } elseif ($startDate !== '') {
            $where[] = "DATE(sr.return_date) >= ?";
            $params[] = $startDate;
            $types .= "s";
        } elseif ($endDate !== '') {
            $where[] = "DATE(sr.return_date) <= ?";
            $params[] = $endDate;
            $types .= "s";
        }

        if ($search !== '') {
            $s = "%" . $search . "%";
            $where[] = "(sr.return_number LIKE ? OR sr.order_number LIKE ? OR i.invoice_number LIKE ? OR CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) LIKE ? OR c.gstin LIKE ?)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $types .= "sssss";
        }

        $whereSql = implode(" AND ", $where);

        $sql = "SELECT sr.id, sr.return_number, sr.order_number, sr.invoice_id, sr.return_date, sr.status,
                       i.invoice_number, i.currency,
                       c.first_name, c.last_name, c.gstin
                FROM vp_sales_returns sr
                LEFT JOIN vp_invoices i ON sr.invoice_id = i.id
                LEFT JOIN vp_order_info c ON i.customer_id = c.customer_id AND c.id = (SELECT MAX(id) FROM vp_order_info WHERE customer_id = i.customer_id)
                WHERE {$whereSql}
                ORDER BY sr.return_date DESC, sr.id DESC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $returnId = (int)$r['id'];

            // Calculate return line totals and tax
            $itemSql = "SELECT sri.return_qty, ii.unit_price, ii.tax_rate 
                        FROM vp_sales_return_items sri 
                        LEFT JOIN vp_invoice_items ii ON sri.invoice_item_id = ii.id 
                        WHERE sri.sales_return_id = ?";
            $itemStmt = $this->conn->prepare($itemSql);
            $taxable = 0.0;
            $taxAmount = 0.0;

            if ($itemStmt) {
                $itemStmt->bind_param('i', $returnId);
                $itemStmt->execute();
                $itemRes = $itemStmt->get_result();
                while ($it = $itemRes->fetch_assoc()) {
                    $qty = (float)($it['return_qty'] ?? 0);
                    $price = (float)($it['unit_price'] ?? 0);
                    $taxRate = (float)($it['tax_rate'] ?? 0);

                    $lineTaxable = $qty * $price;
                    $lineTax = $lineTaxable * ($taxRate / 100);

                    $taxable += $lineTaxable;
                    $taxAmount += $lineTax;
                }
                $itemStmt->close();
            }

            $customerName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if ($customerName === '') {
                $customerName = 'Customer (' . ($r['order_number'] ?? '—') . ')';
            }

            $rows[] = [
                'id'                 => $returnId,
                'voucher_type'       => 'sales_return',
                'voucher_type_label' => 'Sales Return (Credit Note)',
                'voucher_no'         => (string)$r['return_number'],
                'ref_order_no'       => (string)($r['invoice_number'] ?? $r['order_number'] ?? '—'),
                'voucher_date'       => (string)$r['return_date'],
                'customer_name'     => $customerName,
                'gstin'              => (string)($r['gstin'] ?? '—'),
                'taxable_amount'     => round($taxable, 2),
                'tax_amount'         => round($taxAmount, 2),
                'discount_amount'    => 0.0,
                'total_amount'       => round($taxable + $taxAmount, 2),
                'currency'           => (string)($r['currency'] ?? 'INR'),
                'status'             => (string)($r['status'] ?? 'finalized')
            ];
        }
        $stmt->close();

        return $rows;
    }

    /**
     * Get detailed info for single Invoice
     */
    public function getInvoiceDetails(int $id): ?array
    {
        $sql = "SELECT i.*, 
                       c.first_name, c.last_name, c.email, c.mobile, c.address_line1, c.address_line2, 
                       c.city, c.state, c.zipcode, c.country, c.gstin, c.payment_type AS order_payment_type
                FROM vp_invoices i
                LEFT JOIN vp_order_info c ON (c.id = i.vp_order_info_id OR (i.customer_id = c.customer_id AND c.id = (SELECT MAX(id) FROM vp_order_info WHERE customer_id = i.customer_id)))
                WHERE i.id = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $invoice = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$invoice) {
            return null;
        }

        // Fetch line items
        $itemsSql = "SELECT it.*, 
                            COALESCE(
                                NULLIF(ag_p.account_group_name, ''),
                                NULLIF(p.accounts_group, ''),
                                NULLIF(ag.account_group_name, ''),
                                NULLIF(it.groupname, ''),
                                it.item_name
                            ) AS account_group_name 
                     FROM vp_invoice_items it 
                     LEFT JOIN vp_products p ON (p.id = it.product_id OR (it.product_id IS NULL AND it.item_code IS NOT NULL AND it.item_code <> '' AND (p.item_code = it.item_code OR p.sku = it.item_code)))
                     LEFT JOIN account_group ag ON it.groupname = ag.item_group 
                     LEFT JOIN account_group ag_p ON (p.accounts_group IS NOT NULL AND (ag_p.id = p.accounts_group OR ag_p.account_group_name = p.accounts_group))
                     WHERE it.invoice_id = ?
                     GROUP BY it.id";
        $itemsStmt = $this->conn->prepare($itemsSql);
        $items = [];
        if ($itemsStmt) {
            $itemsStmt->bind_param('i', $id);
            $itemsStmt->execute();
            $itemsRes = $itemsStmt->get_result();
            while ($item = $itemsRes->fetch_assoc()) {
                $items[] = $item;
            }
            $itemsStmt->close();
        }

        $custName = trim(($invoice['first_name'] ?? '') . ' ' . ($invoice['last_name'] ?? ''));
        if ($custName === '') {
            $custName = 'Walk-in Customer';
        }

        $payType = trim($invoice['order_payment_type'] ?? $invoice['payment_type'] ?? $invoice['payment_mode'] ?? '');
        if ($payType !== '') {
            $payTypeFormatted = (strtolower($payType) === 'cod') ? 'COD' : ucwords(str_replace('_', ' ', $payType));
            $invoice['payment_type'] = $payTypeFormatted;
            $invoice['party_name']   = $payTypeFormatted;
            $invoice['master_name1'] = $payTypeFormatted;
        }

        $invoice['customer_name']     = $custName;
        $invoice['customer_address1'] = trim($invoice['address_line1'] ?? '');
        $invoice['customer_address2'] = trim($invoice['address_line2'] ?? '');
        $invoice['customer_address3'] = trim($invoice['city'] ?? '');
        $invoice['customer_address4'] = trim($invoice['state'] ?? '');
        $invoice['customer_state']    = trim($invoice['state'] ?? '');
        $invoice['customer_zipcode']  = trim($invoice['zipcode'] ?? '');
        $invoice['customer_mobile']   = trim($invoice['mobile'] ?? '');
        $invoice['customer_email']    = trim($invoice['email'] ?? '');
        $invoice['customer_gstin']    = trim($invoice['gstin'] ?? '');
        $invoice['narration']         = trim($custName . ' ' . $invoice['customer_address1'] . ' ' . $invoice['customer_address2'] . ' ' . $invoice['customer_address3']);
        $invoice['total_qty']         = array_sum(array_column($items, 'quantity'));
        $invoice['sgst']              = array_sum(array_column($items, 'sgst'));
        $invoice['cgst']              = array_sum(array_column($items, 'cgst'));
        $invoice['igst']              = array_sum(array_column($items, 'igst'));
        $invoice['exotic_address']    = 'Main Store';

        $invoice['items'] = $items;
        return $invoice;
    }

    /**
     * Get detailed info for single Sales Return
     */
    public function getSalesReturnDetails(int $id): ?array
    {
        $sql = "SELECT sr.*, i.invoice_number, i.invoice_date, i.currency, i.payment_mode,
                       c.first_name, c.last_name, c.email, c.mobile, c.address_line1, c.address_line2, 
                       c.city, c.state, c.zipcode, c.country, c.gstin, c.payment_type AS order_payment_type
                FROM vp_sales_returns sr
                LEFT JOIN vp_invoices i ON sr.invoice_id = i.id
                LEFT JOIN vp_order_info c ON (c.id = i.vp_order_info_id OR (i.customer_id = c.customer_id AND c.id = (SELECT MAX(id) FROM vp_order_info WHERE customer_id = i.customer_id)))
                WHERE sr.id = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $return = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$return) {
            return null;
        }

        // Fetch return line items
        $itemsSql = "SELECT sri.*, ii.item_name, ii.hsn, ii.unit_price, ii.tax_rate, ii.groupname,
                            COALESCE(
                                NULLIF(ag_p.account_group_name, ''),
                                NULLIF(p.accounts_group, ''),
                                NULLIF(ag.account_group_name, ''),
                                NULLIF(ii.groupname, ''),
                                sri.item_code,
                                ii.item_name
                            ) AS account_group_name 
                     FROM vp_sales_return_items sri 
                     LEFT JOIN vp_invoice_items ii ON sri.invoice_item_id = ii.id 
                     LEFT JOIN vp_products p ON (
                         p.id = COALESCE(sri.product_id, ii.product_id) 
                         OR (sri.product_id IS NULL AND ii.product_id IS NULL AND sri.item_code IS NOT NULL AND sri.item_code <> '' AND (p.item_code = sri.item_code OR p.sku = sri.item_code))
                     )
                     LEFT JOIN account_group ag ON ii.groupname = ag.item_group 
                     LEFT JOIN account_group ag_p ON (p.accounts_group IS NOT NULL AND (ag_p.id = p.accounts_group OR ag_p.account_group_name = p.accounts_group))
                     WHERE sri.sales_return_id = ?
                     GROUP BY sri.id";
        $itemsStmt = $this->conn->prepare($itemsSql);
        $items = [];
        if ($itemsStmt) {
            $itemsStmt->bind_param('i', $id);
            $itemsStmt->execute();
            $itemsRes = $itemsStmt->get_result();
            while ($item = $itemsRes->fetch_assoc()) {
                $items[] = $item;
            }
            $itemsStmt->close();
        }

        $custName = trim(($return['first_name'] ?? '') . ' ' . ($return['last_name'] ?? ''));
        if ($custName === '') {
            $custName = 'Customer (' . ($return['order_number'] ?? '—') . ')';
        }

        $payType = trim($return['order_payment_type'] ?? $return['payment_type'] ?? $return['payment_mode'] ?? '');
        if ($payType !== '') {
            $payTypeFormatted = (strtolower($payType) === 'cod') ? 'COD' : ucwords(str_replace('_', ' ', $payType));
            $return['payment_type'] = $payTypeFormatted;
            $return['party_name']   = $payTypeFormatted;
            $return['master_name1'] = $payTypeFormatted;
        }

        $return['customer_name']     = $custName;
        $return['customer_address1'] = trim($return['address_line1'] ?? '');
        $return['customer_address2'] = trim($return['address_line2'] ?? '');
        $return['customer_address3'] = trim($return['city'] ?? '');
        $return['customer_address4'] = trim($return['state'] ?? '');
        $return['customer_state']    = trim($return['state'] ?? '');
        $return['customer_zipcode']  = trim($return['zipcode'] ?? '');
        $return['customer_mobile']   = trim($return['mobile'] ?? '');
        $return['customer_email']    = trim($return['email'] ?? '');
        $return['customer_gstin']    = trim($return['gstin'] ?? '');
        $return['narration']         = trim(($return['remarks'] ?? '') ?: ('Sales Return against ' . ($return['invoice_number'] ?? '')));
        $return['total_qty']         = array_sum(array_column($items, 'return_qty'));
        $return['exotic_address']    = 'Main Store';

        $return['items'] = $items;
        return $return;
    }
}
