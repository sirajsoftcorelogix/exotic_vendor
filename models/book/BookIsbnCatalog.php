<?php

require_once __DIR__ . '/../inbounding/Inbounding.php';
require_once __DIR__ . '/../product/product.php';

/**
 * Local ISBN metadata from vp_products / prior vp_inbound rows.
 */
class BookIsbnCatalog
{
    private mysqli $conn;
    private Inbounding $inbounding;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->inbounding = new Inbounding($conn);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByNormalizedIsbn(string $normalizedIsbn): ?array
    {
        $normalizedIsbn = trim($normalizedIsbn);
        if ($normalizedIsbn === '') {
            return null;
        }

        $productRow = $this->findProductRow($normalizedIsbn);
        $inboundRow = $this->findInboundRow($normalizedIsbn);

        if ($productRow === null && $inboundRow === null) {
            return null;
        }

        if ($productRow === null && $inboundRow !== null && !empty($inboundRow['Item_code'])) {
            $productRow = $this->findProductRowByItemCode((string) $inboundRow['Item_code']);
        }

        $title = trim((string) ($productRow['title'] ?? ''));
        if ($title === '') {
            return null;
        }

        $authors = $this->resolveAuthorNames($inboundRow, $productRow);
        $publisher = $this->resolvePublisherName($inboundRow, $productRow);

        $pubDate = trim((string) ($productRow['publication_date'] ?? $inboundRow['publication_date'] ?? ''));
        if ($pubDate === '' || $pubDate === '0000-00-00') {
            $pubDate = '';
        }

        $pages = trim((string) ($productRow['pages'] ?? $inboundRow['pages'] ?? ''));
        $coverType = trim((string) ($productRow['cover_type'] ?? $inboundRow['cover_type'] ?? ''));
        $language = trim((string) ($productRow['language'] ?? $inboundRow['language'] ?? ''));
        $edition = trim((string) ($productRow['edition'] ?? $inboundRow['edition'] ?? ''));
        $description = trim((string) ($productRow['snippet_description'] ?? $productRow['description'] ?? ''));

        $coverUrl = '';
        if (!empty($productRow['image'])) {
            $coverUrl = Product::vendorApiImageStorageValue((string) $productRow['image']);
        }

        return [
            'title' => $title,
            'authors' => $authors,
            'publisher' => $publisher,
            'pages' => $pages,
            'publication_date' => $pubDate,
            'cover_type' => $coverType,
            'language' => $language,
            'edition' => $edition,
            'description' => $description,
            'cover_url' => $coverUrl,
            'isbn' => $normalizedIsbn,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findProductRow(string $normalizedIsbn): ?array
    {
        if (!$this->tableHasColumn('vp_products', 'isbn')) {
            return null;
        }

        $sql = "SELECT id, item_code, title, image, isbn, cover_type, edition, publication_date, language, pages,
                       snippet_description, description, edited_by
                FROM vp_products
                WHERE REPLACE(REPLACE(REPLACE(TRIM(isbn), '-', ''), ' ', ''), '.', '') = ?
                  AND TRIM(COALESCE(isbn, '')) <> ''
                ORDER BY id DESC
                LIMIT 1";

        return $this->fetchRow($sql, 's', [$normalizedIsbn]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findProductRowByItemCode(string $itemCode): ?array
    {
        $itemCode = trim($itemCode);
        if ($itemCode === '') {
            return null;
        }

        $sql = "SELECT id, item_code, title, image, isbn, cover_type, edition, publication_date, language, pages,
                       snippet_description, description, edited_by
                FROM vp_products
                WHERE item_code = ?
                ORDER BY id DESC
                LIMIT 1";

        return $this->fetchRow($sql, 's', [$itemCode]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findInboundRow(string $normalizedIsbn): ?array
    {
        if (!$this->tableHasColumn('vp_inbound', 'isbn')) {
            return null;
        }

        $sql = "SELECT Item_code, author, edited_by, compiled_by, translated_by, commentary_by,
                       publisher, isbn, cover_type, edition, publication_date, language, pages
                FROM vp_inbound
                WHERE REPLACE(REPLACE(REPLACE(TRIM(isbn), '-', ''), ' ', ''), '.', '') = ?
                  AND TRIM(COALESCE(isbn, '')) <> ''
                ORDER BY id DESC
                LIMIT 1";

        return $this->fetchRow($sql, 's', [$normalizedIsbn]);
    }

    /**
     * @param array<string, mixed>|null $inboundRow
     * @param array<string, mixed>|null $productRow
     * @return list<string>
     */
    private function resolveAuthorNames(?array $inboundRow, ?array $productRow): array
    {
        $names = [];

        if ($inboundRow !== null) {
            foreach (['author', 'compiled_by', 'edited_by', 'translated_by', 'commentary_by'] as $field) {
                foreach ($this->inbounding->resolveInboundAuthorNameList($inboundRow[$field] ?? '') as $name) {
                    $name = trim($name);
                    if ($name !== '' && !in_array($name, $names, true)) {
                        $names[] = $name;
                    }
                }
            }
        }

        if ($names === [] && $productRow !== null && !empty($productRow['edited_by'])) {
            foreach ($this->inbounding->resolveInboundAuthorNameList($productRow['edited_by']) as $name) {
                $name = trim($name);
                if ($name !== '' && !in_array($name, $names, true)) {
                    $names[] = $name;
                }
            }
        }

        return $names;
    }

    /**
     * @param array<string, mixed>|null $inboundRow
     * @param array<string, mixed>|null $productRow
     */
    private function resolvePublisherName(?array $inboundRow, ?array $productRow): string
    {
        if ($inboundRow !== null && !empty($inboundRow['publisher'])) {
            $pub = $this->inbounding->getPublisherById((int) $inboundRow['publisher']);
            $name = trim((string) ($pub['name'] ?? $pub['publishers'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        return trim((string) ($productRow['publisher'] ?? ''));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchRow(string $sql, string $types, array $params): ?array
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        $stmt = $this->conn->prepare(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
             LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $result = $stmt->get_result();
        $hasColumn = $result && $result->num_rows > 0;
        $stmt->close();

        return $hasColumn;
    }
}
