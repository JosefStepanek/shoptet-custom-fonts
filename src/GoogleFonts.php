<?php

declare(strict_types=1);

namespace CustomFonts;

class GoogleFonts
{
    /**
     * Curated list of the most popular Google Fonts.
     * Ordered: sans-serif => serif => display => monospace
     *
     * @return array<array{name: string, category: string}>
     */
    public static function getList(): array
    {
        return [
            // Sans-serif - most popular
            ['name' => 'Inter',            'category' => 'sans-serif'],
            ['name' => 'Roboto',           'category' => 'sans-serif'],
            ['name' => 'Open Sans',        'category' => 'sans-serif'],
            ['name' => 'Lato',             'category' => 'sans-serif'],
            ['name' => 'Montserrat',       'category' => 'sans-serif'],
            ['name' => 'Poppins',          'category' => 'sans-serif'],
            ['name' => 'Nunito',           'category' => 'sans-serif'],
            ['name' => 'Raleway',          'category' => 'sans-serif'],
            ['name' => 'Ubuntu',           'category' => 'sans-serif'],
            ['name' => 'Rubik',            'category' => 'sans-serif'],
            ['name' => 'Work Sans',        'category' => 'sans-serif'],
            ['name' => 'Oswald',           'category' => 'sans-serif'],
            ['name' => 'Source Sans 3',    'category' => 'sans-serif'],
            ['name' => 'Noto Sans',        'category' => 'sans-serif'],
            ['name' => 'PT Sans',          'category' => 'sans-serif'],
            ['name' => 'Cabin',            'category' => 'sans-serif'],
            ['name' => 'Barlow',           'category' => 'sans-serif'],
            ['name' => 'Fira Sans',        'category' => 'sans-serif'],
            ['name' => 'Heebo',            'category' => 'sans-serif'],
            ['name' => 'Mukta',            'category' => 'sans-serif'],
            ['name' => 'Karla',            'category' => 'sans-serif'],
            ['name' => 'Quicksand',        'category' => 'sans-serif'],
            ['name' => 'DM Sans',          'category' => 'sans-serif'],
            ['name' => 'Josefin Sans',     'category' => 'sans-serif'],
            ['name' => 'Exo 2',            'category' => 'sans-serif'],
            ['name' => 'Libre Franklin',   'category' => 'sans-serif'],
            ['name' => 'Space Grotesk',    'category' => 'sans-serif'],
            ['name' => 'Mulish',           'category' => 'sans-serif'],
            ['name' => 'Arimo',            'category' => 'sans-serif'],
            ['name' => 'Outfit',           'category' => 'sans-serif'],

            // Serif
            ['name' => 'Merriweather',     'category' => 'serif'],
            ['name' => 'Playfair Display', 'category' => 'serif'],
            ['name' => 'Lora',             'category' => 'serif'],
            ['name' => 'PT Serif',         'category' => 'serif'],
            ['name' => 'Libre Baskerville','category' => 'serif'],
            ['name' => 'Crimson Text',     'category' => 'serif'],
            ['name' => 'EB Garamond',      'category' => 'serif'],
            ['name' => 'Bitter',           'category' => 'serif'],
            ['name' => 'Zilla Slab',       'category' => 'serif'],
            ['name' => 'Domine',           'category' => 'serif'],
            ['name' => 'Vollkorn',         'category' => 'serif'],
            ['name' => 'Alegreya',         'category' => 'serif'],
            ['name' => 'Cormorant Garamond','category' => 'serif'],
            ['name' => 'Spectral',         'category' => 'serif'],
            ['name' => 'Cinzel',           'category' => 'serif'],

            // Display / decorative
            ['name' => 'Pacifico',         'category' => 'display'],
            ['name' => 'Dancing Script',   'category' => 'display'],
            ['name' => 'Lobster',          'category' => 'display'],
            ['name' => 'Comfortaa',        'category' => 'display'],
            ['name' => 'Anton',            'category' => 'display'],
            ['name' => 'Righteous',        'category' => 'display'],
            ['name' => 'Abril Fatface',    'category' => 'display'],
            ['name' => 'Satisfy',          'category' => 'display'],

            // Monospace
            ['name' => 'Inconsolata',      'category' => 'monospace'],
            ['name' => 'Source Code Pro',  'category' => 'monospace'],
            ['name' => 'Fira Mono',        'category' => 'monospace'],
            ['name' => 'JetBrains Mono',   'category' => 'monospace'],
            ['name' => 'Space Mono',       'category' => 'monospace'],
        ];
    }

    /**
     * Returns the Google Fonts CSS URL for a given font family.
     */
    public static function getCssUrl(string $family): string
    {
        $slug = urlencode($family);
        return "https://fonts.googleapis.com/css2?family={$slug}:wght@300;400;500;700&display=swap";
    }

    /**
     * Returns the font list as a JSON string (for use in JavaScript).
     */
    public static function getListJson(): string
    {
        return json_encode(self::getList(), JSON_UNESCAPED_UNICODE);
    }
}
