<?php

declare(strict_types=1);

namespace Marko\Inertia\Rendering;

use Marko\Core\Module\ModuleRepositoryInterface;
use Marko\Core\Path\ProjectPaths;
use Marko\Inertia\Exceptions\ComponentNotFoundException;
use Marko\Inertia\InertiaConfig;
use Marko\Inertia\Interfaces\ComponentResolverInterface;

readonly class ModuleComponentResolver implements ComponentResolverInterface
{
    public function __construct(
        private ModuleRepositoryInterface $moduleRepository,
        private ProjectPaths $projectPaths,
        private InertiaConfig $inertiaConfig,
    ) {}

    public function resolve(string $component): string
    {
        $searchedPaths = $this->getSearchedPaths($component);

        foreach ($searchedPaths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw ComponentNotFoundException::forComponent($component, $searchedPaths);
    }

    public function exists(string $component): bool
    {
        foreach ($this->getSearchedPaths($component) as $path) {
            if (is_file($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string>
     */
    public function getSearchedPaths(string $component): array
    {
        [$moduleName, $componentPath] = $this->parseComponent($component);
        $searchRoots = $moduleName === ''
            ? $this->allSearchRoots()
            : $this->moduleSearchRoots($moduleName);

        $paths = [];

        foreach ($searchRoots as $searchRoot) {
            foreach ($this->inertiaConfig->pagePaths() as $pagePath) {
                foreach ($this->inertiaConfig->pageExtensions() as $extension) {
                    $paths[] = $this->buildComponentPath($searchRoot, $pagePath, $componentPath, $extension);
                }
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * Parse component name into module name and path.
     *
     * @return array{0: string, 1: string} [moduleName, componentPath]
     */
    private function parseComponent(
        string $component,
    ): array {
        $normalized = trim(
            str_replace('\\', '/', $component),
            '/',
        );

        if (str_contains($normalized, '::')) {
            [
                $moduleName,
                $componentPath
            ] = explode('::', $normalized, 2);

            return [
                $moduleName,
                trim($componentPath, '/'),
            ];
        }

        return ['', $normalized];
    }

    /**
     * @return array<string>
     */
    private function allSearchRoots(): array
    {
        $roots = [$this->projectPaths->base];

        foreach ($this->moduleRepository->all() as $module) {
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

        foreach ($this->moduleRepository->all() as $module) {
            if ($this->matchesModuleName($module->name, $moduleName)) {
                $roots[] = $module->path;
            }
        }

        if (in_array($moduleName, ['app', 'root'], true)) {
            array_unshift($roots, $this->projectPaths->base);
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

    private function buildComponentPath(
        string $searchRoot,
        string $pagePath,
        string $componentPath,
        string $extension,
    ): string {
        return rtrim($searchRoot, '/')
            . '/'
            . trim($pagePath, '/')
            . '/'
            . ltrim($componentPath, '/')
            . '.'
            . ltrim($extension, '.');
    }
}
