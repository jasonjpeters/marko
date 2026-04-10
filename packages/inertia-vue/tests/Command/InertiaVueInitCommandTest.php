<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Marko\Core\Command\Input;
use Marko\Core\Command\Output;
use Marko\Core\Path\ProjectPaths;
use Marko\Inertia\Vue\Command\InertiaVueInitCommand;
use Marko\Inertia\Vue\InertiaVuePublisher;
use Marko\Inertia\Vue\InertiaVueViteConfigUpdater;
use Marko\Vite\PackageJsonUpdater;
use Marko\Vite\ProjectFilePublisher;
use Marko\Vite\ValueObjects\ViteConfig;

beforeEach(function (): void {
    $this->tempDirectory = sys_get_temp_dir() . '/marko-inertia-vue-init-command-' . bin2hex(random_bytes(6));
    mkdir($this->tempDirectory, 0777, true);
});

afterEach(function (): void {
    if (! isset($this->tempDirectory) || ! is_dir($this->tempDirectory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->tempDirectory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
            continue;
        }

        unlink($item->getPathname());
    }

    rmdir($this->tempDirectory);
});

function makeInertiaVueInitCommand(string $directory): InertiaVueInitCommand
{
    $paths = new ProjectPaths($directory);
    $viteConfig = new ViteConfig(
        devServerUrl: 'http://localhost:5173',
        devProcessFilePath: $directory . '/.marko/dev.json',
        hotFilePath: $directory . '/public/hot',
        manifestPath: $directory . '/public/build/manifest.json',
        buildDirectory: '/build',
        assetsBaseUrl: '',
        defaultEntrypoints: [],
        rootEntrypointPath: 'resources/js/app.ts',
        rootViteConfigPath: 'vite.config.ts',
    );
    $publisher = new ProjectFilePublisher($paths);

    return new InertiaVueInitCommand(
        new PackageJsonUpdater($paths),
        new InertiaVuePublisher($viteConfig, $publisher),
        new InertiaVueViteConfigUpdater($viteConfig, $paths, $publisher),
    );
}

function captureInertiaVueInitOutput(InertiaVueInitCommand $command, array $argv): array
{
    $stream = fopen('php://memory', 'r+');
    $output = new Output($stream);
    $status = $command->execute(new Input($argv), $output);
    rewind($stream);
    $contents = stream_get_contents($stream);
    fclose($stream);

    return [$status, $contents];
}

test('inertia vue init dry run reports planned changes without writing files', function (): void {
    [$status, $output] = captureInertiaVueInitOutput(
        makeInertiaVueInitCommand($this->tempDirectory),
        ['marko', 'inertia-vue:init', '--dry-run'],
    );

    expect($status)->toBe(0)
        ->and($output)->toContain('Would create package.json')
        ->and($output)->toContain('Added devDependency `vue`')
        ->and($output)->toContain('Added devDependency `@vitejs/plugin-vue`')
        ->and($output)->toContain('Added devDependency `@inertiajs/vue3`')
        ->and($output)->toContain('Would publish vite.config.ts (Vue-aware Vite config)')
        ->and($output)->toContain('Would publish resources/js/app.ts (Inertia Vue entrypoint)');

    expect($this->tempDirectory . '/package.json')->not->toBeFile()
        ->and($this->tempDirectory . '/vite.config.ts')->not->toBeFile()
        ->and($this->tempDirectory . '/resources/js/app.ts')->not->toBeFile();
})->group('inertia-vue');

test('inertia vue init force replaces existing bootstrap files and updates package json', function (): void {
    file_put_contents($this->tempDirectory . '/package.json', json_encode([
        'private' => true,
        'type' => 'commonjs',
        'scripts' => [
            'dev' => 'custom-dev',
        ],
        'devDependencies' => [
            'vite' => '^5.0.0',
            'vue' => '^3.0.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    file_put_contents($this->tempDirectory . '/vite.config.ts', 'custom config');
    mkdir($this->tempDirectory . '/resources/js', 0777, true);
    file_put_contents($this->tempDirectory . '/resources/js/app.ts', 'custom entrypoint');

    [$status, $output] = captureInertiaVueInitOutput(
        makeInertiaVueInitCommand($this->tempDirectory),
        ['marko', 'inertia-vue:init', '--force'],
    );

    $packageJson = json_decode(
        (string) file_get_contents($this->tempDirectory . '/package.json'),
        true,
        flags: JSON_THROW_ON_ERROR
    );

    expect($status)->toBe(0)
        ->and($output)->toContain('Updated field `type`')
        ->and($output)->toContain('Updated script `dev`')
        ->and($output)->toContain('Updated devDependency `vite`')
        ->and($output)->toContain('Updated devDependency `vue`')
        ->and($output)->toContain('Updated vite.config.ts (Vue-aware Vite config)')
        ->and($output)->toContain('Updated resources/js/app.ts (Inertia Vue entrypoint)')
        ->and($packageJson['type'])->toBe('module')
        ->and($packageJson['scripts']['dev'])->toBe('vite --config ./vite.config.ts')
        ->and($packageJson['devDependencies']['vite'])->toBe('latest')
        ->and($packageJson['devDependencies']['vue'])->toBe('latest')
        ->and($packageJson['devDependencies']['@vitejs/plugin-vue'])->toBe('latest')
        ->and($packageJson['devDependencies']['@inertiajs/vue3'])->toBe('latest')
        ->and((string) file_get_contents($this->tempDirectory . '/vite.config.ts'))->toContain(
            "import vue from '@vitejs/plugin-vue';"
        )
        ->and((string) file_get_contents($this->tempDirectory . '/resources/js/app.ts'))->toContain(
            'bootstrapMarkoInertiaVue'
        );
})->group('inertia-vue');
