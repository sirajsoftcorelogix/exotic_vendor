<?php 
class PurchaseOrder {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }
    public function getAllPurchaseOrders( $filters = [] ) {
        $sql = "SELECT purchase_orders.*, vp_vendors.vendor_name AS vendor_name FROM purchase_orders LEFT JOIN vp_vendors ON purchase_orders.vendor_id = vp_vendors.id WHERE 1=1";
        if (!empty($filters['search_text'])) {
            $searchText = $this->db->real_escape_string($filters['search_text']);
            $sql .= " AND (purchase_orders.po_number LIKE '%$searchText%' OR vp_vendors.contact_name LIKE '%$searchText%' OR vp_vendors.vendor_name LIKE '%$searchText%' OR EXISTS (SELECT 1 FROM vp_orders vo WHERE (vo.po_id = purchase_orders.id OR vo.po_number = purchase_orders.po_number) AND vo.order_number LIKE '%$searchText%') OR EXISTS (SELECT 1 FROM vp_po_items poi WHERE poi.purchase_orders_id = purchase_orders.id AND poi.order_number LIKE '%$searchText%'))";
        }
        if (!empty($filters['status_filter'])) {
            $statusFilter = $this->db->real_escape_string($filters['status_filter']);
            $sql .= " AND purchase_orders.status = '$statusFilter'";
        }
        if (!empty($filters['exclude_completed'])) {
            $sql .= " AND purchase_orders.status != 'completed'";
        }
        if (!empty($filters['due_date'])) {
            $dueDate = $this->db->real_escape_string($filters['due_date']);
            $sql .= " AND purchase_orders.expected_delivery_date = '$dueDate'";
        }
        
        // po_amount_from and po_amount_to filter
        if (!empty($filters['po_amount_from'])) {
            $poAmountFrom = (float)$filters['po_amount_from'];
            $sql .= " AND purchase_orders.total_cost >= $poAmountFrom";
        }
        if (!empty($filters['po_amount_to'])) {
            $poAmountTo = (float)$filters['po_amount_to'];
            $sql .= " AND purchase_orders.total_cost <= $poAmountTo";
        }
        //po_number filter
        if (!empty($filters['po_number'])) {
            $poNumber = $this->db->real_escape_string($filters['po_number']);
            $sql .= " AND (purchase_orders.po_number LIKE '%$poNumber%' OR EXISTS (SELECT 1 FROM vp_orders vo WHERE (vo.po_id = purchase_orders.id OR vo.po_number = purchase_orders.po_number) AND vo.order_number LIKE '%$poNumber%') OR EXISTS (SELECT 1 FROM vp_po_items poi WHERE poi.purchase_orders_id = purchase_orders.id AND poi.order_number LIKE '%$poNumber%'))";
        }
        //order_number filter
        if (!empty($filters['order_number'])) {
            $orderNumber = $this->db->real_escape_string($filters['order_number']);
            $sql .= " AND (EXISTS (SELECT 1 FROM vp_orders vo WHERE (vo.po_id = purchase_orders.id OR vo.po_number = purchase_orders.po_number) AND vo.order_number LIKE '%$orderNumber%') OR EXISTS (SELECT 1 FROM vp_po_items poi WHERE poi.purchase_orders_id = purchase_orders.id AND poi.order_number LIKE '%$orderNumber%'))";
        }
        //vendor_name filter
        if (!empty($filters['vendor_name'])) {
            $vendorName = $this->db->real_escape_string($filters['vendor_name']);
            $sql .= " AND vp_vendors.vendor_name LIKE '%$vendorName%'";
        }
        //po_date_from and po_date_to filter
        if (!empty($filters['po_from']) && !empty($filters['po_to'])) {
            $poDateFrom = $this->db->real_escape_string($filters['po_from']);
            $poDateTo = $this->db->real_escape_string($filters['po_to']);
            $sql .= " AND purchase_orders.po_date >= '$poDateFrom' AND purchase_orders.po_date < '$poDateTo'";
        }
        // item_category/item_sub_category filter -> search records in vp_orders linked by po_id
        if (!empty($filters['item_category']) || !empty($filters['item_sub_category'])) {
            $conditions = [];
            if (!empty($filters['item_category'])) {
            $itemCategory = $this->db->real_escape_string($filters['item_category']);
            $conditions[] = "vo.groupname = '$itemCategory'";
            }
            if (!empty($filters['item_sub_category'])) {
            $itemSubCategory = $this->db->real_escape_string($filters['item_sub_category']);
            $conditions[] = "vo.subcategories LIKE '%$itemSubCategory%'";
            }
            if (!empty($conditions)) {
            $sql .= " AND EXISTS (SELECT 1 FROM vp_orders vo WHERE vo.po_id = purchase_orders.id AND " . implode(' AND ', $conditions) . ")";
            }
        }
        // item_code filter -> search vp_po_items (used by custom/stock POs)
        if (!empty($filters['item_code'])) {
            $itemCode = $this->db->real_escape_string($filters['item_code']);
            $sql .= " AND EXISTS (SELECT 1 FROM vp_po_items poi WHERE poi.purchase_orders_id = purchase_orders.id AND poi.item_code LIKE '%$itemCode%')";
        }
        // po_type filter
        if (!empty($filters['po_type'])) {
            $poType = $this->db->real_escape_string($filters['po_type']);
            $sql .= " AND purchase_orders.po_type = '$poType'";
        }  

        $sql .= " ORDER BY purchase_orders.id DESC";
        $result = $this->db->query($sql);
        $purchaseOrders = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $purchaseOrders[] = $row;
            }
        }
        return $purchaseOrders;
    }
    public function createPurchaseOrder($data) {
        $sql = "INSERT INTO purchase_orders (po_number, vendor_id, user_id, expected_delivery_date, delivery_address, notes, terms_and_conditions, total_gst, total_cost, subtotal, shipping_cost, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("siissssdddds",
            $data['po_number'],  
            $data['vendor_id'],
            $data['user_id'],
            $data['expected_delivery_date'], 
            $data['delivery_address'], 
            $data['notes'],
            $data['terms_and_conditions'],
            $data['total_gst'],            
            $data['grand_total'],
            $data['subtotal'],
            $data['shipping_cost'],
            $data['status']
        );
        if ($stmt->execute()) {
            return $this->db->insert_id; // Return the ID of the newly created purchase order
        }
        return false; // Return false on failure
    }
    /**
     * Import Purchase Orders from structured CSV/Excel array rows.
     *
     * @param array $rows Parsed associative array or numeric array from CSV/Excel file
     * @param int $currentUserId ID of user performing import
     * @return array Summary of import operation
     */
    public function importPurchaseOrdersFromData(array $rows, int $currentUserId = 0): array {
        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'No data rows found in file.',
                'imported_pos' => 0,
                'imported_items' => 0,
                'errors' => ['File contains no readable data rows.'],
                'details' => []
            ];
        }

        // 1. Identify Header Row & Normalize Mapping
        $headers = [];
        $dataRows = [];

        // Check if first row is associative or contains header titles
        $firstRow = reset($rows);
        $isAssociative = is_array($firstRow) && !isset($firstRow[0]);

        if (!$isAssociative) {
            // First array element is header row
            $headerRow = array_shift($rows);
            foreach ($headerRow as $colIdx => $colName) {
                $headers[$colIdx] = $this->normalizeImportColumnName((string)$colName);
            }
            foreach ($rows as $rIdx => $rawRow) {
                $mappedRow = [];
                foreach ($rawRow as $cIdx => $val) {
                    $key = $headers[$cIdx] ?? 'col_' . $cIdx;
                    $mappedRow[$key] = trim((string)$val);
                }
                $dataRows[] = $mappedRow;
            }
        } else {
            // Already associative array
            foreach ($rows as $rawRow) {
                $mappedRow = [];
                foreach ($rawRow as $key => $val) {
                    $normKey = $this->normalizeImportColumnName((string)$key);
                    $mappedRow[$normKey] = trim((string)$val);
                }
                $dataRows[] = $mappedRow;
            }
        }

        if (empty($dataRows)) {
            return [
                'success' => false,
                'message' => 'No valid data rows after parsing.',
                'imported_pos' => 0,
                'imported_items' => 0,
                'errors' => ['No valid data rows found after header mapping.'],
                'details' => []
            ];
        }

        // 2. Group Rows by PO Key (po_number or vendor_name + po_date combination)
        $poGroups = [];
        $rowNum = 1;

        foreach ($dataRows as $row) {
            $rowNum++;
            $poNumber = trim((string)($row['po_number'] ?? ''));
            $vendorCode = trim((string)($row['vendor_code'] ?? ''));
            $vendorName = trim((string)($row['vendor_name'] ?? $row['vendor'] ?? ''));
            $poDate = $this->parseImportDate($row['po_date'] ?? '');
            $warehouseCode = trim((string)($row['delivery_address'] ?? $row['warehouse_code'] ?? $row['warehouse'] ?? $row['address'] ?? ''));

            // Determine unique group key
            if ($poNumber !== '') {
                $groupKey = 'PO_NUM_' . strtoupper($poNumber);
            } elseif ($vendorCode !== '' || $vendorName !== '') {
                $vKey = $vendorCode !== '' ? $vendorCode : $vendorName;
                $groupKey = 'PO_VEND_' . strtoupper(preg_replace('/[^a-z0-9]/i', '_', $vKey)) . '_' . ($poDate ?: date('Y-m-d'));
            } else {
                $groupKey = 'PO_ROW_' . $rowNum;
            }

            if (!isset($poGroups[$groupKey])) {
                $poGroups[$groupKey] = [
                    'group_key' => $groupKey,
                    'po_number' => $poNumber,
                    'vendor_name' => $vendorName,
                    'vendor_code' => $vendorCode,
                    'po_date' => $poDate ?: date('Y-m-d'),
                    'expected_delivery_date' => $this->parseImportDate($row['expected_delivery_date'] ?? $row['due_date'] ?? ''),
                    'status' => $this->normalizePoStatus($row['status'] ?? 'pending'),
                    'delivery_address' => $warehouseCode,
                    'notes' => trim((string)($row['notes'] ?? '')),
                    'terms_and_conditions' => trim((string)($row['terms_and_conditions'] ?? $row['terms'] ?? '')),
                    'shipping_cost' => (float)str_replace(',', '', $row['shipping_cost'] ?? '0'),
                    'items' => [],
                    'source_rows' => []
                ];
            }

            // Extract Item Details
            $qty = (float)str_replace(',', '', $row['quantity'] ?? $row['qty'] ?? '0');
            $price = (float)str_replace(',', '', $row['price'] ?? $row['rate'] ?? $row['unit_price'] ?? '0');
            $gst = (float)str_replace(',', '', $row['gst'] ?? $row['gst_percent'] ?? '0');
            $itemCode = trim((string)($row['item_code'] ?? $row['item_no'] ?? ''));
            $sku = trim((string)($row['sku'] ?? ''));
            $title = trim((string)($row['title'] ?? $row['item_title'] ?? $row['description'] ?? ''));

            // Skip empty item lines (Item Title & GST are optional)
            if ($qty <= 0 && $price <= 0 && $itemCode === '' && $sku === '') {
                continue;
            }

            // Item Title is optional; fallback to Item Code, SKU, or default
            if ($title === '') {
                $title = $itemCode !== '' ? $itemCode : ($sku !== '' ? $sku : 'PO Item');
            }

            $poGroups[$groupKey]['items'][] = [
                'order_number' => trim((string)($row['order_number'] ?? '')),
                'item_code' => $itemCode,
                'sku' => $sku,
                'title' => $title,
                'hsn' => trim((string)($row['hsn'] ?? '')),
                'quantity' => $qty > 0 ? $qty : 1.0,
                'price' => $price,
                'gst' => $gst,
                'size' => trim((string)($row['size'] ?? '')),
                'color' => trim((string)($row['color'] ?? '')),
                'image' => trim((string)($row['image'] ?? ''))
            ];

            $poGroups[$groupKey]['source_rows'][] = $rowNum;
        }

        // 3. Process each PO Group into Database
        $totalPosImported = 0;
        $totalItemsImported = 0;
        $skippedPos = 0;
        $errors = [];
        $importedDetails = [];

        foreach ($poGroups as $groupKey => $poData) {
            if (empty($poData['items'])) {
                $skippedPos++;
                $errors[] = "Group {$groupKey}: No valid item lines found.";
                continue;
            }

            // A. Resolve Vendor
            $vendorId = $this->resolveVendorForImport($poData['vendor_name'], $poData['vendor_code'], $currentUserId);
            if (!$vendorId) {
                $skippedPos++;
                $errors[] = "Group {$groupKey}: Could not resolve or create vendor '{$poData['vendor_name']}'.";
                continue;
            }

            // B. Calculate Financial Totals
            $subtotal = 0.0;
            $totalGst = 0.0;

            foreach ($poData['items'] as &$item) {
                $lineSubtotal = $item['quantity'] * $item['price'];
                $lineGst = $lineSubtotal * ($item['gst'] / 100.0);
                $lineAmount = $lineSubtotal + $lineGst;

                $item['amount'] = $lineAmount;
                $subtotal += $lineSubtotal;
                $totalGst += $lineGst;
            }
            unset($item);

            $shippingCost = $poData['shipping_cost'];
            $totalCost = $subtotal + $totalGst + $shippingCost;

            // C. Insert Purchase Order Header
            $finalPoNumber = $poData['po_number'];
            $poId = 0;

            if ($finalPoNumber !== '') {
                // Check if po_number already exists
                $chkStmt = $this->db->prepare("SELECT id FROM purchase_orders WHERE po_number = ? LIMIT 1");
                $chkStmt->bind_param("s", $finalPoNumber);
                $chkStmt->execute();
                $existingRes = $chkStmt->get_result()->fetch_assoc();
                
                if (!empty($existingRes['id'])) {
                    // Update existing PO header or append items
                    $poId = (int)$existingRes['id'];
                }
            }

            if ($poId === 0) {
                $insSql = "INSERT INTO purchase_orders 
                    (po_number, po_type, vendor_id, user_id, po_date, expected_delivery_date, delivery_address, notes, terms_and_conditions, subtotal, total_gst, shipping_cost, total_cost, status) 
                    VALUES (?, 'custom', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $dummyPoNum = $finalPoNumber !== '' ? $finalPoNumber : 'TEMP-' . uniqid();
                $expectedDate = $poData['expected_delivery_date'] !== '' ? $poData['expected_delivery_date'] : null;

                $insStmt = $this->db->prepare($insSql);
                $insStmt->bind_param(
                    "siisssssdddds",
                    $dummyPoNum,
                    $vendorId,
                    $currentUserId,
                    $poData['po_date'],
                    $expectedDate,
                    $poData['delivery_address'],
                    $poData['notes'],
                    $poData['terms_and_conditions'],
                    $subtotal,
                    $totalGst,
                    $shippingCost,
                    $totalCost,
                    $poData['status']
                );

                if (!$insStmt->execute()) {
                    $skippedPos++;
                    $errors[] = "Group {$groupKey}: Failed to insert purchase order header ({$this->db->error}).";
                    continue;
                }

                $poId = (int)$this->db->insert_id;

                // Auto-generate PO number if not provided
                if ($finalPoNumber === '') {
                    $finalPoNumber = 'PO-' . date('Y') . '-' . str_pad($poId, 6, '0', STR_PAD_LEFT);
                    $updPoNo = $this->db->prepare("UPDATE purchase_orders SET po_number = ? WHERE id = ?");
                    $updPoNo->bind_param("si", $finalPoNumber, $poId);
                    $updPoNo->execute();
                }
            }

            // D. Insert PO Items
            $itemsInsertedCount = 0;
            $insItemStmt = $this->db->prepare("INSERT INTO vp_po_items 
                (purchase_orders_id, order_number, title, image, hsn, gst, quantity, price, amount, item_code, size, color, sku) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($poData['items'] as $it) {
                $insItemStmt->bind_param(
                    "issssddddssss",
                    $poId,
                    $it['order_number'],
                    $it['title'],
                    $it['image'],
                    $it['hsn'],
                    $it['gst'],
                    $it['quantity'],
                    $it['price'],
                    $it['amount'],
                    $it['item_code'],
                    $it['size'],
                    $it['color'],
                    $it['sku']
                );

                if ($insItemStmt->execute()) {
                    $itemsInsertedCount++;
                }
            }

            $totalPosImported++;
            $totalItemsImported += $itemsInsertedCount;

            $importedDetails[] = [
                'po_id' => $poId,
                'po_number' => $finalPoNumber,
                'vendor_id' => $vendorId,
                'vendor_name' => $poData['vendor_name'],
                'items_count' => $itemsInsertedCount,
                'total_cost' => $totalCost,
                'status' => $poData['status']
            ];
        }

        return [
            'success' => $totalPosImported > 0,
            'message' => $totalPosImported > 0 
                ? "Successfully imported {$totalPosImported} purchase order(s) with {$totalItemsImported} line item(s)."
                : "No purchase orders could be imported.",
            'imported_pos' => $totalPosImported,
            'imported_items' => $totalItemsImported,
            'skipped_pos' => $skippedPos,
            'errors' => $errors,
            'details' => $importedDetails
        ];
    }

    /**
     * Normalize column names from CSV/Excel headers.
     */
    private function normalizeImportColumnName(string $col): string {
        $clean = strtolower(trim($col));
        $clean = str_replace([' ', '-', '_', '.', '#'], '_', $clean);
        $clean = preg_replace('/_+/', '_', $clean);

        $mapping = [
            'vendor_code' => 'vendor_code',
            'vendor_id' => 'vendor_code',
            'supplier_code' => 'vendor_code',
            'supplier_id' => 'vendor_code',
            'v_code' => 'vendor_code',
            'vcode' => 'vendor_code',
            'po_num' => 'po_number',
            'po_no' => 'po_number',
            'ponumber' => 'po_number',
            'purchase_order_number' => 'po_number',
            'purchase_order_no' => 'po_number',
            'vendor' => 'vendor_name',
            'supplier' => 'vendor_name',
            'vendor_title' => 'vendor_name',
            'supplier_name' => 'vendor_name',
            'order_date' => 'po_date',
            'date' => 'po_date',
            'delivery_due_date' => 'expected_delivery_date',
            'due_date' => 'expected_delivery_date',
            'delivery_date' => 'expected_delivery_date',
            'expected_date' => 'expected_delivery_date',
            'item' => 'item_code',
            'item_no' => 'item_code',
            'product_code' => 'item_code',
            'sku_code' => 'sku',
            'item_title' => 'title',
            'product_name' => 'title',
            'item_name' => 'title',
            'description' => 'title',
            'qty' => 'quantity',
            'count' => 'quantity',
            'rate' => 'price',
            'unit_price' => 'price',
            'cost' => 'price',
            'price_per_unit' => 'price',
            'tax' => 'gst',
            'gst_rate' => 'gst',
            'gst_percent' => 'gst',
            'tax_percent' => 'gst',
            'shipping' => 'shipping_cost',
            'freight' => 'shipping_cost',
            'terms' => 'terms_and_conditions',
            'address' => 'delivery_address',
            'warehouse_code' => 'delivery_address',
            'warehouse' => 'delivery_address',
            'wh_code' => 'delivery_address',
            'wh' => 'delivery_address',
            'location_code' => 'delivery_address',
            'warehouse_id' => 'delivery_address'
        ];

        return $mapping[$clean] ?? $clean;
    }

    /**
     * Parse date string into YYYY-MM-DD.
     */
    private function parseImportDate(string $rawDate): string {
        $rawDate = trim($rawDate);
        if ($rawDate === '') {
            return '';
        }

        // Try standard formats
        $time = strtotime($rawDate);
        if ($time !== false && $time > 0) {
            return date('Y-m-d', $time);
        }

        // Try DD/MM/YYYY or DD-MM-YYYY
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $rawDate, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        return '';
    }

    /**
     * Normalize status string to allowed PO status.
     */
    private function normalizePoStatus(string $statusStr): string {
        $statusStr = strtolower(trim($statusStr));
        $allowed = ['pending', 'ordered', 'received', 'completed', 'draft', 'cancelled'];
        
        if (in_array($statusStr, $allowed)) {
            return $statusStr;
        }

        if (in_array($statusStr, ['approved', 'open', 'active'])) {
            return 'ordered';
        }
        if (in_array($statusStr, ['done', 'delivered', 'inbound', 'closed'])) {
            return 'received';
        }

        return 'pending';
    }

    /**
     * Helper to find or create vendor in database during import.
     */
    private function resolveVendorForImport(string &$vendorName, string $vendorCode = '', int $userId = 0): int {
        $vendorName = trim($vendorName);
        $vendorCode = trim($vendorCode);

        // 1. Try lookup by Vendor Code / Vendor ID
        if ($vendorCode !== '') {
            $stmt = $this->db->prepare("SELECT id, vendor_name FROM vp_vendors WHERE vendor_code = ? OR vendor_id = ? OR CAST(id AS CHAR) = ? LIMIT 1");
            $stmt->bind_param("sss", $vendorCode, $vendorCode, $vendorCode);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!empty($row['id'])) {
                if ($vendorName === '' && !empty($row['vendor_name'])) {
                    $vendorName = $row['vendor_name'];
                }
                return (int)$row['id'];
            }
        }

        // 2. Try lookup by Vendor Name
        if ($vendorName !== '') {
            $stmt = $this->db->prepare("SELECT id FROM vp_vendors WHERE LOWER(TRIM(vendor_name)) = LOWER(TRIM(?)) LIMIT 1");
            $stmt->bind_param("s", $vendorName);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!empty($row['id'])) {
                return (int)$row['id'];
            }
        }

        // 3. Create new vendor if vendorCode or vendorName is provided
        if ($vendorCode !== '' || $vendorName !== '') {
            $nameToUse = $vendorName !== '' ? $vendorName : 'Vendor ' . $vendorCode;
            $codeToUse = $vendorCode !== '' ? $vendorCode : 'VEND-' . strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $nameToUse), 0, 6));

            $ins = $this->db->prepare("INSERT INTO vp_vendors (vendor_code, vendor_name, user_id, is_active, groupname, country) VALUES (?, ?, ?, 'active', 'Imported', 'India')");
            $ins->bind_param("ssi", $codeToUse, $nameToUse, $userId);
            if ($ins->execute()) {
                if ($vendorName === '') {
                    $vendorName = $nameToUse;
                }
                return (int)$this->db->insert_id;
            }
        }

        // 4. Fallback to default active vendor
        $res = $this->db->query("SELECT id, vendor_name FROM vp_vendors WHERE is_active = 'active' OR is_active = 1 ORDER BY id ASC LIMIT 1");
        if ($row = $res->fetch_assoc()) {
            if ($vendorName === '' && !empty($row['vendor_name'])) {
                $vendorName = $row['vendor_name'];
            }
            return (int)$row['id'];
        }

        return 1;
    }

    public function cancelPurchaseOrder($id) {
        $sql = "UPDATE purchase_orders SET status = 'cancelled' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    public function getPurchaseOrder($id) {
        $sql = "SELECT purchase_orders.*, vp_vendors.contact_name AS vendor_name, vp_vendors.vendor_phone AS vendor_phone, vp_vendors.vendor_email AS vendor_email FROM purchase_orders LEFT JOIN vp_vendors ON purchase_orders.vendor_id = vp_vendors.id WHERE purchase_orders.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function updatePurchaseOrder($id, $data) {
        $sql = "UPDATE purchase_orders SET vendor_id = ?, user_id = ?, expected_delivery_date = ?, delivery_address = ?, notes = ?, terms_and_conditions = ?, total_gst = ?, total_cost = ?, subtotal = ?, shipping_cost = ?, status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iisssssdddsi",
            $data['vendor_id'],
            $data['user_id'],
            $data['expected_delivery_date'],
            $data['delivery_address'],
            $data['notes'],
            $data['terms_and_conditions'],
            $data['total_gst'],
            $data['total_cost'],
            $data['subtotal'],
            $data['shipping_cost'],
            $data['status'],
            $id
        );
        return $stmt->execute();
    }
    public function updatePurchaseOrderNumber($id, $data) {
        $sql = "UPDATE purchase_orders SET po_number = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si",
            $data['po_number'],
            $id
        );
        return $stmt->execute();
    }
    public function deletePurchaseOrder($id) {
        $sql = "DELETE FROM purchase_orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    public function updateStatus($id, $status) {
        $allowedStatuses = ['pending', 'ordered', 'received', 'cancelled', 'draft', 'completed']; // Define allowed statuses
        if (!in_array($status, $allowedStatuses)) {
            return false; // Invalid status
        }
        $received_at = ($status === 'received') ? date('Y-m-d H:i:s') : null;
        if ($received_at) {
            $sql = "UPDATE purchase_orders SET status = ?, received_at = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ssi", $status, $received_at, $id);
        } else {
            $sql = "UPDATE purchase_orders SET status = ?, received_at = NULL WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("si", $status, $id);
        }        
        return $stmt->execute();
    }
    public function toggleStar($id) {
        // First, get the current star status
        $sql = "SELECT flag_star FROM purchase_orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $newStatus = $row['flag_star'] ? 0 : 1; // Toggle the status
            // Update the star status
            $updateSql = "UPDATE purchase_orders SET flag_star = ? WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->bind_param("ii", $newStatus, $id);
            return $updateStmt->execute();
        }
        return false; // Return false if the purchase order was not found
    }
    public function get_po_status_log($po_id) {
        $sql = "SELECT vpl.*, u.name AS changed_by_username FROM vp_po_status_log vpl LEFT JOIN vp_users u ON vpl.changed_by = u.id WHERE vpl.po_id = ? ORDER BY vpl.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $po_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        return $logs;
    }
    public function updateInvoicePath($id, $invoicePath) {
        $sql = "UPDATE purchase_orders SET vendor_invoice = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $invoicePath, $id);
        return $stmt->execute();
    }
    public function updateCancellationReason($id, $reason) {
        $sql = "UPDATE purchase_orders SET cancellation_reason = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $reason, $id);
        return $stmt->execute();
    }
    public function addPurchaseOrder($data) {
        $sql = "INSERT INTO purchase_orders (po_number, po_type, vendor_id, user_id, expected_delivery_date, delivery_address, notes, terms_and_conditions, total_gst, total_cost, subtotal, shipping_cost, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";    
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssiissssdddds",
            $data['po_number'],  
            $data['po_type'],
            $data['vendor_id'],
            $data['user_id'],
            $data['expected_delivery_date'], 
            $data['delivery_address'], 
            $data['notes'],
            $data['terms_and_conditions'],
            $data['total_gst'],            
            $data['total_cost'],
            $data['subtotal'],
            $data['shipping_cost'],
            $data['status']
        );
        if ($stmt->execute()) {
            return $this->db->insert_id; // Return the ID of the newly created purchase order
        }
        return false;
    }

    /**
     * Open vendor purchase orders containing a SKU (pending, ordered, or draft).
     *
     * @return list<array{po_id:int,po_number:string,vendor_name:string,qty:float,status:string,expected_delivery_date:?string,po_date:?string,sku:string}>
     */
    public function getOpenPurchaseOrdersForSku(
        string $sku,
        string $itemCode = '',
        string $size = '',
        string $color = ''
    ): array {
        $sql = 'SELECT po.id AS po_id, po.po_number, po.status, po.po_date, po.expected_delivery_date,
                       COALESCE(v.vendor_name, \'\') AS vendor_name,
                       poi.quantity AS qty, poi.sku, poi.item_code, poi.size, poi.color
                FROM vp_po_items poi
                INNER JOIN purchase_orders po ON po.id = poi.purchase_orders_id
                LEFT JOIN vp_vendors v ON v.id = po.vendor_id
                WHERE poi.sku = ?
                  AND po.status IN (\'pending\', \'ordered\', \'draft\')
                  AND (TRIM(COALESCE(poi.item_code, "")) = ? OR ? = "")
                  AND (TRIM(COALESCE(poi.size, "")) = ? OR ? = "")
                  AND (TRIM(COALESCE(poi.color, "")) = ? OR ? = "")
                ORDER BY po.po_date DESC, po.id DESC, poi.id DESC';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param(
            'sssssss',
            $sku,
            $itemCode,
            $itemCode,
            $size,
            $size,
            $color,
            $color
        );
        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }
        $res = $stmt->get_result();

        $out = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out[] = [
                    'po_id' => (int) ($row['po_id'] ?? 0),
                    'po_number' => trim((string) ($row['po_number'] ?? '')),
                    'vendor_name' => trim((string) ($row['vendor_name'] ?? '')),
                    'qty' => (float) ($row['qty'] ?? 0),
                    'status' => trim((string) ($row['status'] ?? '')),
                    'expected_delivery_date' => $row['expected_delivery_date'] ?? null,
                    'po_date' => $row['po_date'] ?? null,
                    'sku' => trim((string) ($row['sku'] ?? $sku)),
                ];
            }
        }
        $stmt->close();

        return $out;
    }

    public function getPendingQtyForSku(string $sku): int
    {
        $sku = trim($sku);
        if ($sku === '') {
            return 0;
        }

        $sql = 'SELECT COALESCE(SUM(poi.quantity), 0) AS pending_qty
                FROM vp_po_items poi
                INNER JOIN purchase_orders po ON po.id = poi.purchase_orders_id
                WHERE poi.sku = ?
                  AND po.status IN (\'pending\', \'ordered\', \'draft\')';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return max(0, (int) ($row['pending_qty'] ?? 0));
    }
}