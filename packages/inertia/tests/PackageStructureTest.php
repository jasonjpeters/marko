<?php

declare(strict_types=1);

use Marko\Inertia\Contracts\InertiaInterface;
use Marko\Inertia\Contracts\PageComponentLocatorInterface;

it('creates valid package scaffolding with composer.json, module.php, src, tests, and config', function (): void {
    $packageRoot = dirname(__DIR__);

    expect(file_exists($packageRoot . '/composer.json'))->toBeTrue()
        ->and(file_exists($packageRoot . '/module.php'))->toBeTrue()
        ->and(is_dir($packageRoot . '/src'))->toBeTrue()
        ->and(is_dir($packageRoot . '/tests'))->toBeTrue()
        ->and(is_dir($packageRoot . '/config'))->toBeTrue()
        ->and(file_exists($packageRoot . '/config/inertia.php'))->toBeTrue();
});

it('has a valid composer.json for marko/inertia', function (): void {
    $composer = json_decode(file_get_contents(dirname(__DIR__) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['name'])->toBe('marko/inertia')
        ->and($composer['type'])->toBe('marko-module')
        ->and($composer['license'])->toBe('MIT')
        ->and($composer['require']['php'])->toBe('^8.5')
        ->and($composer['require']['marko/config'])->toBe('self.version')
        ->and($composer['require']['marko/core'])->toBe('self.version')
        ->and($composer['require']['marko/routing'])->toBe('self.version')
        ->and($composer['require']['marko/vite'])->toBe('self.version')
        ->and($composer['autoload']['psr-4']['Marko\\Inertia\\'])->toBe('src/')
        ->and($composer['autoload-dev']['psr-4']['Marko\\Inertia\\Tests\\'])->toBe('tests/');
});

it('has module.php with bindings for the inertia services', function (): void {
    $module = require dirname(__DIR__) . '/module.php';

    expect($module)->toBeArray()
        ->and($module)->toHaveKey('bindings')
        ->and($module['bindings'])->toHaveKey(InertiaInterface::class)
        ->and($module['bindings'])->toHaveKey(PageComponentLocatorInterface::class);
});
