<?php

return [
    'provider' => env('AI_PROVIDER', 'gemini'),
    'enabled'  => env('AI_ENABLED', true),
    'timeout'  => env('AI_TIMEOUT', 10),          // detik
    'retries'  => env('AI_RETRIES', 1),
    'min_confidence_auto' => env('AI_MIN_CONFIDENCE', 0.60),

    'gemini' => [
        'api_key'  => env('GEMINI_API_KEY', ''),
        'model'    => env('GEMINI_MODEL', 'gemini-3.8-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'temperature' => 0.1,
        'max_output_tokens' => 800,
    ],
];
