<?php

declare(strict_types=1);

namespace Marko\Inertia;

use Marko\Core\Module\ModuleRepositoryInterface;
use Marko\Core\Path\ProjectPaths;
use Marko\Inertia\Config\InertiaConfig;
use Marko\Inertia\Contracts\PageComponentLocatorInterface;
use Marko\Inertia\Exceptions\ComponentNotFoundException;

readonly class ModulePageComponentLocator implements PageComponentLocatorInterface
{
    public function __construct(
        private ModuleRepositoryInterface $modules,
        private ProjectPaths $paths,
        private InertiaConfig $config,
    ) {}

    public function resolve(
        string $component,
    ): string {
        $searchedPaths = $this->getSearchedPaths($component);

        foreach ($searchedPaths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw ComponentNotFoundException::forComponent($component, $searchedPaths);
    }

    public function exists(
        string $component,
    ): bool {
        foreach ($this->getSearchedPaths($component) as $path) {
            if (is_file($path)) {
                return true;
            }
        }

        return false;
    }

    public function getSearchedPaths(
        string $component,
    ): array {
        [$moduleName, $componentPath] = $this->parseComponent($component);

        $roots = $moduleName === ''
            ? $this->allSearchRoots()
            : $this->moduleSearchRoots($moduleName);

        $paths = [];

        foreach ($roots as $root) {
            foreach ($this->config->pagePaths() as $pagePath) {
                foreach ($this->config->pageExtensions() as $extension) {
                    $paths[] = $root . '/' . $pagePath . '/' . $componentPath . '.' . $extension;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseComponent(
        string $component,
    ): array {
        $normalized = trim(str_replace('\\', '/', $component), '/');

        if (str_contains($normalized, '::')) {
            [$moduleName, $componentPath] = explode('::', $normalized, 2);

            return [$moduleName, trim($componentPath, '/')];
        }

        return ['', $normalized];
    }

    /**
     * @return array<string>
     */
    private function allSearchRoots(): array
    {
        $roots = [$this->paths->base];

        foreach ($this->modules->all() as $module) {
            $roots[] = $module->path;
        }

        return array_values(array_unique($roots));
    }

    /**
     * @return array<string>
     */
    private function moduleSearchRoots(
        string $moduleName,
    ): array {
        $roots = [];

        foreach ($this->modules->all() as $module) {
            if ($this->matchesModuleName($module->name, $moduleName)) {
                $roots[] = $module->path;
            }
        }

        if (in_array($moduleName, ['app', 'root'], true)) {
            array_unshift($roots, $this->paths->base);
        }

        return array_values(array_unique($roots));
    }

    private function matchesModuleName(
        string $fullName,
        string $shortName,
    ): bool {
        if ($fullName === $shortName) {
            return true;
        }

        $parts = explode('/', $fullName);

        return end($parts) === $shortName;
    }
}
