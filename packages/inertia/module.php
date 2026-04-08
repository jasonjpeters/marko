<?php

declare(strict_types=1);

use Marko\Config\ConfigRepositoryInterface;
use Marko\Core\Container\ContainerInterface;
use Marko\Inertia\Config\InertiaConfig;
use Marko\Inertia\Contracts\InertiaInterface;
use Marko\Inertia\Contracts\PageComponentLocatorInterface;
use Marko\Inertia\Contracts\RootViewRendererInterface;
use Marko\Inertia\InertiaManager;
use Marko\Inertia\ModulePageComponentLocator;
use Marko\Inertia\RootViewRenderer;

return [
    'bindings' => [
        InertiaConfig::class => static function (ContainerInterface $container): InertiaConfig {
            return new InertiaConfig($container->get(ConfigRepositoryInterface::class));
        },
        InertiaInterface::class => InertiaManager::class,
        PageComponentLocatorInterface::class => ModulePageComponentLocator::class,
        RootViewRendererInterface::class => RootViewRenderer::class,
    ],
    'singletons' => [
        InertiaConfig::class,
        InertiaManager::class,
        ModulePageComponentLocator::class,
        RootViewRenderer::class,
    ],
];
