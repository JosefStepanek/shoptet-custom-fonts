<?php

declare(strict_types=1);

namespace CustomFonts;

/**
 * Wrapper around the Shoptet /api/template-include endpoint.
 *
 * GET    /api/template-include            => list currently injected HTML codes
 * POST   /api/template-include            => create/overwrite code for a location
 * DELETE /api/template-include/{location} => remove code for a location
 *
 * Settings are stored as a JSON comment inside the injected HTML block so that
 * the full configuration can be recovered later without a separate database.
 *
 * Docs: https://developers.shoptet.com/api/documentation/inserting-html-codes/
 */
class ShoptetApi
{
    private const LOCATION       = 'common-header';
    private const CONFIG_MARKER  = 'shoptet-custom-fonts-config';

    /** Default heading sizes (desktop) applied when no explicit value is set. */
    public const DEFAULT_HEADING_SIZES = [
        'h1' => '36px',
        'h2' => '30px',
        'h3' => '24px',
        'h4' => '20px',
        'h5' => '18px',
        'h6' => '16px',
    ];

    /** Default heading sizes for mobile (max-width: 767px) – approx. 3/4 of desktop. */
    public const DEFAULT_HEADING_MOBILE_SIZES = [
        'h1' => '27px',
        'h2' => '22px',
        'h3' => '18px',
        'h4' => '15px',
        'h5' => '14px',
        'h6' => '12px',
    ];

    private string $projectId;
    private string $eshopUrl;
    private string $token;

    public function __construct(string $projectId)
    {
        $this->projectId = $projectId;

        $tokenData = OAuth::loadTokenData($projectId);
        if (!$tokenData) {
            throw new \RuntimeException("No token found for project: $projectId");
        }

        $this->eshopUrl = rtrim($tokenData['eshop_url'], '/');
        $this->token    = $tokenData['access_token'];
    }

    /**
     * Returns the full current font settings array, or an empty array if none is set.
     *
     * Shape:
     * [
     *   'body'     => ['family'=>string|null, 'weight'=>string, 'size'=>string, 'extraSelectors'=>string],
     *   'headings' => ['family'=>string|null, 'weight'=>string, 'extraSelectors'=>string,
     *                  'sizes'=>['h1'=>string, ..., 'h6'=>string]],
     * ]
     */
    public function getCurrentSettings(): array
    {
        if (Config::isMockMode()) {
            return $this->getMockSettings();
        }

        $data = $this->apiGet('/api/template-include');
        if (!$data || empty($data['data']['codes'])) {
            return [];
        }

        foreach ($data['data']['codes'] as $code) {
            if (($code['location'] ?? '') !== self::LOCATION) {
                continue;
            }
            $settings = $this->parseSettingsFromHtml($code['code'] ?? '');
            if ($settings) {
                return $settings;
            }
        }

        return [];
    }

    /**
     * Saves the complete font settings to Shoptet's template-include.
     * Encodes the settings as a JSON comment so they can be restored later.
     *
     * @param array $settings Shape as described in getCurrentSettings().
     */
    public function setFonts(array $settings): void
    {
        $html = $this->buildFontHtml($settings);

        if (Config::isMockMode()) {
            $this->saveMockSettings($settings);
            return;
        }

        $this->apiPost('/api/template-include', [
            'location' => self::LOCATION,
            'code'     => $html,
        ]);
    }

    /**
     * Removes all custom font overrides.
     */
    public function clearFonts(): void
    {
        if (Config::isMockMode()) {
            $this->saveMockSettings([]);
            return;
        }

        $this->apiDelete('/api/template-include/' . self::LOCATION);
    }

    // -------------------------------------------------------------------------
    // HTML / CSS generation
    // -------------------------------------------------------------------------

    private function buildFontHtml(array $settings): string
    {
        $parts = [];

        // Collect unique font families to import
        $families = [];
        $bodyFamily     = $settings['body']['family'] ?? null;
        $headingFamily  = $settings['headings']['family'] ?? null;
        if ($bodyFamily)    $families[] = $bodyFamily;
        if ($headingFamily) $families[] = $headingFamily;
        $families = array_unique(array_filter($families));

        foreach ($families as $family) {
            $slug     = str_replace(' ', '+', $family);
            $parts[]  = "<link href=\"https://fonts.googleapis.com/css2?family={$slug}:wght@100;200;300;400;500;600;700;800;900&display=swap\" rel=\"stylesheet\">";
        }

        // Body CSS
        if ($bodyFamily) {
            $b         = $settings['body'] ?? [];
            $weight    = $b['weight'] ?? '400';
            $size      = trim($b['size'] ?? '');
            $mobSize   = trim($b['mobileSize'] ?? '');
            $lineH     = trim($b['lineHeight'] ?? '');
            $extraSel  = $this->sanitizeSelectors($b['extraSelectors'] ?? '');

            $baseSelectors = 'body,p,a,span,li,td,th,input,button,select,textarea,label';
            $selectors     = $extraSel ? "{$baseSelectors},{$extraSel}" : $baseSelectors;

            $css = "{$selectors}{font-family:'{$bodyFamily}',sans-serif!important;font-weight:{$weight}!important;";
            if ($size !== '') {
                $css .= "font-size:{$size}!important;";
            }
            if ($lineH !== '') {
                $css .= "line-height:{$lineH}!important;";
            }
            $css .= '}';
            $parts[] = "<style>{$css}</style>";

            if ($mobSize !== '') {
                $parts[] = "<style>@media(max-width:767px){{$selectors}{font-size:{$mobSize}!important;}}</style>";
            }
        }

        // Headings CSS
        if ($headingFamily) {
            $h           = $settings['headings'] ?? [];
            $globalWeight = $h['weight'] ?? '700';
            $extraSel    = $this->sanitizeSelectors($h['extraSelectors'] ?? '');

            $baseSel   = 'h1,h2,h3,h4,h5,h6,.h1,.h2,.h3,.h4,.h5,.h6';
            $selectors = $extraSel ? "{$baseSel},{$extraSel}" : $baseSel;

            // Base rule: font-family + default weight for all headings
            $css = "{$selectors}{font-family:'{$headingFamily}',sans-serif!important;font-weight:{$globalWeight}!important;}";

            // Per-heading overrides: size, weight, color, text-transform
            $sizes          = $h['sizes']          ?? [];
            $mobileSizes    = $h['mobileSizes']    ?? [];
            $weights        = $h['weights']        ?? [];
            $colors         = $h['colors']         ?? [];
            $textTransforms = $h['textTransforms'] ?? [];
            $mobileCss      = '';
            foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
                $sz    = trim($sizes[$tag]   ?? '');
                $wt    = trim($weights[$tag] ?? '');
                $color = trim($colors[$tag]  ?? '');
                $upper = !empty($textTransforms[$tag]);
                if ($sz !== '' || $wt !== '' || $color !== '' || $upper) {
                    $props = '';
                    if ($sz !== '')    $props .= "font-size:{$sz}!important;";
                    if ($wt !== '')    $props .= "font-weight:{$wt}!important;";
                    if ($color !== '') $props .= "color:{$color}!important;";
                    if ($upper)        $props .= "text-transform:uppercase!important;";
                    $css .= "{$tag},.{$tag}{{$props}}";
                }

                $msz = trim($mobileSizes[$tag] ?? '');
                if ($msz !== '') {
                    $mobileCss .= "{$tag},.{$tag}{font-size:{$msz}!important;}";
                }
            }

            $parts[] = "<style>{$css}</style>";

            if ($mobileCss !== '') {
                $parts[] = "<style>@media(max-width:767px){{$mobileCss}}</style>";
            }
        }

        // Encode full settings as a JSON comment for later retrieval
        $encoded = json_encode($settings, JSON_UNESCAPED_UNICODE);
        $parts[] = "<!-- " . self::CONFIG_MARKER . ":{$encoded} -->";

        return implode("\n", $parts);
    }

    private function parseSettingsFromHtml(string $html): array
    {
        $marker = preg_quote(self::CONFIG_MARKER, '/');
        if (preg_match("/<!-- {$marker}:(\{.*?\}) -->/s", $html, $m)) {
            $decoded = json_decode($m[1], true);
            if (\is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    /**
     * Removes anything that could break the CSS selector string.
     * Allows letters, digits, spaces, commas, dots, hashes, dashes, underscores, and brackets.
     */
    private function sanitizeSelectors(string $raw): string
    {
        $cleaned = preg_replace('/[^a-zA-Z0-9 ,\.#_\-\[\]=":>+~*()]/', '', $raw);
        return trim($cleaned ?? '');
    }

    // -------------------------------------------------------------------------
    // HTTP helpers
    // -------------------------------------------------------------------------

    private function apiGet(string $path): ?array
    {
        $url = "{$this->eshopUrl}{$path}";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $this->authHeaders(),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $status >= 400) {
            return null;
        }
        return json_decode($body, true);
    }

    private function apiPost(string $path, array $payload): ?array
    {
        $url = "{$this->eshopUrl}{$path}";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [...$this->authHeaders(), 'Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $status >= 400) {
            return null;
        }
        return json_decode($body, true);
    }

    private function apiDelete(string $path): bool
    {
        $url = "{$this->eshopUrl}{$path}";
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => $this->authHeaders(),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $status < 400;
    }

    private function authHeaders(): array
    {
        return ["Authorization: Bearer {$this->token}"];
    }

    // -------------------------------------------------------------------------
    // Mock helpers
    // -------------------------------------------------------------------------

    private function getMockSettings(): array
    {
        $file = Config::tokensDir() . '/mock-settings.json';
        if (!file_exists($file)) {
            return [];
        }
        $data = json_decode(file_get_contents($file), true);
        return \is_array($data) ? $data : [];
    }

    private function saveMockSettings(array $settings): void
    {
        $file = Config::tokensDir() . '/mock-settings.json';
        file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT));
    }
}
