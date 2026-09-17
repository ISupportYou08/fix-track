<?php

declare(strict_types=1);

$runtimeRoot = '/tmp/fixtrack';
$runtimeDirectories = [
    $runtimeRoot.'/cache',
    $runtimeRoot.'/storage/framework/cache/data',
    $runtimeRoot.'/storage/framework/sessions',
    $runtimeRoot.'/storage/framework/views',
    $runtimeRoot.'/storage/logs',
];

foreach ($runtimeDirectories as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
}

$runtimeEnvironment = [
    'APP_CONFIG_CACHE' => $runtimeRoot.'/cache/config.php',
    'APP_EVENTS_CACHE' => $runtimeRoot.'/cache/events.php',
    'APP_PACKAGES_CACHE' => $runtimeRoot.'/cache/packages.php',
    'APP_ROUTES_CACHE' => $runtimeRoot.'/cache/routes.php',
    'APP_SERVICES_CACHE' => $runtimeRoot.'/cache/services.php',
    'LARAVEL_STORAGE_PATH' => $runtimeRoot.'/storage',
    'VIEW_COMPILED_PATH' => $runtimeRoot.'/storage/framework/views',
];

foreach ($runtimeEnvironment as $key => $value) {
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
    putenv("{$key}={$value}");
}

require __DIR__.'/../public/index.php';
