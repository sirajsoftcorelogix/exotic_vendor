<?php

require_once __DIR__ . '/../../shared/Http/HttpClient.php';
require_once __DIR__ . '/../Contracts/BookMetadataProviderInterface.php';
require_once __DIR__ . '/../Dto/BookMetadata.php';
require_once __DIR__ . '/../Support/BookPageFormatter.php';
require_once __DIR__ . '/../Support/CoverTypeMapper.php';
require_once __DIR__ . '/../Support/LanguageCodeMapper.php';
require_once __DIR__ . '/../Support/PublicationDateNormalizer.php';

class GoogleBooksProvider implements BookMetadataProviderInterface
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
        return 'google_books';
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
        $apiKey = getenv('GOOGLE_BOOKS_API_KEY');
        if ($apiKey === false || trim((string) $apiKey) === '') {
            $this->lastLookupStatus = [
                'state' => 'key_missing',
                'label' => 'Google Books API key is not configured.',
            ];

            return null;
        }

        $url = 'https://www.googleapis.com/books/v1/volumes?q=isbn:'
            . rawurlencode($normalizedIsbn)
            . '&key=' . rawurlencode(trim((string) $apiKey));

        $response = $this->http->requestJson($url);
        if (!$response['ok']) {
            $httpCode = (int) ($response['http_code'] ?? 0);
            $errorMessage = trim((string) ($response['error'] ?? 'Google Books request failed.'));

            if ($httpCode === 403 && stripos($errorMessage, 'Books API') !== false) {
                $this->lastLookupStatus = [
                    'state' => 'api_disabled',
                    'label' => 'Enable Books API in Google Cloud Console for this API key project.',
                    'http_code' => $httpCode,
                    'detail' => $errorMessage,
                ];
            } else {
                $this->lastLookupStatus = [
                    'state' => 'error',
                    'label' => $errorMessage,
                    'http_code' => $httpCode,
                ];
            }

            return null;
        }

        $payload = $response['data'] ?? null;
        if (!is_array($payload) || empty($payload['items'][0]['volumeInfo'])) {
            $this->lastLookupStatus = [
                'state' => 'not_found',
                'label' => 'No Google Books match for this ISBN.',
            ];

            return null;
        }

        $info = $payload['items'][0]['volumeInfo'];
        $authors = [];
        foreach ($info['authors'] ?? [] as $authorName) {
            if (is_string($authorName) && trim($authorName) !== '') {
                $authors[] = trim($authorName);
            }
        }

        $bindingHint = '';
        foreach ($info['industryIdentifiers'] ?? [] as $identifierRow) {
            if (!is_array($identifierRow)) {
                continue;
            }
            $type = strtolower((string) ($identifierRow['type'] ?? ''));
            if ($type === 'other' && !empty($identifierRow['identifier'])) {
                $bindingHint = (string) $identifierRow['identifier'];
            }
        }
        if ($bindingHint === '' && !empty($info['printType'])) {
            $bindingHint = (string) $info['printType'];
        }

        $coverUrl = '';
        foreach (['thumbnail', 'smallThumbnail', 'medium', 'large'] as $size) {
            if (!empty($info['imageLinks'][$size])) {
                $coverUrl = str_replace('http://', 'https://', (string) $info['imageLinks'][$size]);
                break;
            }
        }

        $subjects = [];
        foreach ($info['categories'] ?? [] as $categoryName) {
            if (is_string($categoryName) && trim($categoryName) !== '') {
                $subjects[] = trim($categoryName);
            }
        }

        $description = trim(strip_tags((string) ($info['description'] ?? '')));
        if ($description === '' && $subjects !== []) {
            $description = 'Categories: ' . implode(', ', $subjects);
        }

        $metadata = new BookMetadata();
        $metadata->sources = [$this->providerCode()];
        $metadata->isbn = $normalizedIsbn;
        $metadata->title = trim((string) ($info['title'] ?? ''));
        $metadata->subtitle = trim((string) ($info['subtitle'] ?? ''));
        $metadata->authors = array_values(array_unique(array_filter($authors)));
        $metadata->publisher = trim((string) ($info['publisher'] ?? ''));
        $metadata->pages = $this->pageFormatter->format($info['pageCount'] ?? null);
        $metadata->publicationDate = $this->dateNormalizer->normalize((string) ($info['publishedDate'] ?? ''));
        $metadata->coverType = $this->coverTypeMapper->map($bindingHint);
        $metadata->language = $this->languageMapper->map((string) ($info['language'] ?? ''));
        $metadata->edition = trim((string) ($info['contentVersion'] ?? ''));
        $metadata->subjects = array_values(array_unique($subjects));
        $metadata->description = $description;
        $metadata->coverUrl = $coverUrl;

        if ($metadata->title === '') {
            $this->lastLookupStatus = [
                'state' => 'not_found',
                'label' => 'Google Books returned an empty title for this ISBN.',
            ];

            return null;
        }

        $this->lastLookupStatus = [
            'state' => 'ok',
            'label' => 'Matched in Google Books.',
        ];

        return $metadata;
    }
}
