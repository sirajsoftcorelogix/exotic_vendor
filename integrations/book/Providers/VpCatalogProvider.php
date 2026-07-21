<?php

require_once __DIR__ . '/../Contracts/BookMetadataProviderInterface.php';
require_once __DIR__ . '/../Dto/BookMetadata.php';
require_once __DIR__ . '/../Support/BookPageFormatter.php';
require_once __DIR__ . '/../Support/PublicationDateNormalizer.php';
require_once __DIR__ . '/../../../models/book/BookIsbnCatalog.php';

class VpCatalogProvider implements BookMetadataProviderInterface
{
    private BookIsbnCatalog $catalog;
    private BookPageFormatter $pageFormatter;
    private PublicationDateNormalizer $dateNormalizer;

    /** @var array<string, mixed> */
    private array $lastLookupStatus = ['state' => 'idle'];

    public function __construct(mysqli $conn)
    {
        $this->catalog = new BookIsbnCatalog($conn);
        $this->pageFormatter = new BookPageFormatter();
        $this->dateNormalizer = new PublicationDateNormalizer();
    }

    public function providerCode(): string
    {
        return 'vp_catalog';
    }

    /**
     * @return array<string, mixed>
     */
    public function getLastLookupStatus(): array
    {
        return $this->lastLookupStatus;
    }

    public function lookupByIsbn(string $normalizedIsbn): ?BookMetadata
    {
        $row = $this->catalog->findByNormalizedIsbn($normalizedIsbn);
        if ($row === null) {
            $this->lastLookupStatus = [
                'state' => 'not_found',
                'label' => 'No match in Exotic product or inbound history for this ISBN.',
            ];

            return null;
        }

        $metadata = new BookMetadata();
        $metadata->sources = [$this->providerCode()];
        $metadata->isbn = (string) ($row['isbn'] ?? $normalizedIsbn);
        $metadata->title = trim((string) ($row['title'] ?? ''));
        $metadata->authors = is_array($row['authors'] ?? null) ? $row['authors'] : [];
        $metadata->publisher = trim((string) ($row['publisher'] ?? ''));
        $metadata->pages = $this->pageFormatter->format($row['pages'] ?? null);
        $metadata->publicationDate = $this->dateNormalizer->normalize((string) ($row['publication_date'] ?? ''));
        $metadata->coverType = trim((string) ($row['cover_type'] ?? ''));
        $metadata->language = trim((string) ($row['language'] ?? ''));
        $metadata->edition = trim((string) ($row['edition'] ?? ''));
        $metadata->description = trim((string) ($row['description'] ?? ''));
        $metadata->coverUrl = trim((string) ($row['cover_url'] ?? ''));

        if ($metadata->title === '') {
            $this->lastLookupStatus = [
                'state' => 'not_found',
                'label' => 'Exotic catalog row found but title is empty.',
            ];

            return null;
        }

        $this->lastLookupStatus = [
            'state' => 'ok',
            'label' => 'Matched in Exotic catalog.',
        ];

        return $metadata;
    }
}
