# marko/vite

Bundle and serve frontend assets in Marko applications with a Vite-first workflow that supports both development and production rendering.

## Installation

```bash
composer require marko/vite
```

## Initialize Project Files

```bash
marko vite:init
npm install
```

The init command prepares the minimum Vite setup for a Marko app:

- creates or updates `package.json`
- publishes `vite.config.ts`
- publishes `resources/js/app.ts`

By default it configures these scripts:

```json
{
  "scripts": {
    "dev": "vite --config ./vite.config.ts",
    "build": "vite build --config ./vite.config.ts"
  }
}
```

You can preview changes first with:

```bash
marko vite:init --dry-run
```

Replace existing generated files with:

```bash
marko vite:init --force
```

## Configuration

Default configuration lives in `config/vite.php`:

```php
return [
    'dev_server_url' => 'http://localhost:5173',
    'dev_process_file_path' => '.marko/dev.json',
    'hot_file_path' => 'public/hot',
    'manifest_path' => 'public/build/manifest.json',
    'build_directory' => '/build',
    'assets_base_url' => '',
    'default_entrypoints' => [],
    'root_entrypoint_path' => 'resources/js/app.ts',
    'root_vite_config_path' => 'vite.config.ts',
];
```

The generated `vite.config.ts` starts with a single JS entrypoint:

```ts
import { defineConfig } from 'vite';
import { createBaseConfig } from './vendor/marko/vite/resources/config/createViteConfig';

export default defineConfig(
  createBaseConfig({
    entrypoints: ['resources/js/app.ts'],
  }),
);
```

## Usage

Start frontend development through Marko's dev orchestrator:

```bash
marko dev:up
```

`marko dev:up` reads your configured development processes and runs the `package.json` `dev` script for Vite.

If you want to run the underlying script directly, it is:

```bash
npm run dev
```

Build production assets:

```bash
npm run build
```

Render tags in PHP:

```php
use Marko\Vite\Contracts\ViteManagerInterface;

$vite = $container->get(ViteManagerInterface::class);

echo $vite->tags('resources/js/app.ts');
```

In a layout, the simplest approach is to render the combined tags inside `<head>`:

```php
<?php

use Marko\Vite\Contracts\ViteManagerInterface;

/** @var ViteManagerInterface $vite */
$vite = $container->get(ViteManagerInterface::class);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Marko App', ENT_QUOTES, 'UTF-8') ?></title>
    <?= $vite->tags('resources/js/app.ts') ?>
</head>
<body>
    <?= $content ?>
</body>
</html>
```

If your templating layer exposes a `$vite` helper, the same idea in a Latte-style layout looks like:

```latte
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$pageTitle ?? 'Marko App'}</title>
    {$vite->tags('resources/js/app.ts')|noescape}
</head>
<body>
    {block content}{/block}
</body>
</html>
```

Render styles and scripts separately when needed. A common pattern is styles in `<head>` and scripts just before `</body>`:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Marko App', ENT_QUOTES, 'UTF-8') ?></title>
    <?= $vite->styles('resources/js/app.ts') ?>
</head>
<body>
    <?= $content ?>
    <?= $vite->scripts('resources/js/app.ts') ?>
</body>
</html>
```

If you configure `default_entrypoints`, you can omit the argument entirely:

```php
echo $vite->tags();
```

## Production And CI

For production builds, run the generated build script:

```bash
npm run build
```

This runs:

```bash
vite build --config ./vite.config.ts
```

The generated Vite config writes compiled assets and a manifest to `public/build`, and Marko reads that manifest at runtime to render the correct production asset tags.

A minimal CI pipeline looks like:

```bash
composer install --no-interaction --prefer-dist
npm ci
npm run build
```

If your deploy pipeline runs tests, place the frontend build before any browser, HTTP, or integration checks that expect `public/build/manifest.json` to exist.

## Extending And Overriding

`marko/vite` is designed to be customized through Marko's module system.

### Swap an interface binding

The package exposes interface bindings for components such as `ViteManagerInterface`, `TagRendererInterface`, `ManifestRepositoryInterface`, and more. In your app module, you can swap one implementation for another:

```php
<?php

declare(strict_types=1);

use App\MyApp\Vite\CustomTagRenderer;
use Marko\Vite\Contracts\TagRendererInterface;

return [
    'bindings' => [
        TagRendererInterface::class => CustomTagRenderer::class,
    ],
];
```

### Intercept public methods with a plugin

Use a plugin when you want to adjust method input or output without replacing the whole class:

```php
<?php

declare(strict_types=1);

namespace App\MyApp\Plugin;

use Marko\Core\Attributes\Before;
use Marko\Core\Attributes\Plugin;
use Marko\Vite\Contracts\ViteManagerInterface;

#[Plugin(target: ViteManagerInterface::class)]
class ViteManagerPlugin
{
    #[Before(method: 'tags')]
    public function addAdminEntrypoint(string|array|null $entrypoints = null): array
    {
        $entrypoints = is_array($entrypoints) ? $entrypoints : array_filter([$entrypoints]);
        $entrypoints[] = 'resources/js/admin.ts';

        return [array_values(array_unique($entrypoints))];
    }
}
```

### Replace a concrete class with a preference

When you need inheritance-based overrides, replace the concrete class globally:

```php
<?php

declare(strict_types=1);

namespace App\MyApp\Vite;

use Marko\Core\Attributes\Preference;
use Marko\Vite\TagRenderer;

#[Preference(replaces: TagRenderer::class)]
class CustomTagRenderer extends TagRenderer
{
    // Override selected methods here.
}
```

## Documentation

Full usage, configuration, and API reference: [marko/vite](https://marko.build/docs/packages/vite/)
