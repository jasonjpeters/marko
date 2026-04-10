<?php

declare(strict_types=1);

namespace Marko\Inertia\Vue\Command;

use Marko\Core\Attributes\Command;
use Marko\Core\Command\CommandInterface;
use Marko\Core\Command\Input;
use Marko\Core\Command\Output;
use Marko\Inertia\Vue\Contracts\InertiaVuePublisherInterface;
use Marko\Inertia\Vue\InertiaVueViteConfigUpdater;
use Marko\Vite\PackageJsonUpdater;
use Marko\Vite\ValueObjects\FilePublishResult;
use Marko\Vite\ValueObjects\PackageJsonUpdateResult;

#[Command(name: 'inertia-vue:init', description: 'Ensure the minimum package.json setup required for Inertia Vue')]
readonly class InertiaVueInitCommand implements CommandInterface
{
    public function __construct(
        private PackageJsonUpdater $packageJson,
        private InertiaVuePublisherInterface $publisher,
        private InertiaVueViteConfigUpdater $viteConfigUpdater,
    ) {}

    public function execute(
        Input $input,
        Output $output,
    ): int {
        $force = $input->hasOption('force') || $input->hasOption('f');
        $dryRun = $input->hasOption('dry-run');

        $result = $this->packageJson->update(
            fields: [
                'private' => true,
                'type' => 'module',
            ],
            scripts: [
                'dev' => 'vite --config ./vite.config.ts',
                'build' => 'vite build --config ./vite.config.ts',
            ],
            devDependencies: [
                'vite' => 'latest',
                'vue' => 'latest',
                '@vitejs/plugin-vue' => 'latest',
                '@inertiajs/vue3' => 'latest',
            ],
            force: $force,
            dryRun: $dryRun,
        );
        $configResult = $this->viteConfigUpdater->ensureVueConfig($force, $dryRun);
        $entrypointResult = $this->publisher->publishJsEntrypoint($force, $dryRun);

        $this->report($result, $dryRun, $output);
        $this->reportConfig($configResult, 'Vue-aware Vite config', $output);
        $this->reportConfig($entrypointResult, 'Inertia Vue entrypoint', $output);

        return 0;
    }

    private function report(
        PackageJsonUpdateResult $result,
        bool $dryRun,
        Output $output,
    ): void {
        if ($result->createdFile) {
            $output->writeLine($dryRun ? 'Would create package.json' : 'Created package.json');
        }

        foreach ($result->added as $change) {
            $output->writeLine(sprintf('Added %s', $change));
        }

        foreach ($result->updated as $change) {
            $output->writeLine(sprintf('Updated %s', $change));
        }

        foreach ($result->alreadyPresent as $change) {
            $output->writeLine(sprintf('%s already present', ucfirst($change)));
        }

        foreach ($result->skipped as $change) {
            $output->writeLine(sprintf('Skipped %s', $change));
        }
    }

    private function reportConfig(
        FilePublishResult $result,
        string $label,
        Output $output,
    ): void {
        match ($result->status) {
            'created' => $output->writeLine(sprintf('Published %s (%s)', $result->path, $label)),
            'replaced' => $output->writeLine(sprintf('Updated %s (%s)', $result->path, $label)),
            'would_create' => $output->writeLine(sprintf('Would publish %s (%s)', $result->path, $label)),
            'would_replace' => $output->writeLine(sprintf('Would update %s (%s)', $result->path, $label)),
            'already_present' => $output->writeLine(sprintf('%s already includes %s', $result->path, $label)),
            'skipped' => $output->writeLine(
                sprintf(
                    'Skipped %s because it already exists with custom contents; use --force to replace it',
                    $result->path,
                )
            ),
            default => null,
        };
    }
}
