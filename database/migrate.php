<?php

declare(strict_types=1);

/**
 * Run migrations. Usage: php database/migrate.php
 */

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';

$envFile = $basePath . '/.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\"'");
            if ($name !== '') { putenv("$name=$value"); $_ENV[$name] = $value; }
        }
    }
}

$config = require $basePath . '/config/database.php';
$c = $config['connections']['mysql'];
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['database'], $c['charset']);
$pdo = new PDO($dsn, $c['username'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$migrationsDir = $basePath . '/database/migrations';
$migrations = glob($migrationsDir . '/*.sql');
sort($migrations);

$pdo->exec("CREATE TABLE IF NOT EXISTS _migrations (name VARCHAR(255) PRIMARY KEY, run_at DATETIME NOT NULL)");

foreach ($migrations as $path) {
    $name = basename($path);
    $exists = $pdo->query("SELECT 1 FROM _migrations WHERE name = " . $pdo->quote($name))->fetch();
    if ($exists) {
        echo "Skip: $name\n";
        continue;
    }
    $sql = file_get_contents($path);
    $pdo->exec($sql);
    $pdo->prepare("INSERT INTO _migrations (name, run_at) VALUES (?, NOW())")->execute([$name]);
    echo "Ran: $name\n";
}

echo "Migrations done.\n";
