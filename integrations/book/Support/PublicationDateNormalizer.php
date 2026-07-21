<?php

class PublicationDateNormalizer
{
    public function normalize(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }

        if (preg_match('/^(\d{4})-(\d{2})$/', $raw, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-01';
        }

        if (preg_match('/^\d{4}$/', $raw)) {
            return $raw . '-01-01';
        }

        $timestamp = strtotime($raw);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return '';
    }
}
