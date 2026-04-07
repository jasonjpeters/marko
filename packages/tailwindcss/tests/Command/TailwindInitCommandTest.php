<?php

declare(strict_types=1);

use Marko\Core\Command\Input;
use Marko\Core\Command\Output;
use Marko\Core\Path\ProjectPaths;
use Marko\TailwindCss\Command\TailwindInitCommand;
use Marko\TailwindCss\DefaultTailwindEntrypointProvider;
use Marko\TailwindCss\TailwindPublisher;
use Marko\TailwindCss\TailwindViteConfigUpdater;
use Marko\Vite\PackageJsonUpdater;
use Marko\Vite\ProjectFilePublisher;
use Marko\Vite\ValueObjects\ViteConfig;
use Marko\Vite\VitePublisher;

beforeEach(function (): void {
    $this->tempDirectory = sys_get_temp_dir() . '/marko-tailwind-init-command-' . bin2hex(random_bytes(6));
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

function makeTailwindInitCommand(string $directory): TailwindInitCommand
{
    $paths = new ProjectPaths($directory);
    $config = new Marko\Config\ConfigRepository([
        'tailwindcss' => [
            'enabled' => true,
            'entrypoints' => [
                'css' => 'resources/css/app.css',
            ],
        ],
    ]);
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
    $projectPublisher = new ProjectFilePublisher($paths);

    return new TailwindInitCommand(
        new PackageJsonUpdater($paths),
        new VitePublisher($viteConfig, $projectPublisher),
        new TailwindPublisher(new DefaultTailwindEntrypointProvider($config), $projectPublisher),
        new TailwindViteConfigUpdater($viteConfig, $paths, $projectPublisher),
    );
}

function captureTailwindInitOutput(TailwindInitCommand $command, array $argv): array
{
    $stream = fopen('php://memory', 'r+');
    $output = new Output($stream);
    $status = $command->execute(new Input($argv), $output);
    rewind($stream);
    $contents = stream_get_contents($stream);
    fclose($stream);

    return [$status, $contents];
}

test('tailwind init dry run reports package and asset setup without writing files', function (): void {
    [$status, $output] = captureTailwindInitOutput(
        makeTailwindInitCommand($this->tempDirectory),
        ['marko', 'tailwind:init', '--dry-run'],
    );

    expect($status)->toBe(0)
        ->and($output)->toContain('Would create package.json')
        ->and($output)->toContain('Added devDependency `tailwindcss`')
        ->and($output)->toContain('Added devDependency `@tailwindcss/vite`')
        ->and($output)->toContain('Would publish vite.config.ts (Vite config)')
        ->and($output)->toContain('Would publish resources/js/app.ts (Vite JS entrypoint)')
        ->and($output)->toContain('Would publish vite.config.ts with Tailwind CSS support')
        ->and($output)->toContain('Would publish resources/css/app.css with Tailwind CSS support');

    expect($this->tempDirectory . '/package.json')->not->toBeFile()
        ->and($this->tempDirectory . '/vite.config.ts')->not->toBeFile()
        ->and($this->tempDirectory . '/resources/js/app.ts')->not->toBeFile()
        ->and($this->tempDirectory . '/resources/css/app.css')->not->toBeFile();
})->group('tailwindcss');

test(
    'tailwind init reports already-present tailwind config and preserves existing vite bootstrap files',
    function (): void {
        mkdir($this->tempDirectory . '/resources/js', 0777, true);
        mkdir($this->tempDirectory . '/resources/css', 0777, true);
        file_put_contents(
            $this->tempDirectory . '/vite.config.ts',
            "import tailwindcss from '@tailwindcss/vite';\nexport default { plugins: [tailwindcss()] };\n",
        );
        file_put_contents($this->tempDirectory . '/resources/js/app.ts', 'existing js');
        file_put_contents($this->tempDirectory . '/resources/css/app.css', 'existing css');
    
        [$status, $output] = captureTailwindInitOutput(
            makeTailwindInitCommand($this->tempDirectory),
            ['marko', 'tailwind:init'],
        );
    
        expect($status)->toBe(0)
            ->and($output)->toContain('vite.config.ts already includes Tailwind CSS support')
            ->and($output)->toContain(
                'Skipped resources/css/app.css because it already exists with custom contents; use --force to replace it'
            )
            ->and($output)->not->toContain('(Vite config)')
            ->and((string) file_get_contents($this->tempDirectory . '/resources/js/app.ts'))->toBe('existing js')
            ->and((string) file_get_contents($this->tempDirectory . '/resources/css/app.css'))->toBe('existing css');
    }
)->group('tailwindcss');
