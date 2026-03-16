<?php

declare(strict_types=1);

namespace CustomFonts;

class GoogleFonts
{
    private const API_URL      = 'https://www.googleapis.com/webfonts/v1/webfonts?sort=popularity&key=';
    private const CACHE_TTL    = 86400; // 24 hours
    private const CACHE_FILE   = 'fonts-cache.json';

    /**
     * Returns the full list of Google Fonts, fetched from the API and cached locally.
     * Falls back to the built-in curated list if the API is unavailable.
     *
     * @return array<array{name: string, category: string}>
     */
    public static function getList(): array
    {
        $apiKey = Config::get('GOOGLE_FONTS_API_KEY');
        if ($apiKey) {
            $cached = self::loadCache();
            if ($cached !== null) {
                return $cached;
            }
            $fetched = self::fetchFromApi($apiKey);
            if ($fetched !== null) {
                self::saveCache($fetched);
                return $fetched;
            }
        }

        return self::curatedList();
    }

    /**
     * Returns the Google Fonts CSS URL for a given font family.
     */
    public static function getCssUrl(string $family): string
    {
        $slug = urlencode($family);
        return "https://fonts.googleapis.com/css2?family={$slug}:wght@300;400;500;700;800&display=swap";
    }

    /**
     * Returns the font list as a JSON string (for use in JavaScript).
     */
    public static function getListJson(): string
    {
        return json_encode(self::getList(), JSON_UNESCAPED_UNICODE);
    }

    // -- Private ----------------------------------------------------------

    /**
     * Fetches the font list from the Google Fonts API.
     *
     * @return array<array{name: string, category: string}>|null
     */
    private static function fetchFromApi(string $apiKey): ?array
    {
        $url = self::API_URL . urlencode($apiKey);

        $ctx = stream_context_create(['http' => [
            'timeout'     => 5,
            'user_agent'  => 'shoptet-custom-fonts/1.0',
            'ignore_errors' => true,
        ]]);

        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            error_log('[custom-fonts] Google Fonts API fetch failed');
            return null;
        }

        $data = json_decode($raw, true);
        if (empty($data['items']) || !\is_array($data['items'])) {
            error_log('[custom-fonts] Google Fonts API returned unexpected response');
            return null;
        }

        $fonts = [];
        foreach ($data['items'] as $item) {
            $name     = trim($item['family'] ?? '');
            $category = trim($item['category'] ?? 'sans-serif');
            if ($name !== '') {
                $fonts[] = ['name' => $name, 'category' => $category];
            }
        }

        return $fonts ?: null;
    }

    /**
     * Loads the font list from cache if it exists and is not expired.
     *
     * @return array<array{name: string, category: string}>|null
     */
    private static function loadCache(): ?array
    {
        $path = self::cachePath();
        if (!file_exists($path)) {
            return null;
        }
        if ((time() - filemtime($path)) > self::CACHE_TTL) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);
        return (\is_array($data) && !empty($data)) ? $data : null;
    }

    /**
     * Saves the font list to the cache file.
     *
     * @param array<array{name: string, category: string}> $fonts
     */
    private static function saveCache(array $fonts): void
    {
        $path = self::cachePath();
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, json_encode($fonts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private static function cachePath(): string
    {
        return Config::tokensDir() . DIRECTORY_SEPARATOR . self::CACHE_FILE;
    }

    /**
     * Built-in curated list used as fallback when the API is unavailable.
     *
     * @return array<array{name: string, category: string}>
     */
    private static function curatedList(): array
    {
        return [
            // Sans-serif
            ['name' => 'Inter',             'category' => 'sans-serif'],
            ['name' => 'Roboto',            'category' => 'sans-serif'],
            ['name' => 'Open Sans',         'category' => 'sans-serif'],
            ['name' => 'Lato',              'category' => 'sans-serif'],
            ['name' => 'Montserrat',        'category' => 'sans-serif'],
            ['name' => 'Poppins',           'category' => 'sans-serif'],
            ['name' => 'Nunito',            'category' => 'sans-serif'],
            ['name' => 'Raleway',           'category' => 'sans-serif'],
            ['name' => 'Ubuntu',            'category' => 'sans-serif'],
            ['name' => 'Rubik',             'category' => 'sans-serif'],
            ['name' => 'Work Sans',         'category' => 'sans-serif'],
            ['name' => 'Oswald',            'category' => 'sans-serif'],
            ['name' => 'Source Sans 3',     'category' => 'sans-serif'],
            ['name' => 'Noto Sans',         'category' => 'sans-serif'],
            ['name' => 'PT Sans',           'category' => 'sans-serif'],
            ['name' => 'Cabin',             'category' => 'sans-serif'],
            ['name' => 'Barlow',            'category' => 'sans-serif'],
            ['name' => 'Fira Sans',         'category' => 'sans-serif'],
            ['name' => 'DM Sans',           'category' => 'sans-serif'],
            ['name' => 'Space Grotesk',     'category' => 'sans-serif'],
            ['name' => 'Outfit',            'category' => 'sans-serif'],
            // Serif
            ['name' => 'Merriweather',      'category' => 'serif'],
            ['name' => 'Playfair Display',  'category' => 'serif'],
            ['name' => 'Lora',              'category' => 'serif'],
            ['name' => 'PT Serif',          'category' => 'serif'],
            ['name' => 'Libre Baskerville', 'category' => 'serif'],
            ['name' => 'EB Garamond',       'category' => 'serif'],
            ['name' => 'Bitter',            'category' => 'serif'],
            // Display
            ['name' => 'Pacifico',          'category' => 'display'],
            ['name' => 'Dancing Script',    'category' => 'display'],
            ['name' => 'Lobster',           'category' => 'display'],
            ['name' => 'Comfortaa',         'category' => 'display'],
            // Monospace
            ['name' => 'Inconsolata',       'category' => 'monospace'],
            ['name' => 'Source Code Pro',   'category' => 'monospace'],
            ['name' => 'JetBrains Mono',    'category' => 'monospace'],
        ];
    }
}
