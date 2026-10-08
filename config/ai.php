<?php

return [
    'provider' => env('AI_PROVIDER', 'gemini'),
    'enabled'  => env('AI_ENABLED', true),
    'timeout'  => (int) env('AI_TIMEOUT', 30),          // detik, per percobaan
    'retries'  => (int) env('AI_RETRIES', 1),
    'min_confidence_auto' => (float) env('AI_MIN_CONFIDENCE', 0.60),

    'gemini' => [
        'api_key'  => env('GEMINI_API_KEY', ''),
        // Urutan = prioritas. Model berikutnya dicoba jika model sebelumnya 503/429/timeout.
        'models'   => array_filter(array_map('trim', explode(',', env(
            'GEMINI_MODELS',
            'gemini-3.5-flash,gemini-3.8-flash,gemini-3.1-flash-lite'
        )))),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'structured_output' => env('GEMINI_STRUCTURED_OUTPUT', true),
    ],
];
