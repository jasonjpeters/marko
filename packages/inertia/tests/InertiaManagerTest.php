<?php

declare(strict_types=1);

use Marko\Config\ConfigRepository;
use Marko\Core\Event\Event;
use Marko\Core\Event\EventDispatcherInterface;
use Marko\Inertia\Config\InertiaConfig;
use Marko\Inertia\Events\InertiaRendering;
use Marko\Inertia\InertiaManager;
use Marko\Inertia\Support\Header;
use Marko\Routing\Http\Request;

function fakeInertiaConfig(array $overrides = []): InertiaConfig
{
    return new InertiaConfig(new ConfigRepository([
        'inertia' => array_replace_recursive([
            'version' => 'v1',
            'root_view' => [
                'id' => 'app',
                'title' => 'Marko',
            ],
            'pages' => [
                'ensure_pages_exist' => false,
                'paths' => ['resources/js/Pages'],
                'extensions' => ['tsx'],
            ],
            'testing' => [
                'ensure_pages_exist' => false,
            ],
            'history' => [
                'encrypt' => false,
            ],
        ], $overrides),
    ]));
}

function makeInertiaManager(?callable $listener = null, array $configOverrides = []): InertiaManager
{
    $pages = new class () implements \Marko\Inertia\Contracts\PageComponentLocatorInterface
    {
        public function resolve(string $component): string
        {
            return '/virtual/' . $component . '.tsx';
        }

        public function exists(string $component): bool
        {
            return true;
        }

        public function getSearchedPaths(string $component): array
        {
            return ['/virtual/' . $component . '.tsx'];
        }
    };

    $renderer = new class () implements \Marko\Inertia\Contracts\RootViewRendererInterface
    {
        public function render(array $page): string
        {
            return '<html><body>' . htmlspecialchars(json_encode($page, JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8') . '</body></html>';
        }
    };

    $events = new class ($listener) implements EventDispatcherInterface
    {
        public function __construct(
            private readonly mixed $listener,
        ) {}

        public function dispatch(Event $event): void
        {
            if ($this->listener !== null) {
                ($this->listener)($event);
            }
        }
    };

    return new InertiaManager(fakeInertiaConfig($configOverrides), $pages, $renderer, $events);
}

it('renders an html bootstrap response for first visits', function (): void {
    $manager = makeInertiaManager();
    $request = new Request(
        server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/dashboard'],
    );

    $response = $manager->render('Dashboard/Index', ['title' => 'Dashboard'], $request);

    expect($response->statusCode())->toBe(200)
        ->and($response->headers()['Content-Type'])->toBe('text/html; charset=utf-8')
        ->and($response->body())->toContain('&quot;component&quot;:&quot;Dashboard\\/Index&quot;')
        ->and($response->body())->toContain('&quot;url&quot;:&quot;\\/dashboard&quot;');
});

it('renders json for inertia requests and merges shared props', function (): void {
    $manager = makeInertiaManager();
    $manager->share('auth', ['user' => 'Taylor']);
    $request = new Request(
        server: [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/dashboard',
            'HTTP_X_INERTIA' => 'true',
        ],
    );

    $response = $manager->render('Dashboard/Index', ['stats' => [1, 2, 3]], $request);
    $payload = json_decode($response->body(), true, flags: JSON_THROW_ON_ERROR);

    expect($response->headers()[Header::INERTIA])->toBe('true')
        ->and($response->headers()['Vary'])->toBe(Header::INERTIA)
        ->and($payload['props'])->toBe([
            'auth' => ['user' => 'Taylor'],
            'stats' => [1, 2, 3],
        ]);
});

it('allows observers to contribute shared props during rendering', function (): void {
    $manager = makeInertiaManager(function (Event $event): void {
        if ($event instanceof InertiaRendering) {
            $event->share('ziggy', ['location' => '/dashboard']);
        }
    });

    $request = new Request(
        server: [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/dashboard',
            'HTTP_X_INERTIA' => 'true',
        ],
    );

    $response = $manager->render('Dashboard/Index', [], $request);
    $payload = json_decode($response->body(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['props'])->toHaveKey('ziggy')
        ->and($payload['props']['ziggy']['location'])->toBe('/dashboard');
});

it('supports partial reload headers for matching components', function (): void {
    $manager = makeInertiaManager();
    $request = new Request(
        server: [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/dashboard',
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_PARTIAL_COMPONENT' => 'Dashboard/Index',
            'HTTP_X_INERTIA_PARTIAL_DATA' => 'stats',
        ],
    );

    $response = $manager->render('Dashboard/Index', [
        'stats' => [1, 2, 3],
        'flash' => ['ok' => true],
    ], $request);
    $payload = json_decode($response->body(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['props'])->toBe([
        'stats' => [1, 2, 3],
    ]);
});

it('returns an inertia location response for version mismatches', function (): void {
    $manager = makeInertiaManager();
    $request = new Request(
        server: [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/dashboard',
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_VERSION' => 'stale-version',
        ],
    );

    $response = $manager->render('Dashboard/Index', [], $request);

    expect($response->statusCode())->toBe(409)
        ->and($response->headers()[Header::LOCATION])->toBe('/dashboard');
});

it('returns a normal redirect for non-inertia location visits', function (): void {
    $manager = makeInertiaManager();
    $request = new Request(
        server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/dashboard'],
    );

    $response = $manager->location('/login', $request);

    expect($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/login');
});
