<?php
require __DIR__ . '/../helpers/book_language_formatter.php';

$nameById = [
    1 => 'Sanskrit',
    2 => 'Hindi',
    3 => 'English',
    4 => 'Arabic',
    5 => 'Urdu',
    6 => 'Devanagari',
    7 => 'Roman',
];

function assertEq(string $label, string $expected, string $actual): void
{
    if ($expected === $actual) {
        echo "PASS: $label\n";
        return;
    }
    echo "FAIL: $label\n";
    echo "  expected: $expected\n";
    echo "  actual:   $actual\n";
}

assertEq(
    'Example 1',
    'Sanskrit Text with English Translation',
    BookLanguageFormatter::formatFromRoleIdCsv([
        'original_languages' => '1',
        'translation_languages' => '3',
    ], $nameById)
);

assertEq(
    'Example 2',
    'Arabic Text with English Translation and Urdu Commentary',
    BookLanguageFormatter::formatFromRoleIdCsv([
        'original_languages' => '4',
        'translation_languages' => '3',
        'commentary_languages' => '5',
    ], $nameById)
);

assertEq(
    'Example 3',
    'Sanskrit Text in Devanagari Script with English Transliteration and Word-to-Word Meaning in English and English Translation and Hindi Commentary',
    BookLanguageFormatter::formatFromRoleIdCsv([
        'original_languages' => '1',
        'script_languages' => '6',
        'transliteration_languages' => '3',
        'word_meaning_languages' => '3',
        'translation_languages' => '3',
        'commentary_languages' => '2',
    ], $nameById)
);

assertEq(
    'Original multiple',
    'Sanskrit and Hindi Texts',
    BookLanguageFormatter::formatFromRoleIdCsv(['original_languages' => '1,2'], $nameById)
);

assertEq(
    'Script multiple',
    'in Roman and Devanagari Scripts',
    BookLanguageFormatter::formatFromRoleIdCsv(['script_languages' => '7,6'], $nameById)
);

assertEq(
    'Language list (3)',
    'English, Hindi and Urdu',
    BookLanguageFormatter::formatLanguageList(['English', 'Hindi', 'Urdu'])
);
