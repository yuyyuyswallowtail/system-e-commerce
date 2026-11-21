<?php
declare(strict_types=1);

/**
 * Database connection checker.
 *
 * Reads .env manually, builds a PDO DSN, and attempts a connection.
 * Functions are callable from tests; the CLI runs only when executed directly.
 */

if (!function_exists('parse_env')) {
    /**
     * Parse a .env file into an associative array.
     *
     * @param string $path Path to the .env file.
     * @return array<string, string> Key-value pairs.
     * @throws \RuntimeException If the file does not exist.
     */
    function parse_env(string $path): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: $path");
        }

        $result = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new \RuntimeException("Cannot read file: $path");
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Skip empty lines and comments
            if ($trimmed === '' || $trimmed[0] === '#') {
                continue;
            }

            $eqPos = strpos($trimmed, '=');
            if ($eqPos === false) {
                continue;
            }

            $key = trim(substr($trimmed, 0, $eqPos));
            $value = trim(substr($trimmed, $eqPos + 1));

            // Strip surrounding quotes
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            if ($key !== '') {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}

if (!function_exists('build_dsn')) {
    /**
     * Build a PDO DSN string from parsed environment variables.
     *
     * @param array<string, string> $env Parsed environment variables.
     * @return string The DSN string.
     */
    function build_dsn(array $env): string
    {
        $host = $env['DB_HOST'] ?? '127.0.0.1';
        $port = $env['DB_PORT'] ?? '3306';
        $name = $env['DB_NAME'] ?? '';

        return "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    }
}

if (!function_exists('check_connection')) {
    /**
     * Check the database connection.
     *
     * @param array<string, string> $env Parsed environment variables.
     * @return array{ok: bool, message: string, warnings: list<string>}
     */
    function check_connection(array $env): array
    {
        $warnings = [];

        // Check pdo_mysql extension
        if (!extension_loaded('pdo_mysql')) {
            return ['ok' => false, 'message' => 'pdo_mysql extension not loaded', 'warnings' => []];
        }

        // Check .env file exists
        $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (!file_exists($envPath)) {
            return ['ok' => false, 'message' => '.env file not found', 'warnings' => []];
        }

        try {
            $parsed = parse_env($envPath);
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'warnings' => []];
        }

        // Merge passed overrides (e.g. port override for tests)
        $parsed = array_merge($parsed, $env);

        $dsn = build_dsn($parsed);
        $user = $parsed['DB_USER'] ?? '';
        $password = $parsed['DB_PASSWORD'] ?? '';

        // Empty password warning
        if ($password === '') {
            $warnings[] = 'Empty database password';
        }

        try {
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $serverInfo = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            $dbName = $parsed['DB_NAME'] ?? 'unknown';
            $host = $parsed['DB_HOST'] ?? 'unknown';
            $port = $parsed['DB_PORT'] ?? 'unknown';

            return [
                'ok' => true,
                'message' => "[OK] Connection successful | host:{$host}:{$port} | database: {$dbName} | server: {$serverInfo}",
                'warnings' => $warnings,
            ];
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            // Do not include the password in the message
            return [
                'ok' => false,
                'message' => "Connection failed: {$msg}",
                'warnings' => $warnings,
            ];
        }
    }
}

// CLI entry point only
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === 'check-db.php') {
    $result = check_connection([]);

    if ($result['ok']) {
        echo $result['message'] . PHP_EOL;
        if (!empty($result['warnings'])) {
            foreach ($result['warnings'] as $warning) {
                echo "[WARN] {$warning}" . PHP_EOL;
            }
        }
        exit(0);
    } else {
        echo "[FAIL] " . $result['message'] . PHP_EOL;
        exit(1);
    }
}
