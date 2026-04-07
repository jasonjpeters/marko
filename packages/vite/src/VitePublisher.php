<?php

declare(strict_types=1);

namespace Marko\Vite;

use Marko\Vite\Contracts\VitePublisherInterface;
use Marko\Vite\ValueObjects\ViteConfig;
use Marko\Vite\ValueObjects\FilePublishResult;

class VitePublisher implements VitePublisherInterface
{
    public function __construct(
        private readonly ViteConfig $config,
        private readonly ProjectFilePublisher $publisher,
    ) {}

    public function publishConfig(
        bool $force = false,
        bool $dryRun = false,
    ): FilePublishResult
    {
        return $this->publisher->publish(
            $this->config->rootViteConfigPath,
            (string) file_get_contents(dirname(__DIR__) . '/stubs/vite.config.ts'),
            $force,
            $dryRun,
        );
    }

    public function publishJsEntrypoint(
        bool $force = false,
        bool $dryRun = false,
    ): FilePublishResult
    {
        return $this->publisher->publish(
            $this->config->rootEntrypointPath,
            (string) file_get_contents(dirname(__DIR__) . '/stubs/resources/js/app.ts'),
            $force,
            $dryRun,
        );
    }
}
