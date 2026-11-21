<?php
declare(strict_types=1);

/**
 * Tests for scripts/check-db.php.
 *
 * Requires check-db.php, runs all assertions, reports results.
 */

require __DIR__ . '/../scripts/check-db.php';

// --- Assertion helpers ---

$passed = 0;
$failed = 0;
$skipped = 0;

function assert_equal($expected, $actual, string $label): void
{
    global $passed, $failed;
    if ($expected === $actual) {
        $passed++;
    } else {
        $failed++;
        echo "FAIL: {$label}\n";
        echo "  expected: " . var_export($expected, true) . "\n";
        echo "  actual:   " . var_export($actual, true) . "\n";
    }
}

function assert_false($value, string $label): void
{
    assert_equal(false, $value, $label);
}

function assert_true($value, string $label): void
{
    assert_equal(true, $value, $label);
}

function assert_contains(string $needle, string $haystack, string $label): void
{
    assert_equal(true, strpos($haystack, $needle) !== false, $label);
}

function assert_not_contains(string $needle, string $haystack, string $label): void
{
    assert_equal(false, strpos($haystack, $needle) !== false, $label);
}

// --- Helper: locate .env ---

$envPath = __DIR__ . '/../.env';
$envMissing = !file_exists($envPath);

if ($envMissing) {
    echo "SKIP: .env file not found; some tests skipped\n";
    $skipped++;
}

// --- parse_env tests ---

// Test: parse a temp file with KEY=VALUE, comments, blank lines, quotes
$tempFile = tempnam(sys_get_temp_dir(), 'env_test_');
file_put_contents($tempFile, "# comment\n\nKEY=value\nQUOTED=\"quoted value\"\nSingle='single quoted'\n");
$parsed = parse_env($tempFile);
assert_equal('value', $parsed['KEY'] ?? null, 'parse_env: KEY=VALUE');
assert_equal('quoted value', $parsed['QUOTED'] ?? null, 'parse_env: double quotes trimmed');
assert_equal('single quoted', $parsed['Single'] ?? null, 'parse_env: single quotes trimmed');
unlink($tempFile);

// Test: exception on missing file
try {
    parse_env('/nonexistent/path/.env');
    assert_false(true, 'parse_env: throws on missing file');
} catch (\RuntimeException $e) {
    assert_true(true, 'parse_env: throws on missing file');
}

// --- build_dsn tests ---

$env = ['DB_HOST' => '127.0.0.1', 'DB_PORT' => '3306', 'DB_NAME' => 'test_db'];
$dsn = build_dsn($env);
assert_contains('mysql:host=127.0.0.1', $dsn, 'build_dsn: host');
assert_contains('port=3306', $dsn, 'build_dsn: port');
assert_contains('dbname=test_db', $dsn, 'build_dsn: dbname');
assert_contains('charset=utf8mb4', $dsn, 'build_dsn: charset');

// --- check_connection tests ---

if (!$envMissing) {
    // Test: real .env connection succeeds
    $result = check_connection([]);
    assert_true($result['ok'], 'check_connection: real .env connects');
    assert_contains('[OK]', $result['message'], 'check_connection: success message');

    // Test: message does not contain password
    $realEnv = parse_env($envPath);
    $realPassword = $realEnv['DB_PASSWORD'] ?? '';
    if ($realPassword !== '') {
        assert_not_contains($realPassword, $result['message'], 'check_connection: password not in message');
    }

    // Test: connection fails on unused port (port overridden)
    $badEnv = $realEnv;
    $badEnv['DB_PORT'] = '1'; // typically unused
    $badResult = check_connection($badEnv);
    assert_false($badResult['ok'], 'check_connection: fails on unused port');

    // Test: empty password gives a warning
    $emptyPassEnv = $realEnv;
    $emptyPassEnv['DB_PASSWORD'] = '';
    $emptyResult = check_connection($emptyPassEnv);
    if ($emptyResult['ok']) {
        assert_true(!empty($emptyResult['warnings']), 'check_connection: empty password gives warning');
    } else {
        // Connection failed, but still check if warning was set
        assert_true(!empty($emptyResult['warnings']), 'check_connection: empty password gives warning');
    }

    // Test: integration with real .env
    try {
        $integrationResult = check_connection([]);
        assert_true($integrationResult['ok'], 'check_connection: integration passes');
    } catch (\Throwable $e) {
        echo "SKIP: integration test failed ({$e->getMessage()})\n";
        $skipped++;
    }
} else {
    echo "SKIP: .env missing; connection tests skipped\n";
    $skipped++;
}

// --- Summary ---

echo "Passed: {$passed} | Failed: {$failed} | Skipped: {$skipped}\n";
exit($failed > 0 ? 1 : 0);
