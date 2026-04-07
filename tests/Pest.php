<?php

declare(strict_types=1);

// Pest configuration for monorepo - runs tests from all packages

require dirname(__DIR__) . '/packages/testing/src/Pest/Expectations.php';
require __DIR__ . '/Support/PackageInventory.php';
require dirname(__DIR__) . '/packages/blog/tests/Pest.php';
require dirname(__DIR__) . '/packages/vite/tests/Pest.php';
require dirname(__DIR__) . '/packages/tailwindcss/tests/Pest.php';
