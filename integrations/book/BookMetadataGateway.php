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

    /** @var array<string, array<string, mixed>> */
    private array $providerDiagnostics = [];

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

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getProviderDiagnostics(): array
    {
        return $this->providerDiagnostics;
    }

    public function lookupByIsbn(string $isbn): ?BookMetadata
    {
        $normalized = $this->isbnNormalizer->normalize($isbn);
        if ($normalized === null) {
            return null;
        }

        $this->providerDiagnostics = [];
        $results = [];
        foreach ($this->providers as $provider) {
            $result = $provider->lookupByIsbn($normalized);
            if ($result instanceof BookMetadata) {
                $results[] = $result;
            }

            if (method_exists($provider, 'getLastLookupStatus')) {
                $this->providerDiagnostics[$provider->providerCode()] = $provider->getLastLookupStatus();
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

        $pickAuthors = static function () use ($openLibrary, $googleBooks): array {
            if ($openLibrary instanceof BookMetadata && $openLibrary->authors !== []) {
                return $openLibrary->authors;
            }
            if ($googleBooks instanceof BookMetadata && $googleBooks->authors !== []) {
                return $googleBooks->authors;
            }

            return [];
        };

        $pickSubjects = static function () use ($openLibrary, $googleBooks): array {
            $subjects = [];
            foreach ([$openLibrary, $googleBooks] as $result) {
                if (!$result instanceof BookMetadata) {
                    continue;
                }
                foreach ($result->subjects as $subject) {
                    $subject = trim((string) $subject);
                    if ($subject !== '' && !in_array($subject, $subjects, true)) {
                        $subjects[] = $subject;
                    }
                }
            }

            return $subjects;
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
        $merged->authors = $pickAuthors();
        $merged->subjects = $pickSubjects();

        if ($merged->description === '' && $merged->subjects !== []) {
            $merged->description = 'Subjects: ' . implode(', ', $merged->subjects);
        }

        $coverCandidates = [];
        if ($googleBooks instanceof BookMetadata && $googleBooks->coverUrl !== '') {
            $coverCandidates[] = $googleBooks->coverUrl;
        }
        if ($openLibrary instanceof BookMetadata && $openLibrary->coverUrl !== '') {
            $coverCandidates[] = $openLibrary->coverUrl;
        }
        $coverCandidates[] = 'https://covers.openlibrary.org/b/isbn/'
            . rawurlencode($normalizedIsbn) . '-L.jpg?default=false';

        $merged->coverUrl = $coverCandidates[0] ?? '';

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
