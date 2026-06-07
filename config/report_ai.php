<?php

return [
    /*
    | Provider, model, keys, and toggle are normally managed in Admin → Settings → AI reports.
    | The env values below are fallback + legacy support when the migration has not run yet.
    */
    'enabled' => filter_var(env('REPORT_AI_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'openai_api_key' => env('OPENAI_API_KEY'),

    /*
    | Base URL for OpenAI-compatible chat completions (no trailing slash).
    | Examples: https://api.openai.com/v1 — or a local LM Studio URL.
    */
    'openai_base_url' => rtrim(env('OPENAI_API_BASE_URL', 'https://api.openai.com/v1'), '/'),

    'model' => env('REPORT_AI_MODEL', 'gpt-4o-mini'),

    'timeout' => (int) env('REPORT_AI_TIMEOUT', 60),

    'max_question_length' => (int) env('REPORT_AI_MAX_QUESTION', 900),

    'max_rows' => min(5000, max(50, (int) env('REPORT_AI_MAX_ROWS', 500))),

    /*
    | Only these logical tables may appear in AI-generated SELECTs.
    | (Avoids exposing users, migrations, installers, credential rows, etc.)
    */
    'allowed_tables' => [
        'organizers',
        'events',
        'event_categories',
        'event_bookings',
        'event_tickets',
        'event_additional_services',
        'event_faqs',
        'speakers',
        'event_speaker',
        'event_booking_checkouts',
    ],

];
