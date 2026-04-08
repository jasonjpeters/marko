<?php

declare(strict_types=1);

namespace Marko\Inertia\Contracts;

use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;

interface InertiaInterface
{
    public function render(
        string $component,
        array $props = [],
        ?Request $request = null,
    ): Response;

    public function location(
        string $url,
        ?Request $request = null,
    ): Response;

    public function share(
        string|array $key,
        mixed $value = null,
    ): void;

    /**
     * @return array<string, mixed>
     */
    public function shared(): array;
}
