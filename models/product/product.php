<?php
class product
{
    private $db;
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
        if (preg_match('/-?\d+/', $str, $m)) {
            return (int)$m[0];
        }
        return (int)$default;
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

    /** HSN from API row (master line). Variants use the same hscode as master — pass the parent item, not the variation. */
    public static function vendorApiHsn(array $row): string
    {
        foreach (['hscode', 'hsn', 'hsn_code', 'hs_code', 'HSN', 'HSCode', 'tax_hsn'] as $k) {
            $v = trim((string)($row[$k] ?? ''));
            if ($v !== '') {
                return $v;
            }
        }

        return '';
    }

    /** Unwrap vendor product/fetch JSON to a list of rows with itemcode set. */
    public static function normalizeVendorProductFetchItems(array $data): array
    {
        if ($data === []) {
            return [];
        }
        foreach (['data', 'items'] as $w) {
            if (isset($data[$w]) && is_array($data[$w])) {
                $data = $data[$w];
            }
        }
        $ic = trim((string)($data['itemcode'] ?? $data['item_code'] ?? ''));
        if ($ic !== '' && !isset($data[0])) {
            return [$data];
        }
        $rows = [];
        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }
            $ric = trim((string)($row['itemcode'] ?? $row['item_code'] ?? ''));
            if ($ric === '') {
                continue;
            }
            $row['itemcode'] = $ric;
            $rows[] = $row;
        }

        return $rows;
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
        return $result ? $result->fetch_assoc() : null;
    }
    public function getAllProducts($limit, $offset, $filters = [])
    {
        $limit = (int)$limit;
        $offset = (int)$offset;
        //$search = "%$search%";

        $search = "";
        if (!empty($filters['item_code'])) {
            $search .= "AND vp_products.item_code like '%" . $filters['item_code'] . "%'";
        }
        if (!empty($filters['title'])) {
            $search .= "AND vp_products.title like '%" . $filters['title'] . "%'";
        }
        if (!empty($filters['vendor_name'])) {
            $search .= "AND vp_products.vendor like '%" . $filters['vendor_name'] . "%'";
        }
        if (!empty($filters['groupname'])) {
            $search .= "AND vp_products.groupname like '%" . $filters['groupname'] . "%'";
        }
        if (!empty($filters['sku'])) {
            $search .= "AND vp_products.sku like '%" . $filters['sku'] . "%'";
        }
        if (!empty($filters['size'])) {
            $search .= "AND vp_products.size like '%" . $filters['size'] . "%'";
        }
        if (!empty($filters['color'])) {
            $search .= "AND vp_products.color like '%" . $filters['color'] . "%'";
        }
        if (isset($filters['local_stock']) && $filters['local_stock'] !== '') {
            $search .= "AND vp_products.local_stock = " . (int)$filters['local_stock'];
        }
        if (isset($filters['permanently_available']) && $filters['permanently_available'] !== '') {
            $search .= "AND vp_products.permanently_available = " . ((int)$filters['permanently_available'] ? 1 : 0);
        }
        if (isset($filters['low_stock']) && $filters['low_stock'] !== '') {
            if ((int)$filters['low_stock'] === 1) {
                $search .= "AND vp_products.local_stock <= IFNULL(vp_products.min_stock, 0)";
            } else {
                $search .= "AND vp_products.local_stock > IFNULL(vp_products.min_stock, 0)";
            }
        }
        if (!empty($filters['marketplace'])) {
            $mp = $filters['marketplace'];
            if ($this->vpProductsHasColumn('marketplace_vendor')) {
                $search .= "AND vp_products.marketplace_vendor like '%" . $mp . "%'";
            } elseif ($this->vpProductsHasColumn('marketplace')) {
                $search .= "AND vp_products.marketplace like '%" . $mp . "%'";
            }
        }


        $stmt = $this->db->prepare("SELECT * FROM vp_products WHERE 1=1 $search order by vp_products.id DESC LIMIT ? OFFSET ?");
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function countAllProducts($filters = [])
    {
        $search = "";
        if (!empty($filters['item_code'])) {
            $search .= "AND vp_products.item_code like '%" . $filters['item_code'] . "%'";
        }
        if (!empty($filters['title'])) {
            $search .= "AND vp_products.title like '%" . $filters['title'] . "%'";
        }
        if (!empty($filters['vendor_name'])) {
            $search .= "AND vp_products.vendor like '%" . $filters['vendor_name'] . "%'";
        }
        if (!empty($filters['groupname'])) {
            $search .= "AND vp_products.groupname like '%" . $filters['groupname'] . "%'";
        }
        if (!empty($filters['sku'])) {
            $search .= "AND vp_products.sku like '%" . $filters['sku'] . "%'";
        }
        if (!empty($filters['size'])) {
            $search .= "AND vp_products.size like '%" . $filters['size'] . "%'";
        }
        if (!empty($filters['color'])) {
            $search .= "AND vp_products.color like '%" . $filters['color'] . "%'";
        }
        if (isset($filters['local_stock']) && $filters['local_stock'] !== '') {
            $search .= "AND vp_products.local_stock = " . (int)$filters['local_stock'];
        }
        if (isset($filters['permanently_available']) && $filters['permanently_available'] !== '') {
            $search .= "AND vp_products.permanently_available = " . ((int)$filters['permanently_available'] ? 1 : 0);
        }
        if (isset($filters['low_stock']) && $filters['low_stock'] !== '') {
            if ((int)$filters['low_stock'] === 1) {
                $search .= "AND vp_products.local_stock <= IFNULL(vp_products.min_stock, 0)";
            } else {
                $search .= "AND vp_products.local_stock > IFNULL(vp_products.min_stock, 0)";
            }
        }
        if (!empty($filters['marketplace'])) {
            $mp = $filters['marketplace'];
            if ($this->vpProductsHasColumn('marketplace_vendor')) {
                $search .= "AND vp_products.marketplace_vendor like '%" . $mp . "%'";
            } elseif ($this->vpProductsHasColumn('marketplace')) {
                $search .= "AND vp_products.marketplace like '%" . $mp . "%'";
            }
        }
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
        $sql = "SELECT * FROM vp_products WHERE (item_code LIKE ? OR title LIKE ?)";
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('ss', $searchTerm, $searchTerm);
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
    public function updateProductFromApi($productData)
    {
        $updatedCount = 0;
        // print_array($productData);
        // exit;
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
                //echo "Updating single itemcode: ".$product['itemcode']."<br/>";           
                $stmt = $this->db->prepare("UPDATE vp_products SET asin = ?, local_stock = ?, upc = ?, location = ?, fba_in = ?, fba_us = ?, leadtime = ?, instock_leadtime = ?, permanently_available = ?, numsold = ?, numsold_india = ?, numsold_global = ?, lastsold = ?, vendor = ?, shippingfee = ?, sourcingfee = ?, price = ?, price_india = ?, price_india_suggested = ?, mrp_india = ?, permanent_discount = ?, discount_global = ?, discount_india = ?, updated_at = ?, sku = ? WHERE item_code = ? AND COALESCE(color,'') = ? AND COALESCE(size,'') = ?");
                if ($stmt) {
                    // $title = isset($product['title']) ? $product['title'] : '';
                    $sku = isset($product['sku']) && !empty($product['sku']) ? $product['sku'] : $product['itemcode'];
                    $color = trim((string)($product['color'] ?? ''));
                    $size = trim((string)($product['size'] ?? ''));
                    // $costPrice = isset($product['cost_price']) ? (float)$product['cost_price'] : 0.0;
                    // $gst = isset($product['gst']) ? (float)$product['gst'] : 0.0;
                    // $hsn = isset($product['hsn']) ? $product['hsn'] : '';
                    // $description = isset($product['description']) ? $product['description'] : '';
                    // $image = isset($product['image']) ? $product['image'] : '';
                    // $stockQuantity = isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : 0;
                    $asin = isset($product['asin']) ? $product['asin'] : '';
                    $localStock = isset($product['local_stock']) ? (int)$product['local_stock'] : 0;
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
                    $updated_at = date('Y-m-d H:i:s');
                    $stmt->bind_param(
                        'sissiiiiiiiiisdddddddddsssss',
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
                        $updated_at,
                        $sku,
                        $product['itemcode'],
                        $color,
                        $size
                    );
                    //echo "Executing update for itemcode: ".$product['itemcode']."<br/>";                          
                    if ($stmt->execute()) {
                        $updatedCount++;
                    }
                    if ($stmt->error) {
                        return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
                    }
                    $stmt->close();
                }
                if (isset($product['variations'])) {
                    foreach ($product['variations'] as $variation) {
                        //echo "Updating variations itemcode: ".$product['itemcode']."<br/>";
                        $stmt = $this->db->prepare("UPDATE vp_products SET asin = ?, local_stock = ?, upc = ?, location = ?, fba_in = ?, fba_us = ?, leadtime = ?, instock_leadtime = ?, permanently_available = ?, numsold = ?, numsold_india = ?, numsold_global = ?, lastsold = ?, vendor = ?, shippingfee = ?, sourcingfee = ?, price = ?, price_india = ?, price_india_suggested = ?, mrp_india = ?, permanent_discount = ?, discount_global = ?, discount_india = ?, updated_at = ?, sku = ? WHERE item_code = ? AND COALESCE(color,'') = ? AND COALESCE(size,'') = ?");
                        if ($stmt) {
                            // $title = isset($product['title']) ? $product['title'] : '';
                            $sku = isset($variation['sku']) && !empty($variation['sku']) ? $variation['sku'] : $product['itemcode'];
                            $color = trim((string)($variation['color'] ?? ''));
                            $size = trim((string)($variation['size'] ?? ''));
                            // $costPrice = isset($product['cost_price']) ? (float)$product['cost_price'] : 0.0;
                            // $gst = isset($product['gst']) ? (float)$product['gst'] : 0.0;
                            // $hsn = isset($product['hsn']) ? $product['hsn'] : '';
                            // $description = isset($product['description']) ? $product['description'] : '';
                            // $image = isset($product['image']) ? $product['image'] : '';
                            // $stockQuantity = isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : 0;
                            $asin = isset($variation['asin']) ? $variation['asin'] : '';
                            $localStock = isset($variation['local_stock']) ? (int)$variation['local_stock'] : 0;
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
                            $updated_at = date('Y-m-d H:i:s');
                            $stmt->bind_param(
                                'sissiiiiiiiiisdddddddddsssss',
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
                                $updated_at,
                                $sku,
                                $product['itemcode'],
                                $color,
                                $size
                            );
                            if ($stmt->execute()) {
                                $updatedCount++;
                            }
                            if ($stmt->error) {
                                return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
                            }
                            $stmt->close();
                        }
                    }
                }
            }
        }
        return ['success' => true, 'updated_count' => $updatedCount, 'message' => 'Products updated successfully.'];
    }
    public function findByItemCodeSizeColor($code, $size, $color)
    {
        $sql = "SELECT * FROM vp_products WHERE item_code = ? AND size = ? AND color = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $code, $size, $color);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res;
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
                FROM vp_products WHERE sku LIKE ? ORDER BY sku ASC LIMIT 25";
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
        $sql = "SELECT * FROM vp_products WHERE sku LIKE ? OR item_code LIKE ? ORDER BY item_code ASC LIMIT 20";
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
        $data['leadtime'] = $this->normalizeIntValue($data['leadtime'] ?? null, 0);
        $data['instock_leadtime'] = $this->normalizeIntValue($data['instock_leadtime'] ?? null, 0);
        $sql = "INSERT INTO vp_products (item_code, sku, size, color, title, image, local_stock, itemprice, finalprice,  groupname, material, cost_price, gst, hsn, description, asin, upc, location, fba_in, fba_us, leadtime, instock_leadtime, permanently_available, numsold, numsold_india, numsold_global, lastsold, vendor, shippingfee, sourcingfee, price, price_india, price_india_suggested, mrp_india, permanent_discount, discount_global, discount_india, product_weight, product_weight_unit, prod_height, prod_width, prod_length, length_unit, created_on, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'ssssssdddsssisssssssssssssisddddddddddsiiisss',
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
            $data['updated_at']
        );
        if ($stmt->execute()) {
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
        return $stmt->execute();
    }
    public function getProductByItemCode($item_code)
    {
        $sql = "SELECT * FROM vp_products WHERE item_code = ?";
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
        $statusText = "Purchase CREATED (SKU : ".$data['sku'].") Qty: " . (int)$data['quantity'];

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
            $statusText = "Purchase STATUS UPDATE (SKU : ".$data['sku'].")";

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
                $statusText = "Purchased (SKU : ".$sku.") Qty: " . $qty;
                
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
                $statusText = "Purchase PARTIAL (SKU : ".$sku.") Qty: " . $consumedQty;
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



    public function getProductByskuExact($sku)
    {
        $sql = "SELECT * FROM vp_products WHERE sku = ? LIMIT 1";
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
        $sql = "SELECT sm.*, ea.address_title AS warehouse_name
                FROM vp_stock_movements sm
                LEFT JOIN exotic_address ea ON sm.warehouse_id = ea.id
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

    public function insertStockMovement($data){
        $this->db->begin_transaction();
        try {
            // 1. Get current stock from vp_products for calculation
            $stmt = $this->db->prepare("SELECT local_stock FROM vp_products WHERE id = ?");
            $stmt->bind_param('i', $data['product_id']);
            $stmt->execute();
            $res = $stmt->get_result();
            $product = $res->fetch_assoc();
            
            if (!$product) throw new Exception("Product not found");

            $current_stock = (int)$product['local_stock'];
            $adj_qty = (int)$data['quantity'];

            // 2. Calculate New Stock
            if ($data['movement_type'] === 'IN' || $data['movement_type'] === 'TRANSFER_IN') {
                $new_stock = $current_stock + $adj_qty;
            } else {
                $new_stock = $current_stock - $adj_qty;
            }

            // 3. Update local_stock in vp_products table
            $updateSql = "UPDATE vp_products SET local_stock = ? WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->bind_param('ii', $new_stock, $data['product_id']);
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update master stock: " . $this->db->error);
            }

            // 4. Insert into vp_stock_movements (History)
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

            $insertStmt->bind_param(
                'isssssssiiisss', 
                $data['product_id'], $data['sku'], $data['item_code'], 
                $data['size'], $data['color'], $data['warehouse_id'], 
                $data['location'], $data['movement_type'], $adj_qty, 
                $new_stock, $data['user_id'], $ref_type, $ref_id, $data['reason']
            );

            if (!$insertStmt->execute()) {
                throw new Exception("Failed to record history: " . $this->db->error);
            }

            // If everything is fine, commit changes
            $this->db->commit();
            return ['success' => true, 'message' => 'Stock updated and history recorded.'];

        } catch (Exception $e) {
            // Rollback if any step fails
            $this->db->rollback();
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

        if (!empty($filters['type']) && in_array($filters['type'], ['IN', 'OUT','TRANSFER_IN','TRANSFER_OUT','OPENING_STOCK'])) {
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

        $sql = "SELECT sm.*, ea.address_title AS warehouse_name 
                FROM vp_stock_movements sm 
                LEFT JOIN exotic_address ea ON sm.warehouse_id = ea.id 
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

        if (!empty($filters['type']) && in_array($filters['type'], ['IN', 'OUT','TRANSFER_IN','TRANSFER_OUT','OPENING_STOCK'])) {
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
    public function setProductLimits($productId, $minStock, $maxStock){
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
}
