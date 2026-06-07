<?php

declare(strict_types=1);

namespace App\Services\ReportAi;

use App\Enums\ReportAiVendor;
use Illuminate\Support\Facades\Http;
use Throwable;

final class ReportAiCompletionClient
{
    public function generate(string $userQuestion): array
    {
        $runtime = ReportAiRuntimeSettings::resolve();

        if ($runtime->apiKey === '') {
            return ['error' => 'Missing AI API key.'];
        }

        $system = implode("\n\n", [
            SchemaPromptBuilder::build(),
            SchemaPromptBuilder::systemPromptRules(),
            'Respond only with minimal JSON.',
        ]);

        return match ($runtime->vendor) {
            ReportAiVendor::OpenAi, ReportAiVendor::DeepSeek => $this->openAiCompatibleChat($runtime, $system, $userQuestion),
            ReportAiVendor::AnthropicClaude => $this->anthropicMessages($runtime, $system, $userQuestion),
            ReportAiVendor::GoogleGemini => $this->geminiGenerateContent($runtime, $system, $userQuestion),
        };
    }

    /**
     * Chat completion expecting any JSON object (used by admin assistants, not SQL reports).
     *
     * @return array{ok?: true, data?: array<string, mixed>, error?: string}
     */
    public function completeGenericJson(string $system, string $userQuestion): array
    {
        $runtime = ReportAiRuntimeSettings::resolve();

        if ($runtime->apiKey === '') {
            return ['error' => 'Missing AI API key.'];
        }

        return match ($runtime->vendor) {
            ReportAiVendor::OpenAi, ReportAiVendor::DeepSeek => $this->openAiCompatibleChatGeneric($runtime, $system, $userQuestion),
            ReportAiVendor::AnthropicClaude => $this->anthropicMessagesGeneric($runtime, $system, $userQuestion),
            ReportAiVendor::GoogleGemini => $this->geminiGenerateContentGeneric($runtime, $system, $userQuestion),
        };
    }

    private function openAiCompatibleChat(ReportAiRuntimeSettings $runtime, string $system, string $userQuestion): array
    {
        $base = $runtime->baseUrlOverride ?? $runtime->vendor->defaultBaseUrl();
        $endpoint = rtrim($base, '/').'/chat/completions';

        $first = $this->postOpenAiStyle($endpoint, $runtime, $system, $userQuestion, jsonMode: true);
        if (($first['ok'] ?? false) === true) {
            return $this->parseAssistantJsonPayload((string) ($first['text'] ?? ''));
        }

        $suffix = "\n\nReturn compact JSON only with keys sql (string SELECT) and summary (string).";
        $second = $this->postOpenAiStyle($endpoint, $runtime, $system, $userQuestion.$suffix, jsonMode: false);
        if (($second['ok'] ?? false) === true) {
            return $this->parseAssistantJsonPayload((string) ($second['text'] ?? ''));
        }

        $hintFirst = trim((string) ($first['snippet'] ?? ''));
        $hintSecond = trim((string) ($second['snippet'] ?? ''));
        $hint = $hintFirst !== '' ? $hintFirst : $hintSecond;

        return ['error' => 'AI chat request failed: '.($hint !== '' ? $hint : 'Unknown error.')];
    }

    /**
     * @return array{ok?: true, data?: array<string, mixed>, error?: string}
     */
    private function openAiCompatibleChatGeneric(ReportAiRuntimeSettings $runtime, string $system, string $userQuestion): array
    {
        $base = $runtime->baseUrlOverride ?? $runtime->vendor->defaultBaseUrl();
        $endpoint = rtrim($base, '/').'/chat/completions';

        $first = $this->postOpenAiStyle($endpoint, $runtime, $system, $userQuestion, jsonMode: true);
        if (($first['ok'] ?? false) === true) {
            $parsed = $this->parseDecodedJson((string) ($first['text'] ?? ''));
            if (isset($parsed['data'])) {
                return ['ok' => true, 'data' => $parsed['data']];
            }
        }

        $suffix = "\n\nReturn exactly one JSON object. No markdown, no commentary.";
        $second = $this->postOpenAiStyle($endpoint, $runtime, $system, $userQuestion.$suffix, jsonMode: false);
        if (($second['ok'] ?? false) === true) {
            $parsed = $this->parseDecodedJson((string) ($second['text'] ?? ''));
            if (isset($parsed['data'])) {
                return ['ok' => true, 'data' => $parsed['data']];
            }
        }

        $hintFirst = trim((string) ($first['snippet'] ?? ''));
        $hintSecond = trim((string) ($second['snippet'] ?? ''));
        $hint = $hintFirst !== '' ? $hintFirst : $hintSecond;

        return ['error' => 'AI chat request failed: '.($hint !== '' ? $hint : 'Unknown error.')];
    }

    /**
     * @return array{ok?: true, data?: array<string, mixed>, error?: string}
     */
    private function anthropicMessagesGeneric(ReportAiRuntimeSettings $runtime, string $system, string $userQuestion): array
    {
        $base = $runtime->baseUrlOverride ?? $runtime->vendor->defaultBaseUrl();
        $endpoint = rtrim($base, '/').'/messages';

        try {
            $response = Http::timeout($runtime->timeoutSeconds)
                ->acceptJson()
                ->withHeaders([
                    'x-api-key' => $runtime->apiKey,
                    'anthropic-version' => '2023-06-01',
                ])
                ->post($endpoint, [
                    'model' => $runtime->resolvedModel,
                    'max_tokens' => 8192,
                    'temperature' => 0,
                    'system' => $system,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $userQuestion."\n\nReturn only one JSON object as specified in the system instructions. No prose outside JSON.",
                        ],
                    ],
                ]);
        } catch (Throwable $e) {
            return ['error' => 'Unable to reach Anthropic: '.$e->getMessage()];
        }

        if (! $response->successful()) {
            return ['error' => 'Anthropic request failed: '.mb_substr((string) $response->body(), 0, 380)];
        }

        $joined = '';
        $blocks = data_get((array) $response->json(), 'content');
        if (is_array($blocks)) {
            foreach ($blocks as $block) {
                if (! is_array($block) || (($block['type'] ?? '') !== 'text')) {
                    continue;
                }
                $joined .= (string) ($block['text'] ?? '');
            }
        }

        $parsed = $this->parseDecodedJson($joined);

        return isset($parsed['data']) ? ['ok' => true, 'data' => $parsed['data']] : ['error' => (string) ($parsed['error'] ?? 'Could not parse Anthropic JSON.')];
    }

    /**
     * @return array{ok?: true, data?: array<string, mixed>, error?: string}
     */
    private function geminiGenerateContentGeneric(ReportAiRuntimeSettings $runtime, string $system, string $userQuestion): array
    {
        $host = $runtime->baseUrlOverride ?? $runtime->vendor->defaultBaseUrl();
        $endpoint = rtrim($host, '/').'/models/'.rawurlencode($runtime->resolvedModel)
            .':generateContent?key='.rawurlencode($runtime->apiKey);

        try {
            $response = Http::timeout($runtime->timeoutSeconds)
                ->acceptJson()
                ->post($endpoint, [
                    'systemInstruction' => ['parts' => [['text' => $system]]],
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $userQuestion]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'responseMimeType' => 'application/json',
                    ],
                ]);
        } catch (Throwable $e) {
            return ['error' => 'Unable to reach Gemini: '.$e->getMessage()];
        }

        if (! $response->successful()) {
            return ['error' => 'Gemini request failed: '.mb_substr((string) $response->body(), 0, 380)];
        }

        $text = data_get((array) $response->json(), 'candidates.0.content.parts.0.text');
        $parsed = $this->parseDecodedJson(is_string($text) ? $text : '');

        return isset($parsed['data']) ? ['ok' => true, 'data' => $parsed['data']] : ['error' => (string) ($parsed['error'] ?? 'Could not parse Gemini JSON.')];
    }

    private function postOpenAiStyle(string $endpoint, ReportAiRuntimeSettings $runtime, string $system, string $userQuestion, bool $jsonMode): array
    {
        $payload = [
            'model' => $runtime->resolvedModel,
            'temperature' => 0.05,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $userQuestion],
            ],
        ];

        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::timeout($runtime->timeoutSeconds)
                ->acceptJson()
                ->withToken($runtime->apiKey)
                ->post($endpoint, $payload);
        } catch (Throwable $e) {
            return ['ok' => false, 'snippet' => $e->getMessage()];
        }

        if (! $response->successful()) {
            $bodySnippet = mb_substr(trim((string) $response->body()), 0, 380);
            if ($bodySnippet === '') {
                $bodySnippet = 'HTTP '.$response->status().($response->reason() !== '' ? ' '.$response->reason() : '');
            }

            return ['ok' => false, 'snippet' => $bodySnippet];
        }

        $text = data_get((array) $response->json(), 'choices.0.message.content');

        return ['ok' => true, 'text' => is_string($text) ? $text : ''];
    }

    private function anthropicMessages(ReportAiRuntimeSettings $runtime, string $system, string $userQuestion): array
    {
        $base = $runtime->baseUrlOverride ?? $runtime->vendor->defaultBaseUrl();
        $endpoint = rtrim($base, '/').'/messages';

        try {
            $response = Http::timeout($runtime->timeoutSeconds)
                ->acceptJson()
                ->withHeaders([
                    'x-api-key' => $runtime->apiKey,
                    'anthropic-version' => '2023-06-01',
                ])
                ->post($endpoint, [
                    'model' => $runtime->resolvedModel,
                    'max_tokens' => 8192,
                    'temperature' => 0,
                    'system' => $system,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $userQuestion."\n\nReturn JSON only with keys sql and summary.",
                        ],
                    ],
                ]);
        } catch (Throwable $e) {
            return ['error' => 'Unable to reach Anthropic: '.$e->getMessage()];
        }

        if (! $response->successful()) {
            return ['error' => 'Anthropic request failed: '.mb_substr((string) $response->body(), 0, 380)];
        }

        $joined = '';
        $blocks = data_get((array) $response->json(), 'content');
        if (is_array($blocks)) {
            foreach ($blocks as $block) {
                if (! is_array($block) || (($block['type'] ?? '') !== 'text')) {
                    continue;
                }
                $joined .= (string) ($block['text'] ?? '');
            }
        }

        return $this->parseAssistantJsonPayload($joined);
    }

    private function geminiGenerateContent(ReportAiRuntimeSettings $runtime, string $system, string $userQuestion): array
    {
        $host = $runtime->baseUrlOverride ?? $runtime->vendor->defaultBaseUrl();
        $endpoint = rtrim($host, '/').'/models/'.rawurlencode($runtime->resolvedModel)
            .':generateContent?key='.rawurlencode($runtime->apiKey);

        try {
            $response = Http::timeout($runtime->timeoutSeconds)
                ->acceptJson()
                ->post($endpoint, [
                    'systemInstruction' => ['parts' => [['text' => $system]]],
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $userQuestion]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'responseMimeType' => 'application/json',
                    ],
                ]);
        } catch (Throwable $e) {
            return ['error' => 'Unable to reach Gemini: '.$e->getMessage()];
        }

        if (! $response->successful()) {
            return ['error' => 'Gemini request failed: '.mb_substr((string) $response->body(), 0, 380)];
        }

        $text = data_get((array) $response->json(), 'candidates.0.content.parts.0.text');

        return $this->parseAssistantJsonPayload(is_string($text) ? $text : '');
    }

    private function unwrapMarkdownFence(string $rawContent): string
    {
        $t = trim($rawContent);
        $fence = str_repeat('`', 3);
        if (str_contains($t, $fence)) {
            $stripped = preg_replace('/^\s*`{3}[a-zA-Z0-9_-]*\s*/', '', $t);
            $stripped = preg_replace('/\s*`{3}\s*$/', '', (string) $stripped);

            return trim((string) $stripped);
        }

        return $t;
    }

    /**
     * @return array{data?: array<string, mixed>, error?: string}
     */
    private function parseDecodedJson(string $rawContent): array
    {
        $decoded = json_decode($this->unwrapMarkdownFence($rawContent), true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return ['error' => 'AI reply was not parseable JSON.'];
        }

        return ['data' => $decoded];
    }

    /**
     * @return array{sql?: string, summary?: string, error?: string}
     */
    private function parseAssistantJsonPayload(string $rawContent): array
    {
        $decoded = json_decode($this->unwrapMarkdownFence($rawContent), true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return ['error' => 'AI reply was not parseable JSON.', 'summary' => $rawContent];
        }

        $sqlRaw = isset($decoded['sql']) ? trim((string) $decoded['sql']) : '';
        $summaryRaw = isset($decoded['summary']) ? trim((string) $decoded['summary']) : '';

        if ($sqlRaw === '') {
            return ['error' => 'AI omitted the SQL SELECT.', 'summary' => $summaryRaw !== '' ? $summaryRaw : null];
        }

        return [
            'sql' => $sqlRaw,
            'summary' => $summaryRaw !== '' ? $summaryRaw : 'Generated report',
        ];
    }
}

