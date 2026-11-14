<?php
declare(strict_types=1);

if (!defined('STDERR')) {
    define('STDERR', fopen('php://stderr', 'w'));
}

function parse_env(string $path): array
{
    if (!file_exists($path)) {
        throw new InvalidArgumentException("File not found: $path");
    }

    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        throw new RuntimeException("Cannot read file: $path");
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $eqPos));
        $value = trim(substr($line, $eqPos + 1));

        if ($key === '') {
            continue;
        }

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $vars[$key] = $value;
    }

    return $vars;
}

function build_dsn(array $env): string
{
    $host = $env['DB_HOST'] ?? '';
    $port = $env['DB_PORT'] ?? '';
    return "mysql:host={$host};port={$port};charset=utf8mb4";
}

function check_connection(array $env): array
{
    $dsn = build_dsn($env);
    $user = $env['DB_USER'] ?? '';
    $password = $env['DB_PASSWORD'] ?? '';
    $database = $env['DB_NAME'] ?? '';

    $warnings = [];

    if ($password === '') {
        $warnings[] = 'Empty password';
    }

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->query('SELECT 1');

        $serverName = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

        return [
            'ok' => true,
            'message' => "[OK] Connection successful | host:{$env['DB_HOST']}:{$env['DB_PORT']} | database: {$database} | server: {$serverName}",
            'warnings' => $warnings,
        ];
    } catch (PDOException $e) {
        $msg = $e->getMessage();

        if (stripos($msg, 'access denied') !== false
            || stripos($msg, 'authentication') !== false
            || stripos($msg, 'password') !== false) {
            return [
                'ok' => false,
                'message' => '[FAIL] Authentication failed',
                'warnings' => $warnings,
            ];
        }

        if (stripos($msg, 'no such file') !== false
            || stripos($msg, 'connect') !== false
            || stripos($msg, 'refused') !== false) {
            return [
                'ok' => false,
                'message' => '[FAIL] Connection refused',
                'warnings' => $warnings,
            ];
        }

        return [
            'ok' => false,
            'message' => '[FAIL] ' . $e->getMessage(),
            'warnings' => $warnings,
        ];
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['SCRIPT_NAME'], '.php') === 'check-db') {
    $envPath = __DIR__ . '/../.env';

    if (!file_exists($envPath)) {
        fwrite(STDERR, "[FAIL] .env file not found\n");
        exit(1);
    }

    if (!extension_loaded('pdo_mysql')) {
        fwrite(STDERR, "[FAIL] pdo_mysql extension not loaded\n");
        exit(1);
    }

    try {
        $env = parse_env($envPath);
    } catch (InvalidArgumentException $e) {
        fwrite(STDERR, "[FAIL] " . $e->getMessage() . "\n");
        exit(1);
    }

    $result = check_connection($env);

    if ($result['ok']) {
        echo $result['message'] . "\n";
        foreach ($result['warnings'] as $warning) {
            fwrite(STDERR, "[WARN] {$warning}\n");
        }
        exit(0);
    }

    fwrite(STDERR, "{$result['message']}\n");
    foreach ($result['warnings'] as $warning) {
        fwrite(STDERR, "[WARN] {$warning}\n");
    }
    exit(1);
}
