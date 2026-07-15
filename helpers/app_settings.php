<?php

if (!function_exists('app_setting')) {
    /**
     * Read a developer-defined app setting value.
     */
    function app_setting(string $key, $default = null)
    {
        global $conn;

        static $cache = null;
        static $model = null;

        if (!($conn instanceof mysqli)) {
            return $default;
        }

        if ($model === null) {
            require_once __DIR__ . '/../models/globals/AppSettings.php';
            $model = new AppSettings($conn);
        }

        if ($cache === null) {
            $cache = [];
        }

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $cache[$key] = $model->get($key, $default);

        return $cache[$key];
    }
}
