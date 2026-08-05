<?php

class Category
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->ensureSchema();
    }

    /**
     * Ensure all required columns exist in the category table.
     */
    public function ensureSchema(): void
    {
        $existingCols = array_flip($this->getExistingTableColumns('category'));

        $schemaCols = [
            'seo_title' => 'VARCHAR(255) NULL DEFAULT NULL',
            'h1_title' => 'VARCHAR(255) NULL DEFAULT NULL',
            'url' => 'VARCHAR(255) NULL DEFAULT NULL',
            'unbox_url' => 'VARCHAR(255) NULL DEFAULT NULL',
            'googlecategory' => 'VARCHAR(500) NULL DEFAULT NULL',
            'numproducts' => 'INT NOT NULL DEFAULT 0',
            'nonmenu' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'iscolor' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'indiablock' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'usblock' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'sizechart_name' => 'VARCHAR(255) NULL DEFAULT NULL',
        ];

        foreach ($schemaCols as $col => $definition) {
            if (!isset($existingCols[$col])) {
                @$this->conn->query("ALTER TABLE `category` ADD COLUMN `{$col}` {$definition}");
            }
        }
    }

    /**
     * Fetch paginated list of categories with search and sorting support.
     *
     * @return array{categories:array,totalPages:int,currentPage:int,limit:int,totalRecords:int,search:string,sortBy:string,sortDir:string}
     */
    public function getAll(int $page = 1, int $limit = 20, string $search = '', string $sortBy = 'category', string $sortDir = 'ASC'): array
    {
        $page = max(1, $page);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;
        $offset = ($page - 1) * $limit;

        $allowedSortColumns = [
            'id' => 'id',
            'category' => 'category',
            'name' => 'name',
            'display_name' => 'display_name',
            'parent' => 'parent',
            'initial' => 'initial',
            'is_active' => 'is_active',
        ];
        $sortByCol = $allowedSortColumns[$sortBy] ?? 'category';
        $sortDirection = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

        $whereClause = '';
        $params = [];
        $types = '';

        $trimmedSearch = trim($search);
        if ($trimmedSearch !== '') {
            $whereClause = " WHERE (CAST(category AS CHAR) LIKE ? OR name LIKE ? OR display_name LIKE ? OR parent LIKE ?)";
            $searchPattern = '%' . $trimmedSearch . '%';
            $params = [$searchPattern, $searchPattern, $searchPattern, $searchPattern];
            $types = 'ssss';
        }

        // Total record count query
        $countSql = "SELECT COUNT(*) AS total FROM category" . $whereClause;
        if ($whereClause !== '') {
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->bind_param($types, ...$params);
            $countStmt->execute();
            $countRes = $countStmt->get_result();
            $countRow = $countRes->fetch_assoc();
            $countStmt->close();
        } else {
            $countRes = $this->conn->query($countSql);
            $countRow = $countRes ? $countRes->fetch_assoc() : ['total' => 0];
        }
        $totalRecords = (int) ($countRow['total'] ?? 0);
        $totalPages = $limit > 0 ? (int) ceil($totalRecords / $limit) : 1;

        // Fetch data query
        $sql = "SELECT * FROM category" . $whereClause . " ORDER BY {$sortByCol} {$sortDirection} LIMIT ? OFFSET ?";
        $fetchStmt = $this->conn->prepare($sql);

        if ($whereClause !== '') {
            $fetchTypes = $types . 'ii';
            $fetchParams = array_merge($params, [$limit, $offset]);
            $fetchStmt->bind_param($fetchTypes, ...$fetchParams);
        } else {
            $fetchStmt->bind_param('ii', $limit, $offset);
        }

        $fetchStmt->execute();
        $res = $fetchStmt->get_result();
        $categories = [];
        while ($row = $res->fetch_assoc()) {
            $categories[] = $row;
        }
        $fetchStmt->close();

        return [
            'categories' => $categories,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'limit' => $limit,
            'totalRecords' => $totalRecords,
            'search' => $trimmedSearch,
            'sortBy' => $sortBy,
            'sortDir' => $sortDirection,
        ];
    }

    /**
     * Get a single category record by primary key id.
     */
    public function getCategoryById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->conn->prepare("SELECT * FROM category WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Check if a category exists by Exotic India API category ID (`category.category`).
     */
    public function getCategoryByApiCategoryId(int $apiCategoryId): ?array
    {
        if ($apiCategoryId <= 0) {
            return null;
        }
        $stmt = $this->conn->prepare("SELECT id FROM category WHERE category = ? LIMIT 1");
        $stmt->bind_param('i', $apiCategoryId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Update an existing category by primary key id.
     *
     * @return array{success:bool,message:string}
     */
    public function updateCategory(int $id, array $data): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid category ID.'];
        }

        $existing = $this->getCategoryById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Category not found.'];
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return ['success' => false, 'message' => 'Category name is required.'];
        }

        $displayName = trim((string) ($data['display_name'] ?? ''));
        if ($displayName === '') {
            $displayName = $name;
        }

        $parent = mb_substr(trim((string) ($data['parent'] ?? '')), 0, 255);
        $parentId = (int) ($data['parent_id'] ?? 0);
        $initial = mb_substr(trim((string) ($data['initial'] ?? '')), 0, 3);
        $isActive = isset($data['is_active']) ? (int) $data['is_active'] : 1;
        $isActive = in_array($isActive, [0, 1], true) ? $isActive : 1;

        $stmt = $this->conn->prepare("UPDATE category SET name = ?, display_name = ?, parent = ?, parent_id = ?, initial = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param('sssiiii', $name, $displayName, $parent, $parentId, $initial, $isActive, $id);

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Category updated successfully.'];
        }

        $error = $stmt->error ?: 'Failed to update category.';
        $stmt->close();
        return ['success' => false, 'message' => $error];
    }

    /**
     * Check if a category is in use in vp_inbound or vp_products.
     *
     * @return array{in_use:bool,inbound_count:int,product_count:int}
     */
    public function getCategoryUsage(int $id): array
    {
        if ($id <= 0) {
            return ['in_use' => false, 'inbound_count' => 0, 'product_count' => 0];
        }

        $cat = $this->getCategoryById($id);
        if (!$cat) {
            return ['in_use' => false, 'inbound_count' => 0, 'product_count' => 0];
        }

        $apiCatId = (int) ($cat['category'] ?? 0);
        $catName = trim((string) ($cat['name'] ?? ''));
        $displayName = trim((string) ($cat['display_name'] ?? ''));
        $internalId = (int) ($cat['id'] ?? 0);

        $matchValues = array_unique(array_filter([
            (string) $apiCatId,
            (string) $internalId,
            $catName,
            $displayName,
        ], function ($val) {
            return $val !== '' && $val !== '0';
        }));

        if (empty($matchValues)) {
            return ['in_use' => false, 'inbound_count' => 0, 'product_count' => 0];
        }

        // 1. Check vp_inbound
        $inboundCount = 0;
        $candidateInboundCols = [
            'group_name',
            'category_code',
            'sub_category_code',
            'sub_sub_category_code',
            'search_group',
            'search_category',
            'search_category_string',
            'search_sub_category',
            'search_sub_sub_category',
            'search_cat',
            'search_sub',
            'search_sub_sub',
        ];
        $existingInboundCols = array_intersect($candidateInboundCols, $this->getExistingTableColumns('vp_inbound'));

        if (!empty($existingInboundCols)) {
            $inboundConditions = [];
            $inboundParams = [];
            $inboundTypes = '';

            foreach ($matchValues as $val) {
                foreach ($existingInboundCols as $col) {
                    $inboundConditions[] = "{$col} = ?";
                    $inboundParams[] = $val;
                    $inboundTypes .= 's';

                    $inboundConditions[] = "({$col} IS NOT NULL AND FIND_IN_SET(?, REPLACE(REPLACE({$col}, '|', ','), ' ', '')) > 0)";
                    $inboundParams[] = $val;
                    $inboundTypes .= 's';
                }
            }

            if (!empty($inboundConditions)) {
                $inboundSql = "SELECT COUNT(*) AS c FROM vp_inbound WHERE " . implode(' OR ', $inboundConditions);
                $stmtInbound = $this->conn->prepare($inboundSql);
                if ($stmtInbound) {
                    $stmtInbound->bind_param($inboundTypes, ...$inboundParams);
                    $stmtInbound->execute();
                    $resInbound = $stmtInbound->get_result();
                    if ($resInbound && $rowInbound = $resInbound->fetch_assoc()) {
                        $inboundCount = (int) ($rowInbound['c'] ?? 0);
                    }
                    $stmtInbound->close();
                }
            }
        }

        // 2. Check vp_products
        $productCount = 0;
        $candidateProductCols = [
            'groupname',
            'category',
            'sub_category',
            'sub_sub_category',
            'search_group',
            'search_category',
            'search_sub_category',
            'search_sub_sub_category',
            'search_cat',
            'search_sub',
            'search_sub_sub',
        ];
        $existingProductCols = array_intersect($candidateProductCols, $this->getExistingTableColumns('vp_products'));

        if (!empty($existingProductCols)) {
            $productConditions = [];
            $productParams = [];
            $productTypes = '';

            foreach ($matchValues as $val) {
                foreach ($existingProductCols as $col) {
                    $productConditions[] = "{$col} = ?";
                    $productParams[] = $val;
                    $productTypes .= 's';

                    $productConditions[] = "({$col} IS NOT NULL AND FIND_IN_SET(?, REPLACE(REPLACE({$col}, '|', ','), ' ', '')) > 0)";
                    $productParams[] = $val;
                    $productTypes .= 's';
                }
            }

            if (!empty($productConditions)) {
                $productSql = "SELECT COUNT(*) AS c FROM vp_products WHERE " . implode(' OR ', $productConditions);
                $stmtProd = $this->conn->prepare($productSql);
                if ($stmtProd) {
                    $stmtProd->bind_param($productTypes, ...$productParams);
                    $stmtProd->execute();
                    $resProd = $stmtProd->get_result();
                    if ($resProd && $rowProd = $resProd->fetch_assoc()) {
                        $productCount = (int) ($rowProd['c'] ?? 0);
                    }
                    $stmtProd->close();
                }
            }
        }

        return [
            'in_use' => ($inboundCount > 0 || $productCount > 0),
            'inbound_count' => $inboundCount,
            'product_count' => $productCount,
        ];
    }

    /**
     * Delete a category record by primary key id.
     *
     * @return array{success:bool,message:string}
     */
    public function deleteCategory(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid category ID.'];
        }

        $existing = $this->getCategoryById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Category not found or already deleted.'];
        }

        $usage = $this->getCategoryUsage($id);
        if ($usage['in_use']) {
            $usedTables = [];
            if ($usage['inbound_count'] > 0) {
                $usedTables[] = sprintf('vp_inbound (%d record%s)', $usage['inbound_count'], $usage['inbound_count'] === 1 ? '' : 's');
            }
            if ($usage['product_count'] > 0) {
                $usedTables[] = sprintf('vp_products (%d product%s)', $usage['product_count'], $usage['product_count'] === 1 ? '' : 's');
            }
            return [
                'success' => false,
                'message' => 'Cannot delete category: it is currently in use in ' . implode(' and ', $usedTables) . '. Please reassign or remove those references first.',
            ];
        }

        $stmt = $this->conn->prepare("DELETE FROM category WHERE id = ?");
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Category deleted successfully.'];
        }

        $error = $stmt->error ?: 'Failed to delete category.';
        $stmt->close();
        return ['success' => false, 'message' => $error];
    }

    /**
     * Perform INSERT-ONLY synchronization from API categories array.
     *
     * Rules:
     * - Compare using SELECT id FROM category WHERE category = API.category LIMIT 1
     * - If record exists -> SKIP completely (never update)
     * - If record missing -> INSERT new row
     * - Process only first occurrence of duplicate Category IDs in API payload
     * - Missing categories in DB not returned by API -> DO NOTHING
     * - Wrap in DB transaction
     * - Calculate execution time & log every sync
     *
     * @return array{success:bool,message:string,summary:array}
     */
    public function syncFromApi(array $apiCategories, int $userId, string $ipAddress): array
    {
        $startTime = microtime(true);
        $receivedCount = count($apiCategories);
        $alreadyExistsCount = 0;
        $newAddedCount = 0;
        $failedCount = 0;
        $seenCategoryIds = [];

        $this->conn->begin_transaction();

        try {
            $checkStmt = $this->conn->prepare("SELECT id FROM category WHERE category = ? LIMIT 1");
            $insertStmt = $this->conn->prepare("
                INSERT INTO category (
                    parent_id, name, display_name, category, parent, initial, is_active,
                    seo_title, h1_title, url, unbox_url, googlecategory,
                    numproducts, nonmenu, iscolor, indiablock, usblock, sizechart_name
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?
                )
            ");

            foreach ($apiCategories as $item) {
                if (!is_array($item)) {
                    $failedCount++;
                    continue;
                }

                if (!isset($item['category']) || trim((string) $item['category']) === '') {
                    $failedCount++;
                    continue;
                }

                $apiCatId = (int) $item['category'];
                if ($apiCatId === 0) {
                    $failedCount++;
                    continue;
                }

                // Duplicate handling: process only first occurrence
                if (isset($seenCategoryIds[$apiCatId])) {
                    continue;
                }
                $seenCategoryIds[$apiCatId] = true;

                // Step 3 & 4: Check if record exists
                $checkStmt->bind_param('i', $apiCatId);
                $checkStmt->execute();
                $checkRes = $checkStmt->get_result();

                if ($checkRes && $checkRes->num_rows > 0) {
                    // Record exists -> Skip completely! Do NOT update anything.
                    $alreadyExistsCount++;
                } else {
                    // Step 5: Record does not exist -> Insert new row
                    // api.linktitle = category.name
                    $rawName = isset($item['linktitle']) && trim((string) $item['linktitle']) !== ''
                        ? trim((string) $item['linktitle'])
                        : (isset($item['name']) ? trim((string) $item['name']) : (isset($item['title']) ? trim((string) $item['title']) : ''));
                    $name = mb_substr($rawName, 0, 100);

                    // api.title = category.display_name
                    $rawDisplayName = isset($item['title']) && trim((string) $item['title']) !== ''
                        ? trim((string) $item['title'])
                        : (isset($item['display_name']) ? trim((string) $item['display_name']) : $rawName);
                    $displayName = mb_substr($rawDisplayName, 0, 100);

                    // api.parent = category.parent
                    $parentStr = isset($item['parent']) ? trim((string) $item['parent']) : '';
                    $parent = mb_substr($parentStr, 0, 255);

                    // parent_id will be resolved dynamically post-sync based on parent -> category mapping
                    $parentId = 0;

                    // category.initial = leave blank if no value found during API pull
                    $rawInitial = isset($item['initial']) ? trim((string) $item['initial']) : '';
                    $initial = mb_substr($rawInitial, 0, 3);

                    // category.is_active = 1 (default)
                    $isActive = isset($item['is_active']) ? (int) $item['is_active'] : 1;

                    // Extended Readonly API Metadata Fields
                    $seoTitle = mb_substr(trim((string) ($item['google_title'] ?? '')), 0, 255);
                    $h1Title = mb_substr(trim((string) ($item['h1title'] ?? '')), 0, 255);
                    $url = mb_substr(trim((string) ($item['url'] ?? '')), 0, 255);
                    $unboxUrl = mb_substr(trim((string) ($item['unbxd_url'] ?? '')), 0, 255);
                    $googleCategory = mb_substr(trim((string) ($item['googlecategory'] ?? '')), 0, 500);
                    $numProducts = (int) ($item['numproducts'] ?? 0);
                    $nonMenu = (int) ($item['nonmenu'] ?? 0);
                    $isColor = (int) ($item['iscolor'] ?? 0);
                    $indiaBlock = (int) ($item['indiablock'] ?? 0);
                    $usBlock = (int) ($item['usblock'] ?? 0);
                    $sizechartName = mb_substr(trim((string) ($item['sizechart_name'] ?? '')), 0, 255);

                    $insertStmt->bind_param(
                        'issississsssiiiiis',
                        $parentId,
                        $name,
                        $displayName,
                        $apiCatId,
                        $parent,
                        $initial,
                        $isActive,
                        $seoTitle,
                        $h1Title,
                        $url,
                        $unboxUrl,
                        $googleCategory,
                        $numProducts,
                        $nonMenu,
                        $isColor,
                        $indiaBlock,
                        $usBlock,
                        $sizechartName
                    );

                    if ($insertStmt->execute()) {
                        $newAddedCount++;
                    } else {
                        $failedCount++;
                    }
                }
            }

            $checkStmt->close();
            $insertStmt->close();

            // Based on category.parent update category.parent_id = parent category.id
            $this->conn->query("
                UPDATE category c
                INNER JOIN category p ON c.parent = p.category
                SET c.parent_id = p.id
                WHERE c.parent IS NOT NULL AND c.parent != '' AND c.parent != '0'
            ");

            $this->conn->query("
                UPDATE category
                SET parent_id = 0
                WHERE parent IS NULL OR parent = '' OR parent = '0'
            ");

            $this->conn->commit();

            $executionTime = round(microtime(true) - $startTime, 2);
            $executionTimeStr = sprintf("%.2f Seconds", $executionTime);

            return [
                'success' => true,
                'message' => 'Synchronization Completed',
                'summary' => [
                    'categories_received' => $receivedCount,
                    'already_exists' => $alreadyExistsCount,
                    'new_categories_added' => $newAddedCount,
                    'failed' => $failedCount,
                    'execution_time' => $executionTimeStr,
                ],
            ];
        } catch (Throwable $e) {
            $this->conn->rollback();

            $executionTime = round(microtime(true) - $startTime, 2);
            $executionTimeStr = sprintf("%.2f Seconds", $executionTime);

            return [
                'success' => false,
                'message' => 'Database error during synchronization: ' . $e->getMessage(),
                'summary' => [
                    'categories_received' => $receivedCount,
                    'already_exists' => $alreadyExistsCount,
                    'new_categories_added' => $newAddedCount,
                    'failed' => $failedCount + ($receivedCount - $alreadyExistsCount - $newAddedCount - $failedCount),
                    'execution_time' => $executionTimeStr,
                ],
            ];
        }
    }

    /**
     * Get existing column names for a given table.
     *
     * @return array<string>
     */
    private function getExistingTableColumns(string $table): array
    {
        $columns = [];
        $escaped = $this->conn->real_escape_string($table);
        $res = $this->conn->query("SHOW COLUMNS FROM `{$escaped}`");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                if (!empty($row['Field'])) {
                    $columns[] = $row['Field'];
                }
            }
        }
        return $columns;
    }

    /**
     * Legacy method retained for backward compatibility with existing category markup calls.
     */
    public function getCategoryData(string $value = ''): array
    {
        $sql = "SELECT c.*, m.markup_perct 
                FROM `category` c 
                LEFT JOIN `category_markup` m ON c.category = m.category_id 
                WHERE c.parent = 0";

        $res = $this->conn->query($sql);
        $category = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        return ['category' => $category];
    }

    /**
     * Legacy method retained for backward compatibility with existing category markup calls.
     */
    public function updateCategoryMarkups(array $markupsData): bool
    {
        foreach ($markupsData as $cat_ref => $percent) {
            $cat_ref = (int) $cat_ref;
            $percent = (float) $percent;

            $sql = "INSERT INTO `category_markup` (category_id, markup_perct) 
                    VALUES ($cat_ref, $percent) 
                    ON DUPLICATE KEY UPDATE markup_perct = $percent";

            if (!$this->conn->query($sql)) {
                return false;
            }
        }
        return true;
    }
}
