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

    /** @var array<string, mixed> */
    private array $lastLookupStatus = ['state' => 'idle'];

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

    /**
     * @return array<string, mixed>
     */
    public function getLastLookupStatus(): array
    {
        return $this->lastLookupStatus;
    }

    public function lookupByIsbn(string $normalizedIsbn): ?BookMetadata
    {
        $bibKey = 'ISBN:' . $normalizedIsbn;
        $url = 'https://openlibrary.org/api/books?bibkeys=' . rawurlencode($bibKey)
            . '&format=json&jscmd=data';

        $payload = $this->http->getJson($url);
        if (!is_array($payload)) {
            $this->lastLookupStatus = [
                'state' => 'error',
                'label' => 'Open Library request failed.',
            ];

            return null;
        }

        $entry = $payload[$bibKey] ?? null;
        if (!is_array($entry) || empty($entry['title'])) {
            $editionUrl = 'https://openlibrary.org/isbn/' . rawurlencode($normalizedIsbn) . '.json';
            $edition = $this->http->getJson($editionUrl);
            if (!is_array($edition) || empty($edition['title'])) {
                $this->lastLookupStatus = [
                    'state' => 'not_found',
                    'label' => 'No Open Library match for this ISBN.',
                ];

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
        $metadata->subjects = $this->extractSubjects($entry['subjects'] ?? []);
        $metadata->description = $this->buildDescriptionFromSubjects($metadata->subjects);
        $metadata->coverUrl = $this->resolveCoverUrl($entry, $normalizedIsbn);

        $this->enrichFromEditionJson($metadata, $normalizedIsbn);

        if ($metadata->title === '') {
            $this->lastLookupStatus = [
                'state' => 'not_found',
                'label' => 'No Open Library match for this ISBN.',
            ];

            return null;
        }

        $this->lastLookupStatus = [
            'state' => 'ok',
            'label' => 'Matched in Open Library.',
        ];

        return $metadata;
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
        $metadata->language = $this->languageMapper->map((string) ($edition['languages'][0]['key'] ?? ''));
        $metadata->coverType = $this->coverTypeMapper->map((string) ($edition['physical_format'] ?? ''));
        $metadata->subjects = $this->extractSubjects($edition['subjects'] ?? []);
        $metadata->description = $this->buildDescriptionFromSubjects($metadata->subjects);
        $metadata->coverUrl = $this->resolveCoverUrl($edition, $isbn);

        $this->lastLookupStatus = [
            'state' => 'ok',
            'label' => 'Matched in Open Library.',
        ];

        return $metadata;
    }

    private function enrichFromEditionJson(BookMetadata $metadata, string $isbn): void
    {
        $editionUrl = 'https://openlibrary.org/isbn/' . rawurlencode($isbn) . '.json';
        $edition = $this->http->getJson($editionUrl);
        if (!is_array($edition)) {
            return;
        }

        if ($metadata->language === '' && !empty($edition['languages'][0]['key'])) {
            $metadata->language = $this->languageMapper->map((string) $edition['languages'][0]['key']);
        }
        if ($metadata->pages === '' && isset($edition['number_of_pages'])) {
            $metadata->pages = $this->pageFormatter->format($edition['number_of_pages']);
        }
        if ($metadata->coverType === '' && !empty($edition['physical_format'])) {
            $metadata->coverType = $this->coverTypeMapper->map((string) $edition['physical_format']);
        }

        $editionSubjects = $this->extractSubjects($edition['subjects'] ?? []);
        if ($editionSubjects !== []) {
            $metadata->subjects = array_values(array_unique(array_merge($metadata->subjects, $editionSubjects)));
            if ($metadata->description === '') {
                $metadata->description = $this->buildDescriptionFromSubjects($metadata->subjects);
            }
        }

        if ($metadata->coverUrl === '' || str_contains($metadata->coverUrl, '/b/isbn/')) {
            $resolvedCover = $this->resolveCoverUrl($edition, $isbn);
            if ($resolvedCover !== '') {
                $metadata->coverUrl = $resolvedCover;
            }
        }
    }

    /**
     * @param mixed $subjectsRaw
     * @return list<string>
     */
    private function extractSubjects($subjectsRaw): array
    {
        if (!is_array($subjectsRaw)) {
            return [];
        }

        $subjects = [];
        foreach ($subjectsRaw as $subjectRow) {
            if (is_array($subjectRow) && !empty($subjectRow['name'])) {
                $subjects[] = trim((string) $subjectRow['name']);
            } elseif (is_string($subjectRow) && trim($subjectRow) !== '') {
                $subjects[] = trim($subjectRow);
            }
        }

        return array_values(array_unique(array_filter($subjects)));
    }

    /**
     * @param list<string> $subjects
     */
    private function buildDescriptionFromSubjects(array $subjects): string
    {
        if ($subjects === []) {
            return '';
        }

        return 'Subjects: ' . implode(', ', $subjects);
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function resolveCoverUrl(array $entry, string $isbn): string
    {
        if (!empty($entry['cover']) && is_array($entry['cover'])) {
            foreach (['large', 'medium', 'small'] as $size) {
                if (!empty($entry['cover'][$size])) {
                    return (string) $entry['cover'][$size];
                }
            }
        }

        $olid = $this->extractOlid($entry);
        if ($olid !== '') {
            return 'https://covers.openlibrary.org/b/olid/' . rawurlencode($olid) . '-L.jpg?default=false';
        }

        return 'https://covers.openlibrary.org/b/isbn/' . rawurlencode($isbn) . '-L.jpg?default=false';
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function extractOlid(array $entry): string
    {
        $identifiers = $entry['identifiers']['openlibrary'] ?? [];
        if (is_array($identifiers) && !empty($identifiers[0])) {
            return trim((string) $identifiers[0]);
        }

        $key = trim((string) ($entry['key'] ?? ''));
        if ($key !== '' && preg_match('#/books/(OL\d+[A-Z0-9]*)#i', $key, $matches)) {
            return $matches[1];
        }

        return '';
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
