# marko/vite

Bundle and serve frontend assets in Marko applications with a Vite-first workflow that supports both development and production rendering.

## Installation

```bash
composer require marko/vite
```

## Quick Example

```php
use Marko\Vite\Contracts\ViteManagerInterface;

$vite = $container->get(ViteManagerInterface::class);

echo $vite->tags('resources/js/app.ts');
```

## Documentation

Full usage, configuration, and API reference: [marko/vite](https://marko.build/docs/packages/vite/)
