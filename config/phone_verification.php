<?php

return [
    'required' => (bool) env('PHONE_VERIFICATION_REQUIRED', false),
    'demo' => [
        'enabled' => (bool) env('PHONE_VERIFICATION_DEMO_ENABLED', true),
        'numbers' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('PHONE_VERIFICATION_DEMO_NUMBERS', '0900000001,0900000002'))
        ))),
        'code' => (string) env('PHONE_VERIFICATION_DEMO_CODE', '135790'),
        'ttl_minutes' => (int) env('PHONE_VERIFICATION_TTL_MINUTES', 10),
    ],
    'real' => [
        'enabled' => (bool) env('PHONE_VERIFICATION_REAL_ENABLED', false),
        'provider' => (string) env('PHONE_VERIFICATION_REAL_PROVIDER', ''),
    ],
];
