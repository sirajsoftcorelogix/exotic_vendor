<?php

/**
 * BusyAccounting Model
 * Responsible for retrieving sales invoices, sales returns, KPI metrics,
 * and voucher line-item breakdowns for accounting/bookkeeping review and BUSY integration.
 * BUSY integration operates organization-wide across all sales and returns (sharing company GSTIN).
 * Optimized with indexed joins, SQL aggregations, and fast pagination.
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
        $limit       = min(5000, max(1, (int)($filters['limit'] ?? 25)));
        $offset      = ($page - 1) * $limit;

        $invoices = [];
        $returns  = [];
        $totalRecords = 0;

        if ($voucherType === 'sales') {
            $totalRecords = $this->countInvoices($startDate, $endDate, $search);
            $invoices     = $this->fetchInvoices($startDate, $endDate, $search, $limit, $offset);
            $all          = $invoices;
        } elseif ($voucherType === 'sales_return') {
            $totalRecords = $this->countSalesReturns($startDate, $endDate, $search);
            $returns      = $this->fetchSalesReturns($startDate, $endDate, $search, $limit, $offset);
            $all          = $returns;
        } else { // 'all'
            $invCount     = $this->countInvoices($startDate, $endDate, $search);
            $retCount     = $this->countSalesReturns($startDate, $endDate, $search);
            $totalRecords = $invCount + $retCount;

            $fetchLimit   = $offset + $limit;
            $invoices     = $this->fetchInvoices($startDate, $endDate, $search, $fetchLimit, 0);
            $returns      = $this->fetchSalesReturns($startDate, $endDate, $search, $fetchLimit, 0);

            $all = array_merge($invoices, $returns);
            usort($all, function ($a, $b) {
                $dateCmp = strcmp($b['voucher_date'], $a['voucher_date']);
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }
                return $b['id'] <=> $a['id'];
            });

            $all = array_slice($all, $offset, $limit);
        }

        $totalPages = $limit > 0 ? (int)ceil($totalRecords / $limit) : 1;

        return [
            'vouchers'      => $all,
            'total_records' => $totalRecords,
            'total_pages'   => $totalPages,
            'current_page'  => $page,
            'limit'         => $limit
        ];
    }

    /**
     * Compute financial KPI summary metrics via fast SQL aggregation (Organization-wide)
     */
    public function getKPIs(array $filters): array
    {
        $startDate = !empty($filters['start_date']) ? trim($filters['start_date']) : '';
        $endDate   = !empty($filters['end_date']) ? trim($filters['end_date']) : '';
        $search    = !empty($filters['search']) ? trim($filters['search']) : '';

        // 1. Sales Invoices KPI Aggregate
        $invWhere = ["1=1"];
        $invParams = [];
        $invTypes = "";

        if ($startDate !== '' && $endDate !== '') {
            $invWhere[] = "DATE(i.invoice_date) BETWEEN ? AND ?";
            $invParams[] = $startDate;
            $invParams[] = $endDate;
            $invTypes .= "ss";
        } elseif ($startDate !== '') {
            $invWhere[] = "DATE(i.invoice_date) >= ?";
            $invParams[] = $startDate;
            $invTypes .= "s";
        } elseif ($endDate !== '') {
            $invWhere[] = "DATE(i.invoice_date) <= ?";
            $invParams[] = $endDate;
            $invTypes .= "s";
        }

        if ($search !== '') {
            $s = "%" . $search . "%";
            $invWhere[] = "(i.invoice_number LIKE ? OR CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) LIKE ? OR c.gstin LIKE ? OR c.payment_type LIKE ?)";
            $invParams[] = $s;
            $invParams[] = $s;
            $invParams[] = $s;
            $invParams[] = $s;
            $invTypes .= "ssss";
        }

        $invWhereSql = implode(" AND ", $invWhere);

        $salesSql = "SELECT COUNT(i.id) AS sales_count,
                            COALESCE(SUM(i.total_amount), 0) AS gross_sales,
                            COALESCE(SUM(i.subtotal), 0) AS sales_taxable,
                            COALESCE(SUM(i.tax_amount), 0) AS sales_tax
                     FROM vp_invoices i
                     LEFT JOIN vp_order_info c ON c.id = i.vp_order_info_id
                     WHERE {$invWhereSql} AND LOWER(TRIM(COALESCE(i.status, ''))) <> 'cancelled'";

        $salesRes = ['sales_count' => 0, 'gross_sales' => 0.0, 'sales_taxable' => 0.0, 'sales_tax' => 0.0];
        $stmt1 = $this->conn->prepare($salesSql);
        if ($stmt1) {
            if ($invTypes !== "") {
                $stmt1->bind_param($invTypes, ...$invParams);
            }
            $stmt1->execute();
            $row = $stmt1->get_result()->fetch_assoc();
            if ($row) {
                $salesRes = [
                    'sales_count'   => (int)($row['sales_count'] ?? 0),
                    'gross_sales'   => (float)($row['gross_sales'] ?? 0),
                    'sales_taxable' => (float)($row['sales_taxable'] ?? 0),
                    'sales_tax'     => (float)($row['sales_tax'] ?? 0)
                ];
            }
            $stmt1->close();
        }

        // 2. Sales Returns KPI Aggregate
        $retWhere = ["1=1"];
        $retParams = [];
        $retTypes = "";

        if ($startDate !== '' && $endDate !== '') {
            $retWhere[] = "DATE(sr.return_date) BETWEEN ? AND ?";
            $retParams[] = $startDate;
            $retParams[] = $endDate;
            $retTypes .= "ss";
        } elseif ($startDate !== '') {
            $retWhere[] = "DATE(sr.return_date) >= ?";
            $retParams[] = $startDate;
            $retTypes .= "s";
        } elseif ($endDate !== '') {
            $retWhere[] = "DATE(sr.return_date) <= ?";
            $retParams[] = $endDate;
            $retTypes .= "s";
        }

        if ($search !== '') {
            $s = "%" . $search . "%";
            $retWhere[] = "(sr.return_number LIKE ? OR sr.order_number LIKE ? OR i.invoice_number LIKE ? OR CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) LIKE ? OR c.gstin LIKE ? OR c.payment_type LIKE ?)";
            $retParams[] = $s;
            $retParams[] = $s;
            $retParams[] = $s;
            $retParams[] = $s;
            $retParams[] = $s;
            $retParams[] = $s;
            $retTypes .= "ssssss";
        }

        $retWhereSql = implode(" AND ", $retWhere);

        $returnsSql = "SELECT COUNT(sr.id) AS return_count,
                              COALESCE(SUM(sri_tot.taxable), 0) AS return_taxable,
                              COALESCE(SUM(sri_tot.tax), 0) AS return_tax,
                              COALESCE(SUM(sri_tot.taxable + sri_tot.tax), 0) AS sales_returns
                       FROM vp_sales_returns sr
                       LEFT JOIN vp_invoices i ON sr.invoice_id = i.id
                       LEFT JOIN vp_order_info c ON c.id = i.vp_order_info_id
                       LEFT JOIN (
                           SELECT sri.sales_return_id,
                                  SUM(sri.return_qty * ii.unit_price) AS taxable,
                                  SUM(sri.return_qty * ii.unit_price * (ii.tax_rate / 100)) AS tax
                           FROM vp_sales_return_items sri
                           LEFT JOIN vp_invoice_items ii ON sri.invoice_item_id = ii.id
                           GROUP BY sri.sales_return_id
                       ) sri_tot ON sri_tot.sales_return_id = sr.id
                       WHERE {$retWhereSql} AND LOWER(TRIM(COALESCE(sr.status, ''))) <> 'cancelled'";

        $retRes = ['return_count' => 0, 'sales_returns' => 0.0, 'return_taxable' => 0.0, 'return_tax' => 0.0];
        $stmt2 = $this->conn->prepare($returnsSql);
        if ($stmt2) {
            if ($retTypes !== "") {
                $stmt2->bind_param($retTypes, ...$retParams);
            }
            $stmt2->execute();
            $row = $stmt2->get_result()->fetch_assoc();
            if ($row) {
                $retRes = [
                    'return_count'   => (int)($row['return_count'] ?? 0),
                    'sales_returns'  => (float)($row['sales_returns'] ?? 0),
                    'return_taxable' => (float)($row['return_taxable'] ?? 0),
                    'return_tax'     => (float)($row['return_tax'] ?? 0)
                ];
            }
            $stmt2->close();
        }

        return [
            'gross_sales'    => $salesRes['gross_sales'],
            'sales_taxable'  => $salesRes['sales_taxable'],
            'sales_tax'      => $salesRes['sales_tax'],
            'sales_count'    => $salesRes['sales_count'],
            'sales_returns'  => $retRes['sales_returns'],
            'return_taxable' => $retRes['return_taxable'],
            'return_tax'     => $retRes['return_tax'],
            'return_count'   => $retRes['return_count'],
            'net_revenue'    => $salesRes['gross_sales'] - $retRes['sales_returns'],
            'net_tax'        => $salesRes['sales_tax'] - $retRes['return_tax']
        ];
    }

    /**
     * Count total invoice vouchers matching filters
     */
    private function countInvoices(string $startDate, string $endDate, string $search): int
    {
        $where = ["1=1", "LOWER(TRIM(COALESCE(i.status, ''))) <> 'cancelled'"];
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
            $where[] = "(i.invoice_number LIKE ? OR CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) LIKE ? OR c.gstin LIKE ? OR c.payment_type LIKE ?)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $types .= "ssss";
        }

        $whereSql = implode(" AND ", $where);
        $sql = "SELECT COUNT(i.id) AS cnt FROM vp_invoices i LEFT JOIN vp_order_info c ON c.id = i.vp_order_info_id WHERE {$whereSql}";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($res['cnt'] ?? 0);
    }

    /**
     * Helper to fetch invoice vouchers (Organization-wide, Indexed Join)
     */
    private function fetchInvoices(string $startDate, string $endDate, string $search, int $limit = 0, int $offset = 0): array
    {
        $where = ["1=1", "LOWER(TRIM(COALESCE(i.status, ''))) <> 'cancelled'"];
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
            $where[] = "(i.invoice_number LIKE ? OR CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) LIKE ? OR c.gstin LIKE ? OR c.payment_type LIKE ?)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $types .= "ssss";
        }

        $whereSql = implode(" AND ", $where);

        $limitSql = "";
        if ($limit > 0) {
            $limitSql = " LIMIT {$offset}, {$limit}";
        }

        $sql = "SELECT i.id, i.invoice_number, i.invoice_date, i.subtotal, i.tax_amount, i.discount_amount, 
                       i.total_amount, i.currency, i.status,
                       c.first_name, c.last_name, c.gstin, c.payment_type AS order_payment_type, c.order_number,
                       (SELECT pp.payment_mode 
                        FROM pos_payments pp 
                        WHERE CONVERT(pp.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(c.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci 
                        ORDER BY pp.payment_amount DESC, pp.id DESC 
                        LIMIT 1) AS pos_payment_mode,
                       (SELECT dd.courier_name 
                        FROM vp_dispatch_details dd 
                        WHERE dd.invoice_id = i.id AND dd.courier_name IS NOT NULL AND TRIM(dd.courier_name) <> '' 
                        ORDER BY dd.id DESC 
                        LIMIT 1) AS dispatch_courier_name
                FROM vp_invoices i
                LEFT JOIN vp_order_info c ON c.id = i.vp_order_info_id
                WHERE {$whereSql}
                ORDER BY i.invoice_date DESC, i.id DESC{$limitSql}";

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

            $payType = trim($r['order_payment_type'] ?? '');
            if (strtolower($payType) === 'offline' && !empty($r['pos_payment_mode'])) {
                $payType = trim($r['pos_payment_mode']);
            }
            $payTypeFormatted = '';
            if ($payType !== '') {
                $payTypeFormatted = (strtolower($payType) === 'cod') ? 'COD' : ucwords(str_replace('_', ' ', $payType));
            }
            $partyName = $payTypeFormatted !== '' ? $payTypeFormatted : $customerName;

            $rows[] = [
                'id'                 => (int)$r['id'],
                'voucher_type'       => 'sales',
                'voucher_type_label' => 'Sales Invoice',
                'voucher_no'         => (string)$r['invoice_number'],
                'ref_order_no'       => '—',
                'voucher_date'       => (string)$r['invoice_date'],
                'payment_type'       => $payTypeFormatted,
                'party_name'         => $partyName,
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
     * Count total sales return vouchers matching filters
     */
    private function countSalesReturns(string $startDate, string $endDate, string $search): int
    {
        $where = ["1=1", "LOWER(TRIM(COALESCE(sr.status, ''))) <> 'cancelled'"];
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
            $where[] = "(sr.return_number LIKE ? OR sr.order_number LIKE ? OR i.invoice_number LIKE ? OR CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) LIKE ? OR c.gstin LIKE ? OR c.payment_type LIKE ?)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $types .= "ssssss";
        }

        $whereSql = implode(" AND ", $where);
        $sql = "SELECT COUNT(sr.id) AS cnt FROM vp_sales_returns sr LEFT JOIN vp_invoices i ON sr.invoice_id = i.id LEFT JOIN vp_order_info c ON c.id = i.vp_order_info_id WHERE {$whereSql}";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($res['cnt'] ?? 0);
    }

    /**
     * Helper to fetch sales return vouchers (Organization-wide, Indexed Join)
     */
    private function fetchSalesReturns(string $startDate, string $endDate, string $search, int $limit = 0, int $offset = 0): array
    {
        $where = ["1=1", "LOWER(TRIM(COALESCE(sr.status, ''))) <> 'cancelled'"];
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
            $where[] = "(sr.return_number LIKE ? OR sr.order_number LIKE ? OR i.invoice_number LIKE ? OR CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) LIKE ? OR c.gstin LIKE ? OR c.payment_type LIKE ?)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $types .= "ssssss";
        }

        $whereSql = implode(" AND ", $where);

        $limitSql = "";
        if ($limit > 0) {
            $limitSql = " LIMIT {$offset}, {$limit}";
        }

        $sql = "SELECT sr.id, sr.return_number, sr.order_number, sr.invoice_id, sr.return_date, sr.status,
                       i.invoice_number, i.currency,
                       c.first_name, c.last_name, c.gstin, c.payment_type AS order_payment_type,
                       (SELECT pp.payment_mode 
                        FROM pos_payments pp 
                        WHERE CONVERT(pp.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(sr.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci 
                        ORDER BY pp.payment_amount DESC, pp.id DESC 
                        LIMIT 1) AS pos_payment_mode,
                       (SELECT dd.courier_name 
                        FROM vp_dispatch_details dd 
                        WHERE dd.invoice_id = sr.invoice_id AND dd.courier_name IS NOT NULL AND TRIM(dd.courier_name) <> '' 
                        ORDER BY dd.id DESC 
                        LIMIT 1) AS dispatch_courier_name,
                       COALESCE(sri_sum.taxable, 0) AS taxable_amount,
                       COALESCE(sri_sum.tax, 0) AS tax_amount
                FROM vp_sales_returns sr
                LEFT JOIN vp_invoices i ON sr.invoice_id = i.id
                LEFT JOIN vp_order_info c ON c.id = i.vp_order_info_id
                LEFT JOIN (
                    SELECT sri.sales_return_id,
                           SUM(sri.return_qty * ii.unit_price) AS taxable,
                           SUM(sri.return_qty * ii.unit_price * (ii.tax_rate / 100)) AS tax
                    FROM vp_sales_return_items sri
                    LEFT JOIN vp_invoice_items ii ON sri.invoice_item_id = ii.id
                    GROUP BY sri.sales_return_id
                ) sri_sum ON sri_sum.sales_return_id = sr.id
                WHERE {$whereSql}
                ORDER BY sr.return_date DESC, sr.id DESC{$limitSql}";

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
            $taxable  = (float)($r['taxable_amount'] ?? 0);
            $taxAmount = (float)($r['tax_amount'] ?? 0);

            $customerName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if ($customerName === '') {
                $customerName = 'Customer (' . ($r['order_number'] ?? '—') . ')';
            }

            $payType = trim($r['order_payment_type'] ?? '');
            if (strtolower($payType) === 'offline' && !empty($r['pos_payment_mode'])) {
                $payType = trim($r['pos_payment_mode']);
            }
            $payTypeFormatted = '';
            if ($payType !== '') {
                $payTypeFormatted = (strtolower($payType) === 'cod') ? 'COD' : ucwords(str_replace('_', ' ', $payType));
            }
            $partyName = $payTypeFormatted !== '' ? $payTypeFormatted : $customerName;

            $rows[] = [
                'id'                 => $returnId,
                'voucher_type'       => 'sales_return',
                'voucher_type_label' => 'Sales Return (Credit Note)',
                'voucher_no'         => (string)$r['return_number'],
                'ref_order_no'       => (string)($r['invoice_number'] ?? $r['order_number'] ?? '—'),
                'voucher_date'       => (string)$r['return_date'],
                'payment_type'       => $payTypeFormatted,
                'party_name'         => $partyName,
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
                       c.city, c.state, c.zipcode, c.country, c.gstin, c.payment_type AS order_payment_type, c.payment_mode AS order_payment_mode
                FROM vp_invoices i
                LEFT JOIN vp_order_info c ON c.id = i.vp_order_info_id
                WHERE i.id = ? AND LOWER(TRIM(COALESCE(i.status, ''))) <> 'cancelled' LIMIT 1";

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

        // Fetch line items with fast indexed subqueries
        $itemsSql = "SELECT it.*, 
                            COALESCE(
                                (SELECT CONVERT(ag_p.account_group_name USING utf8mb4) COLLATE utf8mb4_unicode_ci
                                 FROM vp_products p 
                                 INNER JOIN account_group ag_p ON (
                                     ag_p.id = p.accounts_group 
                                     OR CONVERT(ag_p.account_group_name USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(p.accounts_group USING utf8mb4) COLLATE utf8mb4_unicode_ci
                                 ) 
                                 WHERE p.id = it.product_id AND ag_p.account_group_name IS NOT NULL AND ag_p.account_group_name <> '' LIMIT 1),
                                (SELECT CONVERT(p.accounts_group USING utf8mb4) COLLATE utf8mb4_unicode_ci FROM vp_products p WHERE p.id = it.product_id AND p.accounts_group IS NOT NULL AND p.accounts_group <> '' LIMIT 1),
                                (SELECT CONVERT(ag.account_group_name USING utf8mb4) COLLATE utf8mb4_unicode_ci FROM account_group ag WHERE CONVERT(ag.item_group USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(it.groupname USING utf8mb4) COLLATE utf8mb4_unicode_ci AND ag.account_group_name IS NOT NULL AND ag.account_group_name <> '' LIMIT 1),
                                NULLIF(CONVERT(it.groupname USING utf8mb4) COLLATE utf8mb4_unicode_ci, ''),
                                CONVERT(it.item_name USING utf8mb4) COLLATE utf8mb4_unicode_ci
                            ) AS account_group_name 
                     FROM vp_invoice_items it 
                     WHERE it.invoice_id = ?";

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

        $payType = trim($invoice['order_payment_mode'] ?? $invoice['order_payment_type'] ?? $invoice['payment_type'] ?? $invoice['payment_mode'] ?? '');
        if ($payType !== '') {
            if (strtoupper($payType) === 'YES2971') {
                $payTypeFormatted = 'YES2971';
            } elseif (strtolower($payType) === 'cod') {
                $payTypeFormatted = 'COD';
            } elseif (strtolower($payType) === 'pos' || strtolower($payType) === 'pos_machine' || strtolower($payType) === 'pos machine') {
                $payTypeFormatted = 'POS';
            } else {
                $payTypeFormatted = ucwords(str_replace('_', ' ', $payType));
            }
            $invoice['payment_type'] = $payTypeFormatted;
            $invoice['party_name']   = $payTypeFormatted;
            $invoice['master_name1'] = $payTypeFormatted;
        }

        $invoice['customer_name']     = $custName;
        $invoice['customer_address1'] = trim($invoice['address_line1'] ?? '');
        $invoice['customer_address2'] = trim($invoice['address_line2'] ?? '');
        $invoice['customer_address3'] = trim($invoice['city'] ?? '');
        $invoice['customer_address4'] = trim($invoice['state'] ?? '');
        $invoice['customer_country']    = trim($invoice['country'] ?? '');
        $invoice['customer_state']      = trim($invoice['state'] ?? '');
        $invoice['customer_state_code'] = trim($invoice['state_code'] ?? '');
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
        $sql = "SELECT sr.*, i.invoice_number, i.invoice_date, i.currency,
                       c.first_name, c.last_name, c.email, c.mobile, c.address_line1, c.address_line2, 
                       c.city, c.state, c.zipcode, c.country, c.gstin, c.payment_type AS order_payment_type, c.payment_mode AS order_payment_mode
                FROM vp_sales_returns sr
                LEFT JOIN vp_invoices i ON sr.invoice_id = i.id
                LEFT JOIN vp_order_info c ON c.id = i.vp_order_info_id
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

        // Fetch return line items with fast indexed subqueries
        $itemsSql = "SELECT sri.*, ii.item_name, ii.hsn, ii.unit_price, ii.tax_rate, ii.groupname,
                            COALESCE(
                                (SELECT CONVERT(ag_p.account_group_name USING utf8mb4) COLLATE utf8mb4_unicode_ci 
                                 FROM vp_products p 
                                 INNER JOIN account_group ag_p ON (
                                     ag_p.id = p.accounts_group 
                                     OR CONVERT(ag_p.account_group_name USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(p.accounts_group USING utf8mb4) COLLATE utf8mb4_unicode_ci
                                 ) 
                                 WHERE p.id = COALESCE(sri.product_id, ii.product_id) AND ag_p.account_group_name IS NOT NULL AND ag_p.account_group_name <> '' LIMIT 1),
                                (SELECT CONVERT(p.accounts_group USING utf8mb4) COLLATE utf8mb4_unicode_ci FROM vp_products p WHERE p.id = COALESCE(sri.product_id, ii.product_id) AND p.accounts_group IS NOT NULL AND p.accounts_group <> '' LIMIT 1),
                                (SELECT CONVERT(ag.account_group_name USING utf8mb4) COLLATE utf8mb4_unicode_ci FROM account_group ag WHERE CONVERT(ag.item_group USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(ii.groupname USING utf8mb4) COLLATE utf8mb4_unicode_ci AND ag.account_group_name IS NOT NULL AND ag.account_group_name <> '' LIMIT 1),
                                NULLIF(CONVERT(ii.groupname USING utf8mb4) COLLATE utf8mb4_unicode_ci, ''),
                                NULLIF(CONVERT(sri.item_code USING utf8mb4) COLLATE utf8mb4_unicode_ci, ''),
                                CONVERT(ii.item_name USING utf8mb4) COLLATE utf8mb4_unicode_ci
                            ) AS account_group_name 
                     FROM vp_sales_return_items sri 
                     LEFT JOIN vp_invoice_items ii ON sri.invoice_item_id = ii.id 
                     WHERE sri.sales_return_id = ?";

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

        $payType = trim($return['order_payment_mode'] ?? $return['order_payment_type'] ?? $return['payment_type'] ?? $return['payment_mode'] ?? '');
        if ($payType !== '') {
            if (strtoupper($payType) === 'YES2971') {
                $payTypeFormatted = 'YES2971';
            } elseif (strtolower($payType) === 'cod') {
                $payTypeFormatted = 'COD';
            } elseif (strtolower($payType) === 'pos' || strtolower($payType) === 'pos_machine' || strtolower($payType) === 'pos machine') {
                $payTypeFormatted = 'POS';
            } else {
                $payTypeFormatted = ucwords(str_replace('_', ' ', $payType));
            }
            $return['payment_type'] = $payTypeFormatted;
            $return['party_name']   = $payTypeFormatted;
            $return['master_name1'] = $payTypeFormatted;
        }

        $return['customer_name']     = $custName;
        $return['customer_address1'] = trim($return['address_line1'] ?? '');
        $return['customer_address2'] = trim($return['address_line2'] ?? '');
        $return['customer_address3'] = trim($return['city'] ?? '');
        $return['customer_address4'] = trim($return['state'] ?? '');
        $return['customer_country']    = trim($return['country'] ?? '');
        $return['customer_state']      = trim($return['state'] ?? '');
        $return['customer_state_code'] = trim($return['state_code'] ?? '');
        $return['customer_zipcode']  = trim($return['zipcode'] ?? '');
        $return['customer_mobile']   = trim($return['mobile'] ?? '');
        $return['customer_email']    = trim($return['email'] ?? '');
        $return['customer_gstin']    = trim($return['gstin'] ?? '');
        $return['narration']         = trim('Sales Return for Invoice #' . ($return['invoice_number'] ?? ''));
        $return['total_qty']         = array_sum(array_column($items, 'return_qty'));
        $return['exotic_address']    = 'Main Store';

        $return['items'] = $items;
        return $return;
    }
}
