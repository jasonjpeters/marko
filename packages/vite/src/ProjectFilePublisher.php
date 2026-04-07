<?php

declare(strict_types=1);

namespace Marko\Vite;

use Marko\Core\Path\ProjectPaths;
use Marko\Vite\ValueObjects\FilePublishResult;

class ProjectFilePublisher
{
    public function __construct(
        private readonly ProjectPaths $paths,
    ) {}

    public function publish(
        string $relativePath,
        string $contents,
        bool $force = false,
        bool $dryRun = false,
    ): FilePublishResult {
        $absolutePath = $this->paths->base . '/' . $relativePath;
        $exists = is_file($absolutePath);

        if ($exists && !$force) {
            return new FilePublishResult($relativePath, 'skipped');
        }

        if ($dryRun) {
            return new FilePublishResult($relativePath, $exists ? 'would_replace' : 'would_create');
        }

        $directory = dirname($absolutePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($absolutePath, $contents);

        return new FilePublishResult($relativePath, $exists ? 'replaced' : 'created');
    }
}
