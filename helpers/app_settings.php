<?php

if (!function_exists('app_settings_model')) {
    function app_settings_model(): ?AppSettings
    {
        global $conn;

        static $model = null;

        if (!($conn instanceof mysqli)) {
            return null;
        }

        if ($model === null) {
            require_once __DIR__ . '/../models/globals/AppSettings.php';
            $model = new AppSettings($conn);
        }

        return $model;
    }
}

if (!function_exists('app_setting_cache')) {
    function &app_setting_cache(): array
    {
        static $cache = [];

        return $cache;
    }
}

if (!function_exists('app_setting')) {
    /**
     * Read an active app setting value from app_settings.
     */
    function app_setting(string $key, $default = null)
    {
        $cache = &app_setting_cache();
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $model = app_settings_model();
        if ($model === null) {
            return $default;
        }

        $cache[$key] = $model->get($key, $default);

        return $cache[$key];
    }
}

if (!function_exists('app_setting_set')) {
    /**
     * Write a setting value to app_settings (logs to settings_audit_log).
     */
    function app_setting_set(string $key, $value, int $userId = 0, bool $force = false): bool
    {
        $model = app_settings_model();
        if ($model === null) {
            return false;
        }

        $result = $model->setValue($key, $value, $userId, $force);
        if ($result === true) {
            app_setting_forget($key);
        }

        return $result === true;
    }
}

if (!function_exists('app_setting_forget')) {
    function app_setting_forget(?string $key = null): void
    {
        $cache = &app_setting_cache();
        if ($key === null) {
            $cache = [];

            return;
        }
        unset($cache[$key]);
    }
}

if (!function_exists('app_setting_firm_details')) {
    /**
     * firm_details-compatible row built from app_settings.
     */
    function app_setting_firm_details(): array
    {
        $model = app_settings_model();
        if ($model === null) {
            return ['id' => 1];
        }

        return $model->getFirmDetailsRow();
    }
}

if (!function_exists('app_setting_global_settings')) {
    /**
     * global_settings-compatible row built from app_settings.
     */
    function app_setting_global_settings(): array
    {
        $model = app_settings_model();
        if ($model === null) {
            return ['id' => 1];
        }

        return $model->getGlobalSettingsRow();
    }
}
