<?php

declare(strict_types=1);

namespace Marko\Inertia;

use Marko\Core\Event\EventDispatcherInterface;
use Marko\Inertia\Config\InertiaConfig;
use Marko\Inertia\Contracts\InertiaInterface;
use Marko\Inertia\Contracts\PageComponentLocatorInterface;
use Marko\Inertia\Contracts\RootViewRendererInterface;
use Marko\Inertia\Events\InertiaRendering;
use Marko\Inertia\Support\Header;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;

class InertiaManager implements InertiaInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $shared = [];

    public function __construct(
        private readonly InertiaConfig $config,
        private readonly PageComponentLocatorInterface $pages,
        private readonly RootViewRendererInterface $rootViewRenderer,
        private readonly EventDispatcherInterface $events,
    ) {}

    public function render(
        string $component,
        array $props = [],
        ?Request $request = null,
    ): Response {
        $request ??= Request::fromGlobals();

        if ($this->config->shouldEnsurePagesExist()) {
            $this->pages->resolve($component);
        }

        $version = $this->config->version();
        if (
            $this->isInertiaRequest($request)
            && $version !== null
            && ($requestVersion = $request->header(Header::VERSION)) !== null
            && $requestVersion !== $version
        ) {
            return $this->location($this->currentUrl($request), $request);
        }

        $event = new InertiaRendering($request, $component, $props, $this->shared);
        $this->events->dispatch($event);

        $page = [
            'component' => $component,
            'props' => $this->filterProps($request, array_replace($event->sharedProps, $event->props), $component),
            'url' => $this->currentUrl($request),
            'version' => $version,
        ];

        if ($this->config->encryptHistory()) {
            $page['encryptHistory'] = true;
        }

        if ($this->isInertiaRequest($request)) {
            return new Response(
                body: json_encode($page, JSON_THROW_ON_ERROR),
                statusCode: 200,
                headers: [
                    'Content-Type' => 'application/json',
                    Header::INERTIA => 'true',
                    'Vary' => Header::INERTIA,
                ],
            );
        }

        return Response::html(
            $this->rootViewRenderer->render($page),
            200,
        );
    }

    public function location(
        string $url,
        ?Request $request = null,
    ): Response {
        $request ??= Request::fromGlobals();

        if ($this->isInertiaRequest($request)) {
            return new Response(
                body: '',
                statusCode: 409,
                headers: [
                    Header::LOCATION => $url,
                    'Vary' => Header::INERTIA,
                ],
            );
        }

        return Response::redirect($url);
    }

    public function share(
        string|array $key,
        mixed $value = null,
    ): void {
        if (is_array($key)) {
            $this->shared = array_replace($this->shared, $key);

            return;
        }

        $this->shared[$key] = $value;
    }

    public function shared(): array
    {
        return $this->shared;
    }

    /**
     * @param array<string, mixed> $props
     * @return array<string, mixed>
     */
    private function filterProps(
        Request $request,
        array $props,
        string $component,
    ): array {
        if (! $this->isInertiaRequest($request)) {
            return $props;
        }

        if ($request->header(Header::PARTIAL_COMPONENT) !== $component) {
            return $props;
        }

        $only = $this->parseHeaderList($request->header(Header::PARTIAL_DATA));
        if ($only !== []) {
            return array_intersect_key($props, array_flip($only));
        }

        $except = $this->parseHeaderList($request->header(Header::PARTIAL_EXCEPT));
        if ($except === []) {
            return $props;
        }

        return array_diff_key($props, array_flip($except));
    }

    /**
     * @return array<string>
     */
    private function parseHeaderList(
        ?string $value,
    ): array {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode(',', $value),
        )));
    }

    private function currentUrl(
        Request $request,
    ): string {
        $uri = $request->path();
        $query = $request->query();

        if ($query === []) {
            return $uri;
        }

        $queryString = http_build_query($query);

        return $queryString === '' ? $uri : $uri . '?' . $queryString;
    }

    private function isInertiaRequest(
        Request $request,
    ): bool {
        return strtolower((string) $request->header(Header::INERTIA, '')) === 'true';
    }
}
