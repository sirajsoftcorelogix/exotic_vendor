<?php

/**
 * Normalized book metadata from external catalogs.
 */
class BookMetadata
{
    /** @var list<string> */
    public array $sources = [];

    public string $isbn = '';
    public string $title = '';
    public string $subtitle = '';

    /** @var list<string> */
    public array $authors = [];

    /** @var list<string> */
    public array $subjects = [];

    public string $publisher = '';
    public string $pages = '';
    public string $publicationDate = '';
    public string $coverType = '';
    public string $language = '';
    public string $edition = '';
    public string $description = '';
    public string $coverUrl = '';

    /**
     * @return array<string, mixed>
     */
    public function toInboundArray(): array
    {
        return [
            'isbn' => $this->isbn,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'authors' => $this->authors,
            'subjects' => $this->subjects,
            'publisher' => $this->publisher,
            'pages' => $this->pages,
            'publication_date' => $this->publicationDate,
            'cover_type' => $this->coverType,
            'language' => $this->language,
            'edition' => $this->edition,
            'description' => $this->description,
            'cover_url' => $this->coverUrl,
            'sources' => $this->sources,
        ];
    }

    public function successMessage(): string
    {
        $labels = [];
        foreach ($this->sources as $source) {
            if ($source === 'open_library') {
                $labels[] = 'Open Library';
            } elseif ($source === 'google_books') {
                $labels[] = 'Google Books';
            }
        }

        $via = count($labels) > 0 ? implode(' + ', $labels) : 'external catalogs';

        return $this->title !== ''
            ? 'Found "' . $this->title . '" via ' . $via . '.'
            : 'Book details found via ' . $via . '.';
    }
}
