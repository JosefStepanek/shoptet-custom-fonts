<?php

declare(strict_types=1);

namespace CustomFonts;

class OAuth
{
    /**
     * Handles the installation request from Shoptet.
     * Shoptet calls: GET /install?code=XXX&eshop_url=https://shop.myshoptet.com
     *
     * Docs: https://developers.shoptet.com/api/documentation/installing-the-addon/
     */
    public static function handleInstall(string $code, string $eshopUrl): void
    {
        if (Config::isMockMode()) {
            $projectId = 'mock-eshop';
            self::saveToken($projectId, 'mock-token-12345', $eshopUrl);
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'mock' => true]);
            return;
        }

        // Exchange code for access token
        // Shoptet OAuth endpoint: POST {eshop_url}/action/ApiOAuthServer/getAccessToken
        $token = self::exchangeCodeForToken($code, $eshopUrl);

        // Derive project_id from the e-shop URL
        $projectId = self::getProjectId($eshopUrl, $token);

        // Persist the token
        self::saveToken($projectId, $token, $eshopUrl);

        // Register an empty CSS placeholder (overwritten on first font selection)
        $api = new ShoptetApi($projectId);
        $api->clearFont();

        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }

    /**
     * Verifies the OAuth code from the admin iframe (Simplified OAuth).
     * Returns true if the code is valid, false otherwise.
     *
     * Docs: https://developers.shoptet.com/simplified-oauth-process-for-addons-integration-in-admin/
     */
    public static function verifyAdminCode(string $oauthCode, string $projectId): bool
    {
        if (Config::isMockMode()) {
            return true;
        }

        $tokenData = self::loadTokenData($projectId);
        if (!$tokenData) {
            return false;
        }

        // Verify OAuth code via Shoptet API
        $eshopUrl = $tokenData['eshop_url'];
        $verifyUrl = rtrim($eshopUrl, '/') . '/action/ApiOAuthServer/verifyCode';

        $response = self::httpGet($verifyUrl . '?code=' . urlencode($oauthCode), [
            'Authorization: Bearer ' . $tokenData['access_token'],
        ]);

        return $response !== false && !empty($response);
    }

    public static function saveToken(string $projectId, string $token, string $eshopUrl): void
    {
        $data = [
            'access_token' => $token,
            'eshop_url'    => $eshopUrl,
            'installed_at' => date('c'),
        ];

        $file = Config::tokensDir() . '/' . self::sanitizeFilename($projectId) . '.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }

    public static function loadTokenData(string $projectId): ?array
    {
        if (Config::isMockMode()) {
            return [
                'access_token' => 'mock-token-12345',
                'eshop_url'    => 'https://mock-eshop.myshoptet.com',
                'installed_at' => date('c'),
            ];
        }

        $file = Config::tokensDir() . '/' . self::sanitizeFilename($projectId) . '.json';
        if (!file_exists($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    // -------------------------------------------------------------------------

    private static function exchangeCodeForToken(string $code, string $eshopUrl): string
    {
        $url = rtrim($eshopUrl, '/') . '/action/ApiOAuthServer/getAccessToken';

        $postData = http_build_query([
            'grant_type' => 'authorization_code',
            'code'       => $code,
        ]);

        $partnerToken = Config::partnerToken();
        $response = self::httpPost($url, $postData, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($partnerToken . ':'),
        ]);

        if ($response === false) {
            throw new \RuntimeException('Token exchange failed: no response from ' . $eshopUrl);
        }

        $data = json_decode($response, true);
        if (empty($data['access_token'])) {
            throw new \RuntimeException('Token exchange failed: ' . $response);
        }

        return $data['access_token'];
    }

    private static function getProjectId(string $eshopUrl, string $token): string
    {
        // Use the e-shop hostname as the project_id (stable and unique)
        $parsed = parse_url($eshopUrl);
        return $parsed['host'] ?? md5($eshopUrl);
    }

    private static function sanitizeFilename(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    }

    private static function httpPost(string $url, string $body, array $headers = []): string|false
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private static function httpGet(string $url, array $headers = []): string|false
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
