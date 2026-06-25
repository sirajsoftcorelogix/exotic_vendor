<?php
class product
{
    private $db;
    /** @var string|null */
    private $stockMovementItemCodeColumn = null;
    private $vpProductsCols = null;
    private function vpProductsHasColumn(string $col): bool
    {
        if ($this->vpProductsCols === null) {
            $this->vpProductsCols = [];
            $res = $this->db->query("SHOW COLUMNS FROM vp_products");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    if (!empty($row['Field'])) $this->vpProductsCols[$row['Field']] = true;
                }
            }
        }
        return isset($this->vpProductsCols[$col]);
    }
    private function normalizeIntValue($value, $default = 0)
    {
        if ($value === null) return (int)$default;
        if (is_int($value)) return $value;
        if (is_float($value)) return (int)$value;
        $str = trim((string)$value);
        if ($str === '') return (int)$default;
        if (is_numeric($str)) {
            return max(0, (int)round((float)$str));
        }
        if (preg_match('/-?\d+/', $str, $m)) {
            return (int)$m[0];
        }
        return (int)$default;
    }

    /** DATE column values from vendor API — reject MySQL zero dates like 0000-00-00 00:00:00. */
    private function normalizeApiDateValue($value): string
    {
        $str = trim((string) $value);
        if ($str === '' || preg_match('/^0000-00-00/', $str)) {
            return '';
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $str, $m)) {
            return $m[1];
        }
        $ts = strtotime($str);
        return ($ts !== false) ? date('Y-m-d', $ts) : '';
    }

    /** Cast API payload scalars the same way as modal form POST (trim strings, int/float numbers). */
    private function apiFormString($value): string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return '';
        }
        return trim((string) $value);
    }

    private function apiFormFloat($value, float $default = 0.0): float
    {
        if ($value === null || $value === '' || is_array($value) || is_object($value)) {
            return $default;
        }
        return (float) $value;
    }

    /** @return array<string, mixed> */
    private function castApiProductLikeForm(array $row): array
    {
        foreach ([
            'asin', 'upc', 'location', 'vendor', 'category', 'itemtype', 'snippet_description',
            'keywords', 'hscode', 'hsn', 'long_description', 'long_description_india', 'aplus_content_ids',
            'item_level', 'marketplace_vendor', 'colormap', 'flex_status', 'vendor_us',
            'today_global', 'today_india', 'amazon_itemcode_alias', 'youtube_links', 'sketchfab_links',
            'dimensions', 'size', 'color', 'sku', 'search_term', 'search_category', 'accounts_group',
        ] as $key) {
            if (array_key_exists($key, $row)) {
                $row[$key] = $this->apiFormString($row[$key]);
            }
        }
        foreach ([
            'local_stock', 'fba_in', 'fba_us', 'permanently_available', 'numsold', 'numsold_india',
            'numsold_global', 'india_net_qty', 'usblock', 'indiablock', 'topurchase', 'backorder_percent',
            'backorder_weeks', 'amazon_sold', 'amazon_leadtime',
        ] as $key) {
            if (array_key_exists($key, $row)) {
                $row[$key] = $this->normalizeIntValue($row[$key], 0);
            }
        }
        foreach ([
            'shippingfee', 'sourcingfee', 'price', 'price_india', 'price_india_suggested', 'mrp_india',
            'permanent_discount', 'discount_global', 'discount_india', 'cp', 'usd',
        ] as $key) {
            if (array_key_exists($key, $row)) {
                $row[$key] = $this->apiFormFloat($row[$key]);
            }
        }
        if (array_key_exists('leadtime', $row)) {
            $row['leadtime'] = $this->normalizeIntValue($row['leadtime'], 0);
        }
        if (array_key_exists('instock_leadtime', $row)) {
            $row['instock_leadtime'] = $this->normalizeIntValue($row['instock_leadtime'], 0);
        }
        if (array_key_exists('lastsold', $row)) {
            $row['lastsold'] = $this->normalizeIntValue($row['lastsold'], 0);
        }
        if (array_key_exists('date_first_added', $row)) {
            $row['date_first_added'] = $this->normalizeApiDateValue($row['date_first_added']);
        }
        return $row;
    }

    /** @var string|null Cached vp_products TABLE_COLLATION (e.g. utf8mb4_unicode_ci). */
    private $vpProductsTableCollation = null;

    private function vpProductsTableCollation(): string
    {
        if ($this->vpProductsTableCollation !== null) {
            return $this->vpProductsTableCollation;
        }
        $this->vpProductsTableCollation = 'utf8mb4_unicode_ci';
        $res = $this->db->query(
            "SELECT TABLE_COLLATION FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vp_products' LIMIT 1"
        );
        if ($res && ($row = $res->fetch_assoc()) && !empty($row['TABLE_COLLATION'])) {
            $this->vpProductsTableCollation = (string) $row['TABLE_COLLATION'];
        }
        return $this->vpProductsTableCollation;
    }

    private function connectionCharsetUsesUtf8mb4(string $charset): bool
    {
        return stripos($charset, 'utf8mb4') !== false;
    }

    /** @return array{charset: string, collation: string} */
    private function alignConnectionForVpProducts(): array
    {
        $prevCharset = mysqli_character_set_name($this->db);
        if ($prevCharset === false) {
            $prevCharset = '';
        }
        $prevCollation = '';
        $colRes = $this->db->query('SELECT @@collation_connection AS collation_connection');
        if ($colRes && ($row = $colRes->fetch_assoc()) && !empty($row['collation_connection'])) {
            $prevCollation = (string) $row['collation_connection'];
        }

        $tableCollation = $this->vpProductsTableCollation();
        $tableUsesUtf8mb4 = stripos($tableCollation, 'utf8mb4') !== false;

        if ($tableUsesUtf8mb4 && !$this->connectionCharsetUsesUtf8mb4($prevCharset)) {
            $this->db->set_charset('utf8mb4');
        } elseif (!$tableUsesUtf8mb4 && $this->connectionCharsetUsesUtf8mb4($prevCharset)) {
            try {
                $this->db->set_charset('utf8mb3');
            } catch (\mysqli_sql_exception $e) {
                $this->db->set_charset('utf8');
            }
        }

        $safeCollation = preg_replace('/[^a-zA-Z0-9_]/', '', $tableCollation);
        if ($safeCollation !== '' && $prevCollation !== $safeCollation) {
            $this->db->query("SET collation_connection = '{$safeCollation}'");
        }

        return ['charset' => $prevCharset, 'collation' => $prevCollation];
    }

    /** @param array{charset: string, collation: string} $previous */
    private function restoreConnectionAfterVpProducts(array $previous): void
    {
        if ($previous['charset'] !== '') {
            $current = mysqli_character_set_name($this->db);
            if ($current !== false && $current !== $previous['charset']) {
                try {
                    $this->db->set_charset($previous['charset']);
                } catch (\Throwable $e) {
                    // ignore restore failure
                }
            }
        }
        if ($previous['collation'] !== '') {
            $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $previous['collation']);
            if ($safe !== '') {
                $this->db->query("SET collation_connection = '{$safe}'");
            }
        }
    }

    /**
     * Bulk product update only (updateAllProductScript): align connection to vp_products charset.
     *
     * @return array{charset: string, collation: string}
     */
    public function beginBulkProductUpdateConnection(): array
    {
        return $this->alignConnectionForVpProducts();
    }

    /** @param array{charset: string, collation: string} $previous */
    public function endBulkProductUpdateConnection(array $previous): void
    {
        $this->restoreConnectionAfterVpProducts($previous);
    }

    private function executeVpProductsStmt(\mysqli_stmt $stmt): bool
    {
        return $stmt->execute();
    }

    /**
     * USD / global list price from vendor product/fetch API payload.
     * Prefer explicit USD keys, then price, then itemprice.
     */
    public static function vendorApiUsdPrice(array $apiItem): float
    {
        foreach (['usd_price', 'price_usd', 'usdprice', 'usdPrice'] as $k) {
            if (!array_key_exists($k, $apiItem)) {
                continue;
            }
            $v = $apiItem[$k];
            if ($v === null || $v === '') {
                continue;
            }
            return (float)$v;
        }
        if (isset($apiItem['price']) && $apiItem['price'] !== '' && $apiItem['price'] !== null) {
            return (float)$apiItem['price'];
        }
        if (isset($apiItem['itemprice']) && $apiItem['itemprice'] !== '' && $apiItem['itemprice'] !== null) {
            return (float)$apiItem['itemprice'];
        }
        return 0.0;
    }

    /** Master API row: HSN is on `hscode` (or `hsn`). Same value for all variants — pass the parent object only. */
    public static function vendorApiHsn(array $masterRow): string
    {
        $h = trim((string)($masterRow['hscode'] ?? ''));
        if ($h !== '') {
            return $h;
        }

        return trim((string)($masterRow['hsn'] ?? ''));
    }

    /** Master API row: account group name is on `account_group` (publish/inbound) or `accounts_group`. */
    public static function vendorApiAccountsGroup(array $masterRow): string
    {
        $name = trim((string)($masterRow['account_group'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        return trim((string)($masterRow['accounts_group'] ?? ''));
    }

    /**
     * Catalog image paths sometimes use alternate-angle filenames (e.g. ca0118_a02.webp) that 404 on CDN
     * while the master file (ca0118.webp) in the same folder returns 200.
     */
    public static function normalizeProductCatalogImageUrl(string $pathOrUrl): string
    {
        $s = trim(str_replace('\\', '/', $pathOrUrl));
        if ($s === '') {
            return '';
        }

        return (string)preg_replace('#_a\d+(?=\.[^./]+$)#i', '', $s);
    }

    /**
     * Vendor product/fetch returns image as a relative path (e.g. textiles-12-2025/ca0118.webp).
     * Match bulk import: persist full CDN URL so listing/detail img src works.
     */
    public static function vendorApiImageStorageValue($image): string
    {
        $image = trim(str_replace('\\', '/', (string)$image));
        if ($image === '') {
            return '';
        }
        $image = self::normalizeProductCatalogImageUrl($image);
        if ($image === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }

        return 'https://cdn.exoticindia.com/images/products/original/' . ltrim($image, '/');
    }

    private function applyCatalogImageNormalizeToProductRow(array &$row): void
    {
        if (!array_key_exists('image', $row)) {
            return;
        }
        // Same rules as API sync: relative catalog paths → full cdn.exoticindia.com URL for <img src>.
        $row['image'] = self::vendorApiImageStorageValue((string)$row['image']);
    }

    public static function normalizeVendorProductFetchItems(array $data): array
    {
        if ($data === []) {
            return [];
        }
        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }
        if (isset($data['itemcode']) && trim((string)$data['itemcode']) !== '') {
            return [$data];
        }
        if (isset($data['item_code']) && trim((string)$data['item_code']) !== '') {
            $data['itemcode'] = trim((string)$data['item_code']);

            return [$data];
        }
        $rows = [];
        foreach ($data as $row) {
            if (is_array($row) && trim((string)($row['itemcode'] ?? $row['item_code'] ?? '')) !== '') {
                if (empty($row['itemcode']) && !empty($row['item_code'])) {
                    $row['itemcode'] = trim((string)$row['item_code']);
                }
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Vendor product/fetch exposes search fields under related_search, not always at top level.
     *
     * @return array{search_term:string,search_category:string}
     */
    public static function vendorApiRelatedSearchFields(array $apiItem): array
    {
        $term = trim((string)($apiItem['search_term'] ?? ''));
        $cat = trim((string)($apiItem['search_category'] ?? ''));

        $related = $apiItem['related_search'] ?? null;
        if (is_array($related)) {
            if ($term === '') {
                $term = trim((string)($related['search_term'] ?? ''));
            }
            if ($cat === '') {
                $cat = trim((string)($related['search_category'] ?? ''));
            }
        }

        return [
            'search_term' => $term,
            'search_category' => $cat,
        ];
    }

    /**
     * @return list<array{vendor_id:int,priority:int}>
     */
    public static function extractDiscreteVendorEntriesFromApiItem(array $apiItem): array
    {
        if (!empty($apiItem['discrete_vendor_list']) && is_array($apiItem['discrete_vendor_list'])) {
            $entries = [];
            foreach ($apiItem['discrete_vendor_list'] as $idx => $vendorId) {
                $exoticId = (int) preg_replace('/\D/', '', (string) $vendorId);
                if ($exoticId > 0) {
                    $entries[] = ['vendor_id' => $exoticId, 'priority' => $idx + 1];
                }
            }

            return $entries;
        }

        if (!empty($apiItem['discrete_vendors']) && is_array($apiItem['discrete_vendors'])) {
            $entries = [];
            foreach ($apiItem['discrete_vendors'] as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $exoticId = (int) preg_replace('/\D/', '', (string) ($entry['vendor'] ?? $entry['vendor_id'] ?? ''));
                if ($exoticId <= 0) {
                    continue;
                }
                $priority = (int) ($entry['priority'] ?? 0);
                $entries[] = [
                    'vendor_id' => $exoticId,
                    'priority' => $priority > 0 ? $priority : (count($entries) + 1),
                ];
            }

            return $entries;
        }

        return [];
    }

    public function __construct($db)
    {
        $this->db = $db;
    }
    public function getProduct($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM vp_products WHERE id = ?");
        if ($stmt === false) {
            return null;
        }
        $id = (int)$id;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if ($row) {
            $this->applyCatalogImageNormalizeToProductRow($row);
        }

        return $row;
    }
    public function getAllProducts($limit, $offset, $filters = [])
    {
        $limit = (int)$limit;
        $offset = (int)$offset;
        //$search = "%$search%";

        $search = "";
        if (!empty($filters['item_code'])) {
            $v = $this->db->real_escape_string((string) $filters['item_code']);
            $search .= "AND vp_products.item_code like '%" . $v . "%'";
        }
        if (!empty($filters['title'])) {
            $v = $this->db->real_escape_string((string) $filters['title']);
            $search .= "AND vp_products.title like '%" . $v . "%'";
        }
        if (!empty($filters['vendor_name'])) {
            $v = $this->db->real_escape_string((string) $filters['vendor_name']);
            $search .= "AND vp_products.vendor like '%" . $v . "%'";
        }
        if (!empty($filters['groupname'])) {
            $v = $this->db->real_escape_string((string) $filters['groupname']);
            $search .= "AND vp_products.groupname like '%" . $v . "%'";
        }
        if (!empty($filters['sku'])) {
            $v = $this->db->real_escape_string((string) $filters['sku']);
            $search .= "AND vp_products.sku like '%" . $v . "%'";
        }
        if (!empty($filters['size'])) {
            $v = $this->db->real_escape_string((string) $filters['size']);
            $search .= "AND vp_products.size like '%" . $v . "%'";
        }
        if (!empty($filters['color'])) {
            $v = $this->db->real_escape_string((string) $filters['color']);
            $search .= "AND vp_products.color like '%" . $v . "%'";
        }
        $search .= $this->appendProductCatalogStockFilters($filters);
        if (isset($filters['permanently_available']) && $filters['permanently_available'] !== '') {
            $search .= "AND vp_products.permanently_available = " . ((int)$filters['permanently_available'] ? 1 : 0);
        }
        if (!empty($filters['marketplace'])) {
            $mp = $this->db->real_escape_string((string) $filters['marketplace']);
            if ($this->vpProductsHasColumn('marketplace_vendor')) {
                $search .= "AND vp_products.marketplace_vendor like '%" . $mp . "%'";
            } elseif ($this->vpProductsHasColumn('marketplace')) {
                $search .= "AND vp_products.marketplace like '%" . $mp . "%'";
            }
        }

        $search .= " AND LOWER(TRIM(IFNULL(vp_products.item_level, ''))) <> 'parent' ";

        $stmt = $this->db->prepare("SELECT * FROM vp_products WHERE 1=1 $search order by vp_products.id DESC LIMIT ? OFFSET ?");
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        foreach ($rows as &$row) {
            $this->applyCatalogImageNormalizeToProductRow($row);
        }
        unset($row);

        return $rows;
    }

    /**
     * Exact stock qty and low-stock filters use warehouse physical_stock (not website local_stock).
     */
    private function appendProductCatalogStockFilters(array $filters): string
    {
        $search = '';
        $stockExact = null;
        if (isset($filters['physical_stock']) && $filters['physical_stock'] !== '') {
            $stockExact = (int)$filters['physical_stock'];
        } elseif (isset($filters['local_stock']) && $filters['local_stock'] !== '') {
            $stockExact = (int)$filters['local_stock'];
        }
        if ($stockExact !== null) {
            $search .= 'AND IFNULL(vp_products.physical_stock, 0) = ' . $stockExact;
        }
        if (isset($filters['low_stock']) && $filters['low_stock'] !== '') {
            if ((int)$filters['low_stock'] === 1) {
                $search .= 'AND IFNULL(vp_products.physical_stock, 0) <= IFNULL(vp_products.min_stock, 0)';
            } else {
                $search .= 'AND IFNULL(vp_products.physical_stock, 0) > IFNULL(vp_products.min_stock, 0)';
            }
        }

        return $search;
    }

    public function countAllProducts($filters = [])
    {
        $search = "";
        if (!empty($filters['item_code'])) {
            $v = $this->db->real_escape_string((string) $filters['item_code']);
            $search .= "AND vp_products.item_code like '%" . $v . "%'";
        }
        if (!empty($filters['title'])) {
            $v = $this->db->real_escape_string((string) $filters['title']);
            $search .= "AND vp_products.title like '%" . $v . "%'";
        }
        if (!empty($filters['vendor_name'])) {
            $v = $this->db->real_escape_string((string) $filters['vendor_name']);
            $search .= "AND vp_products.vendor like '%" . $v . "%'";
        }
        if (!empty($filters['groupname'])) {
            $v = $this->db->real_escape_string((string) $filters['groupname']);
            $search .= "AND vp_products.groupname like '%" . $v . "%'";
        }
        if (!empty($filters['sku'])) {
            $v = $this->db->real_escape_string((string) $filters['sku']);
            $search .= "AND vp_products.sku like '%" . $v . "%'";
        }
        if (!empty($filters['size'])) {
            $v = $this->db->real_escape_string((string) $filters['size']);
            $search .= "AND vp_products.size like '%" . $v . "%'";
        }
        if (!empty($filters['color'])) {
            $v = $this->db->real_escape_string((string) $filters['color']);
            $search .= "AND vp_products.color like '%" . $v . "%'";
        }
        $search .= $this->appendProductCatalogStockFilters($filters);
        if (isset($filters['permanently_available']) && $filters['permanently_available'] !== '') {
            $search .= "AND vp_products.permanently_available = " . ((int)$filters['permanently_available'] ? 1 : 0);
        }
        if (!empty($filters['marketplace'])) {
            $mp = $this->db->real_escape_string((string) $filters['marketplace']);
            if ($this->vpProductsHasColumn('marketplace_vendor')) {
                $search .= "AND vp_products.marketplace_vendor like '%" . $mp . "%'";
            } elseif ($this->vpProductsHasColumn('marketplace')) {
                $search .= "AND vp_products.marketplace like '%" . $mp . "%'";
            }
        }
        $search .= " AND LOWER(TRIM(IFNULL(vp_products.item_level, ''))) <> 'parent' ";
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM vp_products WHERE 1=1 $search");
        if ($stmt === false) {
            return 0;
        }
        //$stmt->bind_param('s', $search);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            return 0;
        }
        $row = $result->fetch_assoc();
        return isset($row['cnt']) ? (int)$row['cnt'] : 0;
    }
    public function getProductItems($search = '')
    {
        $searchTerm = "%$search%";
        $sql = "SELECT * FROM vp_products WHERE (item_code LIKE ? OR title LIKE ? OR sku LIKE ?)";
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderItems = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orderItems[] = [
                    'id' => $row['id'],
                    'sku' => $row['sku'],
                    'item_code' => $row['item_code'],
                    'title' => $row['title'],
                    'color' => $row['color'],
                    'size' => $row['size'],
                    'cost_price' => $row['cost_price'],
                    'gst' => $row['gst'],
                    'hsn' => $row['hsn'],
                    'description' => $row['description'],
                    'image' => $row['image'],
                    'local_stock' => $row['local_stock'],
                    'itemprice' => $row['itemprice'],
                    'leadtime' => $row['leadtime'],
                    'numsold' => $row['numsold'],
                    'numsold_india' => $row['numsold_india'],
                    'numsold_global' => $row['numsold_global'],
                    'lastsold' => $row['lastsold'],
                    'instock_leadtime' => $row['instock_leadtime'],
                    'fba_in' => $row['fba_in'],
                    'fba_us' => $row['fba_us']
                ];
            }
        }
        return $orderItems;
    }

    /**
     * Lightweight product search for autocomplete (minimal columns + hard limit).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProductItemsForAutocomplete($search = '', $limit = 40)
    {
        $search = trim((string) $search);
        if ($search === '') {
            return [];
        }
        $searchTerm = '%' . $search . '%';
        $limit = max(1, min(100, (int) $limit));
        $sql = 'SELECT id, sku, item_code, title, color, size, cost_price, gst, hsn, image FROM vp_products
            WHERE sku LIKE ?
            LIMIT ?';
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('si', $searchTerm, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderItems = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orderItems[] = [
                    'id' => $row['id'],
                    'sku' => $row['sku'],
                    'item_code' => $row['item_code'],
                    'title' => $row['title'],
                    'color' => $row['color'],
                    'size' => $row['size'],
                    'cost_price' => $row['cost_price'],
                    'gst' => $row['gst'],
                    'hsn' => $row['hsn'],
                    'image' => $row['image'] ?? '',
                ];
            }
        }
        $stmt->close();

        return $orderItems;
    }

    /**
     * Resolve product image for a direct-purchase line (variant match, then SKU).
     */
    public function getImageForPurchaseLine(string $itemCode, string $sku, string $color, string $size): ?string
    {
        $itemCode = trim($itemCode);
        $sku = trim($sku);
        $color = trim($color);
        $size = trim($size);

        if ($itemCode !== '') {
            $sql = 'SELECT image FROM vp_products WHERE item_code = ? AND COALESCE(color, \'\') = ? AND COALESCE(size, \'\') = ? LIMIT 1';
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('sss', $itemCode, $color, $size);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!empty($res['image'])) {
                    return (string) $res['image'];
                }
            }
        }

        if ($sku !== '') {
            $sql = 'SELECT image FROM vp_products WHERE sku = ? LIMIT 1';
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('s', $sku);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!empty($res['image'])) {
                    return (string) $res['image'];
                }
            }
        }

        return null;
    }

    public function getProductItemsByCode($item_code = '')
    {
        $searchTerm = "%$item_code%";
        $sql = "SELECT * FROM vp_products WHERE item_code LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderItems = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orderItems[] = [
                    'id' => $row['id'],
                    'sku' => $row['sku'],
                    'item_code' => $row['item_code'],
                    'title' => $row['title'],
                    'color' => $row['color'],
                    'size' => $row['size'],
                    'cost_price' => $row['cost_price'],
                    'gst' => $row['gst'],
                    'hsn' => $row['hsn'],
                    'description' => $row['description'],
                    'image' => $row['image']

                ];
            }
        }
        return $orderItems;
    }

    /**
     * Fetch vendor product/fetch API, sync vp_products (keep local stock), return latest cost for a variant line.
     *
     * @return array{success: bool, message?: string, cost_price?: float, cp?: float, gst?: float, hsn?: string}
     */
    public function refreshVariantCostFromVendorApi(string $itemCode, string $size = '', string $color = ''): array
    {
        $itemCode = trim($itemCode);
        if ($itemCode === '') {
            return ['success' => false, 'message' => 'Item code is required to fetch price.'];
        }

        $decoded = $this->fetchVendorProductApiPayload($itemCode);
        if ($decoded === null) {
            return ['success' => false, 'message' => 'Product fetch API request failed.'];
        }

        $productRows = self::normalizeVendorProductFetchItems($decoded);
        if ($productRows === []) {
            return ['success' => false, 'message' => 'No product data in API response.'];
        }

        $bulkConnection = $this->beginBulkProductUpdateConnection();
        try {
            $updateResult = $this->updateProductFromApi($productRows, ['preserve_local_stock' => true]);
        } finally {
            $this->endBulkProductUpdateConnection($bulkConnection);
        }

        if (!is_array($updateResult) || empty($updateResult['success'])) {
            return [
                'success' => false,
                'message' => is_array($updateResult) ? (string) ($updateResult['message'] ?? 'Product update failed.') : 'Product update failed.',
            ];
        }

        $row = $this->findByItemCodeSizeColor($itemCode, trim($size), trim($color));
        if (!$row) {
            $row = $this->findByItemCodeSizeColor($itemCode, '', '');
        }
        if (!$row || empty($row['id'])) {
            return ['success' => false, 'message' => 'Product row not found after API sync.'];
        }

        $cp = (float) ($row['cp'] ?? 0);
        $productId = (int) $row['id'];
        if ($cp > 0) {
            $stmt = $this->db->prepare('UPDATE vp_products SET cost_price = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('di', $cp, $productId);
                $stmt->execute();
                $stmt->close();
                $row['cost_price'] = $cp;
            }
        }

        $cost = (float) ($row['cost_price'] ?? 0);
        if ($cost <= 0 && $cp > 0) {
            $cost = $cp;
        }
        if ($cost <= 0) {
            return ['success' => false, 'message' => 'API returned no cost price (cp) for this product.'];
        }

        return [
            'success' => true,
            'message' => 'Latest cost price fetched from API.',
            'cost_price' => $cost,
            'cp' => $cp,
            'gst' => (float) ($row['gst'] ?? 0),
            'hsn' => trim((string) ($row['hsn'] ?? '')),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchVendorProductApiPayload(string $itemCode): ?array
    {
        $itemCode = trim($itemCode);
        if ($itemCode === '') {
            return null;
        }

        $url = 'https://www.exoticindia.com/vendor-api/product/fetch?itemcodes=' . urlencode($itemCode);
        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : null;
    }

    /** Set accounts_group on all rows for an item code from the vendor product/fetch master row. */
    public function syncAccountsGroupFromApiItem(string $itemCode, array $apiItem): void
    {
        $itemCode = trim($itemCode);
        if ($itemCode === '' || !$this->vpProductsHasColumn('accounts_group')) {
            return;
        }
        $value = self::vendorApiAccountsGroup($apiItem);
        $stmt = $this->db->prepare('UPDATE vp_products SET accounts_group = ? WHERE item_code = ?');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('ss', $value, $itemCode);
        $stmt->execute();
        $stmt->close();
    }

    public function updateProductFromApi($productData, array $options = [])
    {
        $updatedCount = 0;
        // Default: never overwrite vp_products.local_stock from API; pass preserve_local_stock => false to sync stock from API.
        $preserveLocalStock = !array_key_exists('preserve_local_stock', $options) || !empty($options['preserve_local_stock']);
        // print_array($productData);
        // exit;
        $vendorMapSynced = 0;
        $vendorMapSkipped = 0;
        if (isset($productData) && is_array($productData)) {
            foreach ($productData as $product) {
                if (!is_array($product)) {
                    continue;
                }
                $itemcode = trim((string)($product['itemcode'] ?? $product['item_code'] ?? ''));
                if ($itemcode === '') {
                    continue;
                }
                $product['itemcode'] = $itemcode;
                $product = $this->castApiProductLikeForm($product);
                $now = date('Y-m-d H:i:s');
                //echo "Updating single itemcode: ".$product['itemcode']."<br/>";           
                $existingBase = $this->findByItemCodeSizeColor($product['itemcode'], (string)($product['size'] ?? ''), (string)($product['color'] ?? ''));
                $stmt = $this->db->prepare("UPDATE vp_products SET asin = ?, local_stock = ?, upc = ?, location = ?, fba_in = ?, fba_us = ?, leadtime = ?, instock_leadtime = ?, permanently_available = ?, numsold = ?, numsold_india = ?, numsold_global = ?, lastsold = ?, vendor = ?, shippingfee = ?, sourcingfee = ?, price = ?, price_india = ?, price_india_suggested = ?, mrp_india = ?, permanent_discount = ?, discount_global = ?, discount_india = ?, hsn = ?, image = COALESCE(NULLIF(TRIM(?), ''), image), updated_at = ?, sku = ?, category = ?, itemtype = ?, snippet_description = ?, india_net_qty = ?, keywords = ?, usblock = ?, indiablock = ?, hscode = ?, date_first_added = COALESCE(NULLIF(TRIM(?), ''), date_first_added), search_term = ?, search_category = ?, long_description = ?, long_description_india = ?, aplus_content_ids = ?, item_level = ?, marketplace_vendor = ?, colormap = ?, flex_status = ?, vendor_us = ?, today_global = ?, today_india = ?, topurchase = ?, backorder_percent = ?, backorder_weeks = ?, cp = ?, usd = ?, amazon_sold = ?, amazon_leadtime = ?, amazon_itemcode_alias = ?, youtube_links = ?, sketchfab_links = ?, dimensions = ?, update_flag = 1 WHERE item_code = ? AND COALESCE(NULLIF(TRIM(size), ''), '') = COALESCE(NULLIF(TRIM(?), ''), '') AND COALESCE(NULLIF(TRIM(color), ''), '') = COALESCE(NULLIF(TRIM(?), ''), '')");
                if ($stmt) {
                    // $title = isset($product['title']) ? $product['title'] : '';
                    $sku = isset($product['sku']) && !empty($product['sku']) ? $product['sku'] : $product['itemcode'];
                    $color = isset($product['color']) ? (string)$product['color'] : '';
                    $size = isset($product['size']) ? (string)$product['size'] : '';
                    // $costPrice = isset($product['cost_price']) ? (float)$product['cost_price'] : 0.0;
                    // $gst = isset($product['gst']) ? (float)$product['gst'] : 0.0;
                    // $hsn = isset($product['hsn']) ? $product['hsn'] : '';
                    // $description = isset($product['description']) ? $product['description'] : '';
                    $image = self::vendorApiImageStorageValue($product['image'] ?? '');
                    // $stockQuantity = isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : 0;
                    $asin = isset($product['asin']) ? $product['asin'] : '';
                    $localStock = isset($product['local_stock']) ? (int)$product['local_stock'] : 0;
                    if ($preserveLocalStock && $existingBase && array_key_exists('local_stock', $existingBase)) {
                        $localStock = (int)$existingBase['local_stock'];
                    }
                    $upc = isset($product['upc']) ? $product['upc'] : '';
                    $location = isset($product['location']) ? $product['location'] : '';
                    $fba_in = isset($product['fba_in']) ? (int)$product['fba_in'] : 0;
                    $fba_us = isset($product['fba_us']) ? (int)$product['fba_us'] : 0;
                    $leadtime = $this->normalizeIntValue($product['leadtime'] ?? null, 0);
                    $instock_leadtime = $this->normalizeIntValue($product['instock_leadtime'] ?? null, 0);
                    $permanently_available = isset($product['permanently_available']) ? (int)$product['permanently_available'] : 0;
                    $numsold = isset($product['numsold']) ? (int)$product['numsold'] : 0;
                    $numsold_india = isset($product['numsold_india']) ? (int)$product['numsold_india'] : 0;
                    $numsold_global = isset($product['numsold_global']) ? (int)$product['numsold_global'] : 0;
                    $lastsold = $this->normalizeIntValue($product['lastsold'] ?? null, 0);
                    $vendor = isset($product['vendor']) ? $product['vendor'] : '';
                    $shippingfee = isset($product['shippingfee']) ? (float)$product['shippingfee'] : 0.0;
                    $sourcingfee = isset($product['sourcingfee']) ? (float)$product['sourcingfee'] : 0.0;
                    $price = self::vendorApiUsdPrice($product);
                    $price_india = isset($product['price_india']) ? (float)$product['price_india'] : 0.0;
                    $price_india_suggested = isset($product['price_india_suggested']) ? (float)$product['price_india_suggested'] : 0.0;
                    $mrp_india = isset($product['mrp_india']) ? (float)$product['mrp_india'] : 0.0;
                    $permanent_discount = isset($product['permanent_discount']) ? (float)$product['permanent_discount'] : 0.0;
                    $discount_global = isset($product['discount_global']) ? (float)$product['discount_global'] : 0.0;
                    $discount_india = isset($product['discount_india']) ? (float)$product['discount_india'] : 0.0;
                    $hsn = self::vendorApiHsn($product);
                    $updated_at = $now;
                    $category = isset($product['category']) ? $product['category'] : '';
                    $itemtype = isset($product['itemtype']) ? $product['itemtype'] : '';
                    $snippet_description = isset($product['snippet_description']) ? $product['snippet_description'] : '';
                    $india_net_qty = isset($product['india_net_qty']) ? (int)$product['india_net_qty'] : 0;
                    $keywords = isset($product['keywords']) ? $product['keywords'] : '';
                    $usblock = isset($product['usblock']) ? (int)$product['usblock'] : 0;
                    $indiablock = isset($product['indiablock']) ? (int)$product['indiablock'] : 0;
                    $hscode = isset($product['hscode']) ? $product['hscode'] : '';
                    $date_first_added = $this->normalizeApiDateValue($product['date_first_added'] ?? '');
                    $relatedSearch = self::vendorApiRelatedSearchFields($product);
                    $search_term = $relatedSearch['search_term'];
                    $search_category = $relatedSearch['search_category'];
                    $long_description = isset($product['long_description']) ? $product['long_description'] : '';
                    $long_description_india = isset($product['long_description_india']) ? $product['long_description_india'] : '';
                    $aplus_content_ids = isset($product['aplus_content_ids']) ? $product['aplus_content_ids'] : '';
                    $item_level = isset($product['item_level']) ? $product['item_level'] : '';
                    $marketplace_vendor = isset($product['marketplace_vendor']) ? $product['marketplace_vendor'] : '';
                    $colormap = isset($product['colormap']) ? $product['colormap'] : '';
                    $flex_status = isset($product['flex_status']) ? $product['flex_status'] : '';
                    $vendor_us = isset($product['vendor_us']) ? $product['vendor_us'] : '';
                    $today_global = isset($product['today_global']) ? $product['today_global'] : '';
                    $today_india = isset($product['today_india']) ? $product['today_india'] : '';
                    $topurchase = isset($product['topurchase']) ? (int)$product['topurchase'] : 0;
                    $backorder_percent = isset($product['backorder_percent']) ? (int)$product['backorder_percent'] : 0;
                    $backorder_weeks = isset($product['backorder_weeks']) ? (int)$product['backorder_weeks'] : 0;
                    $cp = isset($product['cp']) ? (float)$product['cp'] : 0.0;
                    $usd = isset($product['usd']) ? (float)$product['usd'] : 0.0;
                    $amazon_sold = isset($product['amazon_sold']) ? (int)$product['amazon_sold'] : 0;
                    $amazon_leadtime = isset($product['amazon_leadtime']) ? (int)$product['amazon_leadtime'] : 0;
                    $amazon_itemcode_alias = isset($product['amazon_itemcode_alias']) ? $product['amazon_itemcode_alias'] : '';
                    $youtube_links = isset($product['youtube_links']) ? $product['youtube_links'] : '';
                    $sketchfab_links = isset($product['sketchfab_links']) ? $product['sketchfab_links'] : '';
                    $dimensions = isset($product['dimensions']) ? $product['dimensions'] : '';
                    $bt = 'siss' . str_repeat('i', 9) . 's' . str_repeat('d', 9) . str_repeat('s', 4) . 'sssisiissssssssssssssiiiddiissss' . str_repeat('s', 3);
                    $stmt->bind_param(
                        $bt,
                        $asin,
                        $localStock,
                        $upc,
                        $location,
                        $fba_in,
                        $fba_us,
                        $leadtime,
                        $instock_leadtime,
                        $permanently_available,
                        $numsold,
                        $numsold_india,
                        $numsold_global,
                        $lastsold,
                        $vendor,
                        $shippingfee,
                        $sourcingfee,
                        $price,
                        $price_india,
                        $price_india_suggested,
                        $mrp_india,
                        $permanent_discount,
                        $discount_global,
                        $discount_india,
                        $hsn,
                        $image,
                        $updated_at,
                        $sku,
                        $category,
                        $itemtype,
                        $snippet_description,
                        $india_net_qty,
                        $keywords,
                        $usblock,
                        $indiablock,
                        $hscode,
                        $date_first_added,
                        $search_term,
                        $search_category,
                        $long_description,
                        $long_description_india,
                        $aplus_content_ids,
                        $item_level,
                        $marketplace_vendor,
                        $colormap,
                        $flex_status,
                        $vendor_us,
                        $today_global,
                        $today_india,
                        $topurchase,
                        $backorder_percent,
                        $backorder_weeks,
                        $cp,
                        $usd,
                        $amazon_sold,
                        $amazon_leadtime,
                        $amazon_itemcode_alias,
                        $youtube_links,
                        $sketchfab_links,
                        $dimensions,
                        $product['itemcode'],
                        $size,
                        $color
                    );
                    //echo "Executing update for itemcode: ".$product['itemcode']."<br/>";                          
                    if ($this->executeVpProductsStmt($stmt)) {
                        $updatedCount++;
                        if ($existingBase && isset($existingBase['id'])) {
                            $this->applyApiRefreshStockAdjustments(
                                (int)$existingBase['id'],
                                (string)$sku,
                                (string)$product['itemcode'],
                                (string)$size,
                                (string)$color,
                                (int)$localStock
                            );
                        }
                    }
                    if ($stmt->error) {
                        return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
                    }
                    // If there is no matching row (common when variants aren't pre-created), insert it.
                    if ($stmt->affected_rows < 1) {
                        $exists = $this->findByItemCodeSizeColor($product['itemcode'], $size, $color);
                        if (!$exists) {
                            $img = self::vendorApiImageStorageValue($product['image'] ?? '');
                            $insertId = $this->createProduct([
                                'item_code' => $product['itemcode'],
                                'sku' => (string)$sku,
                                'size' => (string)$size,
                                'color' => (string)$color,
                                'title' => (string)($product['title'] ?? ''),
                                'image' => $img,
                                'local_stock' => (float)$localStock,
                                'itemprice' => (float)$price,
                                'finalprice' => (float)$price,
                                'groupname' => (string)($product['groupname'] ?? ''),
                                'material' => (string)($product['material'] ?? ''),
                                'cost_price' => (float)($product['cp'] ?? 0),
                                'gst' => (float)($product['gst'] ?? 0),
                                'hsn' => (string)$hsn,
                                'description' => (string)($product['snippet_description'] ?? ($product['description'] ?? '')),
                                'asin' => (string)$asin,
                                'upc' => (string)$upc,
                                'location' => (string)$location,
                                'fba_in' => (int)$fba_in,
                                'fba_us' => (int)$fba_us,
                                'leadtime' => (int)$leadtime,
                                'instock_leadtime' => (int)$instock_leadtime,
                                'permanently_available' => (int)$permanently_available,
                                'numsold' => (int)$numsold,
                                'numsold_india' => (int)$numsold_india,
                                'numsold_global' => (int)$numsold_global,
                                'lastsold' => (int)$lastsold,
                                'vendor' => (string)$vendor,
                                'shippingfee' => (float)$shippingfee,
                                'sourcingfee' => (float)$sourcingfee,
                                'price' => (float)$price,
                                'price_india' => (float)$price_india,
                                'price_india_suggested' => (float)$price_india_suggested,
                                'mrp_india' => (float)$mrp_india,
                                'permanent_discount' => (float)$permanent_discount,
                                'discount_global' => (float)$discount_global,
                                'discount_india' => (float)$discount_india,
                                'product_weight' => (float)($product['product_weight'] ?? 0),
                                'product_weight_unit' => (string)($product['product_weight_unit'] ?? ''),
                                'prod_height' => (float)($product['prod_height'] ?? 0),
                                'prod_width' => (float)($product['prod_width'] ?? 0),
                                'prod_length' => (float)($product['prod_length'] ?? 0),
                                'length_unit' => (string)($product['length_unit'] ?? ''),
                                'created_at' => $now,
                                'updated_at' => $now,
                                'category' => $category,
                                'itemtype' => $itemtype,
                                'snippet_description' => $snippet_description,
                                'india_net_qty' => $india_net_qty,
                                'keywords' => $keywords,
                                'usblock' => $usblock,
                                'indiablock' => $indiablock,
                                'hscode' => $hscode,
                                'date_first_added' => $date_first_added,
                                'search_term' => $search_term,
                                'search_category' => $search_category,
                                'long_description' => $long_description,
                                'long_description_india' => $long_description_india,
                                'aplus_content_ids' => $aplus_content_ids,
                                'item_level' => $item_level,
                                'marketplace_vendor' => $marketplace_vendor,
                                'colormap' => $colormap,
                                'flex_status' => $flex_status,
                                'vendor_us' => $vendor_us,
                                'today_global' => $today_global,
                                'today_india' => $today_india,
                                'topurchase' => $topurchase,
                                'backorder_percent' => $backorder_percent,
                                'backorder_weeks' => $backorder_weeks,
                                'cp' => $cp,
                                'usd' => $usd,
                                'amazon_sold' => $amazon_sold,
                                'amazon_leadtime' => $amazon_leadtime,
                                'amazon_itemcode_alias' => $amazon_itemcode_alias,
                                'youtube_links' => $youtube_links,
                                'sketchfab_links' => $sketchfab_links,
                                'dimensions' => $dimensions,
                            ]);
                            if ($insertId) {
                                $updatedCount++;
                            }
                        }
                    }
                    $stmt->close();
                }
                if (isset($product['variations'])) {
                    foreach ($product['variations'] as $variation) {
                        if (!is_array($variation)) {
                            continue;
                        }
                        $variation = $this->castApiProductLikeForm($variation);
                        //echo "Updating variations itemcode: ".$product['itemcode']."<br/>";
                        $existingBase = $this->findByItemCodeSizeColor($product['itemcode'], (string)($variation['size'] ?? ''), (string)($variation['color'] ?? ''));
                        $stmt = $this->db->prepare("UPDATE vp_products SET asin = ?, local_stock = ?, upc = ?, location = ?, fba_in = ?, fba_us = ?, leadtime = ?, instock_leadtime = ?, permanently_available = ?, numsold = ?, numsold_india = ?, numsold_global = ?, lastsold = ?, vendor = ?, shippingfee = ?, sourcingfee = ?, price = ?, price_india = ?, price_india_suggested = ?, mrp_india = ?, permanent_discount = ?, discount_global = ?, discount_india = ?, hsn = ?, image = COALESCE(NULLIF(TRIM(?), ''), image), updated_at = ?, sku = ?, category = ?, itemtype = ?, snippet_description = ?, india_net_qty = ?, keywords = ?, usblock = ?, indiablock = ?, hscode = ?, date_first_added = COALESCE(NULLIF(TRIM(?), ''), date_first_added), search_term = ?, search_category = ?, long_description = ?, long_description_india = ?, aplus_content_ids = ?, item_level = ?, marketplace_vendor = ?, colormap = ?, flex_status = ?, vendor_us = ?, today_global = ?, today_india = ?, topurchase = ?, backorder_percent = ?, backorder_weeks = ?, cp = ?, usd = ?, amazon_sold = ?, amazon_leadtime = ?, amazon_itemcode_alias = ?, youtube_links = ?, sketchfab_links = ?, dimensions = ?, update_flag = 1 WHERE item_code = ? AND COALESCE(NULLIF(TRIM(size), ''), '') = COALESCE(NULLIF(TRIM(?), ''), '') AND COALESCE(NULLIF(TRIM(color), ''), '') = COALESCE(NULLIF(TRIM(?), ''), '')");
                        if ($stmt) {
                            // $title = isset($product['title']) ? $product['title'] : '';
                            $sku = isset($variation['sku']) && !empty($variation['sku']) ? $variation['sku'] : $product['itemcode'];
                            $color = isset($variation['color']) ? (string)$variation['color'] : '';
                            $size = isset($variation['size']) ? (string)$variation['size'] : '';
                            // $costPrice = isset($product['cost_price']) ? (float)$product['cost_price'] : 0.0;
                            // $gst = isset($product['gst']) ? (float)$product['gst'] : 0.0;
                            // $hsn = isset($product['hsn']) ? $product['hsn'] : '';
                            // $description = isset($product['description']) ? $product['description'] : '';
                            $image = self::vendorApiImageStorageValue($variation['image'] ?? ($product['image'] ?? ''));
                            // $stockQuantity = isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : 0;
                            $asin = isset($variation['asin']) ? $variation['asin'] : '';
                            $localStock = isset($variation['local_stock']) ? (int)$variation['local_stock'] : 0;
                            if ($preserveLocalStock && $existingBase && array_key_exists('local_stock', $existingBase)) {
                                $localStock = (int)$existingBase['local_stock'];
                            }
                            $upc = isset($variation['upc']) ? $variation['upc'] : '';
                            $location = isset($variation['location']) ? $variation['location'] : '';
                            $fba_in = isset($variation['fba_in']) ? (int)$variation['fba_in'] : 0;
                            $fba_us = isset($variation['fba_us']) ? (int)$variation['fba_us'] : 0;
                            $leadtime = $this->normalizeIntValue($variation['leadtime'] ?? null, 0);
                            $instock_leadtime = $this->normalizeIntValue($variation['instock_leadtime'] ?? null, 0);
                            $permanently_available = isset($variation['permanently_available']) ? (int)$variation['permanently_available'] : 0;
                            $numsold = isset($variation['numsold']) ? (int)$variation['numsold'] : 0;
                            $numsold_india = isset($variation['numsold_india']) ? (int)$variation['numsold_india'] : 0;
                            $numsold_global = isset($variation['numsold_global']) ? (int)$variation['numsold_global'] : 0;
                            $lastsold = $this->normalizeIntValue($variation['lastsold'] ?? null, 0);
                            $vendor = isset($product['vendor']) ? $product['vendor'] : '';
                            $shippingfee = isset($product['shippingfee']) ? (float)$product['shippingfee'] : 0.0;
                            $sourcingfee = isset($product['sourcingfee']) ? (float)$product['sourcingfee'] : 0.0;
                            $price = self::vendorApiUsdPrice(array_merge($product, $variation));
                            $price_india = isset($product['price_india']) ? (float)$product['price_india'] : 0.0;
                            $price_india_suggested = isset($product['price_india_suggested']) ? (float)$product['price_india_suggested'] : 0.0;
                            $mrp_india = isset($product['mrp_india']) ? (float)$product['mrp_india'] : 0.0;
                            $permanent_discount = isset($product['permanent_discount']) ? (float)$product['permanent_discount'] : 0.0;
                            $discount_global = isset($product['discount_global']) ? (float)$product['discount_global'] : 0.0;
                            $discount_india = isset($product['discount_india']) ? (float)$product['discount_india'] : 0.0;
                            $hsn = self::vendorApiHsn($product);
                            $updated_at = $now;
                            $category = isset($variation['category']) ? $variation['category'] : (isset($product['category']) ? $product['category'] : '');
                            $itemtype = isset($variation['itemtype']) ? $variation['itemtype'] : (isset($product['itemtype']) ? $product['itemtype'] : '');
                            $snippet_description = isset($variation['snippet_description']) ? $variation['snippet_description'] : (isset($product['snippet_description']) ? $product['snippet_description'] : '');
                            $india_net_qty = isset($variation['india_net_qty']) ? (int)$variation['india_net_qty'] : (isset($product['india_net_qty']) ? (int)$product['india_net_qty'] : 0);
                            $keywords = isset($variation['keywords']) ? $variation['keywords'] : (isset($product['keywords']) ? $product['keywords'] : '');
                            $usblock = isset($variation['usblock']) ? (int)$variation['usblock'] : (isset($product['usblock']) ? (int)$product['usblock'] : 0);
                            $indiablock = isset($variation['indiablock']) ? (int)$variation['indiablock'] : (isset($product['indiablock']) ? (int)$product['indiablock'] : 0);
                            $hscode = isset($variation['hscode']) ? $variation['hscode'] : (isset($product['hscode']) ? $product['hscode'] : '');
                            $date_first_added = $this->normalizeApiDateValue(
                                $variation['date_first_added'] ?? $product['date_first_added'] ?? ''
                            );
                            $variationSearch = self::vendorApiRelatedSearchFields($variation);
                            $parentSearch = self::vendorApiRelatedSearchFields($product);
                            $search_term = $variationSearch['search_term'] !== ''
                                ? $variationSearch['search_term']
                                : $parentSearch['search_term'];
                            $search_category = $variationSearch['search_category'] !== ''
                                ? $variationSearch['search_category']
                                : $parentSearch['search_category'];
                            $long_description = isset($variation['long_description']) ? $variation['long_description'] : (isset($product['long_description']) ? $product['long_description'] : '');
                            $long_description_india = isset($variation['long_description_india']) ? $variation['long_description_india'] : (isset($product['long_description_india']) ? $product['long_description_india'] : '');
                            $aplus_content_ids = isset($variation['aplus_content_ids']) ? $variation['aplus_content_ids'] : (isset($product['aplus_content_ids']) ? $product['aplus_content_ids'] : '');
                            $item_level = isset($variation['item_level']) ? $variation['item_level'] : (isset($product['item_level']) ? $product['item_level'] : '');
                            $marketplace_vendor = isset($variation['marketplace_vendor']) ? $variation['marketplace_vendor'] : (isset($product['marketplace_vendor']) ? $product['marketplace_vendor'] : '');
                            $colormap = isset($variation['colormap']) ? $variation['colormap'] : (isset($product['colormap']) ? $product['colormap'] : '');
                            $flex_status = isset($variation['flex_status']) ? $variation['flex_status'] : (isset($product['flex_status']) ? $product['flex_status'] : '');
                            $vendor_us = isset($variation['vendor_us']) ? $variation['vendor_us'] : (isset($product['vendor_us']) ? $product['vendor_us'] : '');
                            $today_global = isset($variation['today_global']) ? $variation['today_global'] : (isset($product['today_global']) ? $product['today_global'] : '');
                            $today_india = isset($variation['today_india']) ? $variation['today_india'] : (isset($product['today_india']) ? $product['today_india'] : '');
                            $topurchase = isset($variation['topurchase']) ? (int)$variation['topurchase'] : (isset($product['topurchase']) ? (int)$product['topurchase'] : 0);
                            $backorder_percent = isset($variation['backorder_percent']) ? (int)$variation['backorder_percent'] : (isset($product['backorder_percent']) ? (int)$product['backorder_percent'] : 0);
                            $backorder_weeks = isset($variation['backorder_weeks']) ? (int)$variation['backorder_weeks'] : (isset($product['backorder_weeks']) ? (int)$product['backorder_weeks'] : 0);
                            $cp = isset($variation['cp']) ? (float)$variation['cp'] : (isset($product['cp']) ? (float)$product['cp'] : 0.0);
                            $usd = isset($variation['usd']) ? (float)$variation['usd'] : (isset($product['usd']) ? (float)$product['usd'] : 0.0);
                            $amazon_sold = isset($variation['amazon_sold']) ? (int)$variation['amazon_sold'] : (isset($product['amazon_sold']) ? (int)$product['amazon_sold'] : 0);
                            $amazon_leadtime = isset($variation['amazon_leadtime']) ? (int)$variation['amazon_leadtime'] : (isset($product['amazon_leadtime']) ? (int)$product['amazon_leadtime'] : 0);
                            $amazon_itemcode_alias = isset($variation['amazon_itemcode_alias']) ? $variation['amazon_itemcode_alias'] : (isset($product['amazon_itemcode_alias']) ? $product['amazon_itemcode_alias'] : '');
                            $youtube_links = isset($variation['youtube_links']) ? $variation['youtube_links'] : (isset($product['youtube_links']) ? $product['youtube_links'] : '');
                            $sketchfab_links = isset($variation['sketchfab_links']) ? $variation['sketchfab_links'] : (isset($product['sketchfab_links']) ? $product['sketchfab_links'] : '');
                            $dimensions = isset($variation['dimensions']) ? $variation['dimensions'] : (isset($product['dimensions']) ? $product['dimensions'] : '');
                            $bt = 'siss' . str_repeat('i', 9) . 's' . str_repeat('d', 9) . str_repeat('s', 4) . 'sssisiissssssssssssssiiiddiissss' . str_repeat('s', 3);
                            $stmt->bind_param(
                                $bt,
                                $asin,
                                $localStock,
                                $upc,
                                $location,
                                $fba_in,
                                $fba_us,
                                $leadtime,
                                $instock_leadtime,
                                $permanently_available,
                                $numsold,
                                $numsold_india,
                                $numsold_global,
                                $lastsold,
                                $vendor,
                                $shippingfee,
                                $sourcingfee,
                                $price,
                                $price_india,
                                $price_india_suggested,
                                $mrp_india,
                                $permanent_discount,
                                $discount_global,
                                $discount_india,
                                $hsn,
                                $image,
                                $updated_at,
                                $sku,
                                $category,
                                $itemtype,
                                $snippet_description,
                                $india_net_qty,
                                $keywords,
                                $usblock,
                                $indiablock,
                                $hscode,
                                $date_first_added,
                                $search_term,
                                $search_category,
                                $long_description,
                                $long_description_india,
                                $aplus_content_ids,
                                $item_level,
                                $marketplace_vendor,
                                $colormap,
                                $flex_status,
                                $vendor_us,
                                $today_global,
                                $today_india,
                                $topurchase,
                                $backorder_percent,
                                $backorder_weeks,
                                $cp,
                                $usd,
                                $amazon_sold,
                                $amazon_leadtime,
                                $amazon_itemcode_alias,
                                $youtube_links,
                                $sketchfab_links,
                                $dimensions,
                                $product['itemcode'],
                                $size,
                                $color
                            );
                            if ($this->executeVpProductsStmt($stmt)) {
                                $updatedCount++;
                                if ($existingBase && isset($existingBase['id'])) {
                                    $this->applyApiRefreshStockAdjustments(
                                        (int)$existingBase['id'],
                                        (string)$sku,
                                        (string)$product['itemcode'],
                                        (string)$size,
                                        (string)$color,
                                        (int)$localStock
                                    );
                                }
                            }
                            if ($stmt->error) {
                                return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
                            }
                            // Same as parent row: insert variation if it doesn't exist yet.
                            if ($stmt->affected_rows < 1) {
                                $exists = $this->findByItemCodeSizeColor($product['itemcode'], $size, $color);
                                if (!$exists) {
                                    $img = self::vendorApiImageStorageValue($variation['image'] ?? ($product['image'] ?? ''));
                                    $insertId = $this->createProduct([
                                        'item_code' => $product['itemcode'],
                                        'sku' => (string)$sku,
                                        'size' => (string)$size,
                                        'color' => (string)$color,
                                        'title' => (string)($product['title'] ?? ''),
                                        'image' => $img,
                                        'local_stock' => (float)$localStock,
                                        'itemprice' => (float)$price,
                                        'finalprice' => (float)$price,
                                        'groupname' => (string)($product['groupname'] ?? ''),
                                        'material' => (string)($product['material'] ?? ''),
                                        'cost_price' => (float)($variation['cp'] ?? ($product['cp'] ?? 0)),
                                        'gst' => (float)($variation['gst'] ?? ($product['gst'] ?? 0)),
                                        'hsn' => (string)$hsn,
                                        'description' => (string)($product['snippet_description'] ?? ($product['description'] ?? '')),
                                        'asin' => (string)$asin,
                                        'upc' => (string)$upc,
                                        'location' => (string)$location,
                                        'fba_in' => (int)$fba_in,
                                        'fba_us' => (int)$fba_us,
                                        'leadtime' => (int)$leadtime,
                                        'instock_leadtime' => (int)$instock_leadtime,
                                        'permanently_available' => (int)$permanently_available,
                                        'numsold' => (int)$numsold,
                                        'numsold_india' => (int)$numsold_india,
                                        'numsold_global' => (int)$numsold_global,
                                        'lastsold' => (int)$lastsold,
                                        'vendor' => (string)$vendor,
                                        'shippingfee' => (float)$shippingfee,
                                        'sourcingfee' => (float)$sourcingfee,
                                        'price' => (float)$price,
                                        'price_india' => (float)($variation['price_india'] ?? ($product['price_india'] ?? 0)),
                                        'price_india_suggested' => (float)($variation['price_india_suggested'] ?? ($product['price_india_suggested'] ?? 0)),
                                        'mrp_india' => (float)($variation['mrp_india'] ?? ($product['mrp_india'] ?? 0)),
                                        'permanent_discount' => (float)($variation['permanent_discount'] ?? ($product['permanent_discount'] ?? 0)),
                                        'discount_global' => (float)($variation['discount_global'] ?? ($product['discount_global'] ?? 0)),
                                        'discount_india' => (float)($variation['discount_india'] ?? ($product['discount_india'] ?? 0)),
                                        'product_weight' => (float)($variation['product_weight'] ?? ($product['product_weight'] ?? 0)),
                                        'product_weight_unit' => (string)($variation['product_weight_unit'] ?? ($product['product_weight_unit'] ?? '')),
                                        'prod_height' => (float)($variation['prod_height'] ?? ($product['prod_height'] ?? 0)),
                                        'prod_width' => (float)($variation['prod_width'] ?? ($product['prod_width'] ?? 0)),
                                        'prod_length' => (float)($variation['prod_length'] ?? ($product['prod_length'] ?? 0)),
                                        'length_unit' => (string)($variation['length_unit'] ?? ($product['length_unit'] ?? '')),
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                        'category' => $category,
                                        'itemtype' => $itemtype,
                                        'snippet_description' => $snippet_description,
                                        'india_net_qty' => $india_net_qty,
                                        'keywords' => $keywords,
                                        'usblock' => $usblock,
                                        'indiablock' => $indiablock,
                                        'hscode' => $hscode,
                                        'date_first_added' => $date_first_added,
                                        'search_term' => $search_term,
                                        'search_category' => $search_category,
                                        'long_description' => $long_description,
                                        'long_description_india' => $long_description_india,
                                        'aplus_content_ids' => $aplus_content_ids,
                                        'item_level' => $item_level,
                                        'marketplace_vendor' => $marketplace_vendor,
                                        'colormap' => $colormap,
                                        'flex_status' => $flex_status,
                                        'vendor_us' => $vendor_us,
                                        'today_global' => $today_global,
                                        'today_india' => $today_india,
                                        'topurchase' => $topurchase,
                                        'backorder_percent' => $backorder_percent,
                                        'backorder_weeks' => $backorder_weeks,
                                        'cp' => $cp,
                                        'usd' => $usd,
                                        'amazon_sold' => $amazon_sold,
                                        'amazon_leadtime' => $amazon_leadtime,
                                        'amazon_itemcode_alias' => $amazon_itemcode_alias,
                                        'youtube_links' => $youtube_links,
                                        'sketchfab_links' => $sketchfab_links,
                                        'dimensions' => $dimensions,
                                    ]);
                                    if ($insertId) {
                                        $updatedCount++;
                                    }
                                }
                            }
                            $stmt->close();
                        }
                    }
                }

                if ($itemcode !== '') {
                    $this->syncAccountsGroupFromApiItem($itemcode, $product);
                    $mapResult = $this->syncProductVendorMapFromApiItem($itemcode, $product);
                    $vendorMapSynced += (int) ($mapResult['synced'] ?? 0);
                    $vendorMapSkipped += (int) ($mapResult['skipped'] ?? 0);
                }
            }
        }
        return [
            'success' => true,
            'updated_count' => $updatedCount,
            'vendor_map_synced' => $vendorMapSynced,
            'vendor_map_skipped' => $vendorMapSkipped,
            'message' => 'Products updated successfully.',
        ];
    }

    private function resolveVpStockMovementsItemCodeColumn(): string
    {
        if (is_string($this->stockMovementItemCodeColumn) && $this->stockMovementItemCodeColumn !== '') {
            return $this->stockMovementItemCodeColumn;
        }
        $fallback = 'item_code';
        $res = $this->db->query('SHOW COLUMNS FROM vp_stock_movements');
        if ($res) {
            $preferred = ['item_code', 'itemcode', 'product_item_code', 'code'];
            $existing = [];
            while ($row = $res->fetch_assoc()) {
                if (isset($row['Field'])) {
                    $existing[strtolower((string)$row['Field'])] = (string)$row['Field'];
                }
            }
            foreach ($preferred as $candidate) {
                if (isset($existing[$candidate])) {
                    $this->stockMovementItemCodeColumn = $existing[$candidate];
                    return $this->stockMovementItemCodeColumn;
                }
            }
        }
        $this->stockMovementItemCodeColumn = $fallback;
        return $this->stockMovementItemCodeColumn;
    }

    private function applyApiRefreshStockAdjustments(
        int $productId,
        string $sku,
        string $itemCode,
        string $size,
        string $color,
        int $apiLocalStock
    ): void {
        if ($productId <= 0 || $itemCode === '') {
            return;
        }

        $size = trim($size);
        $color = trim($color);
        $itemCodeCol = $this->resolveVpStockMovementsItemCodeColumn();
        $safeItemCol = '`' . str_replace('`', '``', $itemCodeCol) . '`';

        $latest = $this->db->prepare("SELECT running_stock, warehouse_id, location
            FROM vp_stock_movements
            WHERE product_id = ? AND {$safeItemCol} = ?
              AND IFNULL(NULLIF(TRIM(size), ''), '') <=> ?
              AND IFNULL(NULLIF(TRIM(color), ''), '') <=> ?
            ORDER BY id DESC
            LIMIT 1");
        if (!$latest) {
            return;
        }
        $latest->bind_param('isss', $productId, $itemCode, $size, $color);
        if (!$latest->execute()) {
            $latest->close();
            return;
        }
        $latestRow = $latest->get_result()->fetch_assoc();
        $latest->close();

        $warehouseId = (int)($latestRow['warehouse_id'] ?? 1);
        if ($warehouseId <= 0) {
            $warehouseId = 1;
        }
        $location = trim((string)($latestRow['location'] ?? ''));
        if ($location === '') {
            $location = 'API refresh';
        }

        if (!$latestRow) {
            $this->insertApiRefreshMovement(
                $productId,
                $sku,
                $itemCode,
                $size,
                $color,
                $warehouseId,
                $location,
                'OPENING_STOCK',
                0,
                0,
                'API_REFRESH_BASELINE',
                'Opening stock baseline created during API refresh'
            );
            $currentStock = 0;
        } else {
            $currentStock = (int)($latestRow['running_stock'] ?? 0);
        }

        $targetDelta = $apiLocalStock - $currentStock;
        if ($targetDelta !== 0) {
            $this->insertApiRefreshMovement(
                $productId,
                $sku,
                $itemCode,
                $size,
                $color,
                $warehouseId,
                $location,
                ($targetDelta > 0 ? 'IN' : 'OUT'),
                abs($targetDelta),
                $apiLocalStock,
                'API_REFRESH',
                'Stock adjusted from API refresh'
            );
        }
    }

    private function insertApiRefreshMovement(
        int $productId,
        string $sku,
        string $itemCode,
        string $size,
        string $color,
        int $warehouseId,
        string $location,
        string $movementType,
        int $quantity,
        int $runningStock,
        string $refType,
        string $reason
    ): void {
        $itemCodeCol = $this->resolveVpStockMovementsItemCodeColumn();
        $safeItemCol = '`' . str_replace('`', '``', $itemCodeCol) . '`';
        $stmt = $this->db->prepare("INSERT INTO vp_stock_movements
            (product_id, sku, {$safeItemCol}, size, color, warehouse_id, location, movement_type, quantity, running_stock, ref_type, ref_id, reason, update_by_user)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return;
        }
        $refId = 'api_refresh:' . date('YmdHis');
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $stmt->bind_param(
            'issssissiisssi',
            $productId,
            $sku,
            $itemCode,
            $size,
            $color,
            $warehouseId,
            $location,
            $movementType,
            $quantity,
            $runningStock,
            $refType,
            $refId,
            $reason,
            $userId
        );
        $stmt->execute();
        $stmt->close();
    }
    /**
     * Resolve a catalog row by item code and variant dimensions. Supports:
     * (1) item code only — empty size & color; (2) color-only; (3) size-only; (4) size + color.
     */
    public function findByItemCodeSizeColor($code, $size, $color)
    {
        $code = trim((string)$code);
        $size = trim((string)$size);
        $color = trim((string)$color);
        if ($code === '') {
            return null;
        }

        $hit = $this->findByItemCodeSizeColorExactSql($code, $size, $color);
        if ($hit) {
            return $hit;
        }

        $rows = $this->fetchAllProductsByItemCode($code);
        if ($rows === []) {
            return null;
        }

        $hit = $this->findInRowsByNormalizedSizeColor($rows, $size, $color);
        if ($hit) {
            return $hit;
        }

        return $this->disambiguateVariantRowsByUpload($rows, $size, $color);
    }

    /**
     * Match NULL/whitespace-only DB values with empty upload cells (size = '' does not match NULL in SQL).
     */
    private function findByItemCodeSizeColorExactSql(string $code, string $size, string $color): ?array
    {
        $sql = "SELECT * FROM vp_products WHERE item_code = ?
            AND COALESCE(NULLIF(TRIM(size), ''), '') = COALESCE(NULLIF(TRIM(?), ''), '')
            AND COALESCE(NULLIF(TRIM(color), ''), '') = COALESCE(NULLIF(TRIM(?), ''), '')
            LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('sss', $code, $size, $color);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $res ?: null;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function fetchAllProductsByItemCode(string $code): array
    {
        $stmt = $this->db->prepare('SELECT * FROM vp_products WHERE item_code = ?');
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return is_array($rows) ? $rows : [];
    }

    private function isVariantDimensionBlank($value): bool
    {
        return $this->normalizeVariantDimensionForMatch($value) === '';
    }

    /**
     * Collapses case and separators so "natural-brown" matches "natural brown".
     */
    private function normalizeVariantDimensionForMatch($value): string
    {
        $s = trim((string)$value);
        if ($s === '') {
            return '';
        }
        $s = strtolower($s);
        $collapsed = preg_replace('/[\s\-_]+/u', '', $s);

        return is_string($collapsed) ? $collapsed : $s;
    }

    /**
     * @param list<array<string,mixed>> $rows
     */
    private function findInRowsByNormalizedSizeColor(array $rows, string $size, string $color): ?array
    {
        $ns = $this->normalizeVariantDimensionForMatch($size);
        $nc = $this->normalizeVariantDimensionForMatch($color);
        $hits = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rs = $this->normalizeVariantDimensionForMatch($row['size'] ?? '');
            $rc = $this->normalizeVariantDimensionForMatch($row['color'] ?? '');
            if ($rs === $ns && $rc === $nc) {
                $hits[] = $row;
            }
        }

        return count($hits) === 1 ? $hits[0] : null;
    }

    /**
     * When exact + full normalized match fail, apply rules per upload shape (single row or unique partial match).
     *
     * @param list<array<string,mixed>> $rows
     */
    private function disambiguateVariantRowsByUpload(array $rows, string $size, string $color): ?array
    {
        $sizeBlank = ($size === '');
        $colorBlank = ($color === '');

        // (1) Item code only — no size, no color in file
        if ($sizeBlank && $colorBlank) {
            if (count($rows) === 1) {
                return $rows[0];
            }
            $bothBlank = [];
            foreach ($rows as $r) {
                if (!is_array($r)) {
                    continue;
                }
                if ($this->isVariantDimensionBlank($r['size'] ?? '') && $this->isVariantDimensionBlank($r['color'] ?? '')) {
                    $bothBlank[] = $r;
                }
            }

            return count($bothBlank) === 1 ? $bothBlank[0] : null;
        }

        // (2) Color-only variant in file — size empty, color set
        if ($sizeBlank && !$colorBlank) {
            $nc = $this->normalizeVariantDimensionForMatch($color);
            $cands = [];
            foreach ($rows as $r) {
                if (!is_array($r)) {
                    continue;
                }
                if (!$this->isVariantDimensionBlank($r['size'] ?? '')) {
                    continue;
                }
                if ($this->normalizeVariantDimensionForMatch($r['color'] ?? '') !== $nc) {
                    continue;
                }
                $cands[] = $r;
            }

            return count($cands) === 1 ? $cands[0] : null;
        }

        // (3) Size-only variant in file — size set, color empty
        if (!$sizeBlank && $colorBlank) {
            $ns = $this->normalizeVariantDimensionForMatch($size);
            $cands = [];
            foreach ($rows as $r) {
                if (!is_array($r)) {
                    continue;
                }
                if ($this->normalizeVariantDimensionForMatch($r['size'] ?? '') !== $ns) {
                    continue;
                }
                if (!$this->isVariantDimensionBlank($r['color'] ?? '')) {
                    continue;
                }
                $cands[] = $r;
            }

            return count($cands) === 1 ? $cands[0] : null;
        }

        // (4) Size + color in file: full normalized match is handled above; no further disambiguation
        return null;
    }

    /**
     * Batch-load vp_products for stock transfer GRN list rows: item_group, label_product_id,
     * label_default_qty. Avoids N+1 findByItemCodeSizeColor / getProductByskuExact calls.
     *
     * @param list<array<string,mixed>> $grnRows
     */
    public function enrichStockTransferGrnRowsForList(array &$grnRows): void
    {
        if ($grnRows === []) {
            return;
        }

        $skus = [];
        $itemCodes = [];
        foreach ($grnRows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $sku = trim((string)($r['sku'] ?? ''));
            if ($sku !== '') {
                $skus[$sku] = true;
            }
            $ic = trim((string)($r['item_code'] ?? ''));
            if ($ic !== '') {
                $itemCodes[$ic] = true;
            }
        }

        $bySku = [];
        foreach ($this->fetchVpProductsWhereInStringColumn('sku', array_keys($skus)) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $k = trim((string)($row['sku'] ?? ''));
            if ($k === '') {
                continue;
            }
            if (!isset($bySku[$k])) {
                $bySku[$k] = $row;
            }
        }

        $byItemCode = [];
        foreach ($this->fetchVpProductsWhereInStringColumn('item_code', array_keys($itemCodes)) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $k = trim((string)($row['item_code'] ?? ''));
            if ($k === '') {
                continue;
            }
            if (!isset($byItemCode[$k])) {
                $byItemCode[$k] = [];
            }
            $byItemCode[$k][] = $row;
        }

        foreach ($grnRows as &$grnRow) {
            if (!is_array($grnRow)) {
                continue;
            }
            $sku = trim((string)($grnRow['sku'] ?? ''));
            $ic = trim((string)($grnRow['item_code'] ?? ''));
            $size = (string)($grnRow['size'] ?? '');
            $color = (string)($grnRow['color'] ?? '');

            $itemGroup = '';
            if ($sku !== '' && isset($bySku[$sku])) {
                $itemGroup = (string)($bySku[$sku]['groupname'] ?? '');
            }
            if ($itemGroup === '' && $ic !== '' && !empty($byItemCode[$ic])) {
                $itemGroup = (string)($byItemCode[$ic][0]['groupname'] ?? '');
            }
            $grnRow['item_group'] = $itemGroup;

            $resolved = null;
            if ($ic !== '' && !empty($byItemCode[$ic])) {
                $resolved = $this->resolveProductFromPreloadedItemCodeRows($byItemCode[$ic], $size, $color);
            }
            if (!$resolved && $sku !== '' && isset($bySku[$sku])) {
                $resolved = $bySku[$sku];
            }

            $grnRow['label_product_id'] = $resolved && !empty($resolved['id']) ? (int)$resolved['id'] : 0;
            $recv = (int)($grnRow['qty_received'] ?? 0);
            $acc = (int)($grnRow['qty_acceptable'] ?? 0);
            $base = max($recv, $acc);
            $grnRow['label_default_qty'] = $base > 0 ? min(99, $base) : 1;
        }
        unset($grnRow);
    }

    /**
     * @param list<string> $values
     * @return list<array<string,mixed>>
     */
    private function fetchVpProductsWhereInStringColumn(string $column, array $values): array
    {
        if ($values === []) {
            return [];
        }
        if ($column !== 'sku' && $column !== 'item_code') {
            return [];
        }

        $out = [];
        $chunkSize = 400;
        foreach (array_chunk($values, $chunkSize) as $chunk) {
            if ($chunk === []) {
                continue;
            }
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $sql = "SELECT * FROM vp_products WHERE `{$column}` IN ({$placeholders})";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                continue;
            }
            $types = str_repeat('s', count($chunk));
            $stmt->bind_param($types, ...$chunk);
            if (!$this->executeVpProductsStmt($stmt)) {
                $stmt->close();
                continue;
            }
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $out[] = $row;
                }
            }
            $stmt->close();
        }

        return $out;
    }

    /**
     * Same resolution rules as findByItemCodeSizeColor, using rows already loaded for one item_code.
     *
     * @param list<array<string,mixed>> $rows
     */
    private function resolveProductFromPreloadedItemCodeRows(array $rows, string $size, string $color): ?array
    {
        if ($rows === []) {
            return null;
        }
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if ($this->rowMatchesItemCodeSizeColorExact($row, $size, $color)) {
                return $row;
            }
        }
        $hit = $this->findInRowsByNormalizedSizeColor($rows, $size, $color);
        if ($hit) {
            return $hit;
        }

        return $this->disambiguateVariantRowsByUpload($rows, $size, $color);
    }

    /**
     * Mirrors findByItemCodeSizeColorExactSql (COALESCE/NULLIF/TRIM) in PHP for in-memory rows.
     */
    private function rowMatchesItemCodeSizeColorExact(array $row, string $size, string $color): bool
    {
        $rs = $this->vpProductVariantCoalescedTrim($row['size'] ?? '');
        $rc = $this->vpProductVariantCoalescedTrim($row['color'] ?? '');
        $us = $this->vpProductVariantCoalescedTrim($size);
        $uc = $this->vpProductVariantCoalescedTrim($color);

        return $rs === $us && $rc === $uc;
    }

    private function vpProductVariantCoalescedTrim($value): string
    {
        $t = trim((string)$value);

        return $t === '' ? '' : $t;
    }

    public function findBySku($sku)
    {
        $sql = "SELECT * FROM vp_products WHERE sku = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res;
    }

    /**
     * Resolve vp_products.id for an invoice line using vp_orders (order_number + item_code).
     */
    public function getProductIdForInvoiceLine(string $orderNumber, string $itemCode): int
    {
        $orderNumber = trim($orderNumber);
        $itemCode = trim($itemCode);
        if ($orderNumber === '' || $itemCode === '') {
            return 0;
        }
        $sql = "SELECT item_code, sku, size, color FROM vp_orders WHERE order_number = ? AND item_code = ? ORDER BY id ASC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('ss', $orderNumber, $itemCode);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return 0;
        }
        $ic = trim((string)($row['item_code'] ?? $itemCode));
        $size = isset($row['size']) ? (string)$row['size'] : '';
        $color = isset($row['color']) ? (string)$row['color'] : '';
        $match = $this->findByItemCodeSizeColor($ic, $size, $color);
        if (!empty($match['id'])) {
            return (int)$match['id'];
        }
        $sku = trim((string)($row['sku'] ?? ''));
        if ($sku !== '') {
            $bySku = $this->findBySku($sku);
            if (!empty($bySku['id']) && strcasecmp((string)($bySku['item_code'] ?? ''), $ic) === 0) {
                return (int)$bySku['id'];
            }
            if (!empty($bySku['id'])) {
                return (int)$bySku['id'];
            }
        }
        return 0;
    }

    /**
     * Autocomplete by SKU only (partial match).
     *
     * @return list<array<string,mixed>>
     */
    public function searchProductsBySkuLike($query)
    {
        $q = '%' . $query . '%';
        $sql = "SELECT id, sku, item_code, title, size, color, image, local_stock
                FROM vp_products WHERE sku LIKE ?
                  AND LOWER(TRIM(IFNULL(item_level, ''))) <> 'parent'
                ORDER BY sku ASC LIMIT 25";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $q);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        return $rows;
    }

    public function searchProductsBySkuOrItemCode($query)
    {
        $q = '%' . $query . '%';
        $sql = "SELECT * FROM vp_products WHERE (sku LIKE ? OR item_code LIKE ?)
                  AND LOWER(TRIM(IFNULL(item_level, ''))) <> 'parent'
                ORDER BY item_code ASC LIMIT 20";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ss', $q, $q);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        return $rows;
    }

    public function createProduct($data)
    {
        $data = $this->castApiProductLikeForm($data);
        if (($data['date_first_added'] ?? '') === '') {
            $data['date_first_added'] = null;
        }
        $sql = "INSERT INTO vp_products (item_code, sku, size, color, title, image, local_stock, itemprice, finalprice,  groupname, material, cost_price, gst, hsn, description, asin, upc, location, fba_in, fba_us, leadtime, instock_leadtime, permanently_available, numsold, numsold_india, numsold_global, lastsold, vendor, shippingfee, sourcingfee, price, price_india, price_india_suggested, mrp_india, permanent_discount, discount_global, discount_india, product_weight, product_weight_unit, prod_height, prod_width, prod_length, length_unit, created_on, updated_at, category, itemtype, snippet_description, india_net_qty, keywords, usblock, indiablock, hscode, date_first_added, search_term, search_category, long_description, long_description_india, aplus_content_ids, item_level, marketplace_vendor, colormap, flex_status, vendor_us, today_global, today_india, topurchase, backorder_percent, backorder_weeks, cp, usd, amazon_sold, amazon_leadtime, amazon_itemcode_alias, youtube_links, sketchfab_links, dimensions)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'ssssssdddsssisssssssssssssisddddddddddsiiissssssisiissssssssssssssiiiddiissss',
            $data['item_code'],
            $data['sku'],
            $data['size'],
            $data['color'],
            $data['title'],
            $data['image'],
            $data['local_stock'],
            $data['itemprice'],
            $data['finalprice'],
            $data['groupname'],
            $data['material'],
            $data['cost_price'],
            $data['gst'],
            $data['hsn'],
            $data['description'],
            $data['asin'],
            $data['upc'],
            $data['location'],
            $data['fba_in'],
            $data['fba_us'],
            $data['leadtime'],
            $data['instock_leadtime'],
            $data['permanently_available'],
            $data['numsold'],
            $data['numsold_india'],
            $data['numsold_global'],
            $data['lastsold'],
            $data['vendor'],
            $data['shippingfee'],
            $data['sourcingfee'],
            $data['price'],
            $data['price_india'],
            $data['price_india_suggested'],
            $data['mrp_india'],
            $data['permanent_discount'],
            $data['discount_global'],
            $data['discount_india'],
            $data['product_weight'],
            $data['product_weight_unit'],
            $data['prod_height'],
            $data['prod_width'],
            $data['prod_length'],
            $data['length_unit'],
            $data['created_at'],
            $data['updated_at'],
            $data['category'],
            $data['itemtype'],
            $data['snippet_description'],
            $data['india_net_qty'],
            $data['keywords'],
            $data['usblock'],
            $data['indiablock'],
            $data['hscode'],
            $data['date_first_added'],
            $data['search_term'],
            $data['search_category'],
            $data['long_description'],
            $data['long_description_india'],
            $data['aplus_content_ids'],
            $data['item_level'],
            $data['marketplace_vendor'],
            $data['colormap'],
            $data['flex_status'],
            $data['vendor_us'],
            $data['today_global'],
            $data['today_india'],
            $data['topurchase'],
            $data['backorder_percent'],
            $data['backorder_weeks'],
            $data['cp'],
            $data['usd'],
            $data['amazon_sold'],
            $data['amazon_leadtime'],
            $data['amazon_itemcode_alias'],
            $data['youtube_links'],
            $data['sketchfab_links'],
            $data['dimensions'],
        );
        if ($this->executeVpProductsStmt($stmt)) {
            return $this->db->insert_id;
        }
        return false;
    }
    public function updateProduct($id, $data)
    {
        $data['leadtime'] = $this->normalizeIntValue($data['leadtime'] ?? null, 0);
        $data['instock_leadtime'] = $this->normalizeIntValue($data['instock_leadtime'] ?? null, 0);
        $sql = "UPDATE vp_products SET title=?, image=?, local_stock=?, itemprice=?, finalprice=?,  groupname=?, material=?, cost_price=?, gst=?, hsn=?, description=?, asin=?, upc=?, location=?, fba_in=?, fba_us=?, leadtime=?, instock_leadtime=?, permanently_available=?, numsold=?, numsold_india=?, numsold_global=?, lastsold=?, vendor=?, shippingfee=?, sourcingfee=?, price=?, price_india=?, price_india_suggested=?, mrp_india=?, permanent_discount=?, discount_global=?, discount_india=?, product_weight=?, product_weight_unit=?, prod_height=?, prod_width=?, prod_length=?, length_unit=?, updated_at=? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'ssiddssddsssssiissiiiiisddddddddddsdddssi',
            $data['title'],
            $data['image'],
            $data['local_stock'],
            $data['itemprice'],
            $data['finalprice'],
            $data['groupname'],
            $data['material'],
            $data['cost_price'],
            $data['gst'],
            $data['hsn'],
            $data['description'],
            $data['asin'],
            $data['upc'],
            $data['location'],
            $data['fba_in'],
            $data['fba_us'],
            $data['leadtime'],
            $data['instock_leadtime'],
            $data['permanently_available'],
            $data['numsold'],
            $data['numsold_india'],
            $data['numsold_global'],
            $data['lastsold'],
            $data['vendor'],
            $data['shippingfee'],
            $data['sourcingfee'],
            $data['price'],
            $data['price_india'],
            $data['price_india_suggested'],
            $data['mrp_india'],
            $data['permanent_discount'],
            $data['discount_global'],
            $data['discount_india'],
            $data['product_weight'],
            $data['product_weight_unit'],
            $data['prod_height'],
            $data['prod_width'],
            $data['prod_length'],
            $data['length_unit'],
            $data['updated_at'],
            $id
        );
        return $this->executeVpProductsStmt($stmt);
    }
    public function getProductByItemCode($item_code, bool $excludeParentFromCatalog = false)
    {
        $notParent = $excludeParentFromCatalog ? " AND LOWER(TRIM(IFNULL(item_level, ''))) <> 'parent' " : '';
        $sql = "SELECT * FROM vp_products WHERE item_code = ? {$notParent}";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $item_code);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            // If only one row is found, return an associative array for backward compatibility,
            // otherwise return all matching rows as an array of associative arrays.
            /*if ($result->num_rows === 1) {
                return $result->fetch_assoc();
            }*/
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return null;
    }
    private function resolveLocalVendorIdByExoticVendorId(string $exoticVendorId): ?int
    {
        $exoticVendorId = trim($exoticVendorId);
        if ($exoticVendorId === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT id FROM vp_vendors
             WHERE TRIM(COALESCE(vendor_id, \'\')) = ?
                OR TRIM(COALESCE(vendor_code, \'\')) = ?
             ORDER BY id ASC
             LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('ss', $exoticVendorId, $exoticVendorId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        return $row ? (int) $row['id'] : null;
    }

    /**
     * Upsert product_vendor_map from Exotic discrete_vendor_list / discrete_vendors.
     *
     * @return array{synced:int,skipped:int,missing_local_vendor:list<int>}
     */
    public function syncProductVendorMapFromApiItem(string $itemCode, array $apiItem): array
    {
        $itemCode = trim($itemCode);
        if ($itemCode === '') {
            return ['synced' => 0, 'skipped' => 0, 'missing_local_vendor' => []];
        }

        $entries = self::extractDiscreteVendorEntriesFromApiItem($apiItem);
        if ($entries === []) {
            return ['synced' => 0, 'skipped' => 0, 'missing_local_vendor' => []];
        }

        $synced = 0;
        $skipped = 0;
        $missingLocalVendor = [];

        foreach ($entries as $entry) {
            $exoticVendorId = (int) ($entry['vendor_id'] ?? 0);
            $priority = max(1, (int) ($entry['priority'] ?? 1));
            if ($exoticVendorId <= 0) {
                $skipped++;
                continue;
            }

            $localVendorId = $this->resolveLocalVendorIdByExoticVendorId((string) $exoticVendorId);
            if ($localVendorId === null) {
                $missingLocalVendor[] = $exoticVendorId;
                $skipped++;
                continue;
            }

            if ($this->upsertProductVendorMapEntry($itemCode, $localVendorId, (string) $exoticVendorId, $priority)) {
                $synced++;
            } else {
                $skipped++;
            }
        }

        return [
            'synced' => $synced,
            'skipped' => $skipped,
            'missing_local_vendor' => $missingLocalVendor,
        ];
    }

    public function upsertProductVendorMapEntry(string $itemCode, int $localVendorId, string $exoticVendorCode, int $priority): bool
    {
        $itemCode = trim($itemCode);
        $exoticVendorCode = trim($exoticVendorCode);
        if ($itemCode === '' || $localVendorId <= 0) {
            return false;
        }
        if ($exoticVendorCode === '') {
            $exoticVendorCode = (string) $localVendorId;
        }

        $now = date('Y-m-d H:i:s');
        $priority = max(1, $priority);

        $stmt = $this->db->prepare('SELECT id FROM product_vendor_map WHERE item_code = ? AND vendor_id = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('si', $itemCode, $localVendorId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            $id = (int) $row['id'];
            $upd = $this->db->prepare(
                'UPDATE product_vendor_map SET vendor_code = ?, priority = ?, updated_at = ? WHERE id = ?'
            );
            if (!$upd) {
                return false;
            }
            $upd->bind_param('sisi', $exoticVendorCode, $priority, $now, $id);

            return $upd->execute();
        }

        $ins = $this->db->prepare(
            'INSERT INTO product_vendor_map (item_code, vendor_id, vendor_code, priority, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$ins) {
            return false;
        }
        $ins->bind_param('sisiss', $itemCode, $localVendorId, $exoticVendorCode, $priority, $now, $now);

        return $ins->execute();
    }

    public function getVendorByItemCode($item_code)
    {
        $sql = "SELECT pvm.id as pvm_id, pvm.*, vv.* FROM product_vendor_map pvm 
            JOIN vp_vendors vv ON pvm.vendor_id = vv.id 
            WHERE pvm.item_code = ?  order by pvm.priority ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $item_code);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return null;
    }

    /**
     * Save or update product vendor mapping
     * @param string $item_code
     * @param int $vendor_id
     * @param string $vendor_code
     * @return bool
     */
    public function saveProductVendor($item_code, $vendor_id, $vendor_code = '')
    {
        $now = date('Y-m-d H:i:s');
        // check existing
        $sql = "SELECT id FROM product_vendor_map WHERE item_code = ? AND vendor_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('si', $item_code, $vendor_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            /*$row = $res->fetch_assoc();
            $id = (int)$row['id'];
            $sql = "UPDATE product_vendor_map SET vendor_id = ?, vendor_code = ?, updated_at = ? WHERE id = ?";
            $stmt2 = $this->db->prepare($sql);
            if (!$stmt2) return false;
            $stmt2->bind_param('issi', $vendor_id, $vendor_code, $now, $id);
            return $stmt2->execute();*/
            return true; // already exists
        } else {
            $sql = "INSERT INTO product_vendor_map (item_code, vendor_id, vendor_code, created_at, updated_at) VALUES (?, ?, ?, ?, ?)";
            $stmt2 = $this->db->prepare($sql);
            if (!$stmt2) return false;
            $stmt2->bind_param('sisss', $item_code, $vendor_id, $vendor_code, $now, $now);
            if ($stmt2->execute()) {
                return $vendor_id;
            }
            return false;
        }
    }
    public function deleteProductVendor($id)
    {

        $sql = "DELETE FROM product_vendor_map WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
    /**
     * Update only vendor priority
     * @param int $id
     * @param int $priority
     * @return array
     */
    public function updatePriority($id, $priority)
    {
        //select existing priority
        $sql = "SELECT * FROM product_vendor_map WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $sql2 = "SELECT * FROM product_vendor_map WHERE item_code = ? AND priority = ?";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->bind_param('si', $row['item_code'], $priority);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($result2 && $result2->num_rows > 0) {
                //another vendor has same priority, reset that vendor priority to 0
                $row2 = $result2->fetch_assoc();
                $sql3 = "UPDATE product_vendor_map SET priority = 0 WHERE id = ?";
                $stmt3 = $this->db->prepare($sql3);
                if ($stmt3 === false) return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
                $vid = (int)$row2['id'];
                $stmt3->bind_param('i', $vid);
                $stmt3->execute();
            }
        }

        // Update the current vendor's priority
        $sql = "UPDATE product_vendor_map SET priority = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        $p = (int)$priority;
        $i = (int)$id;
        $stmt->bind_param('ii', $p, $i);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Priority updated successfully.'];
        }
        return ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
    }

    public function createPurchaseList($data)
    {
        $sql = "
            INSERT INTO purchase_list (
                user_id,
                product_id,
                order_id,
                sku,
                date_added,
                date_purchased,
                status,
                quantity,
                edit_by,
                updated_at,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Prepare failed: ' . $this->db->error
            ];
        }

        $now = date('Y-m-d H:i:s');

        // ✅ bind types: i i i s s s s i i s s
        $types = "iiissssiiss";

        $stmt->bind_param(
            $types,
            $data['user_id'],
            $data['product_id'],
            $data['order_id'],
            $data['sku'],
            $now,                     // date_added
            $data['date_purchased'],  // nullable
            $data['status'],
            $data['quantity'],
            $data['edit_by'],
            $now,
            $now
        );

        if (!$stmt->execute()) {
            return [
                'success' => false,
                'message' => $stmt->error
            ];
        }

        // ✅ INSERT SUCCESS
        $purchase_list_id = (int)$this->db->insert_id;

        // ✅ Logged-in user info (prefer session, fallback to edit_by)
        $loggedUserId = (int)($_SESSION['user']['id'] ?? $data['edit_by'] ?? 0);
        $loggedUserName = $_SESSION['user']['name'] ?? 'Unknown';

        // ✅ Create vp_order_status_log entry
        // status column should remain human-readable
        $statusText = "Purchase CREATED (SKU : " . $data['sku'] . ") Qty: " . (int)$data['quantity'];

        $this->createOrderStatusLog(
            (int)$data['order_id'],         // order_id (required by vp_order_status_log)
            $statusText,                    // status text
            $loggedUserId,                  // changed_by
            $loggedUserName,                // saved inside api_response JSON
            (int)$data['quantity'],         // qty_changed saved inside api_response JSON
            [
                'action' => 'CREATED',
                'purchase_list_id' => $purchase_list_id,
                'product_id' => (int)$data['product_id'],
                'user_id' => (int)$data['user_id'],
                'sku' => $data['sku'],
                'status' => $data['status'],
                'new_qty' => (int)$data['quantity'],
                'date_added' => $now,
            ]
        );

        return [
            'success' => true,
            'purchase_list_id' => $purchase_list_id
        ];
    }


    public function getPurchaseListByUser($user_id, $limit = 100, $offset = 0, $filters = [])
    {
        $sql = "SELECT * FROM purchase_list WHERE user_id = ? AND status = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('isii', $user_id, $filters['status'], $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    /*public function getPurchaseList($limit = 100, $offset = 0, $filters = [])
    {
        // Join with vp_products to allow filtering by product category/groupname and by user
        $where = [];
        $params = [];
        $types = '';

        if (!empty($filters['user_id'])) {
            $where[] = 'pl.user_id = ?';
            $params[] = (int)$filters['user_id'];
            $types .= 'i';
        }
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where[] = 'pl.status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $where[] = 'p.groupname = ?';
            $params[] = $filters['category'];
            $types .= 's';
        }
        if (!empty($filters['search'])) {
            $where[] = '(p.item_code LIKE ? OR p.title LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        if (!empty($filters['added_by'])) {
            $where[] = 'pl.edit_by = ?';
            $params[] = (int)$filters['added_by'];
            $types .= 'i';
        }
        if (!empty($filters['asigned_to'])) {
            $where[] = 'pl.user_id = ?';
            $params[] = (int)$filters['asigned_to'];
            $types .= 'i';
        }

        $dateColumn = (!empty($filters['date_type']) && $filters['date_type'] === 'purchased')
            ? 'pl.date_purchased'
            : 'pl.date_added';

        if (!empty($filters['date_from'])) {
            $where[]  = "$dateColumn >= ?";
            $params[] = $filters['date_from'];
            $types   .= 's';
        }

        if (!empty($filters['date_to'])) {
            $where[]  = "$dateColumn <= ?";
            $params[] = $filters['date_to'];
            $types   .= 's';
        }

        //print_r($filters);

        $orderBy = '';
        if (!empty($filters['sort_by'])) {
            $orderBy = " ORDER BY pl.date_added $filters[sort_by]";
        } else {
            $orderBy = " ORDER BY pl.date_added DESC";
        }


        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }
        //echo $whereSql."**********************";
        $sql = "SELECT pl.id,pl.user_id,pl.product_id,pl.order_id,pl.sku,pl.date_added,pl.date_purchased,pl.status,sum(pl.quantity) as quantity, pl.remarks,pl.edit_by,pl.updated_at,pl.created_at, p.item_code, p.title, p.groupname AS category, p.cost_price, p.image FROM purchase_list pl LEFT JOIN vp_products p ON pl.product_id = p.id $whereSql GROUP BY pl.product_id, p.item_code, p.title, p.groupname, p.cost_price, p.image $orderBy LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        // bind dynamic params followed by limit and offset
        if (!empty($params)) {
            $types_all = $types . 'ii';
            $bindParams = [$types_all];
            foreach ($params as $k => $v) {
                $bindParams[] = &$params[$k];
            }
            $bindParams[] = &$limit;
            $bindParams[] = &$offset;
            // convert to references for call_user_func_array
            $refs = [];
            foreach ($bindParams as $key => $val) {
                $refs[$key] = &$bindParams[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $refs);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }*/

    public function getPurchaseList($limit = 100, $offset = 0, $filters = [], $listType = 'null')
    {
        // -----------------------------
        // Build WHERE for purchase_list (subqueries + latest-row join)
        // NOTE: no alias here because we reuse it in multiple places.
        // -----------------------------
        $plWhere  = [];
        $plParams = [];
        $plTypes  = '';

        if (!empty($filters['user_id'])) {
            $plWhere[]  = 'user_id = ?';
            $plParams[] = (int)$filters['user_id'];
            $plTypes   .= 'i';
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $plWhere[]  = 'status = ?';
            $plParams[] = $filters['status'];
            $plTypes   .= 's';
        }

        if (!empty($filters['added_by'])) {
            $plWhere[]  = 'edit_by = ?';
            $plParams[] = (int)$filters['added_by'];
            $plTypes   .= 'i';
        }

        if (!empty($filters['assigned_to'])) {
            $plWhere[]  = 'user_id = ?';
            $plParams[] = (int)$filters['assigned_to'];
            $plTypes   .= 'i';
        }

        $dateColumn = (!empty($filters['date_type']) && $filters['date_type'] === 'purchased')
            ? 'date_purchased'
            : 'date_added';

        if (!empty($filters['date_from'])) {
            $plWhere[]  = "$dateColumn >= ?";
            $plParams[] = $filters['date_from'];
            $plTypes   .= 's';
        }

        if (!empty($filters['date_to'])) {
            $plWhere[]  = "$dateColumn <= ?";
            $plParams[] = $filters['date_to'];
            $plTypes   .= 's';
        }

        $plWhereSql = '';
        if (!empty($plWhere)) {
            $plWhereSql = ' WHERE ' . implode(' AND ', $plWhere);
        }

        // -----------------------------
        // Build OUTER filters for vp_products (category/search)
        // -----------------------------
        $outerWhere  = [];
        $outerParams = [];
        $outerTypes  = '';

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $outerWhere[]  = 'p.groupname = ?';
            $outerParams[] = $filters['category'];
            $outerTypes   .= 's';
        }

        if (!empty($filters['search'])) {
            $outerWhere[]  = '(p.item_code LIKE ? OR p.title LIKE ? OR o.order_number LIKE ?)';
            $searchTerm    = '%' . $filters['search'] . '%';
            $outerParams[] = $searchTerm;
            $outerParams[] = $searchTerm;
            $outerParams[] = $searchTerm;   // ✅ MISSING ONE
            $outerTypes   .= 'sss';
        }

        $outerWhereSql = '';
        if (!empty($outerWhere)) {
            $outerWhereSql = ' WHERE ' . implode(' AND ', $outerWhere);
        }

        // -----------------------------
        // ORDER BY (use pl_latest alias, not pl)
        // -----------------------------
        $sortDir = 'DESC';
        if (!empty($filters['sort_by'])) {
            $sortDir = (strtoupper($filters['sort_by']) === 'ASC') ? 'ASC' : 'DESC';
        }

        if ($listType == 'master') {
            $orderBy = "
                ORDER BY 
                FIELD(pl_latest.status, 'pending', 'partially_purchased', 'purchased', 'item_not_available', 'alternate','ordered') ASC,
                pl_latest.date_added $sortDir
            ";
        } else {
            $orderBy = " ORDER BY pl_latest.date_added $sortDir";
        }


        // -----------------------------
        // SQL: total quantity per product + latest row per product (by updated_at)
        // -----------------------------
        $sql = "
            SELECT
            pl_latest.id,
            pl_latest.user_id,
            pl_latest.product_id,
            pl_latest.order_id,
            pl_latest.sku,
            pl_latest.date_added,
            pl_latest.date_purchased,
            pl_latest.status,
            qty.quantity,
            pl_latest.remarks,
            pl_latest.edit_by,
            pl_latest.updated_at,
            pl_latest.created_at,
            pl_latest.expected_time_of_delivery,
            p.item_code,
            p.title,
            p.groupname AS category,
            p.cost_price,
            p.image,
            p.product_weight,
            p.prod_height,
            p.prod_width,
            p.prod_length,
            p.vendor,
            o.order_number
            FROM
            (
            SELECT product_id, SUM(quantity) AS quantity
            FROM purchase_list
            $plWhereSql
            GROUP BY product_id
            ) qty
            JOIN
            (
            SELECT pl.*
            FROM purchase_list pl
            JOIN (
                SELECT product_id, MAX(updated_at) AS max_updated_at
                FROM purchase_list
                $plWhereSql
                GROUP BY product_id
            ) latest
                ON latest.product_id = pl.product_id
            AND latest.max_updated_at = pl.updated_at
            $plWhereSql
            ) pl_latest
            ON pl_latest.product_id = qty.product_id
            LEFT JOIN vp_products p
            ON p.id = pl_latest.product_id
            LEFT JOIN vp_orders as o ON o.id = pl_latest.order_id
            $outerWhereSql
            $orderBy
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        // -----------------------------
        // Bind params (IMPORTANT ORDER)
        // We used $plWhereSql 3 times and $outerWhereSql once:
        // 1) qty subquery:        $plParams
        // 2) latest subquery:     $plParams
        // 3) pl_latest filter:    $plParams
        // 4) outer product filter $outerParams
        // 5) limit/offset
        // -----------------------------
        $bindTypes  = $plTypes . $plTypes . $plTypes . $outerTypes . 'ii';
        $bindValues = array_merge($plParams, $plParams, $plParams, $outerParams, [(int)$limit, (int)$offset]);

        // mysqli bind_param requires references
        $refs   = [];
        $refs[] = &$bindTypes;
        foreach ($bindValues as $k => $v) {
            $refs[] = &$bindValues[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    public function countPurchaseList($filters = [])
    {
        // We want the count of DISTINCT product_id groups after applying filters.
        // With ONLY_FULL_GROUP_BY, do NOT "COUNT(*) ... GROUP BY product_id" and then fetch one row.
        // Instead: count distinct product_id using a subquery (or COUNT(DISTINCT ...)).

        // -----------------------------
        // Build WHERE for purchase_list fields
        // -----------------------------
        $plWhere  = [];
        $plParams = [];
        $plTypes  = '';

        if (!empty($filters['user_id'])) {
            $plWhere[]  = 'pl.user_id = ?';
            $plParams[] = (int)$filters['user_id'];
            $plTypes   .= 'i';
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $plWhere[]  = 'pl.status = ?';
            $plParams[] = $filters['status'];
            $plTypes   .= 's';
        }

        if (!empty($filters['added_by'])) {
            $plWhere[]  = 'pl.edit_by = ?';
            $plParams[] = (int)$filters['added_by'];
            $plTypes   .= 'i';
        }

        if (!empty($filters['asigned_to'])) {
            $plWhere[]  = 'pl.user_id = ?';
            $plParams[] = (int)$filters['asigned_to'];
            $plTypes   .= 'i';
        }

        $dateColumn = (!empty($filters['date_type']) && $filters['date_type'] === 'purchased')
            ? 'pl.date_purchased'
            : 'pl.date_added';

        if (!empty($filters['date_from'])) {
            $plWhere[]  = "$dateColumn >= ?";
            $plParams[] = $filters['date_from'];
            $plTypes   .= 's';
        }

        if (!empty($filters['date_to'])) {
            $plWhere[]  = "$dateColumn <= ?";
            $plParams[] = $filters['date_to'];
            $plTypes   .= 's';
        }

        $plWhereSql = '';
        if (!empty($plWhere)) {
            $plWhereSql = ' WHERE ' . implode(' AND ', $plWhere);
        }

        // -----------------------------
        // Build WHERE for vp_products fields (category/search)
        // -----------------------------
        $pWhere  = [];
        $pParams = [];
        $pTypes  = '';

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $pWhere[]  = 'p.groupname = ?';
            $pParams[] = $filters['category'];
            $pTypes   .= 's';
        }

        if (!empty($filters['search'])) {
            $pWhere[]  = '(p.item_code LIKE ? OR p.title LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $pParams[] = $searchTerm;
            $pParams[] = $searchTerm;
            $pTypes   .= 'ss';
        }

        $pWhereSql = '';
        if (!empty($pWhere)) {
            // if purchase_list WHERE exists already, append with AND, else start WHERE
            $pWhereSql = ($plWhereSql ? ' AND ' : ' WHERE ') . implode(' AND ', $pWhere);
        }

        // -----------------------------
        // Count distinct grouped products
        // -----------------------------
        $sql = "
            SELECT COUNT(DISTINCT pl.product_id) AS cnt
            FROM purchase_list pl
            LEFT JOIN vp_products p ON pl.product_id = p.id
            $plWhereSql
            $pWhereSql
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return 0;

        $allParams = array_merge($plParams, $pParams);
        $allTypes  = $plTypes . $pTypes;

        if (!empty($allParams)) {
            // mysqli bind_param requires references
            $bindParams = [];
            $bindParams[] = &$allTypes;
            foreach ($allParams as $k => $v) {
                $bindParams[] = &$allParams[$k];
            }
            call_user_func_array([$stmt, 'bind_param'], $bindParams);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $row = $result->fetch_assoc();
            return isset($row['cnt']) ? (int)$row['cnt'] : 0;
        }

        return 0;
    }


    // Return distinct product categories (groupname) for filter dropdown
    public function getCategories()
    {
        $sql = "SELECT DISTINCT COALESCE(NULLIF(groupname, ''), '-') AS groupname FROM vp_products WHERE groupname IS NOT NULL ORDER BY groupname ASC";
        $res = $this->db->query($sql);
        $cats = [];
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $cats[] = $row['groupname'];
            }
        }
        return $cats;
    }

    /*public function updatePurchaseListStatus($product_id, $status, $date_purchased = null)
    {
        $sql = "UPDATE purchase_list SET status = ?, date_purchased = ?, updated_at = ? WHERE product_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        $date_purchased = $date_purchased ? $date_purchased : date('Y-m-d H:i:s');
        $updatedAt = date('Y-m-d H:i:s');
        $id = (int)$product_id;
        $stmt->bind_param('sssi', $status, $date_purchased, $updatedAt, $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Status updated'];
        }
        return ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
    }*/

    public function updatePurchaseListStatusValue($purchase_list_id, $status)
    {
        $sql = "UPDATE purchase_list SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }

        $id = (int)$purchase_list_id;
        $stmt->bind_param('si', $status, $id);

        if ($stmt->execute()) {

            // ✅ logged-in user info
            $loggedUserId = (int)($_SESSION['user']['id'] ?? 0);
            $loggedUserName = $_SESSION['user']['name'] ?? 'Unknown';
            $sql = "SELECT 
                    pl.id,
                    pl.sku,
                    pl.edit_by,
                    pl.user_id,
                    pl.product_id,
                    pl.order_id
                FROM purchase_list AS pl
                WHERE 
                    pl.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $purchase_list_id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC)[0];
            $statusText = "Purchase STATUS UPDATE (SKU : " . $data['sku'] . ")";

            $this->createOrderStatusLog(
                (int)$data['order_id'],         // order_id (required by vp_order_status_log)
                $statusText,                    // status text
                $loggedUserId,                  // changed_by
                $loggedUserName,                // saved inside api_response JSON
                0,
                [
                    'action' => 'STATUS UPDATE',
                    'purchase_list_id' => $purchase_list_id,
                    'product_id' => (int)$data['product_id'],
                    'user_id' => (int)$data['user_id'],
                    'sku' => $data['sku'],
                    'status' => $status,
                    'date_added' => date('Y/m/d h:i:s'),
                ]
            );
            return ['success' => true, 'message' => 'Status updated'];
        }

        return ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
    }


    public function updatePurchaseListStatus($purchase_list_id, $transactionQty, $status, $purchase_type = 'purchased')
    {
        $remaining = (int)$transactionQty;

        $sql = "SELECT 
                    pl.id,
                    pl.quantity,
                    pl.sku,
                    pl.edit_by,
                    pl.user_id,
                    pl.product_id,
                    pl.order_id,
                    o.order_number
                FROM purchase_list AS pl
                LEFT JOIN vp_orders AS o 
                    ON o.id = pl.order_id
                WHERE 
                    pl.id = ?
                    AND pl.status IN ('pending', 'partially_purchased')
                ORDER BY pl.created_at ASC, pl.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $purchase_list_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'No purchasable quantity found'
            ];
        }

        // ✅ logged-in user info
        $loggedUserId = (int)($_SESSION['user']['id'] ?? 0);
        $loggedUserName = $_SESSION['user']['name'] ?? 'Unknown';

        $finalStatus = null;
        $order_number = null;
        $sku = null;
        $added_by = null;
        $order_id_for_log = null;

        foreach ($rows as $row) {
            if ($remaining <= 0) break;

            $id         = (int)$row['id'];
            $qty        = (int)$row['quantity'];
            $product_id = (int)$row['product_id'];
            $added_by   = (int)$row['edit_by'];

            $order_id_for_log = (int)$row['order_id']; // ✅ needed for vp_order_status_log
            $order_number = $row['order_number'] ?? $order_number;
            $sku          = $row['sku'] ?? $sku;

            /** FULL PURCHASE **/
            if ($qty <= $remaining) {

                $finalStatus = 'purchased';
                $remaining -= $qty;

                $sql = "UPDATE purchase_list 
                        SET quantity = 0,
                            status = 'purchased',
                            date_purchased = NOW(),
                            updated_at = NOW()
                        WHERE id = ?";
                $u = $this->db->prepare($sql);
                $u->bind_param('i', $id);
                $u->execute();

                // ✅ Log into vp_order_status_log
                // status column: readable message
                $statusText = "Purchased (SKU : " . $sku . ") Qty: " . $qty;

                $this->createOrderStatusLog(
                    $order_id_for_log,
                    $statusText,
                    $loggedUserId,
                    $loggedUserName,
                    $qty,
                    [
                        'purchase_list_id' => $id,
                        'action' => 'FULL_PURCHASE',
                        'old_qty' => $qty,
                        'new_qty' => 0,
                    ]
                );
            }
            /** PARTIAL PURCHASE **/
            else {

                $finalStatus = 'partially_purchased';
                $consumedQty = $remaining;
                $newQty      = $qty - $consumedQty;
                $remaining   = 0;

                $sql = "UPDATE purchase_list 
                        SET quantity = ?, 
                            status = 'partially_purchased',
                            updated_at = NOW()
                        WHERE id = ?";
                $u = $this->db->prepare($sql);
                $u->bind_param('ii', $newQty, $id);
                $u->execute();

                // ✅ Log into vp_order_status_log
                $statusText = "Purchase PARTIAL (SKU : " . $sku . ") Qty: " . $consumedQty;
                $this->createOrderStatusLog(
                    $order_id_for_log,
                    $statusText,
                    $loggedUserId,
                    $loggedUserName,
                    $consumedQty,
                    [
                        'purchase_list_id' => $id,
                        'action' => 'PARTIAL_PURCHASE',
                        'old_qty' => $qty,
                        'new_qty' => $newQty,
                    ]
                );
            }
        }

        // ✅ Notification safety (use $finalStatus instead of overwriting $status)
        if ($added_by && $sku) {
            $link = base_url(
                'index.php?page=products&action=master_purchase_list&search=' .
                    $sku . '&status=' . ($finalStatus ?? 'pending')
            );

            require_once 'models/comman/tables.php';
            $commanModel = new Tables($this->db);
            $agent_name = $commanModel->getUserNameById($added_by);

            $orderLink = '';
            if (!empty($order_number)) {
                $url = base_url('index.php?order_number=' . urlencode($order_number));

                $orderLink = '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">
                                ' . htmlspecialchars($order_number) . '
                            </a>';
            }

            insertNotification(
                $added_by,
                'Product Purchased',
                $agent_name . ' has purchased item ' . $sku . ' for order ' . $orderLink,
                $link
            );
        }

        return [
            'success' => true,
            'purchased' => (int)$transactionQty,
            'remaining_not_consumed' => $remaining,
            'message' => 'Purchased Successfully'
        ];
    }

    public function createOrderStatusLog(
        int $order_id,
        string $status,
        int $changed_by,
        string $changed_by_name,
        int $qty_changed,
        ?array $api_response = null
    ) {
        // Put extra details into api_response JSON (since table doesn't have username/qty columns)
        $payload = [
            'changed_by_name' => $changed_by_name,
            'qty_changed' => $qty_changed,
            'status' => $status,
        ];

        if (is_array($api_response)) {
            $payload['api_response'] = $api_response;
        }

        $sql = "INSERT INTO vp_order_status_log
                (order_id, status, changed_by, api_response, change_date, created_on)
                VALUES (?,?,?,?,NOW(),NOW())";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception($this->db->error);
        }

        $api_json = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $stmt->bind_param(
            'isis',
            $order_id,
            $status,       // keep short readable status here
            $changed_by,
            $api_json      // store user name + qty here
        );

        return $stmt->execute();
    }



    public function addPurchaseTransaction($purchase_list_id, $qty, $user_id, $status, $product_id, $reason = '')
    {
        $sql = "INSERT INTO purchase_transactions 
            (product_id,purchase_list_id, qty_purchased, purchased_by, remarks, date_purchased)
            VALUES (?,?,?,?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iiiis', $product_id, $purchase_list_id, $qty, $user_id, $reason);
        $stmt->execute();

        // Update status after transaction
        return $this->updatePurchaseListStatus($purchase_list_id, $qty, $status, 'purchased');
    }

    public function reversePurchaseTransaction($purchase_list_id, $qty, $user_id, $reason = null)
    {
        $qty = !empty($qty) ? -abs($qty) : 0; // always negative

        $sql = "INSERT INTO purchase_transactions (purchase_list_id, qty_purchased, purchased_by, remarks, date_purchased)
            VALUES (?,?,?,?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iiis', $purchase_list_id, $qty, $user_id, $reason);
        $stmt->execute();
        return $this->updatePurchaseListStatus($purchase_list_id, 'unpurchased');
    }




    // Update quantity and remarks for a purchase list item
    public function updatePurchaseItem($id, $quantity, $remarks, $status, $expected_time_of_delivery = null)
    {
        $sql = "UPDATE purchase_list 
                SET quantity = ?, 
                    remarks = ?, 
                    status = ?, 
                    expected_time_of_delivery = ?, 
                    updated_at = ? 
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }

        $updatedAt = date('Y-m-d H:i:s');
        $id  = (int) $id;

        // quantity can be NULL
        $qty = ($quantity === '' || $quantity === null) ? null : (int)$quantity;

        // normalize date (NULL allowed)
        if (!empty($expected_time_of_delivery)) {
            $dt = date_create($expected_time_of_delivery);
            $expected_time_of_delivery = $dt ? $dt->format('Y-m-d') : null;
        } else {
            $expected_time_of_delivery = null;
        }

        // ✅ FIXED bind_param
        $stmt->bind_param(
            'issssi',
            $qty,
            $remarks,
            $status,
            $expected_time_of_delivery,
            $updatedAt,
            $id
        );

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Updated successfully'];
        }

        return ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
    }

    public function deletePurchaseItem($id)
    {
        $sql = "DELETE FROM purchase_list WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        $id = (int)$id;
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Deleted successfully'];
        }
        return ['success' => false, 'message' => 'Delete failed: ' . $stmt->error];
    }

    public function getPurchaseItemById($id)
    {
        // Step 1: Get product_id for that purchase_list row
        $sql = "SELECT product_id FROM purchase_list WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $id = (int)$id;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res || $res->num_rows === 0) return null;

        $row       = $res->fetch_assoc();
        $productId = (int)$row['product_id'];

        // Step 2: Latest row + total quantity for that product
        $sql = "
        SELECT
            pl_latest.id,
            pl_latest.user_id,
            pl_latest.product_id,
            pl_latest.order_id,
            pl_latest.sku,
            pl_latest.date_added,
            pl_latest.date_purchased,
            pl_latest.status,
            qty.quantity,
            pl_latest.remarks,
            pl_latest.edit_by,
            pl_latest.updated_at,
            pl_latest.created_at,
            pl_latest.expected_time_of_delivery,
            p.item_code,
            p.title,
            p.groupname AS category,
            p.cost_price,
            p.image,
            p.product_weight,
            p.prod_height,
            p.prod_width,
            p.prod_length,
            p.vendor,
            u.name AS agent_name,
            vu.name AS added_by_name
        FROM
        (
            SELECT product_id, SUM(quantity) AS quantity
            FROM purchase_list
            WHERE product_id = ?
            GROUP BY product_id
        ) qty
        JOIN
        (
            SELECT pl.*
            FROM purchase_list pl
            JOIN (
                SELECT product_id, MAX(updated_at) AS max_updated_at
                FROM purchase_list
                WHERE product_id = ?
                GROUP BY product_id
            ) latest
                ON latest.product_id = pl.product_id
                AND latest.max_updated_at = pl.updated_at
            WHERE pl.product_id = ?
        ) pl_latest ON pl_latest.product_id = qty.product_id
        LEFT JOIN vp_products p ON p.id = pl_latest.product_id
        LEFT JOIN vp_users u ON pl_latest.user_id = u.id
        LEFT JOIN vp_users vu ON pl_latest.edit_by = vu.id
        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param('iii', $productId, $productId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }



    public function getProductByskuExact($sku, bool $excludeParentFromCatalog = false)
    {
        $notParent = $excludeParentFromCatalog ? " AND LOWER(TRIM(IFNULL(item_level, ''))) <> 'parent' " : '';
        $sql = "SELECT * FROM vp_products WHERE sku = ? {$notParent} LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function getStockSummaryBySku($sku)
    {
        $sql = "SELECT * FROM vp_stock
                WHERE sku = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return ['total_added' => 0, 'total_deducted' => 0];
    }
    public function getStockMovementBySku($sku)
    {
        //product_id, sku, warehouse_id, movement_type, quantity, running_stock, ref_type, ref_id
        $sql = "SELECT 
            SUM(CASE WHEN movement_type = 'IN' THEN quantity ELSE 0 END) AS total_added,
            SUM(CASE WHEN movement_type = 'OUT' THEN quantity ELSE 0 END) AS total_deducted,
            MAX(CASE WHEN movement_type = 'IN' THEN created_at ELSE NULL END) AS last_added_at,
            MAX(CASE WHEN movement_type = 'OUT' THEN created_at ELSE NULL END) AS last_deducted_at,
            running_stock                    
                FROM vp_stock_movements
                WHERE sku = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return ['total_added' => 0, 'total_deducted' => 0];
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return ['total_added' => 0, 'total_deducted' => 0];
    }
    public function stock_history($sku, $limit = 100, $offset = 0, $productId = 0)
    {
        // Join exotic_address and match by sku OR product_id (migration-safe).
        $sql = "SELECT sm.*, ea.address_title AS warehouse_name, u.name AS updated_by_name
                FROM vp_stock_movements sm
                LEFT JOIN exotic_address ea ON sm.warehouse_id = ea.id
                LEFT JOIN vp_users u ON sm.update_by_user = u.id
                WHERE (sm.sku = ? OR sm.product_id = ?)
                ORDER BY sm.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $pid = (int)$productId;
        $stmt->bind_param('siii', $sku, $pid, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    /**
     * Stock ledger display: use ref_type (and movement_type), not movement direction alone.
     * Cancelling an invoice posts movement_type IN — label as reversal of sale, not "Purchase".
     *
     * @return array{ledger_type:string, icon:string, text_color_class:string}
     */
    public function getStockLedgerDisplayForMovement(array $row): array
    {
        $mt = (string)($row['movement_type'] ?? '');
        $rt = strtoupper(trim((string)($row['ref_type'] ?? '')));

        if ($mt === 'IN' && $rt === 'INVOICE_CANCEL') {
            return [
                'ledger_type' => 'Invoice cancellation',
                'icon' => 'fa-undo',
                'text_color_class' => 'text-amber-600',
            ];
        }
        if ($mt === 'OPENING_STOCK') {
            return [
                'ledger_type' => 'Opening stock',
                'icon' => 'fa-boxes',
                'text_color_class' => 'text-emerald-700',
            ];
        }
        if ($rt === 'MANUAL' && ($mt === 'IN' || $mt === 'OUT')) {
            $manualByName = trim((string)($row['updated_by_name'] ?? ''));
            if ($manualByName === '' && !empty($row['update_by_user'])) {
                $manualByName = 'User #' . (int)$row['update_by_user'];
            }
            $ledgerLabel = 'Stock adjustment';
            if ($manualByName !== '') {
                $ledgerLabel .= ' (' . $manualByName . ')';
            }
            return [
                'ledger_type' => $ledgerLabel,
                'icon' => 'fa-sliders-h',
                'text_color_class' => $mt === 'IN' ? 'text-green-600' : 'text-red-600',
            ];
        }
        if ($rt === 'EGREENREFETCH' && ($mt === 'IN' || $mt === 'OUT')) {
            return [
                'ledger_type' => 'Stock adjustment',
                'icon' => 'fa-sliders-h',
                'text_color_class' => $mt === 'IN' ? 'text-green-600' : 'text-red-600',
            ];
        }
        if ($mt === 'IN' && $rt === 'GRN') {
            return [
                'ledger_type' => 'Purchase (GRN)',
                'icon' => 'fa-arrow-up',
                'text_color_class' => 'text-green-600',
            ];
        }
        if ($mt === 'IN' && $rt === 'BULK_IMPORT') {
            return [
                'ledger_type' => 'Bulk import',
                'icon' => 'fa-cloud-upload-alt',
                'text_color_class' => 'text-teal-600',
            ];
        }
        if ($mt === 'IN' && $rt === 'DIRECT_PURCHASE') {
            return [
                'ledger_type' => 'Direct purchase',
                'icon' => 'fa-arrow-up',
                'text_color_class' => 'text-green-600',
            ];
        }
        if ($mt === 'OUT' && $rt === 'DIRECT_PURCHASE_RETURN') {
            return [
                'ledger_type' => 'Purchase return',
                'icon' => 'fa-undo',
                'text_color_class' => 'text-amber-700',
            ];
        }
        if ($mt === 'OUT' && $rt === 'INVOICE') {
            return [
                'ledger_type' => 'Sale (invoice)',
                'icon' => 'fa-arrow-down',
                'text_color_class' => 'text-red-600',
            ];
        }

        $typeMap = [
            'IN' => 'Purchase',
            'OUT' => 'Sale',
            'TRANSFER_IN' => 'Transfer in',
            'TRANSFER_OUT' => 'Transfer out',
        ];
        $iconMap = [
            'IN' => 'fa-arrow-up',
            'OUT' => 'fa-arrow-down',
            'TRANSFER_IN' => 'fa-exchange-alt',
            'TRANSFER_OUT' => 'fa-exchange-alt',
        ];
        $colorMap = [
            'IN' => 'text-green-600',
            'OUT' => 'text-red-600',
            'TRANSFER_IN' => 'text-blue-600',
            'TRANSFER_OUT' => 'text-blue-600',
        ];

        return [
            'ledger_type' => $typeMap[$mt] ?? $mt,
            'icon' => $iconMap[$mt] ?? 'fa-circle',
            'text_color_class' => $colorMap[$mt] ?? '',
        ];
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    public function enrichStockHistoryRowsForLedger(array $rows): array
    {
        foreach ($rows as &$r) {
            $d = $this->getStockLedgerDisplayForMovement($r);
            $r['ledger_type'] = $d['ledger_type'];
            $r['ledger_icon'] = $d['icon'];
            $r['ledger_color_class'] = $d['text_color_class'];
        }
        unset($r);

        return $rows;
    }

    /**
     * Total on-hand across all warehouses: sum of latest running_stock per warehouse_id.
     */
    public function getTotalPhysicalStockAcrossWarehouses(int $product_id): int
    {
        $product_id = (int)$product_id;
        if ($product_id <= 0) {
            return 0;
        }
        $sql = "
            SELECT COALESCE(SUM(sm.running_stock), 0) AS total_stock
            FROM vp_stock_movements sm
            INNER JOIN (
                SELECT warehouse_id, product_id, MAX(id) AS max_id
                FROM vp_stock_movements
                WHERE product_id = ?
                GROUP BY warehouse_id, product_id
            ) latest ON sm.warehouse_id = latest.warehouse_id
                AND sm.product_id = latest.product_id
                AND sm.id = latest.max_id
            WHERE sm.product_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('ii', $product_id, $product_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $total = (int)round((float)($row['total_stock'] ?? 0));

        return max(0, $total);
    }

    /**
     * vp_products.physical_stock = total stock across all warehouses (not a single location).
     */
    public function syncPhysicalStockTotalFromWarehouses(int $product_id): int
    {
        $product_id = (int)$product_id;
        if ($product_id <= 0) {
            return 0;
        }
        $total = $this->getTotalPhysicalStockAcrossWarehouses($product_id);
        $stmt = $this->db->prepare('UPDATE vp_products SET physical_stock = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('ii', $total, $product_id);
            $stmt->execute();
            $stmt->close();
        }

        return $total;
    }

    /**
     * Latest per-warehouse balance from the movement ledger (source of truth).
     */
    public function getLatestRunningStockForSkuWarehouse(string $sku, int $warehouse_id): float
    {
        $sku = trim($sku);
        if ($sku === '' || $warehouse_id <= 0) {
            return 0.0;
        }
        $stmt = $this->db->prepare(
            'SELECT running_stock FROM vp_stock_movements
             WHERE sku = ? AND warehouse_id = ?
             ORDER BY id DESC LIMIT 1'
        );
        if (!$stmt) {
            return 0.0;
        }
        $stmt->bind_param('si', $sku, $warehouse_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['running_stock']) ? (float)$row['running_stock'] : 0.0;
    }

    /**
     * Keep vp_stock.current_stock aligned with latest movement running_stock for sku + warehouse.
     */
    public function syncVpStockRowFromLatestMovement(string $sku, int $warehouse_id, ?int $last_trans_id = null): void
    {
        $sku = trim($sku);
        if ($sku === '' || $warehouse_id <= 0) {
            return;
        }
        $qty = $this->getLatestRunningStockForSkuWarehouse($sku, $warehouse_id);
        if ($qty < 0) {
            $qty = 0.0;
        }

        $sel = $this->db->prepare('SELECT id FROM vp_stock WHERE sku = ? AND warehouse_id = ? LIMIT 1');
        if (!$sel) {
            return;
        }
        $sel->bind_param('si', $sku, $warehouse_id);
        $sel->execute();
        $existing = $sel->get_result()->fetch_assoc();
        $sel->close();

        if ($existing) {
            $stockId = (int)($existing['id'] ?? 0);
            if ($last_trans_id !== null && $last_trans_id > 0) {
                $upd = $this->db->prepare('UPDATE vp_stock SET current_stock = ?, last_trans_id = ? WHERE id = ?');
                if ($upd) {
                    $upd->bind_param('dii', $qty, $last_trans_id, $stockId);
                    $upd->execute();
                    $upd->close();
                }
            } else {
                $upd = $this->db->prepare('UPDATE vp_stock SET current_stock = ? WHERE id = ?');
                if ($upd) {
                    $upd->bind_param('di', $qty, $stockId);
                    $upd->execute();
                    $upd->close();
                }
            }
            return;
        }

        $transId = ($last_trans_id !== null && $last_trans_id > 0) ? $last_trans_id : 0;
        $ins = $this->db->prepare(
            'INSERT INTO vp_stock (sku, warehouse_id, current_stock, last_trans_id) VALUES (?, ?, ?, ?)'
        );
        if ($ins) {
            $ins->bind_param('sidi', $sku, $warehouse_id, $qty, $transId);
            $ins->execute();
            $ins->close();
        }
    }

    /**
     * After a movement: sync vp_stock row (per warehouse) and physical_stock (all warehouses).
     */
    public function syncDerivedStockStores(int $product_id, string $sku, int $warehouse_id, ?int $last_trans_id = null): void
    {
        if ($warehouse_id > 0 && trim($sku) !== '') {
            $this->syncVpStockRowFromLatestMovement($sku, $warehouse_id, $last_trans_id);
        }
        if ($product_id > 0) {
            $this->syncPhysicalStockTotalFromWarehouses($product_id);
        }
    }

    /**
     * Resync vp_stock (all warehouses) and physical_stock for one product from the movement ledger.
     */
    public function syncAllDerivedStoresForProduct(int $product_id): void
    {
        $product_id = (int)$product_id;
        if ($product_id <= 0) {
            return;
        }
        $stmt = $this->db->prepare(
            'SELECT DISTINCT sku, warehouse_id FROM vp_stock_movements
             WHERE product_id = ? AND sku IS NOT NULL AND TRIM(sku) <> \'\' AND warehouse_id > 0'
        );
        if ($stmt) {
            $stmt->bind_param('i', $product_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($row = $res->fetch_assoc())) {
                $sku = trim((string)($row['sku'] ?? ''));
                $wh = (int)($row['warehouse_id'] ?? 0);
                if ($sku !== '' && $wh > 0) {
                    $this->syncVpStockRowFromLatestMovement($sku, $wh);
                }
            }
            $stmt->close();
        }
        $this->syncPhysicalStockTotalFromWarehouses($product_id);
    }

    public function insertStockMovement($data, bool $manageTransaction = true)
    {
        if ($manageTransaction) {
            $this->db->begin_transaction();
        }
        try {
            $stmt = $this->db->prepare('SELECT id FROM vp_products WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $data['product_id']);
            $stmt->execute();
            $res = $stmt->get_result();
            $product = $res->fetch_assoc();
            $stmt->close();

            if (!$product) {
                throw new Exception('Product not found');
            }

            $adj_qty = (int)$data['quantity'];
            $movement_type = strtoupper(trim((string)($data['movement_type'] ?? 'OUT')));
            $isInbound = in_array($movement_type, ['IN', 'TRANSFER_IN', 'OPENING_STOCK'], true);
            $warehouse_id = (int)($data['warehouse_id'] ?? 0);
            $sku = (string)($data['sku'] ?? '');

            // Per-warehouse running_stock chain on the movement row.
            $running_stock = $isInbound ? $adj_qty : 0;
            $lastRunning = 0;
            if ($warehouse_id > 0 && $sku !== '') {
                $whStmt = $this->db->prepare(
                    'SELECT running_stock FROM vp_stock_movements
                     WHERE sku = ? AND warehouse_id = ?
                     ORDER BY id DESC LIMIT 1'
                );
                if ($whStmt) {
                    $whStmt->bind_param('si', $sku, $warehouse_id);
                    $whStmt->execute();
                    $whRes = $whStmt->get_result();
                    $whRow = $whRes ? $whRes->fetch_assoc() : null;
                    $whStmt->close();
                    if ($whRow) {
                        $lastRunning = (int)($whRow['running_stock'] ?? 0);
                    }
                }
            } elseif (!$isInbound && (int)($data['product_id'] ?? 0) > 0) {
                $lastRunning = $this->getTotalPhysicalStockAcrossWarehouses((int)$data['product_id']);
            }

            $strictStockCheck = !array_key_exists('strict_stock_check', $data) || !empty($data['strict_stock_check']);
            if (!$isInbound && $strictStockCheck && $adj_qty > $lastRunning) {
                throw new Exception(
                    'Insufficient stock: available ' . $lastRunning . ', requested ' . $adj_qty
                );
            }

            if ($warehouse_id > 0 && $sku !== '') {
                $running_stock = $isInbound ? $lastRunning + $adj_qty : max(0, $lastRunning - $adj_qty);
            } elseif (!$isInbound) {
                $running_stock = max(0, $lastRunning - $adj_qty);
            }

            // Insert into vp_stock_movements (History)
            $insertSql = "INSERT INTO vp_stock_movements (
                        product_id, sku, item_code, size, color, 
                        warehouse_id, location, movement_type, 
                        quantity, running_stock, update_by_user, 
                        ref_type, ref_id, reason, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $insertStmt = $this->db->prepare($insertSql);
            $ref_type = isset($data['ref_type']) && $data['ref_type'] !== ''
                ? (string)$data['ref_type']
                : 'MANUAL';
            $ref_id = array_key_exists('ref_id', $data) ? (string)$data['ref_id'] : '0';
            $updatedByUser = isset($data['update_by_user'])
                ? (int)$data['update_by_user']
                : (isset($data['user_id']) ? (int)$data['user_id'] : 0);

            $insertStmt->bind_param(
                'isssssssiiisss',
                $data['product_id'],
                $data['sku'],
                $data['item_code'],
                $data['size'],
                $data['color'],
                $data['warehouse_id'],
                $data['location'],
                $data['movement_type'],
                $adj_qty,
                $running_stock,
                $updatedByUser,
                $ref_type,
                $ref_id,
                $data['reason']
            );

            if (!$insertStmt->execute()) {
                throw new Exception("Failed to record history: " . $this->db->error);
            }

            $refIdForTrans = 0;
            if (array_key_exists('ref_id', $data) && is_numeric($data['ref_id']) && (int)$data['ref_id'] > 0) {
                $refIdForTrans = (int)$data['ref_id'];
            }
            $this->syncDerivedStockStores(
                (int)$data['product_id'],
                $sku,
                $warehouse_id,
                $refIdForTrans > 0 ? $refIdForTrans : null
            );

            // If everything is fine, commit changes
            if ($manageTransaction) {
                $this->db->commit();
            }
            return ['success' => true, 'message' => 'Stock updated and history recorded.'];
        } catch (Exception $e) {
            // Rollback if any step fails
            if ($manageTransaction) {
                $this->db->rollback();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    public function updateProductNotes($product_id, $notes)
    {
        $sql = "UPDATE vp_products SET notes = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }
        $id = (int)$product_id;
        $stmt->bind_param('si', $notes, $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Notes updated successfully'];
        }
        return ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
    }
    public function getVariantsByItemCode($item_code)
    {
        $sql = "SELECT id, item_code, title, sku FROM vp_products WHERE item_code = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('s', $item_code);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    public function getFilteredStockHistory($filters = [], $limit = 100, $offset = 0)
    {
        $where = [];
        $params = [];
        $types = '';

        if (!empty($filters['sku']) && !empty($filters['product_id'])) {
            $where[] = '(sm.sku = ? OR sm.product_id = ?)';
            $params[] = $filters['sku'];
            $params[] = (int)$filters['product_id'];
            $types .= 'si';
        } elseif (!empty($filters['sku'])) {
            $where[] = 'sm.sku = ?';
            $params[] = $filters['sku'];
            $types .= 's';
        } elseif (!empty($filters['product_id'])) {
            $where[] = 'sm.product_id = ?';
            $params[] = (int)$filters['product_id'];
            $types .= 'i';
        }

        if (!empty($filters['type']) && in_array($filters['type'], ['IN', 'OUT', 'TRANSFER_IN', 'TRANSFER_OUT', 'OPENING_STOCK'])) {
            $where[] = 'sm.movement_type = ?';
            $params[] = $filters['type'];
            $types .= 's';
        }

        if (!empty($filters['start_date'])) {
            $where[] = 'DATE(sm.created_at) >= ?';
            $params[] = $filters['start_date'];
            $types .= 's';
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'DATE(sm.created_at) <= ?';
            $params[] = $filters['end_date'];
            $types .= 's';
        }

        if (!empty($filters['warehouse'])) {
            $where[] = 'sm.warehouse_id = ?';
            $params[] = $filters['warehouse'];
            $types .= 's';
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        $sql = "SELECT sm.*, ea.address_title AS warehouse_name, u.name AS updated_by_name
                FROM vp_stock_movements sm 
                LEFT JOIN exotic_address ea ON sm.warehouse_id = ea.id 
                LEFT JOIN vp_users u ON sm.update_by_user = u.id
                $whereSql 
                ORDER BY sm.created_at DESC 
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        // bind dynamic params followed by limit and offset
        if (!empty($params)) {
            $types_all = $types . 'ii';
            // Build the bind_param arguments correctly
            $bindArgs = [$types_all];
            foreach ($params as &$param) {
                $bindArgs[] = &$param;
            }
            $bindArgs[] = &$limit;
            $bindArgs[] = &$offset;
            call_user_func_array([$stmt, 'bind_param'], $bindArgs);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    public function getFilteredStockHistoryCount($filters = [])
    {
        $where = [];
        $params = [];
        $types = '';

        if (!empty($filters['sku']) && !empty($filters['product_id'])) {
            $where[] = '(sm.sku = ? OR sm.product_id = ?)';
            $params[] = $filters['sku'];
            $params[] = (int)$filters['product_id'];
            $types .= 'si';
        } elseif (!empty($filters['sku'])) {
            $where[] = 'sm.sku = ?';
            $params[] = $filters['sku'];
            $types .= 's';
        } elseif (!empty($filters['product_id'])) {
            $where[] = 'sm.product_id = ?';
            $params[] = (int)$filters['product_id'];
            $types .= 'i';
        }

        if (!empty($filters['type']) && in_array($filters['type'], ['IN', 'OUT', 'TRANSFER_IN', 'TRANSFER_OUT', 'OPENING_STOCK'])) {
            $where[] = 'sm.movement_type = ?';
            $params[] = $filters['type'];
            $types .= 's';
        }

        if (!empty($filters['start_date'])) {
            $where[] = 'DATE(sm.created_at) >= ?';
            $params[] = $filters['start_date'];
            $types .= 's';
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'DATE(sm.created_at) <= ?';
            $params[] = $filters['end_date'];
            $types .= 's';
        }

        if (!empty($filters['warehouse'])) {
            $where[] = 'sm.warehouse_id = ?';
            $params[] = $filters['warehouse'];
            $types .= 's';
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        $sql = "SELECT COUNT(*) as count FROM vp_stock_movements sm $whereSql";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return 0;

        if (!empty($params)) {
            $bindArgs = [$types];
            foreach ($params as &$param) {
                $bindArgs[] = &$param;
            }
            call_user_func_array([$stmt, 'bind_param'], $bindArgs);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            return $row['count'];
        }
        return 0;
    }

    public function getAllWarehouses()
    {
        $sql = "SELECT id, address_title as name FROM exotic_address WHERE is_active = 1 ORDER BY address_title";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    public function get_stock_movements($id)
    {
        $stmt = $this->db->prepare("SELECT vsm.*,a.address_title as warehouse_name FROM vp_stock_movements as vsm LEFT JOIN exotic_address as a on vsm.warehouse_id=a.id WHERE vsm.product_id = ? ");
        if ($stmt === false) {
            return null;
        }
        $id = (int)$id;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }
    /**
     * Latest running_stock per warehouse for product detail — same basis as POS stock report
     * (latest vp_stock_movements row per warehouse + product_id, not a sum of quantities).
     *
     * @param int $productId
     * @param string $sku Unused; kept for call-site compatibility
     * @return list<array<string,mixed>>
     */
    public function getLatestRunningStockByWarehouseLocation($productId, $sku = '')
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            return [];
        }

        $sql = "SELECT 
                    sm.id AS movement_id,
                    sm.warehouse_id,
                    COALESCE(ea.address_title, CONCAT('Warehouse #', sm.warehouse_id)) AS warehouse_name,
                    sm.location,
                    sm.running_stock,
                    sm.updated_at,
                    sm.created_at
                FROM vp_stock_movements sm
                INNER JOIN (
                    SELECT warehouse_id, MAX(id) AS max_id
                    FROM vp_stock_movements
                    WHERE product_id = ?
                    GROUP BY warehouse_id
                ) latest ON latest.max_id = sm.id
                LEFT JOIN exotic_address ea ON ea.id = sm.warehouse_id
                WHERE sm.product_id = ?
                ORDER BY ea.address_title ASC, sm.location ASC";
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('ii', $productId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        if ($result && $result->num_rows > 0) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();

        return $rows;
    }
    public function updateStockMovementLocation($movementId, $productId, $location)
    {
        $sql = "UPDATE vp_stock_movements 
                SET location = ?, updated_at = NOW()
                WHERE id = ? AND product_id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }
        $movementId = (int)$movementId;
        $productId = (int)$productId;
        $location = trim((string)$location);
        $stmt->bind_param('sii', $location, $movementId, $productId);
        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
        }
        if ($stmt->affected_rows < 1) {
            return ['success' => false, 'message' => 'No stock movement row updated.'];
        }
        return ['success' => true, 'message' => 'Location updated successfully.'];
    }
    public function setProductLimits($productId, $minStock, $maxStock)
    {
        $sql = "UPDATE vp_products 
                SET min_stock = ?, 
                    max_stock = ? 
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        // Bind: min_stock (i), max_stock (i), product_id (i)
        $stmt->bind_param('iii', $minStock, $maxStock, $productId);

        return $stmt->execute();
    }

    public function setProductPermanentlyAvailable($productId, $permanentlyAvailable)
    {
        $productId = (int)$productId;
        $flag = ((int)$permanentlyAvailable) ? 1 : 0;
        $sql = 'UPDATE vp_products SET permanently_available = ? WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ii', $flag, $productId);
        return $stmt->execute();
    }

    public function setProductPublished($productId, $published)
    {
        $productId = (int)$productId;
        $flag = ((int)$published) ? 1 : 0;
        $sql = 'UPDATE vp_products SET published = ? WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ii', $flag, $productId);
        return $stmt->execute();
    }

    public function setProductPriceIndia($productId, $priceIndia)
    {
        $productId = (int)$productId;
        $price = (float)$priceIndia;
        $sql = 'UPDATE vp_products SET price_india = ? WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('di', $price, $productId);
        return $stmt->execute();
    }

    public function setProductPriceUsd($productId, $priceUsd)
    {
        $productId = (int)$productId;
        $price = (float)$priceUsd;
        $sql = 'UPDATE vp_products SET price = ? WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('di', $price, $productId);
        return $stmt->execute();
    }

    public function setProductCp($productId, $cp)
    {
        $productId = (int) $productId;
        $cp = (float) $cp;
        $sql = 'UPDATE vp_products SET cp = ?, cost_price = ? WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ddi', $cp, $cp, $productId);

        return $stmt->execute();
    }

    /**
     * Push CP and/or local_stock_delta to exoticindia.com via vendor product/modify.
     *
     * @param array<string, mixed> $product
     * @return array{success:bool,message:string,http_code?:int,response?:array}
     */
    public function syncCpToVendorFrontend(array $product, float $cp, ?float $localStockDelta = null): array
    {
        if (!function_exists('exotic_india_api_post')) {
            require_once __DIR__ . '/../../helpers/exotic_india_api.php';
        }

        $itemCode = trim((string) ($product['item_code'] ?? ''));
        if ($itemCode === '') {
            return ['success' => false, 'message' => 'Missing item_code for vendor product sync.'];
        }

        $postFields = [];
        if ($cp > 0) {
            $postFields['cp'] = $cp;
        }
        if ($localStockDelta !== null && abs($localStockDelta) > 0.0001) {
            $postFields['local_stock_delta'] = (int) round($localStockDelta);
        }
        if ($postFields === []) {
            return ['success' => false, 'message' => 'Nothing to sync to vendor API.'];
        }

        $size = trim((string) ($product['size'] ?? ''));
        $color = trim((string) ($product['color'] ?? ''));
        $endpoint = 'product/modify'
            . '?itemcode=' . rawurlencode($itemCode)
            . '&size=' . rawurlencode($size)
            . '&color=' . rawurlencode($color);

        $api = exotic_india_api_post(
            $endpoint,
            http_build_query($postFields),
            ['Content-Type: application/x-www-form-urlencoded']
        );

        if (!$api['success']) {
            return [
                'success' => false,
                'message' => trim((string) ($api['message'] ?? '')) !== ''
                    ? (string) $api['message']
                    : 'Vendor product sync failed.',
                'http_code' => (int) ($api['http_code'] ?? 0),
            ];
        }

        $data = is_array($api['data'] ?? null) ? $api['data'] : [];
        $apiSuccess = !isset($data['success']) || (bool) $data['success'];

        return [
            'success' => $apiSuccess,
            'message' => trim((string) ($data['message'] ?? '')) !== ''
                ? (string) $data['message']
                : ($apiSuccess ? 'Vendor product sync completed.' : 'Vendor product sync failed.'),
            'http_code' => (int) ($api['http_code'] ?? 0),
            'response' => $data,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVendorApiVariantRow(string $itemCode, string $size = '', string $color = ''): ?array
    {
        $itemCode = trim($itemCode);
        if ($itemCode === '') {
            return null;
        }

        $decoded = $this->fetchVendorProductApiPayload($itemCode);
        if ($decoded === null) {
            return null;
        }

        $rows = self::normalizeVendorProductFetchItems($decoded);
        if ($rows === []) {
            return null;
        }

        $size = trim($size);
        $color = trim($color);
        $fallback = null;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowSize = trim((string) ($row['size'] ?? ''));
            $rowColor = trim((string) ($row['color'] ?? ''));
            if ($size !== '' && strcasecmp($rowSize, $size) !== 0) {
                continue;
            }
            if ($color !== '' && strcasecmp($rowColor, $color) !== 0) {
                continue;
            }
            if ($size === '' && $color === '' && $fallback === null) {
                $fallback = $row;
                continue;
            }
            if ($size !== '' || $color !== '') {
                return $row;
            }
            $fallback = $row;
        }

        return $fallback;
    }

    /**
     * Compare vendor product/fetch CP and local_stock with purchase line expectations.
     *
     * @return array<string, mixed>
     */
    public function verifyVendorCpAndStockAgainstExpected(
        string $itemCode,
        string $size,
        string $color,
        float $expectedCp,
        ?float $expectedLocalStock = null
    ): array {
        $itemCode = trim($itemCode);
        if ($itemCode === '') {
            return ['success' => false, 'message' => 'Item code is required to verify vendor data.'];
        }

        $vendorRow = $this->getVendorApiVariantRow($itemCode, $size, $color);
        if ($vendorRow === null) {
            return [
                'success' => false,
                'message' => 'Could not load product from vendor API (product/fetch).',
                'item_code' => $itemCode,
            ];
        }

        $vendorCp = (float) ($vendorRow['cp'] ?? 0);
        $vendorStock = (float) ($vendorRow['local_stock'] ?? 0);
        $checks = [];
        $allMatch = true;
        $anyChecked = false;

        if ($expectedCp > 0) {
            $cpMatch = abs($vendorCp - $expectedCp) < 0.01;
            $checks['cp'] = [
                'label' => 'CP (cost price)',
                'checked' => true,
                'expected' => $expectedCp,
                'vendor' => $vendorCp,
                'match' => $cpMatch,
            ];
            $anyChecked = true;
            if (!$cpMatch) {
                $allMatch = false;
            }
        } else {
            $checks['cp'] = [
                'label' => 'CP (cost price)',
                'checked' => false,
                'expected' => 0.0,
                'vendor' => $vendorCp,
                'match' => null,
                'note' => 'No cost on this line to verify.',
            ];
        }

        if ($expectedLocalStock !== null) {
            $stockMatch = abs($vendorStock - $expectedLocalStock) < 0.01;
            $checks['local_stock'] = [
                'label' => 'Local stock',
                'checked' => true,
                'expected' => $expectedLocalStock,
                'vendor' => $vendorStock,
                'local_db' => $expectedLocalStock,
                'match' => $stockMatch,
            ];
            $anyChecked = true;
            if (!$stockMatch) {
                $allMatch = false;
            }
        } else {
            $checks['local_stock'] = [
                'label' => 'Local stock',
                'checked' => false,
                'expected' => null,
                'vendor' => $vendorStock,
                'local_db' => null,
                'match' => null,
                'note' => 'No local product row to compare stock.',
            ];
        }

        if (!$anyChecked) {
            return [
                'success' => false,
                'message' => 'Nothing to verify — enter a cost and/or link a product with local stock.',
                'item_code' => $itemCode,
                'checks' => $checks,
            ];
        }

        $message = $allMatch
            ? 'Vendor CP and stock match expected values on exoticindia.com.'
            : 'Vendor values on exoticindia.com do not fully match expected CP/stock.';

        return [
            'success' => $allMatch,
            'message' => $message,
            'item_code' => $itemCode,
            'size' => trim($size),
            'color' => trim($color),
            'checks' => $checks,
        ];
    }

    public function modifyProduct($id, $data)
    {
        // Build UPDATE query dynamically
        $setClauses = [];
        $paramTypes = '';
        $params = [];

        // Mapping of data keys to database columns
        $columnMap = [
            'title' => 'title',
            'description' => 'description',
            'item_code' => 'item_code',
            'groupname' => 'groupname',
            'vendor' => 'vendor',
            'image' => 'image',
            'price' => 'price',
            'price_india' => 'price_india',
            'gst' => 'gst',
            'category' => 'category',
            'itemtype' => 'itemtype',
            'snippet_description' => 'snippet_description',
            'india_net_qty' => 'india_net_qty',
            'keywords' => 'keywords',
            'usblock' => 'usblock',
            'indiablock' => 'indiablock',
            'hscode' => 'hscode',
            'date_first_added' => 'date_first_added',
            'search_term' => 'search_term',
            'search_category' => 'search_category',
            'long_description' => 'long_description',
            'long_description_india' => 'long_description_india',
            'aplus_content_ids' => 'aplus_content_ids',
            'material' => 'material',
            'item_level' => 'item_level',
            'marketplace_vendor' => 'marketplace_vendor',
            'colormap' => 'colormap',
            'flex_status' => 'flex_status',
            'vendor_us' => 'vendor_us',
            'price_india_suggested' => 'price_india_suggested',
            'mrp_india' => 'mrp_india',
            'permanent_discount' => 'permanent_discount',
            'discount_global' => 'discount_global',
            'today_global' => 'today_global',
            'discount_india' => 'discount_india',
            'today_india' => 'today_india',
            'topurchase' => 'topurchase',
            'backorder_percent' => 'backorder_percent',
            'backorder_weeks' => 'backorder_weeks',
            'leadtime' => 'leadtime',
            'instock_leadtime' => 'instock_leadtime',
            'cp' => 'cp',
            'usd' => 'usd',
            'permanently_available' => 'permanently_available',
            'amazon_sold' => 'amazon_sold',
            'amazon_leadtime' => 'amazon_leadtime',
            'amazon_itemcode_alias' => 'amazon_itemcode_alias',
            'youtube_links' => 'youtube_links',
            'sketchfab_links' => 'sketchfab_links',
            'dimensions' => 'dimensions'
        ];

        foreach ($columnMap as $dataKey => $dbColumn) {
            if (isset($data[$dataKey])) {
                $setClauses[] = "{$dbColumn} = ?";
                $paramTypes .= 's'; // All are treated as strings for binding
                $params[] = $data[$dataKey];
            }
        }

        if (empty($setClauses)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        $paramTypes .= 'i';
        $params[] = (int)$id;

        $sql = "UPDATE vp_products SET " . implode(', ', $setClauses) . " WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }

        $stmt->bind_param($paramTypes, ...$params);

        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
        }

        if ($stmt->affected_rows < 1) {
            return ['success' => false, 'message' => 'No changes made to the product'];
        }

        return ['success' => true, 'message' => 'Product updated successfully'];
    }

    /**
     * Resolve comma-separated category store codes to display names.
     *
     * @return list<string>
     */
    public function resolveCategoryLabelList(string $codesCsv): array
    {
        $codesCsv = trim($codesCsv);
        if ($codesCsv === '') {
            return [];
        }
        $labels = [];
        foreach (array_filter(array_map('trim', explode(',', $codesCsv))) as $code) {
            if ($code === '') {
                continue;
            }
            $stmt = $this->db->prepare('SELECT display_name FROM category WHERE TRIM(CAST(category AS CHAR)) = ? LIMIT 1');
            if (!$stmt) {
                $labels[] = $code;
                continue;
            }
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            $labels[] = ($row && !empty($row['display_name'])) ? (string)$row['display_name'] : $code;
        }

        return $labels;
    }

    /**
     * Determine catalog tree level from category.parent (matches inbound desktop form hierarchy).
     */
    private function categoryLevelFromParent(string $parent): int
    {
        $parent = trim($parent);
        if ($parent === '0') {
            return 0;
        }
        if ($parent === '' || strpos($parent, '|') === false) {
            return 1;
        }

        return min(3, 1 + substr_count($parent, '|'));
    }

    /**
     * Split comma-separated category store codes into Group / Category / Sub / SubSub buckets.
     *
     * @return array{group:string,category:string,sub_category:string,sub_sub_category:string}
     */
    public function resolveFlatCategoryIdsToSections(string $raw): array
    {
        $empty = ['group' => '—', 'category' => '—', 'sub_category' => '—', 'sub_sub_category' => '—'];
        $raw = trim($raw);
        if ($raw === '') {
            return $empty;
        }

        $codes = array_values(array_unique(array_filter(array_map('trim', explode(',', $raw)))));
        if ($codes === []) {
            return $empty;
        }

        $buckets = [0 => [], 1 => [], 2 => [], 3 => []];

        foreach ($codes as $code) {
            $stmt = $this->db->prepare('SELECT display_name, parent FROM category WHERE TRIM(CAST(category AS CHAR)) = ? LIMIT 1');
            if (!$stmt) {
                $buckets[1][] = $code;
                continue;
            }
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();

            if (!$row) {
                $buckets[1][] = $code;
                continue;
            }

            $level = $this->categoryLevelFromParent((string)($row['parent'] ?? ''));
            $label = trim((string)($row['display_name'] ?? ''));
            $buckets[$level][] = $label !== '' ? $label : $code;
        }

        $joinLabels = static function (array $labels): string {
            return $labels !== [] ? implode(', ', $labels) : '—';
        };

        return [
            'group' => $joinLabels($buckets[0]),
            'category' => $joinLabels($buckets[1]),
            'sub_category' => $joinLabels($buckets[2]),
            'sub_sub_category' => $joinLabels($buckets[3]),
        ];
    }

    /**
     * Format pipe-delimited category string (SubSub|Sub|Cat|Group) for read-only display.
     *
     * @return array{group:string,category:string,sub_category:string,sub_sub_category:string}
     */
    public function resolveCategoryPipeSections(string $raw): array
    {
        $empty = ['group' => '—', 'category' => '—', 'sub_category' => '—', 'sub_sub_category' => '—'];
        $raw = trim($raw);
        if ($raw === '') {
            return $empty;
        }

        $joinLabels = static function (array $labels): string {
            return $labels !== [] ? implode(', ', $labels) : '—';
        };

        if (strpos($raw, '|') !== false) {
            $parts = explode('|', $raw);
            // Full inbound string: SubSub | Sub | Cat | Group (four segments, empty slots kept as "")
            // Vendor related_search API often returns three segments: Sub | Cat | Group (no SubSub)
            if (count($parts) === 3) {
                $parts = ['', $parts[0], $parts[1], $parts[2]];
            }
            $sections = [
                'sub_sub_category' => $joinLabels($this->resolveCategoryLabelList($parts[0] ?? '')),
                'sub_category' => $joinLabels($this->resolveCategoryLabelList($parts[1] ?? '')),
                'category' => $joinLabels($this->resolveCategoryLabelList($parts[2] ?? '')),
                'group' => $joinLabels($this->resolveCategoryLabelList($parts[3] ?? '')),
            ];
            $hasAny = false;
            foreach ($sections as $val) {
                if ($val !== '—') {
                    $hasAny = true;
                    break;
                }
            }
            if ($hasAny) {
                return $sections;
            }
        }

        return $this->resolveFlatCategoryIdsToSections($raw);
    }

    /**
     * Build read-only Item Identification and Search Category display fields for product detail.
     *
     * @return array{item_identification:array<string,string>,search_category:array<string,string>}
     */
    public function buildProductCatalogDisplayFields(array $row): array
    {
        $formatGroup = static function (string $raw): string {
            $raw = trim($raw);
            if ($raw === '') {
                return '—';
            }
            if (function_exists('mb_convert_case')) {
                return mb_convert_case($raw, MB_CASE_TITLE, 'UTF-8');
            }

            return ucwords(strtolower($raw));
        };

        $itemSections = $this->resolveCategoryPipeSections((string)($row['category'] ?? ''));
        $groupFromName = $formatGroup((string)($row['groupname'] ?? ''));
        if ($groupFromName !== '—') {
            $itemSections['group'] = $groupFromName;
        } elseif ($itemSections['group'] === '—') {
            $itemSections['group'] = $groupFromName;
        }
        // Flat comma lists can mis-bucket group-level ids; never duplicate group into Category.
        if ($groupFromName !== '—' && $itemSections['category'] !== '—') {
            $groupLower = strtolower($groupFromName);
            $catParts = array_map('trim', explode(',', $itemSections['category']));
            $catParts = array_values(array_filter($catParts, static function ($part) use ($groupLower) {
                return strtolower($part) !== $groupLower;
            }));
            $itemSections['category'] = $catParts !== [] ? implode(', ', $catParts) : '—';
        }

        $keywordsRaw = trim((string)($row['keywords'] ?? ''));
        $snippetRaw = trim((string)($row['snippet_description'] ?? ''));
        $optionalsRaw = '';
        if ($this->vpProductsHasColumn('optionals')) {
            $optionalsRaw = trim((string)($row['optionals'] ?? ''));
        }

        $optionalLabels = [];
        if ($optionalsRaw !== '') {
            foreach (preg_split('/[|,]/', $optionalsRaw) as $part) {
                $part = trim((string)$part);
                if ($part === '') {
                    continue;
                }
                $label = str_replace(['OPTIONALS_', '_'], ['', ' '], $part);
                $optionalLabels[] = ucwords(strtolower($label));
            }
        }

        $searchSections = $this->resolveCategoryPipeSections((string)($row['search_category'] ?? ''));
        $searchTermRaw = trim((string)($row['search_term'] ?? ''));

        return [
            'item_identification' => [
                'group' => $itemSections['group'],
                'category' => $itemSections['category'],
                'sub_category' => $itemSections['sub_category'],
                'sub_sub_category' => $itemSections['sub_sub_category'],
                'keywords' => $keywordsRaw !== '' ? $keywordsRaw : '—',
                'snippet_description' => $snippetRaw !== '' ? $snippetRaw : '—',
                'optionals' => $optionalLabels !== [] ? implode(', ', $optionalLabels) : '—',
            ],
            'search_category' => [
                'search_group' => $searchSections['group'],
                'search_category' => $searchSections['category'],
                'search_sub_category' => $searchSections['sub_category'],
                'search_sub_sub_category' => $searchSections['sub_sub_category'],
                'search_term' => $searchTermRaw !== '' ? $searchTermRaw : '—',
            ],
        ];
    }

    public function getBulkProductUpdateCatalogStats(): array
    {
        $hasFlag = $this->vpProductsHasColumn('update_flag');

        $totalRows = 0;
        $res = $this->db->query('SELECT COUNT(*) AS c FROM vp_products');
        if ($res && ($row = $res->fetch_assoc())) {
            $totalRows = (int) $row['c'];
        }

        $pendingRows = $totalRows;
        $updatedRows = 0;
        if ($hasFlag) {
            $res = $this->db->query(
                'SELECT COUNT(*) AS c FROM vp_products WHERE update_flag IS NULL OR update_flag = 0'
            );
            if ($res && ($row = $res->fetch_assoc())) {
                $pendingRows = (int) $row['c'];
            }
            $updatedRows = max(0, $totalRows - $pendingRows);
        }

        $distinctTotalCodes = 0;
        $res = $this->db->query(
            'SELECT COUNT(DISTINCT item_code) AS c FROM vp_products WHERE TRIM(IFNULL(item_code, \'\')) <> \'\''
        );
        if ($res && ($row = $res->fetch_assoc())) {
            $distinctTotalCodes = (int) $row['c'];
        }

        $distinctPendingCodes = $distinctTotalCodes;
        if ($hasFlag) {
            $res = $this->db->query(
                'SELECT COUNT(DISTINCT item_code) AS c FROM vp_products
                 WHERE (update_flag IS NULL OR update_flag = 0) AND TRIM(IFNULL(item_code, \'\')) <> \'\''
            );
            if ($res && ($row = $res->fetch_assoc())) {
                $distinctPendingCodes = (int) $row['c'];
            }
        }

        return [
            'has_update_flag' => $hasFlag,
            'total_db_rows' => $totalRows,
            'pending_db_rows' => $pendingRows,
            'updated_db_rows' => $updatedRows,
            'distinct_item_codes_total' => $distinctTotalCodes,
            'distinct_item_codes_pending' => $distinctPendingCodes,
        ];
    }

    /** Mark every product row pending again for bulk API sync. */
    public function requeueAllProductsForBulkUpdate(): int
    {
        if (!$this->vpProductsHasColumn('update_flag')) {
            return 0;
        }
        $this->db->query('UPDATE vp_products SET update_flag = 0');
        return (int) $this->db->affected_rows;
    }

    public function fetchProductsForUpdateScript($offset = 0, $limit = 500)
    {
        $offset = max(0, (int) $offset);
        $limit = max(1, min(500, (int) $limit));
        $where = '';
        if ($this->vpProductsHasColumn('update_flag')) {
            $where = ' WHERE update_flag IS NULL OR update_flag = 0';
        }
        $sql = "SELECT id, item_code, sku, size, color FROM vp_products{$where} ORDER BY id ASC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $itemCode = trim((string) ($row['item_code'] ?? ''));
                if ($itemCode !== '') {
                    $products[] = $itemCode;
                }
            }
        }
        $stmt->close();
        return array_values(array_unique($products));
        // if ($result && $result->num_rows > 0) {
        //     return ['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)];
        // }
        //return ['success' => false, 'message' => 'No products found'];
    }
}
