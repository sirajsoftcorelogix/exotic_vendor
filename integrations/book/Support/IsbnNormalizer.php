<?php

class IsbnNormalizer
{
    public function normalize(string $isbn): ?string
    {
        $raw = strtoupper(trim($isbn));
        if ($raw === '') {
            return null;
        }

        $clean = preg_replace('/[^0-9X]/', '', $raw);
        if (!is_string($clean) || $clean === '') {
            return null;
        }

        if (strlen($clean) === 10) {
            return $clean;
        }

        if (strlen($clean) === 13 && ctype_digit($clean)) {
            return $clean;
        }

        return null;
    }

    public function formatDisplay(string $isbn): string
    {
        if (strlen($isbn) === 13) {
            return substr($isbn, 0, 3) . '-'
                . substr($isbn, 3, 1) . '-'
                . substr($isbn, 4, 3) . '-'
                . substr($isbn, 7, 5) . '-'
                . substr($isbn, 12, 1);
        }

        return $isbn;
    }
}
