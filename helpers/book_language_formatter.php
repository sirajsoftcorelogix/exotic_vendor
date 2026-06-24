<?php

/**
 * Builds the readonly inbound book `language` summary from role-based language selections.
 */
class BookLanguageFormatter
{
    /**
     * @return list<array{key:string,label:string,single_template:string,multiple_template:string}>
     */
    public static function roleDefinitions(): array
    {
        return [
            [
                'key' => 'original_languages',
                'label' => 'Original Languages',
                'single_template' => '{languages} Text',
                'multiple_template' => '{languages} Texts',
            ],
            [
                'key' => 'script_languages',
                'label' => 'Script Languages',
                'single_template' => 'in {languages} Script',
                'multiple_template' => 'in {languages} Scripts',
            ],
            [
                'key' => 'transliteration_languages',
                'label' => 'Transliteration Languages',
                'single_template' => 'with {languages} Transliteration',
                'multiple_template' => 'with {languages} Transliteration',
            ],
            [
                'key' => 'word_meaning_languages',
                'label' => 'Word Meaning Languages',
                'single_template' => 'with Word-to-Word Meaning in {languages}',
                'multiple_template' => 'with Word-to-Word Meaning in {languages}',
            ],
            [
                'key' => 'translation_languages',
                'label' => 'Translation Languages',
                'single_template' => 'with {languages} Translation',
                'multiple_template' => 'with {languages} Translation',
            ],
            [
                'key' => 'commentary_languages',
                'label' => 'Commentary Languages',
                'single_template' => 'with {languages} Commentary',
                'multiple_template' => 'with {languages} Commentary',
            ],
            [
                'key' => 'explanation_languages',
                'label' => 'Explanation Languages',
                'single_template' => 'with {languages} Explanation',
                'multiple_template' => 'with {languages} Explanation',
            ],
        ];
    }

    /** @return list<string> */
    public static function orderedRoleKeys(): array
    {
        return array_map(
            static fn(array $role): string => $role['key'],
            self::roleDefinitions()
        );
    }

    /** @return list<array{key:string,label:string}> */
    public static function uiFieldDefinitions(): array
    {
        return array_map(
            static fn(array $role): array => [
                'key' => $role['key'],
                'label' => $role['label'],
            ],
            self::roleDefinitions()
        );
    }

    /**
     * @return list<array{key:string,single_template:string,multiple_template:string}>
     */
    public static function roleDefinitionsForJs(): array
    {
        return array_map(
            static fn(array $role): array => [
                'key' => $role['key'],
                'single_template' => $role['single_template'],
                'multiple_template' => $role['multiple_template'],
            ],
            self::roleDefinitions()
        );
    }

    /** @return list<int> */
    public static function parseIdCsv(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $ids = [];
        foreach (explode(',', $raw) as $part) {
            $id = (int) trim($part);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * @param list<string> $names
     */
    public static function formatLanguageList(array $names): string
    {
        $names = array_values(array_filter(array_map(static function ($name) {
            return trim((string) $name);
        }, $names), static function ($name) {
            return $name !== '';
        }));

        $count = count($names);
        if ($count === 0) {
            return '';
        }
        if ($count === 1) {
            return $names[0];
        }
        if ($count === 2) {
            return $names[0] . ' and ' . $names[1];
        }

        $last = array_pop($names);

        return implode(', ', $names) . ' and ' . $last;
    }

    /**
     * @param list<string> $languageNames Selection order preserved.
     */
    public static function formatRoleSegment(array $languageNames, string $singleTemplate, string $multipleTemplate): string
    {
        $languageNames = array_values(array_filter(array_map(static function ($name) {
            return trim((string) $name);
        }, $languageNames), static function ($name) {
            return $name !== '';
        }));

        if ($languageNames === []) {
            return '';
        }

        $languages = self::formatLanguageList($languageNames);
        $template = count($languageNames) === 1 ? $singleTemplate : $multipleTemplate;

        return str_replace('{languages}', $languages, $template);
    }

    /**
     * @param list<string> $segments
     */
    public static function joinRoleSegments(array $segments): string
    {
        $segments = array_values(array_filter(array_map(static function ($segment) {
            return trim((string) $segment);
        }, $segments), static function ($segment) {
            return $segment !== '';
        }));

        if ($segments === []) {
            return '';
        }

        $result = array_shift($segments);
        $withCount = 0;

        foreach ($segments as $segment) {
            if (str_starts_with($segment, 'in ')) {
                $result .= ' ' . $segment;
                continue;
            }

            if (str_starts_with($segment, 'with ')) {
                if ($withCount === 0) {
                    $result .= ' ' . $segment;
                } else {
                    $result .= ' and ' . substr($segment, 5);
                }
                $withCount++;
                continue;
            }

            $result .= ' and ' . $segment;
        }

        return $result;
    }

    /**
     * @param array<string, string|null> $roleIdCsvByKey
     * @param array<int, string> $nameById
     */
    public static function formatFromRoleIdCsv(array $roleIdCsvByKey, array $nameById): string
    {
        $segments = [];

        foreach (self::roleDefinitions() as $role) {
            $ids = self::parseIdCsv($roleIdCsvByKey[$role['key']] ?? '');
            $names = [];
            foreach ($ids as $id) {
                if (!empty($nameById[$id])) {
                    $names[] = $nameById[$id];
                }
            }

            $segment = self::formatRoleSegment(
                $names,
                $role['single_template'],
                $role['multiple_template']
            );

            if ($segment !== '') {
                $segments[] = $segment;
            }
        }

        return self::joinRoleSegments($segments);
    }
}
