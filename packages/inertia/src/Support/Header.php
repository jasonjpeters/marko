<?php

declare(strict_types=1);

namespace Marko\Inertia\Support;

final class Header
{
    public const string INERTIA = 'X-Inertia';
    public const string VERSION = 'X-Inertia-Version';
    public const string LOCATION = 'X-Inertia-Location';
    public const string PARTIAL_COMPONENT = 'X-Inertia-Partial-Component';
    public const string PARTIAL_DATA = 'X-Inertia-Partial-Data';
    public const string PARTIAL_EXCEPT = 'X-Inertia-Partial-Except';

    private function __construct() {}
}
