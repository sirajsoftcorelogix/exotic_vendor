<?php

class Category
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
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
        $sql = "SELECT id, parent_id, name, display_name, category, parent, initial, is_active FROM category" . $whereClause . " ORDER BY {$sortByCol} {$sortDirection} LIMIT ? OFFSET ?";
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
        $stmt = $this->conn->prepare("SELECT id, parent_id, name, display_name, category, parent, initial, is_active FROM category WHERE id = ? LIMIT 1");
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

        $parent = trim((string) ($data['parent'] ?? ''));
        $parentId = (int) ($data['parent_id'] ?? 0);
        $initial = trim((string) ($data['initial'] ?? ''));
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
            $insertStmt = $this->conn->prepare("INSERT INTO category (parent_id, name, display_name, category, parent, initial, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");

            foreach ($apiCategories as $item) {
                if (!is_array($item)) {
                    $failedCount++;
                    continue;
                }

                $apiCatId = isset($item['category']) ? (int) $item['category'] : 0;
                if ($apiCatId <= 0) {
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
                    $name = isset($item['name']) ? trim((string) $item['name']) : '';
                    $displayName = isset($item['display_name']) && trim((string) $item['display_name']) !== ''
                        ? trim((string) $item['display_name'])
                        : $name;
                    $parent = isset($item['parent']) ? trim((string) $item['parent']) : '';
                    $parentId = isset($item['parent_id']) ? (int) $item['parent_id'] : 0;
                    $initial = isset($item['initial']) ? trim((string) $item['initial']) : '';
                    $isActive = isset($item['is_active']) ? (int) $item['is_active'] : 1;

                    $insertStmt->bind_param('ississi', $parentId, $name, $displayName, $apiCatId, $parent, $initial, $isActive);
                    if ($insertStmt->execute()) {
                        $newAddedCount++;
                    } else {
                        $failedCount++;
                    }
                }
            }

            $checkStmt->close();
            $insertStmt->close();

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
