<?php
// modules/ai/chat.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// ========== LOAD CONFIG ==========
$configPath = __DIR__ . '/config.private.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'config.private.php not found.']);
    exit;
}

require $configPath;

$API_URL = defined('AI_API_URL') ? AI_API_URL : null;
$API_KEY = defined('AI_API_KEY') ? AI_API_KEY : null;

if (!$API_URL || !$API_KEY) {
    http_response_code(500);
    echo json_encode(['error' => 'AI_API_URL or AI_API_KEY not defined in config.private.php']);
    exit;
}

// ========== ONLY POST ==========
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// ========== READ JSON BODY ==========
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body.']);
    exit;
}

// Fix: Use consistent variable names and define both primary and fallback
$primaryModel  = $data['primary_model']  ?? $data['model'] ?? 'x-ai/grok-code-fast-1';
$fallbackModel = $data['fallback_model'] ?? 'google/gemini-2.5-flash';
$systemPrompt  = $data['system_prompt']  ?? '';
$messages      = $data['messages']       ?? [];

if (!is_array($messages) || count($messages) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'No messages provided.']);
    exit;
}

// ========== BUILD FINAL MESSAGES ==========
$finalMessages = [];
if ($systemPrompt !== '') {
    $finalMessages[] = [
        'role' => 'system',
        'content' => $systemPrompt,
    ];
}

foreach ($messages as $m) {
    if (!isset($m['role'], $m['content'])) {
        continue;
    }
    $finalMessages[] = [
        'role' => $m['role'],
        'content' => $m['content'],
    ];
}

/**
 * Call OpenRouter model
 * - Uses OpenAI-compatible /chat/completions
 * - Adds required / recommended headers
 */
function call_model($apiUrl, $apiKey, $model, $messages)
{
    $payload = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 2000,
    ];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            // Recommended by OpenRouter (identify your app + origin)
            'HTTP-Referer: http://localhost',      // change to your domain if deployed
            'X-Title: AgroSync FarmBot',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 45,
    ]);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        throw new Exception('cURL error: ' . $err);
    }

    if ($status < 200 || $status >= 300) {
        $clean   = trim(preg_replace('/\s+/', ' ', (string)$response));
        $snippet = substr($clean, 0, 300);
        throw new Exception("Upstream API HTTP $status: " . $snippet);
    }

    $json = json_decode($response, true);
    if (!is_array($json)) {
        $snippet = substr((string)$response, 0, 200);
        throw new Exception('Invalid JSON from upstream: ' . $snippet);
    }

    return $json;
}

// ========== PRIMARY → FALLBACK ==========
try {
    $result = call_model($API_URL, $API_KEY, $primaryModel, $finalMessages);
} catch (Exception $e) {
    // Log primary model failure for debugging
    error_log("Primary model ($primaryModel) failed: " . $e->getMessage());
    
    try {
        // Try fallback model
        $result = call_model($API_URL, $API_KEY, $fallbackModel, $finalMessages);
    } catch (Exception $fallbackException) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Both primary and fallback model failed.',
            'primary_error' => $e->getMessage(),
            'fallback_error' => $fallbackException->getMessage(),
        ]);
        exit;
    }
}

// ========== NORMALIZE TO choices[0].message.content ==========
if (isset($result['choices'][0]['message']['content'])) {
    echo json_encode([
        'choices' => [
            [
                'message' => [
                    'content' => $result['choices'][0]['message']['content'],
                ],
            ],
        ],
    ]);
    exit;
}

if (isset($result['choices'][0]['text'])) {
    echo json_encode([
        'choices' => [
            [
                'message' => [
                    'content' => $result['choices'][0]['text'],
                ],
            ],
        ],
    ]);
    exit;
}

if (isset($result['reply'])) {
    echo json_encode([
        'choices' => [
            [
                'message' => [
                    'content' => $result['reply'],
                ],
            ],
        ],
    ]);
    exit;
}

// Fallback if upstream is weird
echo json_encode([
    'choices' => [
        [
            'message' => [
                'content' => '(no content from upstream model)',
            ],
        ],
    ],
]);
exit;