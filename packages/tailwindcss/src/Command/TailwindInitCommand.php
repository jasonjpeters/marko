<?php

declare(strict_types=1);

namespace Marko\TailwindCss\Command;

use Marko\Core\Attributes\Command;
use Marko\Core\Command\CommandInterface;
use Marko\Core\Command\Input;
use Marko\Core\Command\Output;
use Marko\TailwindCss\Contracts\TailwindPublisherInterface;
use Marko\TailwindCss\TailwindViteConfigUpdater;
use Marko\Vite\Contracts\VitePublisherInterface;
use Marko\Vite\PackageJsonUpdater;
use Marko\Vite\ValueObjects\FilePublishResult;
use Marko\Vite\ValueObjects\PackageJsonUpdateResult;

#[Command(name: 'tailwind:init', description: 'Ensure the minimum package.json setup required for Tailwind CSS')]
readonly class TailwindInitCommand implements CommandInterface
{
    public function __construct(
        private PackageJsonUpdater $packageJson,
        private VitePublisherInterface $vitePublisher,
        private TailwindPublisherInterface $publisher,
        private TailwindViteConfigUpdater $viteConfigUpdater,
    ) {}

    public function execute(
        Input $input,
        Output $output,
    ): int
    {
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
                'tailwindcss' => 'latest',
                '@tailwindcss/vite' => 'latest',
            ],
            force: $input->hasOption('force') || $input->hasOption('f'),
            dryRun: $input->hasOption('dry-run'),
        );
        $viteConfigResult = $this->vitePublisher->publishConfig(
            force: false,
            dryRun: $input->hasOption('dry-run'),
        );
        $viteEntrypointResult = $this->vitePublisher->publishJsEntrypoint(
            force: false,
            dryRun: $input->hasOption('dry-run'),
        );
        $configResult = $this->viteConfigUpdater->ensureTailwindConfig(
            force: $input->hasOption('force') || $input->hasOption('f'),
            dryRun: $input->hasOption('dry-run'),
        );
        $cssResult = $this->publisher->publishCssEntrypoint(
            force: $input->hasOption('force') || $input->hasOption('f'),
            dryRun: $input->hasOption('dry-run'),
        );

        $this->report($result, $input->hasOption('dry-run'), $output);
        $this->reportBootstrap($viteConfigResult, 'Vite config', $output);
        $this->reportBootstrap($viteEntrypointResult, 'Vite JS entrypoint', $output);
        $this->reportConfig($configResult, $output);
        $this->reportConfig($cssResult, $output);

        return 0;
    }

    private function report(
        PackageJsonUpdateResult $result,
        bool $dryRun,
        Output $output,
    ): void
    {
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
        Output $output,
    ): void
    {
        match ($result->status) {
            'created' => $output->writeLine(sprintf('Published %s with Tailwind CSS support', $result->path)),
            'replaced' => $output->writeLine(sprintf('Updated %s to load Tailwind CSS support', $result->path)),
            'would_create' => $output->writeLine(sprintf('Would publish %s with Tailwind CSS support', $result->path)),
            'would_replace' => $output->writeLine(
                sprintf('Would update %s to load Tailwind CSS support', $result->path)
            ),
            'already_present' => $output->writeLine(
                sprintf('%s already includes Tailwind CSS support', $result->path)
            ),
            'skipped' => $output->writeLine(
                sprintf(
                    'Skipped %s because it already exists with custom contents; use --force to replace it',
                    $result->path
                )
            ),
            default => null,
        };
    }

    private function reportBootstrap(
        FilePublishResult $result,
        string $label,
        Output $output,
    ): void
    {
        match ($result->status) {
            'created' => $output->writeLine(sprintf('Published %s (%s)', $result->path, $label)),
            'would_create' => $output->writeLine(sprintf('Would publish %s (%s)', $result->path, $label)),
            default => null,
        };
    }
}
