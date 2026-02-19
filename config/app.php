<?php

declare(strict_types=1);

return [
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim(getenv('APP_URL') ?: 'https://vibeable.dev', '/'),
    'key' => getenv('APP_KEY') ?: '',
    'timezone' => 'UTC',
    'locale' => 'en',
    'supported_locales' => ['en', 'es', 'fr', 'de', 'ar', 'hi'],
    'rtl_locales' => ['ar'],
];
