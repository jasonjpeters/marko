<?php

declare(strict_types=1);

namespace Marko\Inertia\React;

use Marko\Inertia\React\Contracts\InertiaReactPublisherInterface;
use Marko\Vite\ProjectFilePublisher;
use Marko\Vite\ValueObjects\FilePublishResult;
use Marko\Vite\ValueObjects\ViteConfig;

class InertiaReactPublisher implements InertiaReactPublisherInterface
{
    public function __construct(
        private readonly ViteConfig $viteConfig,
        private readonly ProjectFilePublisher $publisher,
    ) {}

    public function publishJsEntrypoint(
        bool $force = false,
        bool $dryRun = false,
    ): FilePublishResult {
        return $this->publisher->publish(
            $this->viteConfig->rootEntrypointPath,
            (string) file_get_contents(dirname(__DIR__) . '/stubs/resources/js/app.ts'),
            $force,
            $dryRun,
        );
    }
}
