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

    public function lookupByIsbn(string $normalizedIsbn): ?BookMetadata
    {
        $url = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . rawurlencode($normalizedIsbn);
        $apiKey = getenv('GOOGLE_BOOKS_API_KEY');
        if ($apiKey !== false && trim((string) $apiKey) !== '') {
            $url .= '&key=' . rawurlencode(trim((string) $apiKey));
        }

        $payload = $this->http->getJson($url);
        if (!is_array($payload) || empty($payload['items'][0]['volumeInfo'])) {
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

        $coverUrl = '';
        if (!empty($info['imageLinks']['thumbnail'])) {
            $coverUrl = str_replace('http://', 'https://', (string) $info['imageLinks']['thumbnail']);
        } elseif (!empty($info['imageLinks']['smallThumbnail'])) {
            $coverUrl = str_replace('http://', 'https://', (string) $info['imageLinks']['smallThumbnail']);
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
        $metadata->description = trim(strip_tags((string) ($info['description'] ?? '')));
        $metadata->coverUrl = $coverUrl;

        return $metadata->title !== '' ? $metadata : null;
    }
}
