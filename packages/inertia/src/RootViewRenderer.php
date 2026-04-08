<?php

declare(strict_types=1);

namespace Marko\Inertia;

use Marko\Inertia\Config\InertiaConfig;
use Marko\Inertia\Contracts\RootViewRendererInterface;
use Marko\Vite\Contracts\ViteManagerInterface;

class RootViewRenderer implements RootViewRendererInterface
{
    public function __construct(
        private readonly InertiaConfig $config,
        private readonly ViteManagerInterface $vite,
    ) {}

    public function render(
        array $page,
    ): string {
        $title = $this->resolveTitle($page);
        $rootId = htmlspecialchars($this->config->rootElementId(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $pageJson = htmlspecialchars(
            json_encode($page, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title}</title>
    {$this->vite->tags()}
</head>
<body>
    <div id="{$rootId}" data-page="{$pageJson}"></div>
</body>
</html>
HTML;
    }

    /**
     * @param array<string, mixed> $page
     */
    private function resolveTitle(
        array $page,
    ): string {
        $title = $this->config->rootTitle();
        $pageTitle = $page['props']['title'] ?? null;

        if (is_string($pageTitle) && $pageTitle !== '') {
            $title = $pageTitle;
        }

        return htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
