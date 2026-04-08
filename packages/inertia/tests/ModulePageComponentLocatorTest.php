<?php

declare(strict_types=1);

use Marko\Config\ConfigRepository;
use Marko\Core\Module\ModuleManifest;
use Marko\Core\Module\ModuleRepository;
use Marko\Core\Path\ProjectPaths;
use Marko\Inertia\Config\InertiaConfig;
use Marko\Inertia\Exceptions\ComponentNotFoundException;
use Marko\Inertia\ModulePageComponentLocator;

function makeInertiaLocator(string $basePath): ModulePageComponentLocator
{
    $modules = new ModuleRepository([
        new ModuleManifest(
            name: 'app/blog',
            version: '1.0.0',
            path: $basePath . '/app/blog',
            source: 'app',
        ),
        new ModuleManifest(
            name: 'marko/admin-panel',
            version: '1.0.0',
            path: $basePath . '/vendor/marko/admin-panel',
            source: 'vendor',
        ),
    ]);

    $config = new InertiaConfig(new ConfigRepository([
        'inertia' => [
            'version' => null,
            'root_view' => [
                'id' => 'app',
                'title' => 'Marko',
            ],
            'pages' => [
                'ensure_pages_exist' => false,
                'paths' => ['resources/js/Pages'],
                'extensions' => ['tsx', 'vue'],
            ],
            'testing' => [
                'ensure_pages_exist' => false,
            ],
            'history' => [
                'encrypt' => false,
            ],
        ],
    ]));

    return new ModulePageComponentLocator($modules, new ProjectPaths($basePath), $config);
}

it('resolves components from the project root before modules', function (): void {
    $basePath = sys_get_temp_dir() . '/marko-inertia-pages-' . uniqid();
    mkdir($basePath . '/resources/js/Pages/Users', 0755, true);
    mkdir($basePath . '/app/blog/resources/js/Pages/Users', 0755, true);

    file_put_contents($basePath . '/resources/js/Pages/Users/Index.tsx', 'root');
    file_put_contents($basePath . '/app/blog/resources/js/Pages/Users/Index.tsx', 'module');

    $locator = makeInertiaLocator($basePath);

    expect($locator->resolve('Users/Index'))->toBe($basePath . '/resources/js/Pages/Users/Index.tsx')
        ->and($locator->exists('Users/Index'))->toBeTrue();
});

it('supports module-prefixed component names similar to view resolution', function (): void {
    $basePath = sys_get_temp_dir() . '/marko-inertia-pages-' . uniqid();
    mkdir($basePath . '/vendor/marko/admin-panel/resources/js/Pages/Dashboard', 0755, true);
    file_put_contents($basePath . '/vendor/marko/admin-panel/resources/js/Pages/Dashboard/Index.vue', 'vendor');

    $locator = makeInertiaLocator($basePath);

    expect($locator->resolve('admin-panel::Dashboard/Index'))
        ->toBe($basePath . '/vendor/marko/admin-panel/resources/js/Pages/Dashboard/Index.vue');
});

it('throws a helpful exception when a component cannot be found', function (): void {
    $basePath = sys_get_temp_dir() . '/marko-inertia-pages-' . uniqid();
    mkdir($basePath, 0755, true);

    $locator = makeInertiaLocator($basePath);

    try {
        $locator->resolve('Missing/Page');
        $this->fail('Expected ComponentNotFoundException was not thrown');
    } catch (ComponentNotFoundException $e) {
        expect($e->getMessage())->toContain("Inertia component 'Missing/Page' not found.")
            ->and($e->getContext())->toContain($basePath . '/resources/js/Pages/Missing/Page.tsx')
            ->and($e->getSuggestion())->toContain('Verify the component name');
    }
});
