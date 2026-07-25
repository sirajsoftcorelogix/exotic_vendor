<?php

class CoverTypeMapper
{
    /** @var list<string> */
    private array $coverTypeOptions;

    public function __construct()
    {
        $bookCoverTypeOptions = [];
        require __DIR__ . '/../../../views/inbounding/partials/book_cover_types.php';
        $this->coverTypeOptions = $bookCoverTypeOptions;
    }

    public function map(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $lower = strtolower($raw);
        $map = [
            'paperback' => 'Paperback',
            'softcover' => 'Paperback',
            'soft cover' => 'Paperback',
            'hardcover' => 'Hardcover',
            'hardback' => 'Hardcover',
            'hard cover' => 'Hardcover',
            'dust jacket' => 'Dust Jacket Hardcover',
            'spiral' => 'Spiral Bound',
            'wire-o' => 'Wire-O Binding',
            'saddle stitch' => 'Saddle Stitch',
            'leather' => 'Leather Bound',
            'cloth' => 'Cloth Bound',
            'board book' => 'Board Book',
            'flexibound' => 'Flexibound',
        ];

        foreach ($map as $needle => $option) {
            if (str_contains($lower, $needle)) {
                return $option;
            }
        }

        foreach ($this->coverTypeOptions as $option) {
            if (strcasecmp($option, $raw) === 0) {
                return $option;
            }
        }

        return '';
    }
}
