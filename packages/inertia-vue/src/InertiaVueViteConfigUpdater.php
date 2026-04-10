<?php

declare(strict_types=1);

namespace Marko\Inertia\Vue;

use Marko\Core\Path\ProjectPaths;
use Marko\Vite\ProjectFilePublisher;
use Marko\Vite\ValueObjects\FilePublishResult;
use Marko\Vite\ValueObjects\ViteConfig;

class InertiaVueViteConfigUpdater
{
    public function __construct(
        private readonly ViteConfig $viteConfig,
        private readonly ProjectPaths $paths,
        private readonly ProjectFilePublisher $publisher,
    ) {}

    public function ensureVueConfig(
        bool $force = false,
        bool $dryRun = false,
    ): FilePublishResult {
        $relativePath = $this->viteConfig->rootViteConfigPath;
        $absolutePath = $this->paths->base . '/' . $relativePath;

        if (! is_file($absolutePath)) {
            return $this->publisher->publish($relativePath, $this->vueStub(), false, $dryRun);
        }

        $contents = (string) file_get_contents($absolutePath);

        if ($this->containsVuePlugin($contents) || $this->normalized($contents) === $this->normalized($this->vueStub())) {
            return new FilePublishResult($relativePath, 'already_present');
        }

        $replacement = $this->containsTailwindPlugin($contents)
            ? $this->tailwindVueStub()
            : $this->vueStub();

        if ($this->isReplaceableViteStub($contents) || $force) {
            return $this->publisher->publish($relativePath, $replacement, true, $dryRun);
        }

        return new FilePublishResult($relativePath, 'skipped');
    }

    private function containsVuePlugin(string $contents): bool
    {
        return str_contains($contents, '@vitejs/plugin-vue')
            || str_contains($contents, 'plugins: [vue()]')
            || str_contains($contents, 'plugins: [vue(), tailwindcss()]');
    }

    private function containsTailwindPlugin(string $contents): bool
    {
        return str_contains($contents, '@tailwindcss/vite')
            || str_contains($contents, "entrypoints: ['resources/js/app.ts', 'resources/css/app.css']")
            || str_contains($contents, 'entrypoints: ["resources/js/app.ts", "resources/css/app.css"]');
    }

    private function normalized(string $contents): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $contents));
    }

    private function isReplaceableViteStub(string $contents): bool
    {
        $normalized = $this->normalized($contents);

        return $normalized === $this->normalized($this->viteStub())
            || $normalized === $this->normalized($this->tailwindStub())
            || $normalized === $this->normalized($this->legacyViteStub())
            || $normalized === $this->normalized($this->vendorProxyViteStub())
            || $normalized === $this->normalized($this->legacyTailwindProxyStub())
            || $normalized === $this->normalized($this->vendorTailwindProxyStub());
    }

    private function viteStub(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2) . '/vite/stubs/vite.config.ts');
    }

    private function tailwindStub(): string
    {
        return str_replace(
            "entrypoints: ['resources/js/app.ts'],",
            "plugins: [tailwindcss()],\n    entrypoints: ['resources/js/app.ts', 'resources/css/app.css'],",
            str_replace(
                "import { createBaseConfig } from './vendor/marko/vite/resources/config/createViteConfig';",
                "import tailwindcss from '@tailwindcss/vite';\nimport { createBaseConfig } from './vendor/marko/vite/resources/config/createViteConfig';",
                $this->viteStub(),
            ),
        );
    }

    private function vueStub(): string
    {
        return str_replace(
            "entrypoints: ['resources/js/app.ts'],",
            "plugins: [vue()],\n    entrypoints: ['resources/js/app.ts'],",
            str_replace(
                "import { createBaseConfig } from './vendor/marko/vite/resources/config/createViteConfig';",
                "import vue from '@vitejs/plugin-vue';\nimport { createBaseConfig } from './vendor/marko/vite/resources/config/createViteConfig';",
                $this->viteStub(),
            ),
        );
    }

    private function tailwindVueStub(): string
    {
        return str_replace(
            "entrypoints: ['resources/js/app.ts'],",
            "plugins: [vue(), tailwindcss()],\n    entrypoints: ['resources/js/app.ts', 'resources/css/app.css'],",
            str_replace(
                "import { createBaseConfig } from './vendor/marko/vite/resources/config/createViteConfig';",
                "import vue from '@vitejs/plugin-vue';\nimport tailwindcss from '@tailwindcss/vite';\nimport { createBaseConfig } from './vendor/marko/vite/resources/config/createViteConfig';",
                $this->viteStub(),
            ),
        );
    }

    private function legacyViteStub(): string
    {
        return "export { default } from './modules/vite/resources/config/vite.config';\n";
    }

    private function vendorProxyViteStub(): string
    {
        return "export { default } from './vendor/marko/vite/resources/config/vite.config.ts';\n";
    }

    private function legacyTailwindProxyStub(): string
    {
        return "export { default } from './modules/tailwindcss/resources/config/vite.config';\n";
    }

    private function vendorTailwindProxyStub(): string
    {
        return "export { default } from './vendor/marko/tailwindcss/resources/config/vite.config.ts';\n";
    }
}
