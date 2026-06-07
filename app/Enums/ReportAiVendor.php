<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportAiVendor: string
{
    case OpenAi = 'openai';

    /** OpenAI-compatible chat completions (`/v1/chat/completions`). */
    case DeepSeek = 'deepseek';

    /** Anthropic Messages API. */
    case AnthropicClaude = 'anthropic_claude';

    /** Google Gemini `generateContent` (API key query param). */
    case GoogleGemini = 'google_gemini';

    public function label(): string
    {
        return match ($this) {
            self::OpenAi => 'OpenAI',
            self::DeepSeek => 'DeepSeek',
            self::AnthropicClaude => 'Anthropic (Claude)',
            self::GoogleGemini => 'Google (Gemini)',
        };
    }

    public function defaultBaseUrl(): string
    {
        return match ($this) {
            self::OpenAi => 'https://api.openai.com/v1',
            self::DeepSeek => 'https://api.deepseek.com',
            self::AnthropicClaude => 'https://api.anthropic.com/v1',
            self::GoogleGemini => 'https://generativelanguage.googleapis.com/v1beta',
        };
    }

    /**
     * Suggested starter model slug for each vendor (also used when the admin leaves “Model” empty).
     */
    public function defaultModel(): string
    {
        return match ($this) {
            self::OpenAi => 'gpt-4o-mini',
            self::DeepSeek => 'deepseek-chat',
            self::AnthropicClaude => 'claude-3-5-sonnet-20241022',
            self::GoogleGemini => 'gemini-2.0-flash',
        };
    }
}
