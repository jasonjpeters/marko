<?php

declare(strict_types=1);

namespace Marko\Vite\Command;

use Marko\Core\Attributes\Command;
use Marko\Core\Command\CommandInterface;
use Marko\Core\Command\Input;
use Marko\Core\Command\Output;
use Marko\Vite\Contracts\VitePublisherInterface;
use Marko\Vite\PackageJsonUpdater;
use Marko\Vite\ValueObjects\FilePublishResult;
use Marko\Vite\ValueObjects\PackageJsonUpdateResult;

#[Command(name: 'vite:init', description: 'Ensure the minimum package.json setup required for Vite')]
readonly class ViteInitCommand implements CommandInterface
{
    public function __construct(
        private PackageJsonUpdater $packageJson,
        private VitePublisherInterface $publisher,
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
            ],
            force: $input->hasOption('force') || $input->hasOption('f'),
            dryRun: $input->hasOption('dry-run'),
        );
        $configResult = $this->publisher->publishConfig(
            force: $input->hasOption('force') || $input->hasOption('f'),
            dryRun: $input->hasOption('dry-run'),
        );
        $entrypointResult = $this->publisher->publishJsEntrypoint(
            force: $input->hasOption('force') || $input->hasOption('f'),
            dryRun: $input->hasOption('dry-run'),
        );

        $this->report($result, $input->hasOption('dry-run'), $output);
        $this->reportConfig($configResult, $output);
        $this->reportConfig($entrypointResult, $output);

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
            'created' => $output->writeLine(sprintf('Published %s', $result->path)),
            'replaced' => $output->writeLine(sprintf('Replaced %s', $result->path)),
            'would_create' => $output->writeLine(sprintf('Would publish %s', $result->path)),
            'would_replace' => $output->writeLine(sprintf('Would replace %s', $result->path)),
            'skipped' => $output->writeLine(sprintf('Skipped %s because it already exists', $result->path)),
            default => null,
        };
    }
}
