<?php

require_once __DIR__ . '/Contracts/BookMetadataProviderInterface.php';
require_once __DIR__ . '/Dto/BookMetadata.php';
require_once __DIR__ . '/Providers/OpenLibraryProvider.php';
require_once __DIR__ . '/Providers/GoogleBooksProvider.php';
require_once __DIR__ . '/Support/IsbnNormalizer.php';
require_once __DIR__ . '/../shared/Http/HttpClient.php';

/**
 * Orchestrates external book metadata providers (Open Library, Google Books, …).
 */
class BookMetadataGateway
{
    /** @var list<BookMetadataProviderInterface> */
    private array $providers;

    private IsbnNormalizer $isbnNormalizer;

    /**
     * @param list<BookMetadataProviderInterface>|null $providers
     */
    public function __construct(?array $providers = null, ?IsbnNormalizer $isbnNormalizer = null)
    {
        $http = new HttpClient();
        $this->providers = $providers ?? [
            new OpenLibraryProvider($http),
            new GoogleBooksProvider($http),
        ];
        $this->isbnNormalizer = $isbnNormalizer ?? new IsbnNormalizer();
    }

    public function lookupByIsbn(string $isbn): ?BookMetadata
    {
        $normalized = $this->isbnNormalizer->normalize($isbn);
        if ($normalized === null) {
            return null;
        }

        $results = [];
        foreach ($this->providers as $provider) {
            $result = $provider->lookupByIsbn($normalized);
            if ($result instanceof BookMetadata) {
                $results[] = $result;
            }
        }

        return $this->mergeResults($normalized, $results);
    }

    public function normalizeIsbn(string $isbn): ?string
    {
        return $this->isbnNormalizer->normalize($isbn);
    }

    public function formatIsbnDisplay(string $normalizedIsbn): string
    {
        return $this->isbnNormalizer->formatDisplay($normalizedIsbn);
    }

    /**
     * @param list<BookMetadata> $results
     */
    private function mergeResults(string $normalizedIsbn, array $results): ?BookMetadata
    {
        if ($results === []) {
            return null;
        }

        $openLibrary = null;
        $googleBooks = null;
        foreach ($results as $result) {
            if (in_array('open_library', $result->sources, true)) {
                $openLibrary = $result;
            }
            if (in_array('google_books', $result->sources, true)) {
                $googleBooks = $result;
            }
        }

        $pick = static function (string $getter) use ($openLibrary, $googleBooks): string {
            if ($openLibrary instanceof BookMetadata) {
                $value = $openLibrary->{$getter};
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
            if ($googleBooks instanceof BookMetadata) {
                $value = $googleBooks->{$getter};
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }

            return '';
        };

        $merged = new BookMetadata();
        $merged->isbn = $this->isbnNormalizer->formatDisplay($normalizedIsbn);
        $merged->title = $pick('title');
        $merged->subtitle = $pick('subtitle');
        $merged->publisher = $pick('publisher');
        $merged->pages = $pick('pages');
        $merged->publicationDate = $pick('publicationDate');
        $merged->coverType = $pick('coverType');
        $merged->language = $pick('language');
        $merged->edition = $pick('edition');
        $merged->description = $pick('description');

        if ($openLibrary instanceof BookMetadata && $openLibrary->authors !== []) {
            $merged->authors = $openLibrary->authors;
        } elseif ($googleBooks instanceof BookMetadata) {
            $merged->authors = $googleBooks->authors;
        }

        if ($openLibrary instanceof BookMetadata && $openLibrary->coverUrl !== '') {
            $merged->coverUrl = $openLibrary->coverUrl;
        } elseif ($googleBooks instanceof BookMetadata && $googleBooks->coverUrl !== '') {
            $merged->coverUrl = $googleBooks->coverUrl;
        } else {
            $merged->coverUrl = 'https://covers.openlibrary.org/b/isbn/'
                . rawurlencode($normalizedIsbn) . '-L.jpg?default=false';
        }

        foreach ($results as $result) {
            foreach ($result->sources as $source) {
                if (!in_array($source, $merged->sources, true)) {
                    $merged->sources[] = $source;
                }
            }
        }

        return $merged->title !== '' ? $merged : null;
    }
}
