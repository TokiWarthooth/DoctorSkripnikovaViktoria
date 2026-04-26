<?php

declare(strict_types=1);

namespace App;

final class Env
{
    public static function load(string $rootDir): void
    {
        $path = $rootDir . '/.env';
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            $name = trim($parts[0]);
            $value = trim($parts[1] ?? '', " \t\"'");
            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $name)) {
                continue;
            }
            if (getenv($name) === false) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
            }
        }
    }
}
