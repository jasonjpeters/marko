<?php

declare(strict_types=1);

return [
    'version' => $_ENV['INERTIA_VERSION'] ?? null,

    'root_view' => [
        'id' => $_ENV['INERTIA_ROOT_ID'] ?? 'app',
        'title' => $_ENV['APP_NAME'] ?? 'Marko',
    ],

    'ssr' => [
        'enabled' => (bool) ($_ENV['INERTIA_SSR_ENABLED'] ?? false),
        'url' => $_ENV['INERTIA_SSR_URL'] ?? 'http://127.0.0.1:13714',
        'ensure_bundle_exists' => (bool) ($_ENV['INERTIA_SSR_ENSURE_BUNDLE_EXISTS'] ?? true),
        'throw_on_error' => (bool) ($_ENV['INERTIA_SSR_THROW_ON_ERROR'] ?? false),
    ],

    'pages' => [
        'ensure_pages_exist' => false,
        'paths' => [
            'resources/js/Pages',
        ],
        'extensions' => ['js', 'jsx', 'svelte', 'ts', 'tsx', 'vue'],
    ],

    'testing' => [
        'ensure_pages_exist' => true,
    ],

    'history' => [
        'encrypt' => (bool) ($_ENV['INERTIA_ENCRYPT_HISTORY'] ?? false),
    ],
];
