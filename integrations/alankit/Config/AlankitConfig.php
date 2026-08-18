<?php

namespace Integrations\Alankit\Config;

/**
 * Configuration container for Alankit (Eraahi Gateway) API credentials and settings.
 */
class AlankitConfig
{
    private string $username;
    private string $password;
    private string $subscriptionKey;
    private string $appKey;
    private string $gstin;
    private string $baseUrl;
    private string $publicKeyPath;
    private bool $forceRefreshAccessToken;

    public function __construct(
        string $username,
        string $password,
        string $subscriptionKey,
        string $appKey,
        string $gstin,
        string $baseUrl = 'https://eraahigateway.alankit.com',
        string $publicKeyPath = 'public.txt',
        bool $forceRefreshAccessToken = true
    ) {
        $this->username = trim($username);
        $this->password = trim($password);
        $this->subscriptionKey = trim($subscriptionKey);
        $this->appKey = trim($appKey);
        $this->gstin = strtoupper(trim($gstin));
        $this->baseUrl = rtrim(trim($baseUrl), '/');
        $this->publicKeyPath = trim($publicKeyPath);
        $this->forceRefreshAccessToken = $forceRefreshAccessToken;
    }

    /**
     * Build config from array or application environment settings.
     *
     * @param array<string, mixed>|null $config
     */
    public static function fromAppConfig(?array $config = null): self
    {
        if ($config === null && function_exists('getAlankitConfig')) {
            $config = getAlankitConfig();
        }

        $config = $config ?? [];

        $username = (string) ($config['username'] ?? $config['user_name'] ?? '');
        $password = (string) ($config['password'] ?? '');
        $subscriptionKey = (string) ($config['subscription_key'] ?? $config['ocp_subscription_key'] ?? '');
        $appKey = (string) ($config['app_key'] ?? '');
        $gstin = (string) ($config['gstin'] ?? '');
        $baseUrl = (string) ($config['base_url'] ?? 'https://eraahigateway.alankit.com');
        $publicKeyPath = (string) ($config['public_key_path'] ?? 'public.txt');
        $forceRefresh = !empty($config['force_refresh_access_token']);

        return new self(
            $username,
            $password,
            $subscriptionKey,
            $appKey,
            $gstin,
            $baseUrl,
            $publicKeyPath,
            $forceRefresh
        );
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getSubscriptionKey(): string
    {
        return $this->subscriptionKey;
    }

    public function getAppKey(): string
    {
        return $this->appKey;
    }

    public function getGstin(): string
    {
        return $this->gstin;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getPublicKeyPath(): string
    {
        return $this->publicKeyPath;
    }

    public function getForceRefreshAccessToken(): bool
    {
        return $this->forceRefreshAccessToken;
    }

    public function isValid(): bool
    {
        return $this->username !== ''
            && $this->password !== ''
            && $this->subscriptionKey !== ''
            && $this->appKey !== ''
            && $this->gstin !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'password' => '***',
            'subscription_key' => $this->subscriptionKey !== '' ? '***' : '',
            'app_key' => $this->appKey !== '' ? '***' : '',
            'gstin' => $this->gstin,
            'base_url' => $this->baseUrl,
            'public_key_path' => $this->publicKeyPath,
            'force_refresh_access_token' => $this->forceRefreshAccessToken,
        ];
    }
}
