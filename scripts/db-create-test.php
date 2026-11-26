<?php

declare(strict_types=1);

const SEC_TEST_DATABASE = 'system_e_commerce_test';

/**
 * Parse a dotenv file into a key => value array. Comments and blank lines are skipped.
 *
 * @return array<string, string>
 */
function sec_parse_env_file(string $path): array
{
    if (! is_file($path) || ! is_readable($path)) {
        return [];
    }

    $values = [];

    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && str_ends_with($value, $value[0])) {
            $value = substr($value, 1, -1);
        }

        $values[$key] = $value;
    }

    return $values;
}

/**
 * Create the test database when it does not exist.
 *
 * @param  array<string, string>  $env
 * @return array{0: bool, 1: string}
 */
function sec_create_test_database(array $env, string $database = SEC_TEST_DATABASE): array
{
    if (preg_match('/^[A-Za-z0-9_]+$/', $database) !== 1) {
        return [false, 'invalid database name'];
    }

    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $user = $env['DB_USERNAME'] ?? '';
    $password = $env['DB_PASSWORD'] ?? '';

    if ($user === '') {
        return [false, 'DB_USERNAME is not set'];
    }

    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port),
            $user,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        $pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4', $database));
    } catch (PDOException $exception) {
        return [false, substr(preg_replace('/\s+/', ' ', $exception->getMessage()) ?? 'connection failed', 0, 120)];
    }

    return [true, $database];
}

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    [$ok, $message] = sec_create_test_database(sec_parse_env_file(dirname(__DIR__).'/.env'));

    echo $ok ? "[OK] {$message}\n" : "[FAIL] {$message}\n";
    exit($ok ? 0 : 1);
}
