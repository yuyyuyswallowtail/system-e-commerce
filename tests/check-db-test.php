<?php
declare(strict_types=1);

require_once __DIR__ . '/../scripts/check-db.php';

$passed = 0;
$failed = 0;
$skipped = 0;

function assert_equal($expected, $actual, string $label): void
{
    if ($expected === $actual) {
        global $passed;
        $passed++;
    } else {
        global $failed;
        $failed++;
        echo "FAIL: {$label}\n";
        echo "  expected: " . var_export($expected, true) . "\n";
        echo "  actual:   " . var_export($actual, true) . "\n";
    }
}

function assert_false($actual, string $label): void
{
    if ($actual === false) {
        global $passed;
        $passed++;
    } else {
        global $failed;
        $failed++;
        echo "FAIL: {$label}\n";
    }
}

function assert_true($actual, string $label): void
{
    if ($actual === true) {
        global $passed;
        $passed++;
    } else {
        global $failed;
        $failed++;
        echo "FAIL: {$label}\n";
    }
}

$envPath = __DIR__ . '/../.env';

if (!file_exists($envPath)) {
    echo "SKIP: .env not found, skipping all tests\n";
    global $skipped;
    $skipped++;
    echo "Passed: {$passed} | Failed: {$failed} | Skipped: {$skipped}\n";
    exit(0);
}

$env = parse_env($envPath);
$dbPassword = $env['DB_PASSWORD'] ?? '';

// --- parse_env tests ---

// Test: KEY=VALUE parsing
$tempFile = tempnam(sys_get_temp_dir(), 'env_');
file_put_contents($tempFile, "FOO=bar\nBAZ=qux\n");
$parsed = parse_env($tempFile);
assert_equal('bar', $parsed['FOO'] ?? null, 'parse_env: KEY=VALUE');
assert_equal('qux', $parsed['BAZ'] ?? null, 'parse_env: second key');
unlink($tempFile);

// Test: comments and blank lines ignored
$tempFile = tempnam(sys_get_temp_dir(), 'env_');
file_put_contents($tempFile, "# comment\n\nKEY=value\n");
$parsed = parse_env($tempFile);
assert_equal('value', $parsed['KEY'] ?? null, 'parse_env: comments and blank lines ignored');
unlink($tempFile);

// Test: quotes trimmed
$tempFile = tempnam(sys_get_temp_dir(), 'env_');
file_put_contents($tempFile, 'KEY="quoted"\n');
$parsed = parse_env($tempFile);
assert_equal('quoted', $parsed['KEY'] ?? null, 'parse_env: double quotes trimmed');
unlink($tempFile);

// Test: exception on missing file
try {
    parse_env('/nonexistent/file.env');
    assert_false(true, 'parse_env: exception on missing file');
} catch (InvalidArgumentException $e) {
    assert_true(true, 'parse_env: exception on missing file');
}

// --- build_dsn test ---

$testEnv = [
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3307',
];
$dsn = build_dsn($testEnv);
assert_equal('mysql:host=127.0.0.1;port=3307;charset=utf8mb4', $dsn, 'build_dsn: constructs correct DSN');

// --- check_connection tests ---

// Test: fails on unused port (override port only)
$testEnv = $env;
$testEnv['DB_PORT'] = '1';
try {
    $result = check_connection($testEnv);
    assert_false($result['ok'], 'check_connection: fails on unused port');
} catch (Exception $e) {
    assert_true(true, 'check_connection: fails on unused port');
}

// Test: message does not contain the password
$testEnv = $env;
$testEnv['DB_PORT'] = '1';
try {
    $result = check_connection($testEnv);
    assert_false(
        strpos($result['message'], $dbPassword) !== false,
        'check_connection: message does not contain password'
    );
} catch (Exception $e) {
    assert_true(true, 'check_connection: message does not contain password');
}

// Test: empty password gives a warning
$testEnv = $env;
$testEnv['DB_PASSWORD'] = '';
$result = check_connection($testEnv);
assert_true(
    in_array('Empty password', $result['warnings'], true),
    'check_connection: empty password gives a warning'
);

// --- Integration test with real .env ---

try {
    $result = check_connection($env);
    if ($result['ok']) {
        assert_true(true, 'check_connection: integration with real .env');
    } else {
        echo "SKIP: integration test: connection failed ({$result['message']})\n";
        global $skipped;
        $skipped++;
    }
} catch (Exception $e) {
    echo "SKIP: integration test: exception ({$e->getMessage()})\n";
    global $skipped;
    $skipped++;
}

echo "Passed: {$passed} | Failed: {$failed} | Skipped: {$skipped}\n";
exit($failed > 0 ? 1 : 0);
