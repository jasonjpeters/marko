# marko/inertia

Server-side Inertia integration for Marko applications, including page rendering, shared props, middleware, asset bootstrapping, and optional SSR support.

## Installation

```bash
composer require marko/inertia
```

`marko/inertia` builds on top of Marko routing, sessions, and Vite.

## Quick Example

```php
use Marko\Inertia\Interfaces\InertiaInterface;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;

class DashboardController
{
    public function __construct(
        private readonly InertiaInterface $inertia,
    ) {}

    #[Get('/dashboard')]
    public function index(Request $request): Response
    {
        return $this->inertia->render(
            'Dashboard/Index',
            ['title' => 'Dashboard'],
            $request,
        );
    }
}
```

## Client Setup

Pair this package with one of the companion client scaffolding packages:

- `marko/inertia-react`
- `marko/inertia-vue`
- `marko/inertia-svelte`

Those packages publish a framework-specific `resources/js/app.ts` and update `vite.config.ts` to match the selected client runtime.

## Documentation

Full usage, configuration, and API reference: [marko/inertia](https://marko.build/docs/packages/inertia/)
