<?php

declare(strict_types=1);

namespace Marko\Vite;

use Marko\Vite\ValueObjects\ViteConfig;

class ScaffoldTemplateRenderer
{
    public function __construct(
        private readonly ViteConfig $config,
    ) {}

    /**
     * @param list<string> $imports
     * @param list<string> $plugins
     * @param list<string>|null $entrypoints
     */
    public function renderViteConfig(
        array $imports = [],
        array $plugins = [],
        ?array $entrypoints = null,
    ): string {
        $configImport = $this->relativePath(
            $this->config->rootViteConfigPath,
            'vendor/marko/vite/resources/config/createViteConfig',
        );

        $lines = [
            "import { defineConfig } from 'vite';",
            ...$imports,
            sprintf("import { createBaseConfig } from '%s';", $configImport),
            '',
            'export default defineConfig(',
            '  createBaseConfig({',
        ];

        if ($plugins !== []) {
            $lines[] = sprintf('    plugins: [%s],', implode(', ', $plugins));
        }

        $resolvedEntrypoints = $entrypoints ?? [$this->config->rootEntrypointPath];
        $lines[] = sprintf(
            '    entrypoints: [%s],',
            implode(', ', array_map(
                static fn (string $entrypoint): string => sprintf("'%s'", $entrypoint),
                $resolvedEntrypoints,
            )),
        );
        $lines[] = '  }),';
        $lines[] = ');';

        return implode("\n", $lines) . "\n";
    }

    public function renderViteEntrypoint(): string
    {
        $bootstrapImport = $this->relativePath(
            $this->config->rootEntrypointPath,
            'vendor/marko/vite/resources/js/bootstrap',
        );

        return implode("\n", [
            sprintf("import { bootstrapMarkoVite } from '%s';", $bootstrapImport),
            '',
            sprintf("bootstrapMarkoVite('%s');", $this->config->rootEntrypointPath),
            '',
        ]);
    }

    public function renderInertiaReactEntrypoint(): string
    {
        $bootstrapImport = $this->relativePath(
            $this->config->rootEntrypointPath,
            'vendor/marko/inertia-react/resources/js/bootstrap',
        );

        return implode("\n", [
            sprintf("import { bootstrapMarkoInertiaReact } from '%s';", $bootstrapImport),
            '',
            'const pages = import.meta.glob([',
            sprintf("  '%s',", $this->globPath('./pages/**/*.jsx')),
            sprintf("  '%s',", $this->globPath('./pages/**/*.tsx')),
            sprintf("  '%s',", $this->globPath('app/**/resources/js/pages/**/*.jsx')),
            sprintf("  '%s',", $this->globPath('app/**/resources/js/pages/**/*.tsx')),
            sprintf("  '%s',", $this->globPath('modules/**/resources/js/pages/**/*.jsx')),
            sprintf("  '%s',", $this->globPath('modules/**/resources/js/pages/**/*.tsx')),
            sprintf("  '%s',", $this->globPath('vendor/marko/**/resources/js/pages/**/*.jsx')),
            sprintf("  '%s',", $this->globPath('vendor/marko/**/resources/js/pages/**/*.tsx')),
            ']);',
            '',
            'bootstrapMarkoInertiaReact({ pages });',
            '',
        ]);
    }

    public function renderInertiaVueEntrypoint(): string
    {
        $bootstrapImport = $this->relativePath(
            $this->config->rootEntrypointPath,
            'vendor/marko/inertia-vue/resources/js/bootstrap',
        );

        return implode("\n", [
            sprintf("import { bootstrapMarkoInertiaVue } from '%s';", $bootstrapImport),
            '',
            'const pages = import.meta.glob([',
            sprintf("  '%s',", $this->globPath('./pages/**/*.vue')),
            sprintf("  '%s',", $this->globPath('app/**/resources/js/pages/**/*.vue')),
            sprintf("  '%s',", $this->globPath('modules/**/resources/js/pages/**/*.vue')),
            sprintf("  '%s',", $this->globPath('vendor/marko/**/resources/js/pages/**/*.vue')),
            ']);',
            '',
            'bootstrapMarkoInertiaVue({ pages });',
            '',
        ]);
    }

    public function renderInertiaSvelteEntrypoint(): string
    {
        $bootstrapImport = $this->relativePath(
            $this->config->rootEntrypointPath,
            'vendor/marko/inertia-svelte/resources/js/bootstrap',
        );

        return implode("\n", [
            sprintf("import { bootstrapMarkoInertiaSvelte } from '%s';", $bootstrapImport),
            '',
            'const pages = import.meta.glob([',
            sprintf("  '%s',", $this->globPath('./pages/**/*.svelte')),
            sprintf("  '%s',", $this->globPath('app/**/resources/js/pages/**/*.svelte')),
            sprintf("  '%s',", $this->globPath('modules/**/resources/js/pages/**/*.svelte')),
            sprintf("  '%s',", $this->globPath('vendor/marko/**/resources/js/pages/**/*.svelte')),
            ']);',
            '',
            'bootstrapMarkoInertiaSvelte({ pages });',
            '',
        ]);
    }

    private function globPath(string $path): string
    {
        if (str_starts_with($path, './')) {
            return './' . ltrim($path, './');
        }

        return $this->relativePath($this->config->rootEntrypointPath, $path);
    }

    private function relativePath(
        string $from,
        string $to,
    ): string {
        $fromDirectory = dirname(str_replace('\\', '/', $from));
        $fromParts = $fromDirectory === '.' ? [] : array_values(array_filter(explode('/', trim($fromDirectory, '/'))));
        $toParts = array_values(array_filter(explode('/', trim(str_replace('\\', '/', $to), '/'))));

        while ($fromParts !== [] && $toParts !== [] && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $relativeParts = [
            ...array_fill(0, count($fromParts), '..'),
            ...$toParts,
        ];

        $relative = implode('/', $relativeParts);

        if ($relative === '') {
            return './';
        }

        if (! str_starts_with($relative, '.')) {
            return './' . $relative;
        }

        return $relative;
    }
}
