<?php

class BookPageFormatter
{
    /**
     * @param mixed $pages
     */
    public function format($pages): string
    {
        if ($pages === null || $pages === '') {
            return '';
        }

        $count = (int) $pages;
        if ($count <= 0) {
            return trim((string) $pages);
        }

        return (string) $count;
    }
}
