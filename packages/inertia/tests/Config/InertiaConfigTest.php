<?php

declare(strict_types=1);

use Marko\Config\ConfigRepository;
use Marko\Inertia\Config\InertiaConfig;

function inertiaConfigRepository(array $overrides = []): ConfigRepository
{
    return new ConfigRepository([
        'inertia' => array_replace_recursive([
            'version' => 'test-version',
            'root_view' => [
                'id' => 'app',
                'title' => 'Marko',
            ],
            'pages' => [
                'ensure_pages_exist' => false,
                'paths' => ['resources/js/Pages'],
                'extensions' => ['js', 'ts', 'tsx'],
            ],
            'testing' => [
                'ensure_pages_exist' => true,
            ],
            'history' => [
                'encrypt' => false,
            ],
        ], $overrides),
    ]);
}

it('returns normalized page paths and extensions', function (): void {
    $config = new InertiaConfig(inertiaConfigRepository([
        'pages' => [
            'paths' => [' resources/js/Pages ', '/resources/js/admin/Pages/'],
            'extensions' => ['.JS', ' tsx ', ''],
        ],
    ]));

    expect($config->pagePaths())->toBe([
        'resources/js/Pages',
        'resources/js/admin/Pages',
    ])->and($config->pageExtensions())->toBe([
        'js',
        'tsx',
    ]);
});

it('uses testing ensure pages setting while running tests', function (): void {
    $config = new InertiaConfig(inertiaConfigRepository([
        'pages' => [
            'ensure_pages_exist' => false,
        ],
        'testing' => [
            'ensure_pages_exist' => true,
        ],
    ]));

    expect($config->shouldEnsurePagesExist())->toBeTrue();
});

it('exposes root settings version and history encryption', function (): void {
    $config = new InertiaConfig(inertiaConfigRepository([
        'version' => 'abc123',
        'root_view' => [
            'id' => 'frontend',
            'title' => 'Admin',
        ],
        'history' => [
            'encrypt' => true,
        ],
    ]));

    expect($config->version())->toBe('abc123')
        ->and($config->rootElementId())->toBe('frontend')
        ->and($config->rootTitle())->toBe('Admin')
        ->and($config->encryptHistory())->toBeTrue();
});
