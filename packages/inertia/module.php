<?php

declare(strict_types=1);

use Marko\Config\ConfigRepositoryInterface;
use Marko\Core\Container\ContainerInterface;
use Marko\Core\Event\EventDispatcherInterface;
use Marko\Inertia\Inertia;
use Marko\Inertia\InertiaConfig;
use Marko\Inertia\Interfaces\ComponentResolverInterface;
use Marko\Inertia\Interfaces\InertiaInterface;
use Marko\Inertia\Interfaces\RootRendererInterface;
use Marko\Inertia\Interfaces\SsrGatewayInterface;
use Marko\Inertia\Props\PropsResolver;
use Marko\Inertia\Rendering\ModuleComponentResolver;
use Marko\Inertia\Rendering\RootRenderer;
use Marko\Inertia\Response\ResponseFactory;
use Marko\Inertia\Ssr\SsrGateway;
use Marko\Session\Contracts\SessionInterface;

return [
    'bindings' => [
        InertiaConfig::class => static function (ContainerInterface $container): InertiaConfig {
            return new InertiaConfig($container->get(ConfigRepositoryInterface::class));
        },
        ResponseFactory::class => static function (ContainerInterface $container): ResponseFactory {
            return new ResponseFactory(
                config: $container->get(InertiaConfig::class),
                components: $container->get(ComponentResolverInterface::class),
                rootRenderer: $container->get(RootRendererInterface::class),
                events: $container->get(EventDispatcherInterface::class),
                propsResolver: $container->get(PropsResolver::class),
                sessionResolver: static function () use ($container): ?object {
                    try {
                        return $container->get(SessionInterface::class);
                    } catch (Throwable) {
                        return null;
                    }
                },
            );
        },
        InertiaInterface::class => Inertia::class,
        ComponentResolverInterface::class => ModuleComponentResolver::class,
        RootRendererInterface::class => RootRenderer::class,
        SsrGatewayInterface::class => SsrGateway::class,
    ],
    'singletons' => [
        InertiaConfig::class,
        Inertia::class,
        ModuleComponentResolver::class,
        PropsResolver::class,
        ResponseFactory::class,
        RootRenderer::class,
        SsrGateway::class,
    ],
];
