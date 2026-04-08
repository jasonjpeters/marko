<?php

declare(strict_types=1);

namespace Marko\Inertia\Events;

use Marko\Core\Event\Event;
use Marko\Routing\Http\Request;

class InertiaRendering extends Event
{
    /**
     * @param array<string, mixed> $props
     * @param array<string, mixed> $sharedProps
     */
    public function __construct(
        public readonly Request $request,
        public readonly string $component,
        public array $props = [],
        public array $sharedProps = [],
    ) {}

    public function share(
        string|array $key,
        mixed $value = null,
    ): void {
        if (is_array($key)) {
            $this->sharedProps = array_replace($this->sharedProps, $key);

            return;
        }

        $this->sharedProps[$key] = $value;
    }
}
