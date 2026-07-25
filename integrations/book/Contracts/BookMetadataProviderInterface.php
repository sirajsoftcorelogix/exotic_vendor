<?php

require_once __DIR__ . '/../Dto/BookMetadata.php';

/**
 * One external book-metadata source (Open Library, Google Books, …).
 */
interface BookMetadataProviderInterface
{
    public function providerCode(): string;

    public function lookupByIsbn(string $normalizedIsbn): ?BookMetadata;
}
