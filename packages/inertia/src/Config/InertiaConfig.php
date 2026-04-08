<?php

declare(strict_types=1);

namespace Marko\Inertia\Config;

use Marko\Config\ConfigRepositoryInterface;

readonly class InertiaConfig
{
    public function __construct(
        private ConfigRepositoryInterface $config,
    ) {}

    public function version(): ?string
    {
        $version = $this->config->get('inertia.version');

        return is_string($version) && $version !== '' ? $version : null;
    }

    public function rootElementId(): string
    {
        return $this->config->getString('inertia.root_view.id');
    }

    public function rootTitle(): string
    {
        return $this->config->getString('inertia.root_view.title');
    }

    /**
     * @return array<string>
     */
    public function pagePaths(): array
    {
        return array_values(array_filter(
            array_map(
                static fn (mixed $path): string => trim((string) $path, " \t\n\r\0\x0B/"),
                $this->config->getArray('inertia.pages.paths'),
            ),
            static fn (string $path): bool => $path !== '',
        ));
    }

    /**
     * @return array<string>
     */
    public function pageExtensions(): array
    {
        return array_values(array_filter(
            array_map(
                static fn (mixed $extension): string => ltrim(strtolower(trim((string) $extension)), '.'),
                $this->config->getArray('inertia.pages.extensions'),
            ),
            static fn (string $extension): bool => $extension !== '',
        ));
    }

    public function shouldEnsurePagesExist(): bool
    {
        $isTesting = class_exists(\Pest\TestSuite::class)
            || defined('PHPUNIT_COMPOSER_INSTALL')
            || in_array($_ENV['APP_ENV'] ?? '', ['test', 'testing'], true);

        if ($isTesting && $this->config->has('inertia.testing.ensure_pages_exist')) {
            return $this->config->getBool('inertia.testing.ensure_pages_exist');
        }

        return $this->config->getBool('inertia.pages.ensure_pages_exist');
    }

    public function encryptHistory(): bool
    {
        return $this->config->getBool('inertia.history.encrypt');
    }
}
