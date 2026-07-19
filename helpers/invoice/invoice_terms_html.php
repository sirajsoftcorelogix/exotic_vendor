<?php

/**
 * Terms & Conditions block for invoice PDF templates.
 * Omits the label when there is no content.
 */
function invoice_format_terms_and_conditions_block(?string $terms): string
{
    $terms = trim((string) $terms);
    if ($terms === '') {
        return '';
    }

    return '<b>Terms & Conditions:</b><br>' . nl2br(htmlspecialchars($terms, ENT_QUOTES, 'UTF-8'));
}
