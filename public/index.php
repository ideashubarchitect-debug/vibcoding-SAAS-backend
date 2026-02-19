<?php

declare(strict_types=1);

/**
 * vibeable.dev REST API entry point.
 * Configure your web server to point document root to this directory.
 */

use Vibeable\Backend\Bootstrap;

require dirname(__DIR__) . '/vendor/autoload.php';

$basePath = dirname(__DIR__);
$envFile = $basePath . '/.env';

if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\"'");
            if ($name !== '' && !array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

$bootstrap = new Bootstrap($basePath);
$app = $bootstrap->createApp();
$app->run();
