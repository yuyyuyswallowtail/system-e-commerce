<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;

require_once dirname(__DIR__, 3).'/scripts/db-create-test.php';

it('parses a dotenv file manually', function () {
    $path = tempnam(sys_get_temp_dir(), 'envtest');
    file_put_contents($path, "# comment\n\nDB_HOST=127.0.0.1\nQUOTED_KEY=\"quoted value\"\nNAME='single'\nNOEQUALS\n");

    $values = sec_parse_env_file($path);
    unlink($path);

    expect($values)->toBe(['DB_HOST' => '127.0.0.1', 'QUOTED_KEY' => 'quoted value', 'NAME' => 'single']);
});

it('returns an empty array for a missing file', function () {
    expect(sec_parse_env_file('/nonexistent/.env'))->toBe([]);
});

it('rejects an invalid database name and a missing user', function () {
    expect(sec_create_test_database(['DB_USERNAME' => 'root'], 'bad name; drop'))->toBe([false, 'invalid database name'])
        ->and(sec_create_test_database([], 'system_e_commerce_test'))->toBe([false, 'DB_USERNAME is not set']);
});

it('fails with a short message that hides credentials when the server is unreachable', function () {
    [$ok, $message] = sec_create_test_database([
        'DB_HOST' => '127.0.0.1', 'DB_PORT' => '1', 'DB_USERNAME' => 'nobody', 'DB_PASSWORD' => 'secret-pass-123',
    ]);

    expect($ok)->toBeFalse()
        ->and(strlen($message))->toBeLessThanOrEqual(120)
        ->and($message)->not->toContain('secret-pass-123');
});

it('creates the test database with the real credentials', function () {
    [$ok, $message] = sec_create_test_database(sec_parse_env_file(base_path('.env')));

    expect($ok)->toBeTrue()->and($message)->toBe('system_e_commerce_test');
});

it('prints OK and exits 0 when run directly', function () {
    $result = Process::path(base_path())->run([PHP_BINARY, 'scripts/db-create-test.php']);

    expect($result->successful())->toBeTrue()
        ->and($result->output())->toStartWith('[OK]');
});
