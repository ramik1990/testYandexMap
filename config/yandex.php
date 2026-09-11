<?php

return [
    'entry_host' => env('YANDEX_MAPS_HOST', 'yandex.ru'),
    'page_size' => 50,
    'max_reviews' => (int) env('YANDEX_MAX_REVIEWS', 600),
    'ranking' => env('YANDEX_REVIEWS_RANKING', 'by_time'),
    'timeout' => (int) env('YANDEX_HTTP_TIMEOUT', 25),
    'delay_ms' => [
        'min' => (int) env('YANDEX_DELAY_MIN_MS', 400),
        'max' => (int) env('YANDEX_DELAY_MAX_MS', 1200),
    ],
    'proxy' => env('YANDEX_PROXY'),
    'user_agents' => array_values(array_filter(array_map('trim', explode('|', env('YANDEX_USER_AGENTS') ?: implode('|', [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:129.0) Gecko/20100101 Firefox/129.0',
    ]))))),
];
