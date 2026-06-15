<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsDriverInterface;
use Illuminate\Support\Facades\Http;

class CustomHttpDriver implements SmsDriverInterface
{
    private array $credentials;

    public function __construct(
        private string $endpoint,
        private string $method,
        private string $headersJson,
        private string $bodyTemplate,
        private string $successKey,
        private string $successValue,
        private string $apiKey,
        string $credentialsJson,
    ) {
        $this->credentials = json_decode($credentialsJson ?: '{}', true) ?? [];
    }

    public function send(string $mobile, string $message, string $senderId): array
    {
        try {
            // Build placeholder map — raw values, no pre-encoding
            $vars = array_merge($this->credentials, [
                'mobile'    => $mobile,
                'message'   => $message,
                'sender_id' => $senderId,
                'api_key'   => $this->apiKey,
            ]);

            $url     = $this->replacePlaceholders($this->endpoint, $vars);
            $headers = $this->buildHeaders($vars);
            $params  = $this->buildParams($vars);

            $request  = Http::timeout(15)->withHeaders($headers);
            $response = strtoupper($this->method) === 'GET'
                ? $request->get($url, $params)
                : $request->post($url, $params);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function checkBalance(): array
    {
        return ['success' => false, 'error' => 'Custom HTTP provider ke liye balance check available nahi hai.'];
    }

    public function testConnection(string $apiKey, string $senderId): array
    {
        if (empty($this->endpoint)) {
            return ['success' => false, 'error' => 'Endpoint URL required.'];
        }

        $parsed = parse_url($this->endpoint);
        if (empty($parsed['host'])) {
            return ['success' => false, 'error' => 'Invalid endpoint URL.'];
        }

        try {
            $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . $parsed['host'];
            Http::timeout(5)->get($baseUrl);
            return [
                'success' => true,
                'message' => 'Server reachable hai. Credentials verify karne ke liye ek real SMS bhejo.',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Server unreachable: ' . $e->getMessage()];
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function replacePlaceholders(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) $value, $template);
        }
        return $template;
    }

    private function buildHeaders(array $vars): array
    {
        if (empty($this->headersJson)) {
            return [];
        }
        $raw = json_decode($this->headersJson, true) ?? [];
        $out = [];
        foreach ($raw as $k => $v) {
            $out[$k] = $this->replacePlaceholders((string) $v, $vars);
        }
        return $out;
    }

    private function buildParams(array $vars): array
    {
        if (empty($this->bodyTemplate)) {
            return [];
        }

        // Detect format BEFORE replacing placeholders to avoid encoding issues.

        // JSON format: parse template as JSON structure first, then fill values
        $templateObj = json_decode($this->bodyTemplate, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($templateObj)) {
            return $this->fillArray($templateObj, $vars);
        }

        // Query string format: key={placeholder}&key2={placeholder2}
        // Split on & at template level (before value replacement) so message
        // content containing & or = doesn't break the structure.
        $params = [];
        foreach (explode('&', $this->bodyTemplate) as $pair) {
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            $key = trim($k);
            if ($key === '') continue;
            $params[$key] = $this->replacePlaceholders(trim($v), $vars);
        }
        return $params;
    }

    private function fillArray(array $arr, array $vars): array
    {
        foreach ($arr as $k => &$v) {
            if (is_string($v)) {
                $v = $this->replacePlaceholders($v, $vars);
            } elseif (is_array($v)) {
                $v = $this->fillArray($v, $vars);
            }
        }
        return $arr;
    }

    private function parseResponse(\Illuminate\Http\Client\Response $response): array
    {
        $body = $response->json();

        // Plain text response (some providers return "success" or "1234567890")
        if ($body === null) {
            $text    = trim($response->body());
            $success = $response->successful() && (
                empty($this->successKey) ||
                str_contains(strtolower($text), strtolower($this->successValue ?: 'success'))
            );
            return ['success' => $success, 'response' => $text];
        }

        // No success key configured → treat HTTP 2xx as success
        if (empty($this->successKey)) {
            return ['success' => $response->successful(), 'response' => $body];
        }

        // Navigate dotted key path, e.g. "data.status"
        $actual   = data_get($body, $this->successKey);
        $expected = $this->successValue;

        $success = match (true) {
            is_bool($actual)    => $actual === filter_var($expected, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            is_numeric($actual) => (string) $actual === (string) $expected,
            default             => strtolower((string) $actual) === strtolower((string) $expected),
        };

        return [
            'success'  => $success,
            'response' => $body,
            'error'    => $success ? null : "Expected {$this->successKey}='{$expected}', got '" . json_encode($actual) . "'",
        ];
    }
}
