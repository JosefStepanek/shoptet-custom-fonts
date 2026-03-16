<?php

declare(strict_types=1);

namespace CustomFonts;

/**
 * Wrapper around the Shoptet /api/template-include endpoint.
 *
 * GET    /api/template-include            → list currently injected HTML codes
 * POST   /api/template-include            → create/overwrite code for a location
 * DELETE /api/template-include/{location} → remove code for a location
 *
 * Docs: https://developers.shoptet.com/api/documentation/inserting-html-codes/
 */
class ShoptetApi
{
    private const LOCATION = 'common-header';
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
     * Returns the currently configured Google Fonts family (e.g. "Roboto"),
     * or null if no custom font is set.
     */
    public function getCurrentFontFamily(): ?string
    {
        if (Config::isMockMode()) {
            return $this->getMockFont();
        }

        $data = $this->apiGet('/api/template-include');
        if (!$data || empty($data['data']['codes'])) {
            return null;
        }

        foreach ($data['data']['codes'] as $code) {
            if (($code['location'] ?? '') !== self::LOCATION) {
                continue;
            }
            return $this->parseFontFamilyFromHtml($code['code'] ?? '');
        }

        return null;
    }

    /**
     * Sets a Google Font for the entire e-shop.
     * Writes a <link> + <style> block into common-header.
     */
    public function setGoogleFont(string $family): void
    {
        $slug = str_replace(' ', '+', $family);
        $cssUrl = "https://fonts.googleapis.com/css2?family={$slug}:wght@300;400;500;700&display=swap";

        $html = $this->buildFontHtml($family, $cssUrl);

        if (Config::isMockMode()) {
            $this->saveMockFont($family);
            return;
        }

        $this->apiPost('/api/template-include', [
            'location' => self::LOCATION,
            'code'     => $html,
        ]);
    }

    /**
     * Removes the custom font (restores the e-shop to its default typeface).
     */
    public function clearFont(): void
    {
        if (Config::isMockMode()) {
            $this->saveMockFont(null);
            return;
        }

        $this->apiDelete('/api/template-include/' . self::LOCATION);
    }

    // -------------------------------------------------------------------------

    private function buildFontHtml(string $family, string $cssUrl): string
    {
        $familyCss = "'{$family}', sans-serif";
        return <<<HTML
<link id="shoptet-custom-fonts" href="{$cssUrl}" rel="stylesheet">
<style>
body,p,a,span,li,td,th,input,button,select,textarea,label {
  font-family: {$familyCss};
}
h1,h2,h3,h4,h5,h6 {
  font-family: {$familyCss};
}
</style>
HTML;
    }

    private function parseFontFamilyFromHtml(string $html): ?string
    {
        // Extract font family from href: ...family=Roboto:wght...
        if (preg_match('/family=([^:&"]+)/', $html, $m)) {
            return str_replace('+', ' ', urldecode($m[1]));
        }
        return null;
    }

    private function apiGet(string $path): ?array
    {
        $url = "{$this->eshopUrl}{$path}";
        $ch = curl_init($url);
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
        $ch = curl_init($url);
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
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => $this->authHeaders(),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_exec($ch);
        curl_close($ch);
        return $status < 400;
    }

    private function authHeaders(): array
    {
        return ["Authorization: Bearer {$this->token}"];
    }

    // --- Mock helpers ---

    private function getMockFont(): ?string
    {
        $file = Config::tokensDir() . '/mock-font.txt';
        if (!file_exists($file)) {
            return null;
        }
        $v = trim(file_get_contents($file));
        return $v === '' ? null : $v;
    }

    private function saveMockFont(?string $family): void
    {
        $file = Config::tokensDir() . '/mock-font.txt';
        file_put_contents($file, $family ?? '');
    }
}
