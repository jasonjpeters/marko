<?php

declare(strict_types=1);

namespace Marko\Inertia\Contracts;

interface RootViewRendererInterface
{
    /**
     * @param array<string, mixed> $page
     */
    public function render(array $page): string;
}
