<?php

require_once __DIR__ . '/../../integrations/book/BookMetadataGateway.php';
require_once __DIR__ . '/../../models/author/Author.php';
require_once __DIR__ . '/../../models/publisher/Publisher.php';

/**
 * Inbound use-case: ISBN lookup + local author/publisher catalog matching.
 */
class BookIsbnLookupService
{
    private mysqli $conn;
    private BookMetadataGateway $gateway;
    private Author $authorModel;
    private Publisher $publisherModel;

    public function __construct(mysqli $conn, ?BookMetadataGateway $gateway = null)
    {
        $this->conn = $conn;
        $this->gateway = $gateway ?? new BookMetadataGateway();
        $this->authorModel = new Author($conn);
        $this->publisherModel = new Publisher($conn);
    }

    /**
     * @return array{success:bool,message:string,data?:array,catalog_matches?:array}
     */
    public function lookupByIsbn(string $isbn): array
    {
        $normalized = $this->gateway->normalizeIsbn($isbn);
        if ($normalized === null) {
            return [
                'success' => false,
                'message' => 'Please enter a valid ISBN-10 or ISBN-13.',
            ];
        }

        $metadata = $this->gateway->lookupByIsbn($isbn);
        if (!$metadata instanceof BookMetadata) {
            return [
                'success' => false,
                'message' => 'No book found for ISBN ' . $this->gateway->formatIsbnDisplay($normalized) . '.',
            ];
        }

        $catalogMatches = $this->resolveCatalogMatches($metadata);

        return [
            'success' => true,
            'message' => $metadata->successMessage(),
            'data' => $metadata->toInboundArray(),
            'catalog_matches' => $catalogMatches,
            'provider_status' => $this->gateway->getProviderDiagnostics(),
        ];
    }

    /**
     * @return array{authors:list<array{id:string,name:string}>,publisher:?array{id:string,name:string},unmatched_authors:list<string>,unmatched_publisher:?string}
     */
    private function resolveCatalogMatches(BookMetadata $metadata): array
    {
        $matchedAuthors = [];
        $unmatchedAuthors = [];

        foreach ($metadata->authors as $authorName) {
            $authorName = trim((string) $authorName);
            if ($authorName === '') {
                continue;
            }

            $match = $this->authorModel->findBestMatchByName($authorName);
            if ($match !== null) {
                $matchedAuthors[] = $match;
            } else {
                $unmatchedAuthors[] = $authorName;
            }
        }

        $publisherName = trim($metadata->publisher);
        $publisherMatch = $publisherName !== '' ? $this->publisherModel->findBestMatchByName($publisherName) : null;

        return [
            'authors' => $matchedAuthors,
            'publisher' => $publisherMatch,
            'unmatched_authors' => $unmatchedAuthors,
            'unmatched_publisher' => ($publisherMatch === null && $publisherName !== '') ? $publisherName : null,
        ];
    }
}
