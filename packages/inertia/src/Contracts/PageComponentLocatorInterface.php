<?php

declare(strict_types=1);

namespace Marko\Inertia\Contracts;

interface PageComponentLocatorInterface
{
    public function resolve(string $component): string;

    public function exists(string $component): bool;

    /**
     * @return array<string>
     */
    public function getSearchedPaths(string $component): array;
}
