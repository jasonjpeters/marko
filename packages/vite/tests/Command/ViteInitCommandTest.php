<?php

declare(strict_types=1);

use Marko\Core\Command\Input;
use Marko\Core\Command\Output;
use Marko\Core\Path\ProjectPaths;
use Marko\Vite\Command\ViteInitCommand;
use Marko\Vite\PackageJsonUpdater;
use Marko\Vite\ProjectFilePublisher;
use Marko\Vite\ValueObjects\ViteConfig;
use Marko\Vite\VitePublisher;

beforeEach(function (): void {
    $this->tempDirectory = sys_get_temp_dir() . '/marko-vite-init-command-' . bin2hex(random_bytes(6));
    mkdir($this->tempDirectory, 0777, true);
});

afterEach(function (): void {
    if (!isset($this->tempDirectory) || !is_dir($this->tempDirectory)) {
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

function makeViteInitCommand(string $directory): ViteInitCommand
{
    $paths = new ProjectPaths($directory);

    return new ViteInitCommand(
        new PackageJsonUpdater($paths),
        new VitePublisher(
            new ViteConfig(
                devServerUrl: 'http://localhost:5173',
                devProcessFilePath: $directory . '/.marko/dev.json',
                hotFilePath: $directory . '/public/hot',
                manifestPath: $directory . '/public/build/manifest.json',
                buildDirectory: '/build',
                assetsBaseUrl: '',
                defaultEntrypoints: [],
                rootEntrypointPath: 'resources/js/app.ts',
                rootViteConfigPath: 'vite.config.ts',
            ),
            new ProjectFilePublisher($paths),
        ),
    );
}

function captureViteInitOutput(ViteInitCommand $command, array $argv): array
{
    $stream = fopen('php://memory', 'r+');
    $output = new Output($stream);
    $status = $command->execute(new Input($argv), $output);
    rewind($stream);
    $contents = stream_get_contents($stream);
    fclose($stream);

    return [$status, $contents];
}

test('vite init dry run reports planned changes without writing files', function (): void {
    [$status, $output] = captureViteInitOutput(
        makeViteInitCommand($this->tempDirectory),
        ['marko', 'vite:init', '--dry-run'],
    );

    expect($status)->toBe(0)
        ->and($output)->toContain('Would create package.json')
        ->and($output)->toContain('Added field `type`')
        ->and($output)->toContain('Added script `dev`')
        ->and($output)->toContain('Would publish vite.config.ts')
        ->and($output)->toContain('Would publish resources/js/app.ts');

    expect($this->tempDirectory . '/package.json')->not->toBeFile()
        ->and($this->tempDirectory . '/vite.config.ts')->not->toBeFile()
        ->and($this->tempDirectory . '/resources/js/app.ts')->not->toBeFile();
})->group('vite');

test('vite init force replaces existing bootstrap files and updates package json', function (): void {
    file_put_contents($this->tempDirectory . '/package.json', json_encode([
        'private' => true,
        'type' => 'commonjs',
        'scripts' => [
            'dev' => 'custom-dev',
        ],
        'devDependencies' => [
            'vite' => '^5.0.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    file_put_contents($this->tempDirectory . '/vite.config.ts', 'custom config');
    mkdir($this->tempDirectory . '/resources/js', 0777, true);
    file_put_contents($this->tempDirectory . '/resources/js/app.ts', 'custom entrypoint');

    [$status, $output] = captureViteInitOutput(
        makeViteInitCommand($this->tempDirectory),
        ['marko', 'vite:init', '--force'],
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
        ->and($output)->toContain('Replaced vite.config.ts')
        ->and($output)->toContain('Replaced resources/js/app.ts')
        ->and($packageJson['type'])->toBe('module')
        ->and($packageJson['scripts']['dev'])->toBe('vite --config ./vite.config.ts')
        ->and($packageJson['devDependencies']['vite'])->toBe('latest')
        ->and((string) file_get_contents($this->tempDirectory . '/vite.config.ts'))->toContain('createBaseConfig')
        ->and((string) file_get_contents($this->tempDirectory . '/resources/js/app.ts'))->toContain(
            'bootstrapMarkoVite'
        );
})->group('vite');
