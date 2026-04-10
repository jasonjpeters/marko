<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Marko\Core\Command\Input;
use Marko\Core\Command\Output;
use Marko\Core\Path\ProjectPaths;
use Marko\Inertia\React\Command\InertiaReactInitCommand;
use Marko\Inertia\React\InertiaReactPublisher;
use Marko\Inertia\React\InertiaReactViteConfigUpdater;
use Marko\Vite\PackageJsonUpdater;
use Marko\Vite\ProjectFilePublisher;
use Marko\Vite\ValueObjects\ViteConfig;

beforeEach(function (): void {
    $this->tempDirectory = sys_get_temp_dir() . '/marko-inertia-react-init-command-' . bin2hex(random_bytes(6));
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

function makeInertiaReactInitCommand(string $directory): InertiaReactInitCommand
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

    return new InertiaReactInitCommand(
        new PackageJsonUpdater($paths),
        new InertiaReactPublisher($viteConfig, $publisher),
        new InertiaReactViteConfigUpdater($viteConfig, $paths, $publisher),
    );
}

function captureInertiaReactInitOutput(InertiaReactInitCommand $command, array $argv): array
{
    $stream = fopen('php://memory', 'r+');
    $output = new Output($stream);
    $status = $command->execute(new Input($argv), $output);
    rewind($stream);
    $contents = stream_get_contents($stream);
    fclose($stream);

    return [$status, $contents];
}

test('inertia react init dry run reports planned changes without writing files', function (): void {
    [$status, $output] = captureInertiaReactInitOutput(
        makeInertiaReactInitCommand($this->tempDirectory),
        ['marko', 'inertia-react:init', '--dry-run'],
    );

    expect($status)->toBe(0)
        ->and($output)->toContain('Would create package.json')
        ->and($output)->toContain('Added devDependency `react`')
        ->and($output)->toContain('Added devDependency `react-dom`')
        ->and($output)->toContain('Added devDependency `@vitejs/plugin-react`')
        ->and($output)->toContain('Added devDependency `@inertiajs/react`')
        ->and($output)->toContain('Would publish vite.config.ts (React-aware Vite config)')
        ->and($output)->toContain('Would publish resources/js/app.ts (Inertia React entrypoint)');

    expect($this->tempDirectory . '/package.json')->not->toBeFile()
        ->and($this->tempDirectory . '/vite.config.ts')->not->toBeFile()
        ->and($this->tempDirectory . '/resources/js/app.ts')->not->toBeFile();
})->group('inertia-react');

test('inertia react init force replaces existing bootstrap files and updates package json', function (): void {
    file_put_contents($this->tempDirectory . '/package.json', json_encode([
        'private' => true,
        'type' => 'commonjs',
        'scripts' => [
            'dev' => 'custom-dev',
        ],
        'devDependencies' => [
            'vite' => '^5.0.0',
            'react' => '^19.0.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    file_put_contents($this->tempDirectory . '/vite.config.ts', 'custom config');
    mkdir($this->tempDirectory . '/resources/js', 0777, true);
    file_put_contents($this->tempDirectory . '/resources/js/app.ts', 'custom entrypoint');

    [$status, $output] = captureInertiaReactInitOutput(
        makeInertiaReactInitCommand($this->tempDirectory),
        ['marko', 'inertia-react:init', '--force'],
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
        ->and($output)->toContain('Updated devDependency `react`')
        ->and($output)->toContain('Updated vite.config.ts (React-aware Vite config)')
        ->and($output)->toContain('Updated resources/js/app.ts (Inertia React entrypoint)')
        ->and($packageJson['type'])->toBe('module')
        ->and($packageJson['scripts']['dev'])->toBe('vite --config ./vite.config.ts')
        ->and($packageJson['devDependencies']['vite'])->toBe('latest')
        ->and($packageJson['devDependencies']['react'])->toBe('latest')
        ->and($packageJson['devDependencies']['react-dom'])->toBe('latest')
        ->and($packageJson['devDependencies']['@vitejs/plugin-react'])->toBe('latest')
        ->and($packageJson['devDependencies']['@inertiajs/react'])->toBe('latest')
        ->and((string) file_get_contents($this->tempDirectory . '/vite.config.ts'))->toContain(
            "import react from '@vitejs/plugin-react';"
        )
        ->and((string) file_get_contents($this->tempDirectory . '/resources/js/app.ts'))->toContain(
            'bootstrapMarkoInertiaReact'
        );
})->group('inertia-react');
