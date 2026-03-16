<?php

declare(strict_types=1);

// Bootstrap
require_once dirname(__DIR__) . '/vendor/autoload.php';

use CustomFonts\Config;
use CustomFonts\OAuth;
use CustomFonts\ShoptetApi;
use CustomFonts\GoogleFonts;

Config::load();

// Simple router based on REQUEST_URI
$requestUri  = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName  = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$basePath    = rtrim(dirname($scriptName), '/');
$path        = '/' . ltrim(substr($requestUri, strlen($basePath)), '/');
$path        = strtok($path, '?'); // strip query string

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// Hlavičky pro iframe
header('X-Content-Type-Options: nosniff');

// ─────────────────────────────────────────────────────────────
// GET /install  – called by Shoptet when the addon is installed
// ─────────────────────────────────────────────────────────────
if ($path === '/install' && $method === 'GET') {
    $code     = trim($_GET['code'] ?? '');
    $eshopUrl = trim($_GET['eshop_url'] ?? '');

    if (empty($code) || empty($eshopUrl)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing code or eshop_url']);
        exit;
    }

    try {
        OAuth::handleInstall($code, $eshopUrl);
    } catch (Throwable $e) {
        error_log('[custom-fonts] Install error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Install failed']);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────
// GET /admin  – Admin UI (rendered inside Shoptet admin iframe)
// URL pattern: /admin?project_id=#PROJECT_ID#&code=#OAUTH_CODE#
// ─────────────────────────────────────────────────────────────
if ($path === '/admin' && $method === 'GET') {
    $projectId = trim($_GET['project_id'] ?? '');
    $oauthCode = trim($_GET['code'] ?? '');

    // Mock mode: allow access without project_id or OAuth code
    if (Config::isMockMode() && empty($projectId)) {
        $projectId = 'mock-eshop';
    }

    if (empty($projectId)) {
        http_response_code(400);
        echo 'Missing project_id.';
        exit;
    }

    // Verify OAuth code (always passes in mock mode)
    if (!Config::isMockMode() && !OAuth::verifyAdminCode($oauthCode, $projectId)) {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }

    // Fetch the currently active font family
    try {
        $api         = new ShoptetApi($projectId);
        $currentFont = $api->getCurrentFontFamily();
    } catch (Throwable $e) {
        $currentFont = null;
    }

    $fonts    = GoogleFonts::getList();
    $fontsJson = GoogleFonts::getListJson();
    $baseUrl  = Config::baseUrl();
    $isMock   = Config::isMockMode();

    include dirname(__DIR__) . '/templates/admin.php';
    exit;
}

// ─────────────────────────────────────────────────────────────
// POST /api/save  – save font selection from admin UI
// ─────────────────────────────────────────────────────────────
if ($path === '/api/save' && $method === 'POST') {
    header('Content-Type: application/json');

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $projectId = trim($body['project_id'] ?? '');
    $fontFamily = trim($body['font_family'] ?? '');

    if (Config::isMockMode() && empty($projectId)) {
        $projectId = 'mock-eshop';
    }

    if (empty($projectId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing project_id']);
        exit;
    }

    try {
        $api = new ShoptetApi($projectId);

        if ($fontFamily === '' || $fontFamily === 'none') {
            $api->clearFont();
            echo json_encode(['status' => 'ok', 'font' => null]);
        } else {
            // Validate against allowlist to prevent injection
            $allowed = array_column(GoogleFonts::getList(), 'name');
            if (!in_array($fontFamily, $allowed, true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid font family']);
                exit;
            }

            $api->setGoogleFont($fontFamily);
            echo json_encode(['status' => 'ok', 'font' => $fontFamily]);
        }
    } catch (Throwable $e) {
        error_log('[custom-fonts] Save error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save font']);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────
// GET /health  – health check endpoint
// ─────────────────────────────────────────────────────────────
if ($path === '/health' && $method === 'GET') {
    header('Content-Type: application/json');
    echo json_encode([
        'status'    => 'ok',
        'mock_mode' => Config::isMockMode(),
        'version'   => '1.0.0',
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 404
// ─────────────────────────────────────────────────────────────
http_response_code(404);
echo json_encode(['error' => 'Not found', 'path' => $path]);
