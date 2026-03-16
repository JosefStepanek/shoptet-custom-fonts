<?php

declare(strict_types=1);

namespace CustomFonts;

class Config
{
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = dirname(__DIR__) . '/.env';
        if (file_exists($envFile)) {
            $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->load();
        }

        self::$loaded = true;
    }

    public static function get(string $key, string $default = ''): string
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }

    public static function isMockMode(): bool
    {
        return strtolower(self::get('MOCK_MODE', 'false')) === 'true';
    }

    public static function baseUrl(): string
    {
        return rtrim(self::get('BASE_URL', 'http://localhost'), '/');
    }

    public static function partnerToken(): string
    {
        return self::get('PARTNER_TOKEN');
    }

    public static function tokensDir(): string
    {
        return dirname(__DIR__) . '/tokens';
    }
}
