<?php

class LanguageCodeMapper
{
    public function map(string $code): string
    {
        $code = strtolower(trim($code));
        if ($code === '') {
            return '';
        }

        $code = preg_replace('/^\/languages\//', '', $code);
        $map = [
            'en' => 'English',
            'hi' => 'Hindi',
            'sa' => 'Sanskrit',
            'bn' => 'Bengali',
            'ta' => 'Tamil',
            'te' => 'Telugu',
            'mr' => 'Marathi',
            'gu' => 'Gujarati',
            'kn' => 'Kannada',
            'ml' => 'Malayalam',
            'pa' => 'Punjabi',
            'ur' => 'Urdu',
            'fr' => 'French',
            'de' => 'German',
            'es' => 'Spanish',
        ];

        if (isset($map[$code])) {
            return $map[$code];
        }

        return strtoupper($code);
    }
}
