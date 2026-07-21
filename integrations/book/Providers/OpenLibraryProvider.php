<?php

require_once __DIR__ . '/../../shared/Http/HttpClient.php';
require_once __DIR__ . '/../Contracts/BookMetadataProviderInterface.php';
require_once __DIR__ . '/../Dto/BookMetadata.php';
require_once __DIR__ . '/../Support/BookPageFormatter.php';
require_once __DIR__ . '/../Support/CoverTypeMapper.php';
require_once __DIR__ . '/../Support/LanguageCodeMapper.php';
require_once __DIR__ . '/../Support/PublicationDateNormalizer.php';

class OpenLibraryProvider implements BookMetadataProviderInterface
{
    private HttpClient $http;
    private BookPageFormatter $pageFormatter;
    private PublicationDateNormalizer $dateNormalizer;
    private CoverTypeMapper $coverTypeMapper;
    private LanguageCodeMapper $languageMapper;

    public function __construct(?HttpClient $http = null)
    {
        $this->http = $http ?? new HttpClient();
        $this->pageFormatter = new BookPageFormatter();
        $this->dateNormalizer = new PublicationDateNormalizer();
        $this->coverTypeMapper = new CoverTypeMapper();
        $this->languageMapper = new LanguageCodeMapper();
    }

    public function providerCode(): string
    {
        return 'open_library';
    }

    public function lookupByIsbn(string $normalizedIsbn): ?BookMetadata
    {
        $bibKey = 'ISBN:' . $normalizedIsbn;
        $url = 'https://openlibrary.org/api/books?bibkeys=' . rawurlencode($bibKey)
            . '&format=json&jscmd=data';

        $payload = $this->http->getJson($url);
        if (!is_array($payload)) {
            return null;
        }

        $entry = $payload[$bibKey] ?? null;
        if (!is_array($entry) || empty($entry['title'])) {
            $editionUrl = 'https://openlibrary.org/isbn/' . rawurlencode($normalizedIsbn) . '.json';
            $edition = $this->http->getJson($editionUrl);
            if (!is_array($edition) || empty($edition['title'])) {
                return null;
            }

            return $this->mapEditionJson($normalizedIsbn, $edition);
        }

        $metadata = new BookMetadata();
        $metadata->sources = [$this->providerCode()];
        $metadata->isbn = $normalizedIsbn;
        $metadata->title = trim((string) ($entry['title'] ?? ''));
        $metadata->subtitle = trim((string) ($entry['subtitle'] ?? ''));
        $metadata->authors = $this->extractAuthorNames($entry['authors'] ?? []);
        $metadata->publisher = $this->extractPublisherName($entry['publishers'] ?? []);
        $metadata->pages = $this->pageFormatter->format($entry['number_of_pages'] ?? null);
        $metadata->publicationDate = $this->dateNormalizer->normalize((string) ($entry['publish_date'] ?? ''));
        $metadata->coverType = $this->coverTypeMapper->map((string) ($entry['physical_format'] ?? ''));
        $metadata->language = $this->languageMapper->map((string) ($entry['languages'][0]['key'] ?? ''));
        $metadata->coverUrl = $this->extractCoverUrl($entry['cover'] ?? null, $normalizedIsbn);

        return $metadata->title !== '' ? $metadata : null;
    }

    /**
     * @param array<string, mixed> $edition
     */
    private function mapEditionJson(string $isbn, array $edition): BookMetadata
    {
        $authors = [];
        foreach ($edition['authors'] ?? [] as $authorKey) {
            if (is_string($authorKey) && $authorKey !== '') {
                $authors[] = $this->fetchAuthorName($authorKey);
            }
        }

        $metadata = new BookMetadata();
        $metadata->sources = [$this->providerCode()];
        $metadata->isbn = $isbn;
        $metadata->title = trim((string) ($edition['title'] ?? ''));
        $metadata->authors = array_values(array_unique(array_filter($authors)));
        $metadata->publisher = $this->extractPublisherName($edition['publishers'] ?? []);
        $metadata->pages = $this->pageFormatter->format($edition['number_of_pages'] ?? null);
        $metadata->publicationDate = $this->dateNormalizer->normalize((string) ($edition['publish_date'] ?? ''));
        $metadata->coverUrl = 'https://covers.openlibrary.org/b/isbn/' . rawurlencode($isbn) . '-L.jpg?default=false';

        return $metadata;
    }

    /**
     * @param mixed $authorsRaw
     * @return list<string>
     */
    private function extractAuthorNames($authorsRaw): array
    {
        $authors = [];
        if (!is_array($authorsRaw)) {
            return $authors;
        }

        foreach ($authorsRaw as $authorRow) {
            if (is_array($authorRow) && !empty($authorRow['name'])) {
                $authors[] = trim((string) $authorRow['name']);
            }
        }

        return array_values(array_unique(array_filter($authors)));
    }

    /**
     * @param mixed $publishersRaw
     */
    private function extractPublisherName($publishersRaw): string
    {
        if (!is_array($publishersRaw)) {
            return '';
        }

        foreach ($publishersRaw as $publisherRow) {
            if (is_array($publisherRow) && !empty($publisherRow['name'])) {
                return trim((string) $publisherRow['name']);
            }
            if (is_string($publisherRow) && trim($publisherRow) !== '') {
                return trim($publisherRow);
            }
        }

        return '';
    }

    /**
     * @param mixed $coverRaw
     */
    private function extractCoverUrl($coverRaw, string $isbn): string
    {
        if (is_array($coverRaw)) {
            foreach (['large', 'medium', 'small'] as $size) {
                if (!empty($coverRaw[$size])) {
                    return (string) $coverRaw[$size];
                }
            }
        }

        return 'https://covers.openlibrary.org/b/isbn/' . rawurlencode($isbn) . '-L.jpg?default=false';
    }

    private function fetchAuthorName(string $authorKey): string
    {
        $authorKey = ltrim($authorKey, '/');
        if ($authorKey === '') {
            return '';
        }

        $url = 'https://openlibrary.org/' . $authorKey . '.json';
        $payload = $this->http->getJson($url);
        if (!is_array($payload)) {
            return '';
        }

        return trim((string) ($payload['name'] ?? $payload['personal_name'] ?? ''));
    }
}
